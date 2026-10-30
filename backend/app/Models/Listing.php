<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Listing extends Model
{
    protected $fillable = ['host_id', 'name', 'price', 'available_from', 'available_to', 'status', 'battery'];
    protected $casts = ['available_from' => 'date', 'available_to' => 'date'];

    public function bookings() { return $this->hasMany(Booking::class); }
    public function events() { return $this->hasMany(LockEvent::class); }

    public function logEvent(string $event, string $result = 'success'): void
    {
        $this->events()->create(compact('event', 'result'));
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
