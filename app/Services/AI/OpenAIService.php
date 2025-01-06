<?php

namespace App\Services\AI;

use App\Enums\OperationIdentifier;
use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use OpenAI\Laravel\Facades\OpenAI as OpenAIFacade;

/**
 * Handles calls to the OpenAI API via chat() endpoints,
 * capturing token usage and optimizing requests.
 */
class OpenAIService
{
    protected ?array $lastUsage = null;

    /**
     * Get the last usage metrics from the OpenAI API.
     *
     * @return array|null The usage metrics or null if not set.
     */
    public function getLastUsage(): ?array
    {
        return $this->lastUsage;
    }

    /**
     * Perform an OpenAI operation based on the provided identifier and parameters.
     *
     * @param  OperationIdentifier  $operationIdentifier  The ENUM identifier for the OpenAI operation.
     * @param  array  $params  Additional parameters for the OpenAI operation.
     *                         Expected keys:
     *                         - 'ast_data' (array|null)
     *                         - 'raw_code' (string)
     *                         - 'previous_results' (string)
     * @return string The response content from OpenAI.
     *
     * @throws InvalidArgumentException If the operation identifier is invalid or prompt is missing.
     * @throws Exception If the OpenAI response does not contain content.
     */
    public function performOperation(OperationIdentifier $operationIdentifier, array $params = []): string
    {
        $opConfig = config("ai.passes.{$operationIdentifier->value}", []);
        if (empty($opConfig)) {
            $msg = "No config found for operation [{$operationIdentifier->value}].";
            Log::error($msg);
            throw new InvalidArgumentException($msg);
        }

        // Initialize variables from config with fallbacks
        $model = $opConfig['model'] ?? config('ai.default.model');
        $maxTokens = $params['max_tokens'] ?? $opConfig['max_tokens'] ?? config('ai.default.max_tokens');
        $temperature = $params['temperature'] ?? $opConfig['temperature'] ?? config('ai.default.temperature');

        // Retrieve prompt text using AIPromptBuilder
        $promptBuilder = new AIPromptBuilder(
            operationIdentifier: $operationIdentifier,
            config: $opConfig,
            astData: $params['ast_data'] ?? null,
            rawCode: $params['raw_code'] ?? '',
            previousResults: $params['previous_results'] ?? ''
        );

        $messagesJson = $promptBuilder->buildPrompt();

        if (empty($messagesJson)) {
            $msg = "Failed to build prompt for operation [{$operationIdentifier->value}].";
            Log::error($msg);
            throw new InvalidArgumentException($msg);
        }

        Log::debug("OpenAIService.performOperation => [{$operationIdentifier->value}]", [
            'model' => $model,
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ]);

        // Prepare chat payload
        $payload = [
            'model' => $model,
            'messages' => json_decode($messagesJson, true),
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        try {
            $this->lastUsage = null; // Reset usage
            Context::add('operation', $operationIdentifier->value);

            Log::info("OpenAIService => sending chat request [{$operationIdentifier->value}]", [
                'payload' => array_merge($payload, ['messages' => '<<omitted>>']),
            ]);

            // Implement caching to avoid redundant API calls
            $cacheKey = $this->generateCacheKey($operationIdentifier, $payload);
            if (Cache::has($cacheKey)) {
                Log::info("OpenAIService => cache hit for [{$operationIdentifier->value}].");

                return Cache::get($cacheKey);
            }

            // Make the chat() call
            $response = OpenAIFacade::chat()->create($payload);

            Log::info("OpenAIService => received chat response [{$operationIdentifier->value}]", [
                'response' => $response,
            ]);

            // Extract content
            $content = $response['choices'][0]['message']['content'] ?? null;
            if (! $content) {
                $msg = 'No content in chat response from OpenAI';
                Log::error("OpenAIService: {$msg} for [{$operationIdentifier->value}].");
                throw new Exception($msg);
            }

            // Extract usage
            $usage = $response['usage'] ?? null;
            if ($usage) {
                $this->lastUsage = [
                    'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                    'completion_tokens' => $usage['completion_tokens'] ?? 0,
                    'total_tokens' => $usage['total_tokens'] ?? 0,
                ];
            }

            // Cache the response for future identical requests
            Cache::put($cacheKey, trim((string) $content), now()->addMinutes(10)); // Adjust cache duration as needed

            return trim((string) $content);
        } catch (\JsonException $je) {
            Log::error('OpenAIService: JSON error => '.$je->getMessage(), [
                'exception' => $je,
                'operation' => $operationIdentifier->value,
            ]);
            throw $je;
        } catch (\Throwable $throwable) {
            Log::error("OpenAI request failed [{$operationIdentifier->value}]: ".$throwable->getMessage(), [
                'exception' => $throwable,
            ]);
            throw $throwable;
        } finally {
            Context::forget('operation');
        }
    }

    /**
     * Generate a unique cache key based on operation and payload.
     */
    protected function generateCacheKey(OperationIdentifier $operationIdentifier, array $payload): string
    {
        return 'openai_'.md5($operationIdentifier->value.json_encode($payload));
    }
}
