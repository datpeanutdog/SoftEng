<?php
namespace App\Http\Controllers;

use App\Models\{Booking, Listing};
use Illuminate\Http\Request;

class HostController extends Controller
{
    private function own(Listing $l): Listing
    {
        abort_unless($l->host_id === auth()->id(), 403);
        return $l;
    }

    public function dashboard()
    {
        $me = auth()->user();
        $ids = $me->listings()->pluck('id');
        $pending = Booking::with(['guest', 'listing'])->whereIn('listing_id', $ids)->where('status', 'pending')->oldest()->get();
        $checkedIn = Booking::whereIn('listing_id', $ids)->where('status', 'approved')->where('access_status', 'active')
            ->where('check_in', '<=', now())->where('check_out', '>=', now())->count();
        $low = $me->listings()->where('battery', '<', 20)->count();
        return view('host.dashboard', compact('pending', 'checkedIn', 'low'));
    }

    // ----- Listings -----
    public function listings() { return view('host.listings', ['listings' => auth()->user()->listings()->orderBy('id')->get()]); }
    public function create() { return view('host.listing-form', ['listing' => new Listing(['status' => 'active'])]); }
    public function edit(Listing $listing) { return view('host.listing-form', ['listing' => $this->own($listing)]); }

    private function data(Request $r): array
    {
        return $r->validate([
            'name' => 'required|string|max:100',
            'price' => 'required|integer|min:1',
            'available_from' => 'nullable|date',
            'available_to' => 'nullable|date|after_or_equal:available_from',
            'status' => 'required|in:active,paused',
        ]);
    }
    public function store(Request $r)
    {
        auth()->user()->listings()->create($this->data($r));
        return redirect()->route('host.listings.index')->with('ok', 'Listing added.');
    }
    public function update(Request $r, Listing $listing)
    {
        $this->own($listing)->update($this->data($r));
        return redirect()->route('host.listings.index')->with('ok', 'Listing updated.');
    }
    public function destroy(Listing $listing)
    {
        $this->own($listing)->delete();
        return back()->with('ok', 'Listing deleted.');
    }

    // ----- Doorlock -----
    public function doorlock(Request $r)
    {
        $listings = auth()->user()->listings()->orderBy('id')->get();
        $listing = $listings->firstWhere('id', (int) $r->query('listing')) ?? $listings->first();
        $access = $listing ? $listing->bookings()->with('guest')->where('status', 'approved')
            ->whereNotNull('access_status')->latest('check_in')->get() : collect();
        $events = $listing ? $listing->events()->latest()->take(8)->get() : collect();
        return view('host.doorlock', compact('listings', 'listing', 'access', 'events'));
    }
    public function removeAccess(Booking $booking)
    {
        abort_unless($booking->listing->host_id === auth()->id(), 403);
        $booking->removeAccess();
        return back()->with('ok', 'Access removed for ' . $booking->guest->name . '.');
    }
    public function replaceBattery(Listing $listing)
    {
        $this->own($listing)->update(['battery' => 100]);
        $listing->logEvent('Battery replaced');
        return back()->with('ok', 'Battery marked as replaced.');
    }
    // Demo helpers: simulate the physical lock, since there is no real hardware.
    public function setBattery(Request $r, Listing $listing)
    {
        $v = $r->validate(['battery' => 'required|integer|between:0,100']);
        $this->own($listing)->update($v);
        if ($v['battery'] < 20) $listing->logEvent('Host notified: battery below 20%');
        return back()->with('ok', 'Battery set to ' . $v['battery'] . '%.');
    }
    public function failedAttempt(Listing $listing)
    {
        $this->own($listing)->logEvent('Wrong code entered', 'failed');
        $n = $listing->events()->where('result', 'failed')->where('created_at', '>=', now()->subMinutes(10))->count();
        if ($n >= 3) {
            $listing->logEvent('Host notified: repeated failed attempts');
            return back()->with('ok', 'Repeated failed attempts: host notified.');
        }
        return back()->with('ok', 'Failed attempt logged.');
    }
}
