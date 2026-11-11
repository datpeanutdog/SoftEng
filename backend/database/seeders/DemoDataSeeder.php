<?php
namespace Database\Seeders;

use App\Models\{Listing, User};
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoDataSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Passwords: admins admin123, hosts host123, guests guest123
        $acct = fn ($name, $email, $role, $pw) => User::firstOrCreate(['email' => $email],
            ['name' => $name, 'password' => Hash::make($pw), 'role' => $role, 'active' => true]);

        // Team accounts
        $acct('Gerry Johnru Ratunil', 'gerry@lockbay.demo', 'Admin', 'admin123');
        $acct('Jo Nathaniel Lucero', 'jo@lockbay.demo', 'Admin', 'admin123');
        $acct('Joshlie Daven Miralles', 'joshlie@lockbay.demo', 'Host', 'host123');
        $acct('John Benedict Acebido', 'john@lockbay.demo', 'Host', 'host123');
        $acct('Loniel Jude Rana Jr', 'loniel@lockbay.demo', 'Host', 'host123');

        // Common-name guests. Maria, Daniel, Ana, Joel and Kim own the sample bookings.
        $acct('Maria Santos', 'maria@lockbay.demo', 'Guest', 'guest123');
        $acct('Daniel Cruz', 'daniel@lockbay.demo', 'Guest', 'guest123');
        $acct('Ana Reyes', 'ana@lockbay.demo', 'Guest', 'guest123');
        $acct('Joel Tan', 'joel@lockbay.demo', 'Guest', 'guest123');
        $acct('Kim Lopez', 'kim@lockbay.demo', 'Guest', 'guest123');
        // Two clean guests with no bookings (their first booking will be auto-flagged)
        $acct('Juan Dela Cruz', 'juan@lockbay.demo', 'Guest', 'guest123');
        $acct('Angela Bautista', 'angela@lockbay.demo', 'Guest', 'guest123');

        // Real Cagayan de Oro places + sample bookings (only on an empty database;
        // to reload them later run: php artisan db:seed --class=CdoListingsSeeder)
        if (Listing::count() === 0) {
            $this->call(CdoListingsSeeder::class);
        }
    }
}