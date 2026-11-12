<?php

use Illuminate\Support\Facades\Route;

// Home address of the backend. The old DoorKey web page was removed; the mobile app only uses /api.
Route::get('/', fn () => response()->json(['app' => 'LockBay API', 'status' => 'running']));

// Where HitPay sends the guest after the checkout page (it adds ?reference=...&status=...). The app confirms payment on its own.
Route::get('/pay/done', function () {
    $ok = request('status') === 'completed';
    $msg = $ok
        ? '<h2>Thank you!</h2><p>Your payment is being confirmed. You can close this page and go back to the app.</p>'
        : '<h2>Payment not completed</h2><p>If no money was taken, go back to the app and try again. If you were charged, the app will update shortly.</p>';
    return response('<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><body style="font-family:sans-serif;text-align:center;padding:48px">' . $msg . '</body>');
});

// DEMO checkout (only works when PAYMENT_DRIVER=demo; otherwise these return 404)
Route::get('/pay/demo/{ref}', [\App\Http\Controllers\DemoCheckoutController::class, 'show']);
Route::post('/pay/demo/{ref}', [\App\Http\Controllers\DemoCheckoutController::class, 'complete']);

// PayPal: where the guest lands after approving / cancelling on PayPal
Route::get('/pay/paypal/return', [\App\Http\Controllers\PayPalReturnController::class, 'done']);
Route::get('/pay/paypal/cancel', fn () => response('<!doctype html><meta name="viewport" content="width=device-width,initial-scale=1"><body style="font-family:sans-serif;text-align:center;padding:48px"><h2>Payment cancelled</h2><p>No money was taken. Go back to the app to try again.</p></body>'));
