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
        $operationConfig = config("ai.operations.{$operationKey}", []);

        // Use default configuration values if specific ones are not set in operationConfig
        $model = $operationConfig['model'] ?? config('ai.openai_model', 'text-davinci-003');
        $maxTokens = $operationConfig['max_tokens'] ?? config('ai.max_tokens', 500);
        $temperature = $operationConfig['temperature'] ?? config('ai.temperature', 0.5);
        $promptTemplate = $operationConfig['prompt'] ?? '';

        // Ensure that a prompt is provided
        if (empty($prompt)) {
            if (empty($promptTemplate)) {
                Log::error("The 'prompt' parameter is required for the '{$operationKey}' operation and no default prompt is set.");
                return null;
            }
            $prompt = $promptTemplate;
            Log::info("Using default prompt for operation '{$operationKey}'.");
        }

        // Merge default parameters, operation-specific parameters, and any overridden params
        $payload = array_merge([
            'prompt' => $prompt,
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
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
