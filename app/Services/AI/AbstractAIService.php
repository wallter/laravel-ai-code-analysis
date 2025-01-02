<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Exception;

abstract class AbstractAIService
{
    /**
     * Get the operation key for configuration.
     *
     * @return string
     */
    abstract protected function getOperationKey(): string;

    /**
     * Send a request to the AI API with the given prompt and parameters.
     *
     * @param string $prompt
     * @param array $params Optional parameters to override defaults.
     * @return string|null
     */
    protected function sendRequest(string $prompt, array $params = []): ?string
    {
        $operationKey = $this->getOperationKey();
        $apiKey = config('ai.openai_api_key');

        if (!$apiKey) {
            Log::error('OpenAI API key is missing.');
            return null;
        }

        // Retrieve operation-specific default parameters
        $operationConfig = config("ai.operations.{$operationKey}");

        if (!$operationConfig) {
            Log::error("AI operation configuration for '{$operationKey}' not found.");
            return null;
        }

        // Merge default parameters, operation-specific parameters, and any overridden params
        $payload = array_merge([
            'prompt' => $prompt,
            'model' => $operationConfig['model'],
            'max_tokens' => $operationConfig['max_tokens'],
            'temperature' => $operationConfig['temperature'],
            // Add other default parameters here if needed
        ], $params);

        try {
            Log::info("Sending request to OpenAI for operation '{$operationKey}'", ['payload' => $payload]);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/completions', $payload);

            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['choices'][0]['text'])) {
                    return trim($data['choices'][0]['text']);
                }
                Log::error("Unexpected response structure from OpenAI for operation '{$operationKey}'", ['response' => $data]);
                return null;
            }

            Log::error("OpenAI API error: " . $response->body());
            return null;
        } catch (Exception $e) {
            Log::error("Exception in AbstractAIService: " . $e->getMessage(), ['exception' => $e]);
            return null;
        }
    }
}
