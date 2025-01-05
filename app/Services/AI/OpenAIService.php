<?php

namespace App\Services\AI;

use Exception;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use OpenAI\Laravel\Facades\OpenAI as OpenAIFacade;

/**
 * Handles calls to the OpenAI API via chat() endpoints,
 * capturing token usage in $this->lastUsage.
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
     * @param  string  $operationIdentifier  The identifier for the OpenAI operation.
     * @param  array  $params  The parameters for the OpenAI operation.
     * @return string The response content from OpenAI.
     *
     * @throws InvalidArgumentException If the operation identifier is invalid.
     * @throws Exception If the OpenAI response does not contain content.
     */
    public function performOperation(string $operationIdentifier, array $params = []): string
    {
        $opConfig = config("ai.operations.{$operationIdentifier}", []);
        if (empty($opConfig)) {
            $msg = "No config found for operation [{$operationIdentifier}].";
            Log::error($msg);
            throw new InvalidArgumentException($msg);
        }

        // Merge with defaults
        $model = $opConfig['model'] ?? config('ai.default.model');
        $maxTokens = $params['max_tokens'] ?? $opConfig['max_tokens'] ?? config('ai.default.max_tokens');
        $temperature = $params['temperature'] ?? $opConfig['temperature'] ?? config('ai.default.temperature');
        $systemMessage = $opConfig['system_message'] ?? config('ai.default.system_message');
        $promptText = $params['prompt'] ?? $opConfig['prompt'];

        if (empty($promptText)) {
            $msg = "No prompt text provided for [{$operationIdentifier}].";
            Log::error($msg);
            throw new InvalidArgumentException($msg);
        }

        Log::debug("OpenAIService.performOperation => [{$operationIdentifier}]", [
            'model' => $model, 'max_tokens' => $maxTokens, 'temperature' => $temperature,
        ]);

        // Prepare chat payload
        $payload = [
            'model' => $model,
            'messages' => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user', 'content' => $promptText],
            ],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
        ];

        try {
            $this->lastUsage = null; // reset usage
            Context::add('operation', $operationIdentifier);

            Log::info("OpenAIService => sending chat request [{$operationIdentifier}]", [
                'payload' => array_merge($payload, ['messages' => '<<omitted>>']),
            ]);

            // 1) Make the chat() call
            $response = OpenAIFacade::chat()->create($payload);

            Log::info("OpenAIService => received chat response [{$operationIdentifier}]", [
                'response' => $response,
            ]);

            // 2) Extract content
            $content = $response['choices'][0]['message']['content'] ?? null;
            throw_unless($content, new Exception('No content in chat response from OpenAI'));

            // 3) Extract usage
            // Usually looks like:
            // "usage": {
            //   "prompt_tokens": 123,
            //   "completion_tokens": 456,
            //   "total_tokens": 579
            // }
            $usage = $response['usage'] ?? null;
            if ($usage) {
                $this->lastUsage = [
                    'prompt_tokens' => $usage['prompt_tokens'] ?? 0,
                    'completion_tokens' => $usage['completion_tokens'] ?? 0,
                    'total_tokens' => $usage['total_tokens'] ?? 0,
                ];
            }

            return trim($content);
        } catch (\Throwable $e) {
            Log::error("OpenAI request failed [{$operationIdentifier}]: ".$e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        } finally {
            Context::forget('operation');
        }
    }
}
