<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    protected $fillable = ['listing_id', 'guest_id', 'check_in', 'check_out', 'status', 'flag_reason',
        'access_code', 'access_status', 'decided_at'];
    protected $casts = ['check_in' => 'datetime', 'check_out' => 'datetime', 'decided_at' => 'datetime'];

    public const FLAGS = ['first_booking' => 'First booking', 'within_24h' => 'Within 24 hours'];

    public function listing() { return $this->belongsTo(Listing::class); }
    public function guest() { return $this->belongsTo(User::class, 'guest_id'); }

    public function getRangeAttribute(): string
    {
        return $this->check_in->format('M d') . ' - ' . $this->check_out->format('M d');
    }

    /** Auto-flagging rules: the reason a booking needs manual admin approval, or null. */
    public static function flagFor(User $guest, Carbon $checkIn): ?string
    {
        if (!static::where('guest_id', $guest->id)->exists()) return 'first_booking';
        if ($checkIn->lt(now()->addDay())) return 'within_24h';
        return null;
    }

    public function approve(): void
    {
        $this->update([
            'status' => 'approved',
            'access_code' => str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
            'access_status' => 'active',
            'decided_at' => now(),
        ]);
        $this->listing->logEvent('Access code created for ' . $this->guest->name);
    }

    public function reject(string $status): void
    {
        $this->update(['status' => $status, 'decided_at' => now()]);
    }

    public function removeAccess(): void
    {
        $this->update(['access_status' => 'removed']);
        $this->listing->logEvent('Access removed for ' . $this->guest->name);
    }
}
