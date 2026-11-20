<?php

namespace Tests\Feature;

use App\Models\Listing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ListingManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_host_can_create_a_listing(): void
    {
        $host = User::factory()->create(['role' => 'Host']);

        $this->actingAs($host)->postJson('/api/host/listings', [
            'name' => 'New host listing',
            'type' => 'Hotel',
            'location' => 'Carmen',
            'price' => 1500,
            'status' => 'active',
        ])->assertCreated()
            ->assertJsonPath('name', 'New host listing')
            ->assertJsonPath('price', 1500);

        $this->assertDatabaseHas('listings', [
            'host_id' => $host->id,
            'name' => 'New host listing',
            'location' => 'Carmen',
            'price' => 1500,
        ]);
    }
}
