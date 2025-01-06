<?php

namespace App\Services\AI;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use OpenAI\Exceptions\ErrorException;

class OpenAIService
{
    protected string $apiKey;

    protected string $apiBaseUrl = 'https://api.openai.com/v1/chat/completions';

    protected array $lastUsage = [];

    public function __construct()
    {
        $this->apiKey = config('ai.openai_api_key');
    }

    /**
     * Perform an AI operation using the OpenAI API.
     *
     * @throws ErrorException
     */
    public function performOperation(string $operationIdentifier, array $params): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer '.$this->apiKey,
                'Content-Type' => 'application/json',
            ])->post($this->apiBaseUrl, $params);

            if ($response->successful()) {
                $data = $response->json();
                $this->lastUsage = $data['usage'] ?? [];

                return $data['choices'][0]['message']['content'] ?? '';
            } else {
                $error = $response->json('error.message', 'Unknown error');
                Log::error("OpenAIService: API request failed for operation '{$operationIdentifier}': {$error}");
                throw new Exception("OpenAI request failed [{$operationIdentifier}]: {$error}");
            }
        } catch (Exception $exception) {
            Log::error("OpenAIService: Exception during API request for operation '{$operationIdentifier}': {$exception->getMessage()}", [
                'exception' => $exception,
            ]);
            throw new Exception("OpenAI request failed [{$operationIdentifier}]: {$exception->getMessage()}");
        }
    }

    /**
     * Get the last usage metrics.
     */
    public function getLastUsage(): array
    {
        return $this->lastUsage;
    }
}
