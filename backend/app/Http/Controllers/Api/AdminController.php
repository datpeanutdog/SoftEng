<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Booking, User};
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

    public function updateBooking(Request $request, Booking $booking)
    {
        $d = $request->validate(['status' => ['required', 'in:Approved,Denied,Cancelled']]);
        if (!in_array($booking->status, ['Pending', 'Flagged'], true)) {
            return response()->json(['message' => 'This booking was already handled.'], 422);
        }
        $d['status'] === 'Approved' ? $booking->approve() : $booking->reject($d['status']);
        return response()->json($booking->toRow());
    }

    public function toggleUser(Request $request, User $user)
    {
        if ($user->role === 'Admin') return response()->json(['message' => 'Admin accounts cannot be changed.'], 403);
        $user->update(['active' => ! $user->active]);
        if (! $user->active) $user->tokens()->delete(); // a disabled user is logged out everywhere
        return response()->json($user);
    }
}
