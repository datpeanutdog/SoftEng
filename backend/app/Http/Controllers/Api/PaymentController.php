<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\HitPay;
use App\Services\PayPal;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PaymentController extends Controller
{
    private function fail(string $m, int $c = 422) { return response()->json(['message' => $m], $c); }

    private function gateway()
    {
        return match (config('services.payments.driver')) {
            'paypal' => app(PayPal::class),
            'hitpay' => app(HitPay::class),
            default => null,
        };
    }

    public function pay(Request $request, Booking $booking)
    {
        if ((int) $booking->guest_id !== (int) $request->user()->id) return $this->fail('Unauthorized.', 403);
        if (! in_array($booking->status, ['Pending', 'Flagged'], true)) return $this->fail('Payment can only be made for a pending booking.');
        if ($booking->payment_status !== 'unpaid') return $this->fail('Payment has already been submitted.');

        if (config('services.payments.driver') === 'demo') {
            $ref = 'demo_' . Str::random(32);
            $booking->update(['payment_method' => 'online', 'payment_reference' => $ref]);
            return response()->json(['checkout_url' => url('/pay/demo/' . $ref), 'booking' => $booking->toRow(true)]);
        }

        $gateway = $this->gateway();
        if (! $gateway || ! $gateway->enabled()) {
            return $this->fail('Online payment is not set up yet. Check PAYMENT_DRIVER and the API keys in the backend .env file.', 503);
        }

        try {
            $checkout = $gateway->createCheckout($booking, $request->user());
        } catch (\Throwable $e) {
            report($e);
            return $this->fail('Could not start the payment: ' . $e->getMessage(), 502);
        }

        $booking->update(['payment_method' => 'online', 'payment_reference' => $checkout['id']]);
        return response()->json(['checkout_url' => $checkout['checkout_url'], 'booking' => $booking->toRow(true)]);
    }

    /** The app polls this while the guest is paying; it asks the gateway directly. */
    public function status(Request $request, Booking $booking)
    {
        if ((int) $booking->guest_id !== (int) $request->user()->id) return $this->fail('Unauthorized.', 403);
        $gateway = $this->gateway();
        if ($booking->payment_status === 'unpaid' && $gateway && $gateway->enabled()) {
            try { $gateway->sync($booking); } catch (\Throwable $e) { report($e); }
        }
        return response()->json($booking->toRow(true));
    }

    public function webhook(Request $request, HitPay $hp)
    {
        if (! $hp->validSignature($request->getContent(), $request->header('Hitpay-Signature'))) {
            return $this->fail('Invalid signature.', 401);
        }
        $id = $request->input('id'); // the payment request id
        $booking = $id ? Booking::where('payment_reference', $id)->first() : null;
        if ($booking && $booking->payment_status === 'unpaid' && $hp->enabled()) $hp->sync($booking);
        return response()->json(['ok' => true]);
    }
}
