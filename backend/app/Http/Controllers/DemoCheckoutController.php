<?php
namespace App\Http\Controllers;

use App\Models\Booking;
use Illuminate\Http\Request;

/**
 * DEMO checkout page: simulates a payment gateway with NO real money.
 * Only works while PAYMENT_DRIVER=demo in backend/.env. Never use this for real customers.
 */
class DemoCheckoutController extends Controller
{
    private function page(string $body)
    {
        return response('<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Demo checkout</title></head>'
            . '<body style="font-family:sans-serif;max-width:420px;margin:32px auto;padding:0 16px;text-align:center">'
            . '<div style="background:#fff3cd;border:1px solid #e6c200;border-radius:8px;padding:8px;font-size:13px;margin-bottom:20px">DEMO MODE - no real money is charged</div>'
            . $body . '</body></html>');
    }

    private function find(string $ref): ?Booking
    {
        if (config('services.payments.driver') !== 'demo' || ! str_starts_with($ref, 'demo_')) return null;
        return Booking::where('payment_reference', $ref)->first();
    }

    public function show(string $ref)
    {
        $b = $this->find($ref);
        if (! $b) abort(404);
        if ($b->payment_status !== 'unpaid') {
            return $this->page('<h2>Already paid</h2><p>This booking was already paid. Go back to the app.</p>');
        }
        $total = number_format((int) $b->price * (int) $b->nights);
        $btn = fn ($v, $label) => '<button name="method" value="' . $v . '" style="display:block;width:100%;margin:8px 0;padding:14px;font-size:16px;border:0;border-radius:8px;background:#222;color:#fff;cursor:pointer">' . $label . '</button>';
        return $this->page(
            '<h2>' . e($b->listing_title) . '</h2><p>' . e($b->range) . ' (' . (int) $b->nights . ' night(s))</p>'
            . '<p style="font-size:28px;font-weight:700">&#8369;' . $total . '</p>'
            . '<form method="POST" action="' . e(url('/pay/demo/' . $ref)) . '"><input type="hidden" name="_token" value="' . csrf_token() . '">'
            . $btn('gcash', 'Pay with GCash (demo)') . $btn('maya', 'Pay with Maya (demo)') . $btn('card', 'Pay with card (demo)')
            . '</form>'
        );
    }

    public function complete(Request $request, string $ref)
    {
        $b = $this->find($ref);
        if (! $b) abort(404);
        $method = $request->input('method');
        if (! in_array($method, ['gcash', 'maya', 'card'], true)) abort(422);

        if ($b->payment_status === 'unpaid') {
            $update = ['payment_status' => 'verified', 'payment_method' => $method];
            if (in_array($b->status, ['Cancelled', 'Denied'], true)) $update['refund_status'] = 'due';
            $b->update($update);
        }
        return $this->page('<h2>Payment successful</h2><p>This was a demo payment. Go back to the app - your booking will show as paid.</p>');
    }
}
