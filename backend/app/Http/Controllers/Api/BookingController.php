<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    public function index(Request $request)
    {
        if ($request->user()->role === 'Guest') {
            return response()->json(
                Booking::where('guest_id', $request->user()->id)->get(),
                200,
            );
        }

        if ($request->user()->role === 'Host') {
            return response()->json(
                Booking::where('host_id', $request->user()->id)->orWhereNull('host_id')->get(),
                200,
            );
        }

        return response()->json(Booking::all(), 200);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'listing_id' => ['required', 'string'],
            'listing_title' => ['required', 'string'],
            'host_id' => ['required', 'string'],
            'price' => ['required', 'numeric'],
            'guest_name' => ['required', 'string'],
        ]);

        $booking = Booking::create([
            'guest_id' => $request->user()->id,
            'host_id' => $validated['host_id'],
            'listing_id' => $validated['listing_id'],
            'listing_title' => $validated['listing_title'],
            'guest_name' => $validated['guest_name'],
            'price' => $validated['price'],
            'code' => (string) random_int(100000, 999999),
            'status' => 'Pending',
            'go_enabled' => false,
        ]);

        return response()->json($booking, 201);
    }

    public function update(Request $request, Booking $booking)
    {
        if ($request->user()->role !== 'Guest' || $booking->guest_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized.'], 403);
        }

        $booking->update($request->only(['status', 'go_enabled']));

        return response()->json($booking);
    }

    public function approve(Request $request, Booking $booking)
    {
        if ($request->user()->role !== 'Host' || $booking->host_id !== $request->user()->id) {
            return response()->json(['message' => 'Unauthorized host action.'], 403);
        }

        $validated = $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        if ($validated['code'] !== $booking->code) {
            return response()->json(['message' => 'Code mismatch.'], 422);
        }

        $booking->update([
            'status' => 'Approved',
            'go_enabled' => true,
        ]);

        return response()->json($booking);
    }
}
