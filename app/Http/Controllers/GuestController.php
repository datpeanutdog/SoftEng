<?php
namespace App\Http\Controllers;

use App\Models\{Booking, Listing};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class GuestController extends Controller
{
    public function home()
    {
        return view('guest.home', [
            'listings' => Listing::where('status', 'active')->orderBy('id')->get(),
            'bookings' => auth()->user()->bookings()->with('listing')->latest()->get(),
        ]);
    }

    public function store(Request $r)
    {
        $d = $r->validate([
            'listing_id' => 'required|exists:listings,id',
            'check_in' => 'required|date|after_or_equal:today',
            'check_out' => 'required|date|after:check_in',
        ]);
        $listing = Listing::findOrFail($d['listing_id']);
        abort_unless($listing->status === 'active', 422);

        $in = Carbon::parse($d['check_in'])->setTime(14, 0);   // check-in 2:00 PM
        $out = Carbon::parse($d['check_out'])->setTime(11, 0); // check-out 11:00 AM

        if (($listing->available_from && $in->lt($listing->available_from->copy()->startOfDay()))
            || ($listing->available_to && $out->gt($listing->available_to->copy()->endOfDay()))) {
            return back()->withErrors(['check_in' => 'Those dates are outside this listing\'s availability.'])->withInput();
        }
        $taken = Booking::where('listing_id', $listing->id)
            ->whereIn('status', ['pending', 'flagged', 'approved'])
            ->where('check_in', '<', $out)->where('check_out', '>', $in)->exists();
        if ($taken) {
            return back()->withErrors(['check_in' => 'Those dates are already requested or booked.'])->withInput();
        }

        $flag = Booking::flagFor($r->user(), $in);
        Booking::create([
            'listing_id' => $listing->id, 'guest_id' => $r->user()->id,
            'check_in' => $in, 'check_out' => $out,
            'status' => $flag ? 'flagged' : 'pending', 'flag_reason' => $flag,
        ]);
        return back()->with('ok', $flag
            ? 'Request sent. An admin needs to review it first.'
            : 'Request sent. The host will review it soon.');
    }
}
