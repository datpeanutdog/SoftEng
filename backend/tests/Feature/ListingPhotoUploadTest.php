<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ListingPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_host_can_upload_and_replace_listing_photos(): void
    {
        Storage::fake('public');
        $host = User::factory()->create(['role' => 'Host']);
        $listing = $this->listing($host);

        $this->actingAs($host)->post("/api/host/listings/{$listing->id}/photos", [
            'photos' => [
                UploadedFile::fake()->create('room.jpg', 1, 'image/jpeg'),
                UploadedFile::fake()->create('lobby.png', 1, 'image/png'),
            ],
        ])->assertOk()->assertJsonCount(2, 'photos');

        $oldPaths = $listing->uploadedPhotos()->pluck('path')->all();
        $this->assertCount(2, $oldPaths);
        foreach ($oldPaths as $path) Storage::disk('public')->assertExists($path);

        $this->actingAs($host)->post("/api/host/listings/{$listing->id}/photos", [
            'photos' => [UploadedFile::fake()->create('replacement.webp', 1, 'image/webp')],
        ])->assertOk()->assertJsonCount(1, 'photos');

        $this->assertDatabaseCount('listing_photos', 1);
        foreach ($oldPaths as $path) Storage::disk('public')->assertMissing($path);
    }

    public function test_host_cannot_upload_photos_to_another_hosts_listing(): void
    {
        Storage::fake('public');
        $owner = User::factory()->create(['role' => 'Host']);
        $otherHost = User::factory()->create(['role' => 'Host']);
        $listing = $this->listing($owner);

        $this->actingAs($otherHost)->post("/api/host/listings/{$listing->id}/photos", [
            'photos' => [UploadedFile::fake()->create('room.jpg', 1, 'image/jpeg')],
        ])->assertForbidden();

        $this->assertDatabaseCount('listing_photos', 0);
    }

    public function test_host_can_delete_a_listing_with_active_bookings_and_its_uploaded_photos(): void
    {
        Storage::fake('public');
        $host = User::factory()->create(['role' => 'Host']);
        $guest = User::factory()->create(['role' => 'Guest']);
        $listing = $this->listing($host);

        $this->actingAs($host)->post("/api/host/listings/{$listing->id}/photos", [
            'photos' => [UploadedFile::fake()->create('room.jpg', 1, 'image/jpeg')],
        ])->assertOk();
        $photoPath = $listing->uploadedPhotos()->value('path');

        Booking::create([
            'guest_id' => $guest->id,
            'host_id' => $host->id,
            'listing_id' => $listing->id,
            'listing_title' => $listing->name,
            'guest_name' => $guest->name,
            'price' => $listing->price,
            'nights' => 2,
            'code' => '123456',
            'status' => 'Approved',
            'check_in' => now()->addDays(2)->setTime(14, 0),
            'check_out' => now()->addDays(4)->setTime(11, 0),
        ]);

        $this->actingAs($host)->deleteJson("/api/host/listings/{$listing->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Listing deleted.');

        $this->assertDatabaseMissing('listings', ['id' => $listing->id]);
        $this->assertDatabaseMissing('bookings', ['listing_id' => $listing->id]);
        $this->assertDatabaseMissing('listing_photos', ['listing_id' => $listing->id]);
        Storage::disk('public')->assertMissing($photoPath);
    }

    private function listing(User $host): Listing
    {
        return Listing::create([
            'host_id' => $host->id,
            'name' => 'Test stay',
            'type' => 'Hotel',
            'location' => 'Carmen',
            'price' => 1500,
            'status' => 'active',
        ]);
    }
}
