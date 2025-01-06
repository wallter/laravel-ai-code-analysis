<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiderUpgradeCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'aider:upgrade';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Perform upgrade automation tasks using Aider with data from an HTTP API';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Aider upgrade automation tasks...');

        // Step 1: Fetch data from the HTTP API
        $apiResponse = Http::get('https://api.example.com/data');

        if ($apiResponse->failed()) {
            $this->error('Failed to fetch data from the HTTP API.');
            Log::error('AiderUpgradeCommand: HTTP API request failed.', ['response' => $apiResponse->body()]);

            return 1;
        }

        $data = $apiResponse->json();

        $this->info('Data fetched successfully from the HTTP API.');

        // Step 2: Call Aider with the fetched data
        $aiderResponse = Http::withHeaders([
            'Authorization' => 'Bearer '.config('services.aider.api_key'),
            'Accept' => 'application/json',
        ])->post(config('services.aider.endpoint').'/upgrade', [
            'data' => $data,
        ]);

        if ($aiderResponse->failed()) {
            $this->error('Aider API request failed.');
            Log::error('AiderUpgradeCommand: Aider API request failed.', ['response' => $aiderResponse->body()]);

            return 1;
        }

        $aiderResult = $aiderResponse->json();

        $this->info('Aider has successfully performed the upgrade tasks.');
        Log::info('AiderUpgradeCommand: Upgrade tasks completed.', ['result' => $aiderResult]);

        return 0;
    }
}
