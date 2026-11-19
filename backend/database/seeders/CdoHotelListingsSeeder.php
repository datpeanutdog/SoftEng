<?php
namespace Database\Seeders;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Adds the CDO hotel catalog without replacing bookings or user-created listings.
 * Prices are illustrative; confirm rates, classifications, and amenities before publishing.
 */
class CdoHotelListingsSeeder extends Seeder
{
    public function run(): void
    {
        $host = User::where('email', 'joshlie@lockbay.demo')->firstOrFail();
        $from = now()->toDateString();
        $to = now()->addMonths(6)->toDateString();

        $hotels = [
            [
                'name' => 'Seda Centrio',
                'location' => 'Centrio Mall, C.M. Recto Avenue',
                'price' => 4200,
                'star_rating' => 4,
                'serves_breakfast' => true,
                'has_pool' => true,
                'featured' => true,
            ],
            [
                'name' => 'Mallberry Suites Business Hotel',
                'location' => 'Limketkai Center, Lapasan',
                'price' => 3500,
                'star_rating' => 4,
                'serves_breakfast' => true,
                'has_pool' => true,
                'featured' => true,
            ],
            [
                'name' => 'N Hotel',
                'location' => 'Kauswagan Highway',
                'price' => 3000,
                'star_rating' => 3,
                'serves_breakfast' => true,
                'has_pool' => true,
                'featured' => false,
            ],
            [
                'name' => 'New Dawn Plus Hotel',
                'location' => 'Don Apolinar Velez Street, Downtown',
                'price' => 2600,
                'star_rating' => 3,
                'serves_breakfast' => true,
                'has_pool' => false,
                'featured' => false,
            ],
            [
                'name' => 'Red Planet Cagayan de Oro',
                'location' => 'C.M. Recto Avenue, Downtown',
                'price' => 2200,
                'star_rating' => 3,
                'serves_breakfast' => false,
                'has_pool' => false,
                'featured' => false,
            ],
            [
                'name' => '1A Express Hotel',
                'location' => 'C.M. Recto Avenue, Downtown',
                'price' => 2500,
                'star_rating' => 3,
                'serves_breakfast' => true,
                'has_pool' => false,
                'featured' => false,
            ],
            [
                'name' => 'Marco Hotel',
                'location' => 'Upper Carmen',
                'price' => 3500,
                'star_rating' => 3,
                'serves_breakfast' => true,
                'has_pool' => true,
                'featured' => false,
            ],
            [
                'name' => 'Pryce Plaza Hotel',
                'location' => 'Carmen Hill, Carmen',
                'price' => 3200,
                'star_rating' => 4,
                'serves_breakfast' => true,
                'has_pool' => true,
                'featured' => false,
            ],
            [
                'name' => 'Grand City Hotel',
                'location' => 'Hayes Street, Downtown',
                'price' => 1700,
                'star_rating' => 2,
                'serves_breakfast' => false,
                'has_pool' => false,
                'featured' => false,
            ],
        ];

        foreach ($hotels as $hotel) {
            $listing = Listing::firstOrNew(['name' => $hotel['name']]);
            $isNew = !$listing->exists;
            $listing->fill($hotel + [
                'host_id' => $host->id,
                'type' => 'Hotel',
                'status' => 'active',
                'available_from' => $from,
                'available_to' => $to,
            ]);
            if ($isNew) {
                $listing->save();
            } else {
                $listing->forceFill([
                    'star_rating' => $hotel['star_rating'],
                    'serves_breakfast' => $hotel['serves_breakfast'],
                    'has_pool' => $hotel['has_pool'],
                    'featured' => $hotel['featured'],
                ])->save();
            }
        }

        $existingHotelFeatures = [
            'Limketkai Luxe Hotel' => [4, true, true, true],
            'Pearlmont Hotel' => [3, true, false, false],
        ];
        foreach ($existingHotelFeatures as $name => [$stars, $breakfast, $pool, $featured]) {
            Listing::where('name', $name)->update([
                'star_rating' => $stars,
                'serves_breakfast' => $breakfast,
                'has_pool' => $pool,
                'featured' => $featured,
            ]);
        }
    }
}
