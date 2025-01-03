<?php

namespace App\Services\AI;

use OpenAI\Laravel\Facades\OpenAI as OpenAIFacade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;

/**
 * OpenAIService - revised to use chat() calls rather than completions.
 */
class OpenAIService
{
    /**
     * Perform an operation based on the given operation identifier (from config/ai.php).
     *
     * @param  string  $operationIdentifier
     * @param  array   $params  Additional parameters: 'user_message' (required), etc.
     * @return string  AI-generated response (trimmed).
     *
     * @throws InvalidArgumentException if no prompt is provided and no default is set.
     * @throws Exception for unexpected response or other errors.
     */
    public function performOperation(string $operationIdentifier, array $params = []): string
    {
        // 1) Retrieve config for this operation
        $operationConfig = Config::get("ai.operations.{$operationIdentifier}", []);

        // 2) Determine model, system message, tokens, temperature, etc.
        $model       = $operationConfig['model']       ?? Config::get('ai.openai_model', 'gpt-4o-mini');
        $maxTokens   = $operationConfig['max_tokens']  ?? Config::get('ai.max_tokens', 500);
        $temperature = $operationConfig['temperature'] ?? Config::get('ai.temperature', 0.5);

        // 3) If a specialized system message is in config, use it; else fallback.
        //    We'll store it under 'system_message' or similar in config.
        $systemMessage = $operationConfig['system_message']
            ?? 'You are a helpful AI assistant.';

        // 4) The user-facing prompt
        $promptTemplate = $operationConfig['user_message'] ?? '';
        if (empty($params['user_message']) && empty($promptTemplate)) {
            $msg = "No 'user_message' found for [{$operationIdentifier}] and no default prompt in config.";
            Log::error($msg);
            throw new InvalidArgumentException($msg);
        }
        $userMessage = $params['user_message'] ?? $promptTemplate;

        // 5) Construct the chat payload (system + user messages)
        $payload = [
            'model'       => $model,
            'messages'    => [
                [
                    'role'    => 'system',
                    'content' => str_replace(PHP_EOL, "\n", $systemMessage),
                ],
                [
                    'role'    => 'user',
                    'content' => str_replace(PHP_EOL, "\n", $userMessage),
                ],
            ],
            'max_tokens'  => $maxTokens,
            'temperature' => $temperature,
        ];

        // Merge any additional parameters (like custom temperature or messages array)
        // but ensure we don't overwrite our messages.
        // For instance, if 'messages' is passed, you can unify or replace them.
        // We'll do a simple merge for any other top-level keys.
        foreach ($params as $key => $value) {
            if (! in_array($key, ['user_message'])) {
                $payload[$key] = $value; 
            }
        }

        try {
            Log::info("Sending chat request to OpenAI [{$operationIdentifier}]", [
                'payload' => array_merge($payload, ['messages' => '<<omitted for brevity>>']),
            ]);

            // 6) Execute the chat() call
            $response = OpenAIFacade::chat()->create($payload);

            Log::info("Received chat response from OpenAI [{$operationIdentifier}]", [
                'response' => $response,
            ]);

            // 7) Check the response structure:
            //    chat API typically returns ->choices[0]->message->content
            $content = $response['choices'][0]['message']['content'] ?? null;
            if (! $content) {
                Log::error("Unexpected response structure from OpenAI [{$operationIdentifier}]", [
                    'response' => $response,
                ]);
                throw new Exception("No content in OpenAI chat response.");
            }

            return trim($content);
        } catch (Exception $e) {
            // Log & rethrow
            Log::error("OpenAI chat request failed [{$operationIdentifier}]: " . $e->getMessage(), [
                'exception' => $e,
            ]);
            throw $e;
        }
    }
}