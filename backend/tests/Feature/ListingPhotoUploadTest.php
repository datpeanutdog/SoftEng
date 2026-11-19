<?php

namespace Tests\Feature;

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
                UploadedFile::fake()->image('room.jpg'),
                UploadedFile::fake()->image('lobby.png'),
            ],
        ])->assertOk()->assertJsonCount(2, 'photos');

        $oldPaths = $listing->uploadedPhotos()->pluck('path')->all();
        $this->assertCount(2, $oldPaths);
        foreach ($oldPaths as $path) Storage::disk('public')->assertExists($path);

        $this->actingAs($host)->post("/api/host/listings/{$listing->id}/photos", [
            'photos' => [UploadedFile::fake()->image('replacement.webp')],
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
            'photos' => [UploadedFile::fake()->image('room.jpg')],
        ])->assertForbidden();

        $this->assertDatabaseCount('listing_photos', 0);
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
