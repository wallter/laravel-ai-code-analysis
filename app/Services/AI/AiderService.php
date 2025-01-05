<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiderService
{
    protected string $apiKey;
    protected string $endpoint;

    public function __construct()
    {
        $this->apiKey = config('services.aider.api_key');
        $this->endpoint = config('services.aider.endpoint', 'https://api.aider.com');
    }

    /**
     * Interact with Aider API.
     *
     * @param array $data The data to send to Aider.
     * @return array The response from Aider.
     * @throws \Exception If the API request fails.
     */
    public function interact(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->post("{$this->endpoint}/interact", [
                'data' => $data,
            ]);

            if ($response->failed()) {
                Log::error('AiderService: Aider API request failed.', ['response' => $response->body()]);
                throw new \Exception('Aider API request failed.');
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('AiderService: Exception during Aider interaction.', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Upgrade using Aider API.
     *
     * @param array $data The data to send for the upgrade.
     * @return array The response from Aider.
     * @throws \Exception If the API request fails.
     */
    public function upgrade(array $data): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Accept' => 'application/json',
            ])->post("{$this->endpoint}/upgrade", [
                'data' => $data,
            ]);

            if ($response->failed()) {
                Log::error('AiderService: Aider upgrade API request failed.', ['response' => $response->body()]);
                throw new \Exception('Aider upgrade API request failed.');
            }

            return $response->json();
        } catch (\Throwable $e) {
            Log::error('AiderService: Exception during Aider upgrade.', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
