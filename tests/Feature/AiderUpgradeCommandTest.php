<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AiderUpgradeCommandTest extends TestCase
{

    #[Test]
    public function it_performs_upgrade_tasks_successfully()
    {
        // Mock the HTTP API responses
        Http::fake([
            'https://api.example.com/data' => Http::response(['key' => 'value'], 200),
            'https://api.aider.com/upgrade' => Http::response(['status' => 'success'], 200),
        ]);

        $this->artisan('aider:upgrade')
             ->expectsOutput('Starting Aider upgrade automation tasks...')
             ->expectsOutput('Data fetched successfully from the HTTP API.')
             ->expectsOutput('Aider has successfully performed the upgrade tasks.')
             ->assertExitCode(0);
    }

    #[Test]
    public function it_handles_http_api_failure()
    {
        // Mock a failed HTTP API response
        Http::fake([
            'https://api.example.com/data' => Http::response(null, 500),
        ]);

        $this->artisan('aider:upgrade')
             ->expectsOutput('Starting Aider upgrade automation tasks...')
             ->expectsOutput('Failed to fetch data from the HTTP API.')
             ->assertExitCode(1);
    }

    #[Test]
    public function it_handles_aider_api_failure()
    {
        // Mock successful HTTP API response and failed Aider API response
        Http::fake([
            'https://api.example.com/data' => Http::response(['key' => 'value'], 200),
            'https://api.aider.com/upgrade' => Http::response(null, 500),
        ]);

        $this->artisan('aider:upgrade')
             ->expectsOutput('Starting Aider upgrade automation tasks...')
             ->expectsOutput('Data fetched successfully from the HTTP API.')
             ->expectsOutput('Aider API request failed.')
             ->assertExitCode(1);
    }
}
