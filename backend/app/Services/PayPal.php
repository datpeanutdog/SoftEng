<?php
namespace App\Services;

use App\Models\Booking;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Online payments through PayPal Checkout (Orders v2 API).
 * Flow: create order -> guest approves on PayPal -> server CAPTURES (money moves only here) -> booking verified.
 * Client ID / secret live ONLY in backend/.env. Default is the SANDBOX (fake money).
 */
class PayPal
{
    public function enabled(): bool
    {
        return filled(config('services.paypal.client_id')) && filled(config('services.paypal.secret'));
    }

    private function base(): string
    {
        return rtrim((string) config('services.paypal.base'), '/');
    }

    private function token(): string
    {
        $key = 'paypal_access_token_' . md5($this->base() . config('services.paypal.client_id'));
        return Cache::remember($key, 3000, function () {
            $res = Http::asForm()->acceptJson()->timeout(20)
                ->withBasicAuth((string) config('services.paypal.client_id'), (string) config('services.paypal.secret'))
                ->post($this->base() . '/v1/oauth2/token', ['grant_type' => 'client_credentials']);
            $token = data_get($res->json(), 'access_token');
            if ($res->failed() || ! $token) {
                throw new RuntimeException('PayPal rejected the Client ID / Secret. Check PAYPAL_CLIENT_ID and PAYPAL_CLIENT_SECRET in .env (and that they are SANDBOX keys).');
            }
            return (string) $token;
        });
    }

    private function client(): PendingRequest
    {
        return Http::withToken($this->token())->acceptJson()->asJson()->timeout(20)->baseUrl($this->base());
    }

    private function total(Booking $b): float
    {
        return (float) ((int) $b->price * (int) $b->nights);
    }

    private function currency(): string
    {
        return strtoupper((string) config('services.paypal.currency', 'PHP'));
    }

    private function returnBase(): string
    {
        return rtrim((string) (config('services.paypal.return_base') ?: url('/')), '/');
    }

    /** Creates a PayPal order. Returns ['id' => order id, 'checkout_url' => PayPal approval link]. */
    public function createCheckout(Booking $b, $guest): array
    {
        $res = $this->client()->withHeaders(['PayPal-Request-Id' => (string) Str::uuid()])->post('/v2/checkout/orders', [
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => "booking-{$b->id}",
                'description' => "DoorKey booking #{$b->id} - {$b->listing_title}",
                'amount' => [
                    'currency_code' => $this->currency(),
                    'value' => number_format($this->total($b), 2, '.', ''),
                ],
            ]],
            'payment_source' => ['paypal' => ['experience_context' => [
                'brand_name' => 'DoorKey',
                'user_action' => 'PAY_NOW',
                'shipping_preference' => 'NO_SHIPPING',
                'return_url' => $this->returnBase() . '/pay/paypal/return',
                'cancel_url' => $this->returnBase() . '/pay/paypal/cancel',
            ]]],
        ]);

        if ($res->failed()) {
            throw new RuntimeException((string) (data_get($res->json(), 'details.0.description') ?: data_get($res->json(), 'message') ?: 'Could not start the payment.'));
        }

        $id = data_get($res->json(), 'id');
        $url = null;
        foreach ((array) data_get($res->json(), 'links', []) as $link) {
            if (in_array(data_get($link, 'rel'), ['payer-action', 'approve'], true)) { $url = data_get($link, 'href'); break; }
        }
        if (! $id || ! $url) throw new RuntimeException('PayPal did not return an approval link.');
        return ['id' => $id, 'checkout_url' => $url];
    }

    /**
     * Checks the order with PayPal. If the guest approved it, captures the payment now.
     * Marks the booking verified only when PayPal confirms a COMPLETED capture for the right amount.
     */
    public function sync(Booking $b): bool
    {
        if ($b->payment_status === 'verified') return true;
        if ($b->payment_method !== 'online' || ! filled($b->payment_reference)) return false;
        if (str_starts_with((string) $b->payment_reference, 'demo_')) return false;

        $path = '/v2/checkout/orders/' . $b->payment_reference;
        $res = $this->client()->get($path);
        if ($res->failed()) return false;

        if (data_get($res->json(), 'status') === 'APPROVED') { // approved by the guest, money not taken yet
            $this->client()->withHeaders(['PayPal-Request-Id' => 'capture-' . $b->payment_reference])
                ->withBody('{}', 'application/json')->post($path . '/capture');
            $res = $this->client()->get($path); // always re-read the final state from PayPal
            if ($res->failed()) return false;
        }

        $order = (array) $res->json();
        if (data_get($order, 'status') !== 'COMPLETED') return false;
        if (data_get($order, 'purchase_units.0.reference_id') !== "booking-{$b->id}") return false; // different booking

        $expected = $this->total($b);
        foreach ((array) data_get($order, 'purchase_units.0.payments.captures', []) as $c) {
            if (data_get($c, 'status') === 'COMPLETED'
                && (float) data_get($c, 'amount.value') >= $expected - 0.009
                && strtoupper((string) data_get($c, 'amount.currency_code')) === $this->currency()) {
                $update = ['payment_status' => 'verified', 'payment_method' => 'paypal'];
                if (in_array($b->status, ['Cancelled', 'Denied'], true)) $update['refund_status'] = 'due'; // paid after cancel: money must go back
                $b->update($update);
                return true;
            }
        }
        return false;
    }
}
