<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $fillable = ['host_id', 'name', 'type', 'location', 'price', 'available_from', 'available_to', 'status', 'battery', 'photo', 'star_rating', 'serves_breakfast', 'has_pool', 'featured'];
    protected $casts = [
        'available_from' => 'date',
        'available_to' => 'date',
        'star_rating' => 'integer',
        'serves_breakfast' => 'boolean',
        'has_pool' => 'boolean',
        'featured' => 'boolean',
    ];

    public function bookings() { return $this->hasMany(Booking::class); }
    public function events() { return $this->hasMany(LockEvent::class); }

    public function logEvent(string $event, string $result = 'success'): void
    {
        $this->events()->create(compact('event', 'result'));
    }

    /** Public URL of this listing's photo, or null when no file has been added yet. */
    public function photoUrl(): ?string
    {
        if (!$this->photo) return null;
        foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
            $path = public_path("images/listings/{$this->photo}.{$ext}");
            if (is_file($path)) return asset("images/listings/{$this->photo}.{$ext}") . '?v=' . filemtime($path);
        }
        return null;
    }

    /**
     * All photos of this listing, main one first. Extra photos are numbered:
     *   limketkai-luxe-hotel.jpg, limketkai-luxe-hotel-2.jpg, limketkai-luxe-hotel-3.jpg ... (up to 8)
     */
    public function photoUrls(): array
    {
        if (!$this->photo) return [];
        $urls = [];
        for ($n = 1; $n <= 8; $n++) {
            $base = $n === 1 ? $this->photo : "{$this->photo}-{$n}";
            foreach (['jpg', 'jpeg', 'png', 'webp'] as $ext) {
                $path = public_path("images/listings/{$base}.{$ext}");
                if (is_file($path)) { $urls[] = asset("images/listings/{$base}.{$ext}") . '?v=' . filemtime($path); break; }
            }
        }
        return $urls;
    }

    public function availabilityLabel(): string
    {
        if ($this->status === 'paused') return 'Paused';
        if (!$this->available_from || !$this->available_to) return 'Not set';
        $a = $this->available_from; $b = $this->available_to;
        return $a->year === $b->year
            ? $a->format('M d') . ' - ' . $b->format('M d, Y')
            : $a->format('M d, Y') . ' - ' . $b->format('M d, Y');
    }
}