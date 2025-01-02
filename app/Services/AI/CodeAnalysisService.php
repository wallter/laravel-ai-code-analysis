<?php

namespace App\Services\AI;

use OpenAI\Client;
use Illuminate\Support\Facades\Log;

class CodeAnalysisService
{
    protected Client $client;
    protected $apiKey;
    protected $model;
    protected $temperature;
    protected $maxTokens;

    public function __construct()
    {
        $this->apiKey = config('services.openai.api_key');
        $this->model = config('services.openai.model');
        $this->temperature = config('services.openai.temperature');
        $this->maxTokens = config('services.openai.max_tokens');
        $this->client = new Client([
            'api_key' => $this->apiKey,
        ]);
    }

    /**
     * Send an analysis request to OpenAI API.
     *
     * @param string $input
     * @return array
     */
    public function sendAnalysisRequest(string $input): array
    {
        try {
            $response = $this->client->completions()->create([
                'model'       => $this->model,
                'prompt'      => $input,
                'temperature' => $this->temperature,
                'max_tokens'  => $this->maxTokens,
            ]);

            return [
                'summary' => trim($response['choices'][0]['text'] ?? ''),
                'tokens'  => $response['usage']['total_tokens'] ?? 0,
            ];
        } catch (\Exception $e) {
            Log::error('Exception in CodeAnalysisService: ' . $e->getMessage());
            return [];
        }
    }
}
