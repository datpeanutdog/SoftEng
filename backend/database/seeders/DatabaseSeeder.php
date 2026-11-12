<?php
namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // DemoDataSeeder creates the team accounts, sample guests, places and bookings.
        $this->call([DemoDataSeeder::class, CdoHotelListingsSeeder::class]);
    }
}
