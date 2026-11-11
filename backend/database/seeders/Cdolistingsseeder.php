<?php
namespace Database\Seeders;

use App\Models\{Booking, Listing, LockEvent, User};
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Real places in Cagayan de Oro City (names/locations from Google Maps).
 *
 * KEEPS all user accounts. Replaces ONLY listings, bookings and lock events.
 * Run on an existing database with:
 *     php artisan db:seed --class=CdoListingsSeeder
 *
 * NOTE: prices are PLACEHOLDERS (per night, PHP). Google gives no room rates -
 * check each place on Booking.com / Agoda / Airbnb and update the numbers.
 * Prices marked (reviewer) came from guest reviews and are only rough.
 */
class CdoListingsSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Wipe old sample data (users are NOT touched).
        LockEvent::query()->delete();
        Booking::query()->delete();
        Listing::query()->delete();

        $host = fn (string $email) => User::where('email', $email)->firstOrFail();
        $guest = fn (string $email) => User::where('email', $email)->firstOrFail();

        $joshlie = $host('joshlie@lockbay.demo');
        $john = $host('john@lockbay.demo');
        $loniel = $host('loniel@lockbay.demo');

        $from = now()->toDateString();
        $to = now()->addMonths(6)->toDateString();

        $mk = fn ($owner, $name, $type, $loc, $price, $battery = 100, $status = 'active') => Listing::create([
            'host_id' => $owner->id, 'name' => $name, 'type' => $type, 'location' => $loc, 'price' => $price,
            'battery' => $battery, 'status' => $status, 'available_from' => $from, 'available_to' => $to,
        ]);

        // ---- Joshlie: hotels + a motel -------------------------------------------------
        $luxe     = $mk($joshlie, 'Limketkai Luxe Hotel',  'Hotel', 'Limketkai Drive, Lapasan',  3500, 82);          // placeholder price
        $pearl    = $mk($joshlie, 'Pearlmont Hotel',       'Hotel', 'Limketkai Drive, Downtown', 1600);              // (reviewer)
        $sogo     = $mk($joshlie, 'Hotel Sogo Cagayan de Oro', 'Motel', 'Claro M. Recto Ave, Lapasan', 1100, 18);   // (reviewer) low battery, demos the alert

        // ---- John: Airbnbs --------------------------------------------------------------
        $ljcozy   = $mk($john, 'Cozy Place-Uptown CDO(Near Hall of Justice/SM)',                 'Airbnb', 'Bria Condo, Rodolfo N. Pelaez Blvd', 1800);       // placeholder price
        $studio   = $mk($john, 'Modern Studio @ the Heart of CDO', 'Airbnb', 'Avida Towers Aspira, Ramon Chavez St', 1600, 64); // placeholder price

        // ---- Loniel: pension house + resorts -------------------------------------------
        $manor    = $mk($loniel, 'Rich Manor Pension House',    'Pension house', 'Corrales Ave, Downtown', 1100);            // (reviewer)
        $chali    = $mk($loniel, 'Chali Resort & Conference Center', 'Resort', 'Zone 3 Old Road, Cugman', 4500);              // placeholder price
        $mk($loniel, 'Casa Monte', 'Resort', 'Camiguin St, Macasandig', 3500, 100, 'paused');                                // placeholder price, paused to demo that state

        // ---- Sample bookings (guest accounts already exist) ----------------------------
        $maria = $guest('maria@lockbay.demo');
        $daniel = $guest('daniel@lockbay.demo');
        $ana = $guest('ana@lockbay.demo');
        $joel = $guest('joel@lockbay.demo');
        $kim = $guest('kim@lockbay.demo');

        $at = fn ($d, $h) => now()->addDays($d)->setTime($h, 0);
        $b = fn ($l, $u, $in, $out, $status, $x = []) => Booking::create([
            'guest_id' => $u->id, 'host_id' => $l->host_id, 'listing_id' => $l->id, 'listing_title' => $l->name,
            'guest_name' => $u->name, 'price' => $l->price, 'code' => (string) random_int(100000, 999999),
            'check_in' => $in, 'check_out' => $out, 'status' => $status,
            'nights' => max(1, (int) $in->copy()->startOfDay()->diffInDays($out->copy()->startOfDay())),
        ] + $x);

        // Maria is checked in now: paid + verified + approved (so she is no longer a "first booking")
        $b($luxe, $maria, $at(-1, 14), $at(1, 11), 'Approved', [
            'code' => '482913', 'go_enabled' => true, 'access_status' => 'active', 'decided_at' => now()->subDays(2),
            'payment_method' => 'ewallet', 'payment_reference' => 'DEMO-GCASH-0001', 'payment_status' => 'verified',
        ]);
        // Normal pending requests (the host decides)
        $b($ljcozy, $daniel, $at(5, 14), $at(8, 11), 'Pending', [
            'payment_method' => 'ewallet', 'payment_reference' => 'DEMO-GCASH-0002', 'payment_status' => 'pending_verification',
        ]);
        $b($chali, $ana, $at(10, 14), $at(12, 11), 'Pending'); // unpaid
        $b($pearl, $maria, $at(20, 14), $at(22, 11), 'Pending', ['payment_method' => 'cash', 'payment_status' => 'pending_verification']);
        // Flagged requests (an admin decides)
        $b($sogo, $joel, now()->addHours(10), $at(2, 11), 'Flagged', ['flag_reason' => 'within_24h']);
        $b($studio, $kim, $at(14, 14), $at(16, 11), 'Flagged', ['flag_reason' => 'first_booking']);

        foreach ([['Door unlocked with access code', 'success', 26], ['Wrong code entered', 'failed', 26.1], ['Wrong code entered', 'failed', 26.2]] as [$e, $r, $h]) {
            $luxe->events()->create(['event' => $e, 'result' => $r, 'created_at' => now()->subMinutes((int) ($h * 60))]);
        }

        // Link the photo files in public/images/listings/
        $this->call(ListingPhotosSeeder::class);
    }
}