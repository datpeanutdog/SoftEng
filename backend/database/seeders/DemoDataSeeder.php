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

        // Passwords: admins admin123, hosts host123, guests guest123
        $acct = fn ($name, $email, $role, $pw) => User::firstOrCreate(['email' => $email],
            ['name' => $name, 'password' => Hash::make($pw), 'role' => $role, 'active' => true]);

        // Team accounts
        $acct('Gerry Johnru Ratunil', 'gerry@lockbay.demo', 'Admin', 'admin123');
        $acct('Jo Nathaniel Lucero', 'jo@lockbay.demo', 'Admin', 'admin123');
        $joshlie = $acct('Joshlie Daven Miralles', 'joshlie@lockbay.demo', 'Host', 'host123');
        $john = $acct('John Benedict Acebido', 'john@lockbay.demo', 'Host', 'host123');
        $loniel = $acct('Loniel Jude Rana Jr', 'loniel@lockbay.demo', 'Host', 'host123');

        // Common-name guests. Maria, Daniel, Ana, Joel and Kim own the sample bookings.
        $maria = $acct('Maria Santos', 'maria@lockbay.demo', 'Guest', 'guest123');
        $daniel = $acct('Daniel Cruz', 'daniel@lockbay.demo', 'Guest', 'guest123');
        $ana = $acct('Ana Reyes', 'ana@lockbay.demo', 'Guest', 'guest123');
        $joel = $acct('Joel Tan', 'joel@lockbay.demo', 'Guest', 'guest123');
        $kim = $acct('Kim Lopez', 'kim@lockbay.demo', 'Guest', 'guest123');
        // Two clean guests with no bookings (their first booking will be auto-flagged)
        $acct('Juan Dela Cruz', 'juan@lockbay.demo', 'Guest', 'guest123');
        $acct('Angela Bautista', 'angela@lockbay.demo', 'Guest', 'guest123');

        // Sample places in Cagayan de Oro (names are fictional; prices in PHP per night)
        $from = now()->toDateString();
        $to = now()->addMonths(6)->toDateString();
        $mk = fn ($host, $name, $type, $loc, $price, $battery = 100, $status = 'active') => Listing::create([
            'host_id' => $host->id, 'name' => $name, 'type' => $type, 'location' => $loc, 'price' => $price,
            'battery' => $battery, 'status' => $status, 'available_from' => $from, 'available_to' => $to]);

        // Joshlie: hotels and a motel
        $divisoria = $mk($joshlie, 'Divisoria Grand Hotel', 'Hotel', 'Divisoria, Poblacion', 2800, 82);
        $limketkai = $mk($joshlie, 'Limketkai Executive Suites', 'Hotel', 'Limketkai Drive, Lapasan', 3500);
        $lapasan = $mk($joshlie, 'Lapasan Express Motel', 'Motel', 'Lapasan', 1100, 18); // low battery, to demo the alert
        // John: Airbnbs
        $carmen = $mk($john, 'Carmen Garden Loft', 'Airbnb', 'Carmen', 1800);
        $mk($john, 'Kauswagan Seaview Studio', 'Airbnb', 'Kauswagan', 1600, 64);
        $mk($john, 'Macajalar Bay Beach House', 'Airbnb', 'Puerto', 3200, 100, 'paused');
        // Loniel: a resort and a pension house
        $macasandig = $mk($loniel, 'Macasandig Riverside Villa', 'Resort', 'Macasandig', 4500);
        $mk($loniel, 'Cugman Pension House', 'Pension house', 'Cugman', 1200);

        $at = fn ($d, $h) => now()->addDays($d)->setTime($h, 0);
        $b = fn ($l, $u, $in, $out, $status, $x = []) => Booking::create([
            'guest_id' => $u->id, 'host_id' => $l->host_id, 'listing_id' => $l->id, 'listing_title' => $l->name,
            'guest_name' => $u->name, 'price' => $l->price, 'code' => (string) random_int(100000, 999999),
            'check_in' => $in, 'check_out' => $out, 'status' => $status] + $x);

        // Maria is checked in now (so she is no longer a "first booking")
        $b($divisoria, $maria, $at(-1, 14), $at(1, 11), 'Approved', ['code' => '482913', 'go_enabled' => true, 'access_status' => 'active', 'decided_at' => now()->subDays(2)]);
        // Normal pending requests (the host decides)
        $b($carmen, $daniel, $at(5, 14), $at(8, 11), 'Pending');
        $b($macasandig, $ana, $at(10, 14), $at(12, 11), 'Pending');
        $b($limketkai, $maria, $at(20, 14), $at(22, 11), 'Pending');
        // Flagged requests (an admin decides)
        $b($lapasan, $joel, now()->addHours(10), $at(2, 11), 'Flagged', ['flag_reason' => 'within_24h']);
        $b($carmen, $kim, $at(14, 14), $at(16, 11), 'Flagged', ['flag_reason' => 'first_booking']);

        foreach ([['Door unlocked with access code', 'success', 26], ['Wrong code entered', 'failed', 26.1], ['Wrong code entered', 'failed', 26.2]] as [$e, $r, $h]) {
            $divisoria->events()->create(['event' => $e, 'result' => $r, 'created_at' => now()->subMinutes((int) ($h * 60))]);
        }
    }
}
