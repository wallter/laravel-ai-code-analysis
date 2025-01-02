<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CodeAnalysisService
{
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
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/completions', [
                'model'       => $this->model,
                'prompt'      => $input,
                'temperature' => $this->temperature,
                'max_tokens'  => $this->maxTokens,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return [
                    'summary' => trim($data['choices'][0]['text'] ?? ''),
                    'tokens'  => $data['usage']['total_tokens'] ?? 0,
                ];
            }

            Log::error('OpenAI API error: ' . $response->body());
            return [];
        } catch (\Exception $e) {
            Log::error('Exception in CodeAnalysisService: ' . $e->getMessage());
            return [];
        }
    }
}
