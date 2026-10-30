<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Booking, Listing};
use Illuminate\Http\Request;

class ListingController extends Controller
{
    private function fail(string $m, int $c = 403) { return response()->json(['message' => $m], $c); }

    private function own(Request $r, Listing $l): bool { return (int) $l->host_id === (int) $r->user()->id; }

    private function hostRow(Listing $l): array
    {
        return [
            'id' => $l->id, 'name' => $l->name, 'price' => $l->price, 'availability' => $l->availabilityLabel(),
            'status' => $l->status, 'battery' => $l->battery,
            'available_from' => $l->available_from?->toDateString(), 'available_to' => $l->available_to?->toDateString(),
        ];
    }

    private function data(Request $r): array
    {
        return $r->validate([
            'name' => ['required', 'string', 'max:100'],
            'price' => ['required', 'integer', 'min:1'],
            'available_from' => ['nullable', 'date'],
            'available_to' => ['nullable', 'date', 'after_or_equal:available_from'],
            'status' => ['required', 'in:active,paused'],
        ]);
    }

    /** Guests: listings open for booking. */
    public function index()
    {
        $live = fn ($q) => $q->whereIn('status', ['Pending', 'Flagged', 'Approved'])->where('check_out', '>=', now());
        return response()->json(Listing::where('status', 'active')->with(['bookings' => $live])->orderBy('id')->get()->map(fn ($l) => [
            'id' => $l->id, 'name' => $l->name, 'price' => $l->price, 'availability' => $l->availabilityLabel(),
            'available_from' => $l->available_from?->toDateString(), 'available_to' => $l->available_to?->toDateString(),
            // dates only, never guest names: each pair is [check-in day, check-out day]
            'booked' => $l->bookings->map(fn ($b) => [$b->check_in->toDateString(), $b->check_out->toDateString()])->values()]));
    }

    public function hostListings(Request $r)
    {
        return response()->json(Listing::where('host_id', $r->user()->id)->orderBy('id')->get()->map(fn ($l) => $this->hostRow($l)));
    }

    public function store(Request $r)
    {
        $listing = Listing::create($this->data($r) + ['host_id' => $r->user()->id]); // host comes from the login, not the app
        return response()->json($this->hostRow($listing), 201);
    }

    public function update(Request $r, Listing $listing)
    {
        if (!$this->own($r, $listing)) return $this->fail('Not your listing.');
        $listing->update($this->data($r));
        return response()->json($this->hostRow($listing));
    }

    public function destroy(Request $r, Listing $listing)
    {
        if (!$this->own($r, $listing)) return $this->fail('Not your listing.');
        $busy = $listing->bookings()->whereIn('status', ['Pending', 'Flagged', 'Approved'])->where('check_out', '>=', now())->exists();
        if ($busy) return $this->fail('This listing has active bookings. Pause it instead of deleting it.', 422);
        $listing->delete();
        return response()->json(['message' => 'Listing deleted.']);
    }

    public function doorlock(Request $r)
    {
        $ls = Listing::where('host_id', $r->user()->id)->orderBy('id')->get();
        $l = $ls->firstWhere('id', (int) $r->query('listing')) ?? $ls->first();
        if (!$l) return response()->json(['listing' => null, 'access' => [], 'events' => []]);
        return response()->json([
            'listing' => ['id' => $l->id, 'name' => $l->name, 'battery' => $l->battery],
            'access' => $l->bookings()->where('status', 'Approved')->whereNotNull('access_status')->latest('check_in')->get()
                ->map(fn ($b) => $b->toRow()),
            'events' => $l->events()->latest()->take(8)->get()->map(fn ($e) => [
                'id' => $e->id, 'time' => $e->created_at->format('M d, g:i A'), 'event' => $e->event, 'result' => $e->result]),
        ]);
    }

    public function removeAccess(Request $r, Booking $booking)
    {
        if ((int) $booking->host_id !== (int) $r->user()->id) return $this->fail('Not your booking.');
        $booking->removeAccess();
        return response()->json(['message' => 'Access removed.']);
    }

    public function replaceBattery(Request $r, Listing $listing)
    {
        if (!$this->own($r, $listing)) return $this->fail('Not your listing.');
        $listing->update(['battery' => 100]);
        $listing->logEvent('Battery replaced');
        return response()->json(['message' => 'Battery marked as replaced.']);
    }
}
