<?php

namespace App\Services\AI;

use App\Enums\OperationIdentifier;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
     * @return string AI-generated content.
     *
     * @throws Exception If the API request fails.
     */
    public function performOperation(OperationIdentifier $operationIdentifier, array $params): string
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
                $operationName = $operationIdentifier->value;
                Log::error("OpenAIService: API request failed for operation '{$operationName}': {$error}");
                throw new Exception("OpenAI request failed [{$operationName}]: {$error}");
            }
        } catch (Exception $exception) {
            $operationName = $operationIdentifier->value;
            Log::error("OpenAIService: Exception during API request for operation '{$operationName}': {$exception->getMessage()}", [
                'exception' => $exception,
            ]);
            throw new Exception("OpenAI request failed [{$operationName}]: {$exception->getMessage()}");
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
