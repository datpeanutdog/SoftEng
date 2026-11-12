<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use App\Services\PayPal;
use Illuminate\Http\Request;

/** Where PayPal sends the guest after approving. Captures the payment, then shows a thank-you page. */
class PayPalReturnController extends Controller
{
    public function done(Request $request, PayPal $pp)
    {
        if (config('services.payments.driver') !== 'paypal') abort(404);

        $orderId = (string) $request->query('token'); // PayPal adds ?token=ORDER_ID&PayerID=...
        $booking = $orderId !== '' ? Booking::where('payment_reference', $orderId)->first() : null;

        $ok = false;
        if ($booking && $pp->enabled()) {
            try { $ok = $pp->sync($booking); } catch (\Throwable $e) { report($e); }
        }

        $msg = $ok
            ? '<h2>Payment successful</h2><p>Thank you! Go back to the app - your booking will show as paid.</p>'
            : '<h2>Payment not confirmed yet</h2><p>If you completed the payment, go back to the app. It will update in a few seconds. Otherwise you can try again from the app.</p>';
        return response('<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><body style="font-family:sans-serif;text-align:center;padding:48px">' . $msg . '</body>');
    }
}
