<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ListingPhotoUploadTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_host_can_upload_a_photo_for_their_listing(): void
    {
        $host = User::factory()->create(['role' => 'Host']);
        $listing = Listing::create([
            'host_id' => $host->id,
            'name' => 'Test stay',
            'type' => 'Hotel',
            'location' => 'Carmen',
            'price' => 1500,
            'status' => 'active',
        ]);

        $path = null;

        try {
            $response = $this->actingAs($host)->post("/api/host/listings/{$listing->id}/photo", [
                'photo' => UploadedFile::fake()->createWithContent(
                    'room.png',
                    base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC'),
                ),
            ]);

            $response->assertOk()->assertJsonPath('id', $listing->id);

            $photo = $listing->fresh()->photo;
            $this->assertNotNull($photo);
            $path = public_path("images/listings/{$photo}.png");
            $this->assertFileExists($path);
        } finally {
            if ($path) {
                File::delete($path);
            }
        }
    }

    public function test_a_host_cannot_upload_a_photo_for_another_hosts_listing(): void
    {
        $owner = User::factory()->create(['role' => 'Host']);
        $otherHost = User::factory()->create(['role' => 'Host']);
        $listing = Listing::create([
            'host_id' => $owner->id,
            'name' => 'Test stay',
            'type' => 'Hotel',
            'location' => 'Carmen',
            'price' => 1500,
            'status' => 'active',
        ]);

        $this->actingAs($otherHost)->postJson("/api/host/listings/{$listing->id}/photo", [
            'photo' => UploadedFile::fake()->create('room.png', 100, 'image/png'),
        ])->assertForbidden();
    }
}
