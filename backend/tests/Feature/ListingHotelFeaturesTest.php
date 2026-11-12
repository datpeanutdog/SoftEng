<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Database\Seeders\CdoHotelListingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingHotelFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_listings_include_hotel_category_fields(): void
    {
        $guest = User::factory()->create(['role' => 'Guest']);
        $host = User::factory()->create(['role' => 'Host']);
        $listing = Listing::create([
            'host_id' => $host->id,
            'name' => 'Featured city hotel',
            'type' => 'Hotel',
            'location' => 'Downtown',
            'price' => 3000,
            'status' => 'active',
            'star_rating' => 4,
            'serves_breakfast' => true,
            'has_pool' => true,
            'featured' => true,
        ]);

        $this->actingAs($guest)->getJson('/api/listings')
            ->assertOk()
            ->assertJsonPath('0.id', $listing->id)
            ->assertJsonPath('0.star_rating', 4)
            ->assertJsonPath('0.serves_breakfast', true)
            ->assertJsonPath('0.has_pool', true)
            ->assertJsonPath('0.featured', true);
    }

    public function test_cdo_hotel_seeder_is_repeatable_and_keeps_existing_listings(): void
    {
        $host = User::factory()->create([
            'role' => 'Host',
            'email' => 'joshlie@lockbay.demo',
        ]);
        $existing = Listing::create([
            'host_id' => $host->id,
            'name' => 'My own listing',
            'type' => 'Hotel',
            'location' => 'Uptown',
            'price' => 1800,
            'status' => 'paused',
        ]);

        $this->seed(CdoHotelListingsSeeder::class);
        $this->seed(CdoHotelListingsSeeder::class);

        $this->assertSame(10, Listing::count());
        $this->assertSame('paused', $existing->fresh()->status);
        $this->assertSame(1800, $existing->fresh()->price);
        $this->assertDatabaseHas('listings', [
            'name' => 'Seda Centrio',
            'star_rating' => 4,
            'featured' => true,
        ]);
    }
}
