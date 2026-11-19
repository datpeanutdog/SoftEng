<?php
namespace Database\Seeders;

use App\Models\Listing;
use Illuminate\Database\Seeder;

/**
 * Links each real listing to its photo file in backend/public/images/listings/.
 * Safe to run any time: it only sets the `photo` column (no bookings/users touched).
 *     php artisan db:seed --class=ListingPhotosSeeder
 * Put the image in the folder named exactly like the value on the right
 * (.jpg, .jpeg, .png or .webp), e.g. limketkai-luxe-hotel.jpg
 */
class ListingPhotosSeeder extends Seeder
{
    public function run(): void
    {
        $photos = [
            'Limketkai Luxe Hotel'              => 'limketkai-luxe-hotel',
            'Pearlmont Hotel'                   => 'pearlmont-hotel',
            'Hotel Sogo Cagayan de Oro'         => 'hotel-sogo-cdo',
            'Cozy Place-Uptown CDO(Near Hall of Justice/SM)'                     => 'lj-cozy-place',
            'Modern Studio @ the Heart of CDO'  => 'modern-studio-cdo',
            'Rich Manor Pension House'          => 'rich-manor-pension-house',
            'Chali Resort & Conference Center'  => 'chali-resort',
            'Casa Monte'                        => 'casa-monte',
        ];
        foreach ($photos as $name => $file) {
            Listing::where('name', $name)->update(['photo' => $file]);
        }
    }
}