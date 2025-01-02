<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;

abstract class AbstractAIService
{
    /**
     * Send a request to the AI API with the given prompt and parameters.
     *
     * @param string $prompt
     * @param array $params Optional parameters to override defaults.
     * @return string|null
     */
    protected function sendRequest(string $prompt, array $params = []): ?string
    {
        $apiKey = config('ai.openai_api_key');

        if (!$apiKey) {
            \Log::error('OpenAI API key is missing.');
            return null;
        }

        // Merge default and overridden parameters
        $payload = array_merge([
            'model'        => config('ai.openai_model', 'text-davinci-003'),
            'prompt'       => $prompt,
            'max_tokens'   => config('ai.max_tokens', 150),
            'temperature'  => config('ai.temperature', 0.7),
            // Add other default parameters here
        ], $params);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/completions', $payload);

            if ($response->successful()) {
                $data = $response->json();
                return trim($data['choices'][0]['text'] ?? '');
            }

            \Log::error("OpenAI API error: " . $response->body());
            return null;
        } catch (\Exception $e) {
            \Log::error("Exception in AbstractAIService: " . $e->getMessage());
            return null;
        }
    }
}
