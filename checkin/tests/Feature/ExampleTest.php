<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_landing_page_is_available_to_guests(): void
    {
        $this->seed();

        $response = $this->get('/');

        $response->assertOk()
            ->assertSee('A better way to arrive')
            ->assertSee('Sunny Loft, Downtown')
            ->assertSee('Garden Room')
            ->assertDontSee('Beach Cottage')
            ->assertSee(route('login'))
            ->assertSee(route('register'));
    }
}
