<?php
namespace Database\Seeders;

use App\Models\{Booking, Listing, User};
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Every demo account uses the password: password
        $mk = fn ($n, $e, $r, $s = 'active') => User::create(['name' => $n, 'email' => $e, 'role' => $r, 'status' => $s, 'password' => 'password']);
        $mk('Site Admin', 'admin@email.com', 'admin');
        $lea = $mk('Lea Garcia', 'lea@email.com', 'host');
        $maria = $mk('Maria Santos', 'maria@email.com', 'guest');
        $daniel = $mk('Daniel Cruz', 'daniel@email.com', 'guest');
        $ana = $mk('Ana Reyes', 'ana@email.com', 'guest');
        $joel = $mk('Joel Tan', 'joel@email.com', 'guest');
        $kim = $mk('Kim Lopez', 'kim@email.com', 'guest');
        $mk('Paolo Dizon', 'paolo@email.com', 'guest', 'suspended');

        $from = now()->toDateString();
        $to = now()->addMonths(3)->toDateString();
        $loft = Listing::create(['host_id' => $lea->id, 'name' => 'Sunny Loft, Downtown', 'price' => 45, 'available_from' => $from, 'available_to' => $to, 'battery' => 82]);
        $garden = Listing::create(['host_id' => $lea->id, 'name' => 'Garden Room', 'price' => 32, 'available_from' => $from, 'available_to' => $to]);
        Listing::create(['host_id' => $lea->id, 'name' => 'Beach Cottage', 'price' => 78, 'status' => 'paused', 'available_from' => $from, 'available_to' => $to]);

        $at = fn ($days, $h) => now()->addDays($days)->setTime($h, 0);
        $b = fn ($l, $g, $in, $out, $status, $extra = []) => Booking::create([
            'listing_id' => $l->id, 'guest_id' => $g->id, 'check_in' => $in, 'check_out' => $out, 'status' => $status] + $extra);

        // Maria is checked in right now, so she is not a "first booking" any more
        $b($loft, $maria, $at(-1, 14), $at(1, 11), 'approved', ['access_code' => '482913', 'access_status' => 'active', 'decided_at' => now()->subDays(2)]);
        // Normal pending requests (host decides)
        $b($loft, $daniel, $at(5, 14), $at(8, 11), 'pending');
        $b($garden, $ana, $at(10, 14), $at(12, 11), 'pending');
        $b($loft, $maria, $at(20, 14), $at(22, 11), 'pending');
        // Flagged requests (admin decides)
        $b($garden, $joel, now()->addHours(10), $at(2, 11), 'flagged', ['flag_reason' => 'within_24h']);
        $b($loft, $kim, $at(14, 14), $at(16, 11), 'flagged', ['flag_reason' => 'first_booking']);

        foreach ([['Door unlocked with access code', 'success', 26], ['Wrong code entered', 'failed', 26.1], ['Wrong code entered', 'failed', 26.2]] as [$e, $r, $h]) {
            $loft->events()->create(['event' => $e, 'result' => $r, 'created_at' => now()->subMinutes((int) ($h * 60))]);
        }
    }
}
