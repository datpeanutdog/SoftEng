<?php
namespace Database\Seeders;

use App\Models\{Booking, Listing, User};
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        if (Listing::count() > 0) return;
        $host = User::where('email', 'host@doorkey.demo')->firstOrFail();

        // Demo guests, password: guest123
        $g = fn ($n, $e) => User::firstOrCreate(['email' => $e], ['name' => $n, 'password' => Hash::make('guest123'), 'role' => 'Guest', 'active' => true]);
        $maria = $g('Maria Santos', 'maria@doorkey.demo');
        $daniel = $g('Daniel Cruz', 'daniel@doorkey.demo');
        $ana = $g('Ana Reyes', 'ana@doorkey.demo');
        $joel = $g('Joel Tan', 'joel@doorkey.demo');
        $kim = $g('Kim Lopez', 'kim@doorkey.demo');

        $from = now()->toDateString();
        $to = now()->addMonths(3)->toDateString();
        $loft = Listing::create(['host_id' => $host->id, 'name' => 'Sunny Loft, Downtown', 'price' => 45, 'available_from' => $from, 'available_to' => $to, 'battery' => 82]);
        $garden = Listing::create(['host_id' => $host->id, 'name' => 'Garden Room', 'price' => 32, 'available_from' => $from, 'available_to' => $to]);
        Listing::create(['host_id' => $host->id, 'name' => 'Beach Cottage', 'price' => 78, 'status' => 'paused', 'available_from' => $from, 'available_to' => $to]);

        $at = fn ($d, $h) => now()->addDays($d)->setTime($h, 0);
        $b = fn ($l, $u, $in, $out, $status, $x = []) => Booking::create([
            'guest_id' => $u->id, 'host_id' => $l->host_id, 'listing_id' => $l->id, 'listing_title' => $l->name,
            'guest_name' => $u->name, 'price' => $l->price, 'code' => (string) random_int(100000, 999999),
            'check_in' => $in, 'check_out' => $out, 'status' => $status] + $x);

        // Maria is checked in now (so she is no longer a "first booking")
        $b($loft, $maria, $at(-1, 14), $at(1, 11), 'Approved', ['code' => '482913', 'go_enabled' => true, 'access_status' => 'active', 'decided_at' => now()->subDays(2)]);
        // Normal pending requests (host decides)
        $b($loft, $daniel, $at(5, 14), $at(8, 11), 'Pending');
        $b($garden, $ana, $at(10, 14), $at(12, 11), 'Pending');
        $b($loft, $maria, $at(20, 14), $at(22, 11), 'Pending');
        // Flagged requests (admin decides)
        $b($garden, $joel, now()->addHours(10), $at(2, 11), 'Flagged', ['flag_reason' => 'within_24h']);
        $b($loft, $kim, $at(14, 14), $at(16, 11), 'Flagged', ['flag_reason' => 'first_booking']);

        foreach ([['Door unlocked with access code', 'success', 26], ['Wrong code entered', 'failed', 26.1], ['Wrong code entered', 'failed', 26.2]] as [$e, $r, $h]) {
            $loft->events()->create(['event' => $e, 'result' => $r, 'created_at' => now()->subMinutes((int) ($h * 60))]);
        }
    }
}
