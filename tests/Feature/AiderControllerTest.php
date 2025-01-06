<?php

namespace Tests\Feature;

use App\Services\AI\AiderServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class AiderControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_can_interact_with_aider()
    {
        // Arrange: Mock the AiderServiceInterface
        $mock = Mockery::mock(AiderServiceInterface::class);
        $mock->shouldReceive('interact')
             ->once()
             ->with(['key' => 'value'])
             ->andReturn(['result' => 'success']);

        $this->app->instance(AiderServiceInterface::class, $mock);

        // Act: Perform the POST request to interact with Aider
        $response = $this->postJson(route('aider.interact'), ['key' => 'value']);

        // Assert: Check if the response is as expected
        $response->assertStatus(200)
                 ->assertJson(['result' => ['result' => 'success']]);
    }
}
