<?php
namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Online payments through HitPay (GCash, Maya, QR Ph, cards...).
 * The API key lives ONLY in backend/.env (HITPAY_API_KEY) - never in the app.
 * Default is the SANDBOX (fake money). For real money change HITPAY_BASE_URL and the key in .env.
 */
class HitPay
{
    public function enabled(): bool
    {
        return filled(config('services.hitpay.key'));
    }

    private function client(): PendingRequest
    {
        return Http::withHeaders([
                'X-BUSINESS-API-KEY' => (string) config('services.hitpay.key'),
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->acceptJson()->asJson()->timeout(20)
            ->baseUrl(rtrim((string) config('services.hitpay.base'), '/'));
    }

    private function total(Booking $b): float
    {
        return (float) ((int) $b->price * (int) $b->nights);
    }

    /** Creates a hosted checkout page for this booking. Returns ['id' => payment request id, 'checkout_url' => '...']. */
    public function createCheckout(Booking $b, $guest): array
    {
        $payload = [
            'amount' => number_format($this->total($b), 2, '.', ''), // HitPay amounts are in pesos, e.g. "3500.00"
            'currency' => (string) config('services.hitpay.currency', 'PHP'),
            'name' => $guest->name,
            'email' => $guest->email,
            'purpose' => "DoorKey booking #{$b->id} - {$b->listing_title}",
            'reference_number' => "booking-{$b->id}",
            'redirect_url' => config('services.hitpay.redirect') ?: url('/pay/done'),
        ];
        $methods = array_values((array) config('services.hitpay.methods'));
        if ($methods) $payload['payment_methods'] = $methods; // empty = show every method enabled on your HitPay account

        $res = $this->client()->post('/payment-requests', array_filter($payload, fn ($v) => $v !== null && $v !== ''));
        if ($res->failed()) {
            throw new RuntimeException((string) (data_get($res->json(), 'message') ?: 'Could not start the payment.'));
        }
        $id = data_get($res->json(), 'id');
        $url = data_get($res->json(), 'url');
        if (! $id || ! $url) throw new RuntimeException('HitPay did not return a checkout link.');
        return ['id' => $id, 'checkout_url' => $url];
    }

    /**
     * Asks HitPay whether this booking's payment request was really completed, for the right amount.
     * Only then is the booking marked verified. Never trusts the redirect or the webhook body. Returns true when paid.
     */
    public function sync(Booking $b): bool
    {
        if ($b->payment_status === 'verified') return true;
        if ($b->payment_method !== 'online' || ! filled($b->payment_reference)) return false;
        if (str_starts_with((string) $b->payment_reference, 'demo_')) return false; // demo payments are confirmed by the demo page

        $res = $this->client()->get('/payment-requests/' . $b->payment_reference);
        if ($res->failed()) return false;
        $data = (array) $res->json();

        if (data_get($data, 'status') !== 'completed') return false;
        $ref = data_get($data, 'reference_number');
        if ($ref && $ref !== "booking-{$b->id}") return false; // belongs to a different booking

        $expected = $this->total($b);
        $currency = strtolower((string) config('services.hitpay.currency', 'PHP'));
        foreach ((array) data_get($data, 'payments', []) as $p) {
            if (data_get($p, 'status') === 'succeeded'
                && (float) data_get($p, 'amount') >= $expected - 0.009
                && strtolower((string) data_get($p, 'currency', $currency)) === $currency) {
                $update = [
                    'payment_status' => 'verified',
                    'payment_method' => data_get($p, 'payment_type') ?: 'online', // card, gcash, ...
                ];
                // Paid after the guest already cancelled / was declined: money must go back.
                if (in_array($b->status, ['Cancelled', 'Denied'], true)) $update['refund_status'] = 'due';
                $b->update($update);
                return true;
            }
        }
        return false;
    }

    /** HitPay signs the raw JSON body with HMAC-SHA256 using your "salt" (header Hitpay-Signature). */
    public function validSignature(string $payload, ?string $header): bool
    {
        $salt = (string) config('services.hitpay.salt');
        if ($salt === '') return true; // no salt set: harmless, because sync() re-checks everything with HitPay itself
        return $header && hash_equals(hash_hmac('sha256', $payload, $salt), $header);
    }
}
