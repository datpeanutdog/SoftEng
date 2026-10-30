<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Booking extends Model
{
    protected $fillable = [
        'guest_id', 'host_id', 'listing_id', 'listing_title', 'guest_name', 'price', 'code', 'status',
        'go_enabled', 'check_in', 'check_out', 'flag_reason', 'access_status', 'decided_at',
    ];
    protected $casts = ['check_in' => 'datetime', 'check_out' => 'datetime', 'decided_at' => 'datetime', 'go_enabled' => 'boolean'];

    public const FLAGS = ['first_booking' => 'First booking', 'within_24h' => 'Within 24 hours'];

    public function listing() { return $this->belongsTo(Listing::class); }

    public function getRangeAttribute(): string
    {
        $a = $this->check_in; $b = $this->check_out;
        return $a->year === $b->year
            ? $a->format('M d') . ' - ' . $b->format('M d, Y')
            : $a->format('M d, Y') . ' - ' . $b->format('M d, Y');
    }

    /** Auto-flag rules: reason this booking needs admin review, or null. */
    public static function flagFor(User $guest, Carbon $checkIn): ?string
    {
        if (!static::where('guest_id', $guest->id)->exists()) return 'first_booking';
        if ($checkIn->lt(now()->addDay())) return 'within_24h';
        return null;
    }

    public function approve(): void
    {
        $this->update(['status' => 'Approved', 'go_enabled' => true, 'access_status' => 'active', 'decided_at' => now()]);
        $this->listing?->logEvent('Access code created for ' . $this->guest_name);
    }

    public function reject(string $status): void // Denied | Cancelled
    {
        $this->update(['status' => $status, 'go_enabled' => false, 'decided_at' => now()]);
    }

    public function removeAccess(): void
    {
        $this->update(['access_status' => 'removed', 'go_enabled' => false]);
        $this->listing?->logEvent('Access removed for ' . $this->guest_name);
    }

    /** JSON shape the mobile app uses. The door code is only shown to the guest, once approved. */
    public function toRow(bool $withCode = false): array
    {
        return [
            'id' => $this->id, 'guest' => $this->guest_name, 'listing' => $this->listing_title,
            'dates' => $this->range, 'price' => $this->price, 'status' => $this->status,
            'flag' => self::FLAGS[$this->flag_reason] ?? null, 'access' => $this->access_status,
            'code' => $withCode && $this->status === 'Approved' && $this->access_status === 'active' ? $this->code : null,
            'check_in' => $this->check_in?->toIso8601String(), 'check_out' => $this->check_out?->toIso8601String(),
        ];
    }
}
