<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ExampleTest extends TestCase
{

    /** @test */
    public function test_the_application_returns_a_successful_response(): void
    {
        // Create and authenticate a verified user
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        // Act: Perform the GET request and follow redirects
        $response = $this->followingRedirects()->get('/');

        // Assert: Check if the final response status is 200
        $response->assertStatus(200);
    }
}
