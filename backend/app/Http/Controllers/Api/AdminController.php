<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function metrics()
    {
        return response()->json([
            'bookings' => Booking::count(),
            'active_bookings' => Booking::where('status', '!=', 'Denied')->count(),
            'pending' => Booking::where('status', 'Pending')->count(),
            'users' => User::count(),
        ]);
    }

    public function users()
    {
        return response()->json(User::all());
    }

    public function updateBooking(Request $request, Booking $booking)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:Pending,Approved,Denied'],
        ]);

        $booking->update([
            'status' => $validated['status'],
            'go_enabled' => $validated['status'] === 'Approved',
        ]);

        return response()->json($booking);
    }

    public function toggleUser(Request $request, User $user)
    {
        $user->update([
            'active' => ! $user->active,
        ]);

        return response()->json($user);
    }
}
