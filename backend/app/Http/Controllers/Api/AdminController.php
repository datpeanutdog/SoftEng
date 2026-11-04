<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Booking, User};
use App\Services\DecisionSms;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function metrics()
    {
        return response()->json([
            'bookings' => Booking::count(),
            'active_bookings' => Booking::whereNotIn('status', ['Denied', 'Cancelled'])->count(),
            'pending' => Booking::where('status', 'Pending')->count(),
            'flagged' => Booking::where('status', 'Flagged')->count(),
            'users' => User::count(),
            'approved_today' => Booking::where('status', 'Approved')->whereDate('decided_at', today())->count(),
            'cancelled_today' => Booking::whereIn('status', ['Denied', 'Cancelled'])->whereDate('decided_at', today())->count(),
        ]);
    }

    public function users() { return response()->json(User::orderBy('id')->get()); }

    public function updateBooking(Request $request, Booking $booking, DecisionSms $sms)
    {
        $d = $request->validate(['status' => ['required', 'in:Approved,Denied,Cancelled']]);
        if (!in_array($booking->status, ['Pending', 'Flagged'], true)) {
            return response()->json(['message' => 'This booking was already handled.'], 422);
        }
        if ($d['status'] === 'Approved' && $booking->payment_status !== 'verified') {
            return response()->json(['message' => 'Payment must be verified before approving this booking.'], 422);
        }
        $d['status'] === 'Approved' ? $booking->approve() : $booking->reject($d['status']);
        $notification = $sms->send($booking);
        return response()->json($booking->toRow() + ['sms' => $notification]);
    }

    public function verifyPayment(Booking $booking)
    {
        if (! in_array($booking->status, ['Pending', 'Flagged'], true)) {
            return response()->json(['message' => 'Only pending bookings can have payment verified.'], 422);
        }
        if ($booking->payment_status !== 'pending_verification') {
            return response()->json(['message' => 'This booking has no payment awaiting verification.'], 422);
        }

        $booking->update(['payment_status' => 'verified']);

        return response()->json($booking->toRow());
    }

    public function completeRefund(Booking $booking)
    {
        if (! in_array($booking->status, ['Denied', 'Cancelled'], true) || $booking->refund_status !== 'due') {
            return response()->json(['message' => 'This booking has no refund due.'], 422);
        }

        $booking->update(['refund_status' => 'refunded']);

        return response()->json($booking->toRow());
    }

    public function resendDecisionSms(Booking $booking, DecisionSms $sms)
    {
        if (! in_array($booking->status, ['Approved', 'Denied', 'Cancelled'], true)) {
            return response()->json(['message' => 'SMS can only be resent for a booking that has been decided.'], 422);
        }

        $notification = $sms->send($booking);
        return response()->json($booking->toRow() + ['sms' => $notification]);
    }

    public function toggleUser(Request $request, User $user)
    {
        if ($user->role === 'Admin') return response()->json(['message' => 'Admin accounts cannot be changed.'], 403);
        $user->update(['active' => ! $user->active]);
        if (! $user->active) $user->tokens()->delete(); // a disabled user is logged out everywhere
        return response()->json($user);
    }
}
