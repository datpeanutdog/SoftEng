<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Booking, Listing};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    private function fail(string $m, int $c = 422) { return response()->json(['message' => $m], $c); }

    public function index(Request $request)
    {
        $u = $request->user();
        $q = Booking::query()->latest();
        if ($u->role === 'Guest') $q->where('guest_id', $u->id);
        elseif ($u->role === 'Host') $q->where('host_id', $u->id);
        return response()->json($q->get()->map(fn ($b) => $b->toRow($u->role === 'Guest')));
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'listing_id' => ['required', 'exists:listings,id'],
            'check_in' => ['required', 'date', 'after_or_equal:today'],
            'check_out' => ['required', 'date', 'after:check_in'],
        ]);
        $listing = Listing::findOrFail($d['listing_id']);
        if ($listing->status !== 'active') throw ValidationException::withMessages(['listing_id' => ['This listing is paused.']]);

        $in = Carbon::parse($d['check_in'])->setTime(14, 0);   // check-in 2:00 PM
        $out = Carbon::parse($d['check_out'])->setTime(11, 0); // check-out 11:00 AM

        if (($listing->available_from && $in->lt($listing->available_from->copy()->startOfDay()))
            || ($listing->available_to && $out->gt($listing->available_to->copy()->endOfDay()))) {
            throw ValidationException::withMessages(['check_in' => ['Those dates are outside this listing\'s availability.']]);
        }
        $taken = Booking::where('listing_id', $listing->id)->whereIn('status', ['Pending', 'Flagged', 'Approved'])
            ->where('check_in', '<', $out)->where('check_out', '>', $in)->exists();
        if ($taken) throw ValidationException::withMessages(['check_in' => ['Those dates are already requested or booked.']]);

        $flag = Booking::flagFor($request->user(), $in);
        $booking = Booking::create([
            'guest_id' => $request->user()->id,
            'host_id' => $listing->host_id,           // taken from the listing, never from the client
            'listing_id' => $listing->id,
            'listing_title' => $listing->name,
            'guest_name' => $request->user()->name,
            'price' => $listing->price,
            'code' => (string) random_int(100000, 999999),
            'status' => $flag ? 'Flagged' : 'Pending',
            'flag_reason' => $flag,
            'go_enabled' => false,
            'check_in' => $in, 'check_out' => $out,
        ]);
        return response()->json([
            'message' => $flag ? 'Request sent. An admin needs to review it first.' : 'Request sent. The host will review it soon.',
            'booking' => $booking->toRow(true),
        ], 201);
    }

    /** Guests may only cancel their own request (the old version let them set any status). */
    public function update(Request $request, Booking $booking)
    {
        if ((int) $booking->guest_id !== (int) $request->user()->id) return $this->fail('Unauthorized.', 403);
        if (!in_array($booking->status, ['Pending', 'Flagged'], true)) return $this->fail('Only pending requests can be cancelled.');
        $booking->reject('Cancelled');
        return response()->json($booking->toRow(true));
    }

    public function approve(Request $request, Booking $booking)
    {
        if ((int) $booking->host_id !== (int) $request->user()->id) return $this->fail('Unauthorized host action.', 403);
        if ($booking->status === 'Flagged') return $this->fail('Flagged bookings need admin approval.', 403);
        if ($booking->status !== 'Pending') return $this->fail('This booking was already handled.');
        // The code is optional now: if the host types one, it must match the guest's code.
        if ($request->filled('code') && $request->input('code') !== $booking->code) return $this->fail('Code mismatch.');
        $booking->approve();
        return response()->json($booking->toRow());
    }

    public function decline(Request $request, Booking $booking)
    {
        if ((int) $booking->host_id !== (int) $request->user()->id) return $this->fail('Unauthorized host action.', 403);
        if ($booking->status !== 'Pending') return $this->fail('This booking was already handled.');
        $booking->reject('Denied');
        return response()->json($booking->toRow());
    }
}
