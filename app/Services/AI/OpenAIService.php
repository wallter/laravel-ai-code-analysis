<?php

namespace App\Services\AI;

use OpenAI\Laravel\Facades\OpenAI as OpenAIFacade;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Context;
use InvalidArgumentException;
use Exception;

/**
 * Handles calls to the OpenAI API via chat() endpoints,
 * capturing token usage in $this->lastUsage.
 */
class OpenAIService
{
    protected ?array $lastUsage = null;

    public function getLastUsage(): ?array
    {
        return $this->lastUsage;
    }

    public function performOperation(string $operationIdentifier, array $params = []): string
    {
        $opConfig = config("ai.operations.{$operationIdentifier}", []);
        if (empty($opConfig)) {
            $msg = "No config found for operation [{$operationIdentifier}].";
            Log::error($msg);
            throw new InvalidArgumentException($msg);
        }

        // Merge with defaults
        $model         = $opConfig['model']         ?? config('ai.default.model');
        $maxTokens     = $params['max_tokens']      ?? $opConfig['max_tokens']      ?? config('ai.default.max_tokens');
        $temperature   = $params['temperature']     ?? $opConfig['temperature']     ?? config('ai.default.temperature');
        $systemMessage = $opConfig['system_message'] ?? config('ai.default.system_message');
        $promptText    = $params['prompt']          ?? $opConfig['prompt'];

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
            'model'       => $model,
            'messages'    => [
                ['role' => 'system', 'content' => $systemMessage],
                ['role' => 'user',   'content' => $promptText],
            ],
            'max_tokens'  => $maxTokens,
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
            throw_unless($content, new Exception("No content in chat response from OpenAI"));

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
                    'prompt_tokens'     => $usage['prompt_tokens']     ?? 0,
                    'completion_tokens' => $usage['completion_tokens'] ?? 0,
                    'total_tokens'      => $usage['total_tokens']      ?? 0,
                ];
            }

            return trim($content);
        } catch (\Throwable $e) {
            Log::error("OpenAI request failed [{$operationIdentifier}]: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        } finally {
            Context::forget('operation');
        }
    }
}