<?php

namespace App\Services\AI;

use OpenAI\Laravel\Facades\OpenAI as OpenAIFacade;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Exception;

class OpenAIService
{
    /**
     * Perform an operation based on the given operation identifier.
     *
     * @param string $operationIdentifier The identifier for the AI operation to perform.
     * @param array $params Optional parameters to customize the AI request.
     *                      - 'prompt' (string): The prompt to send to the AI model.
     *                      - Additional parameters as required by the AI model.
     *
     * @return string The AI-generated response text.
     *
     * @throws InvalidArgumentException If the 'prompt' parameter is not provided.
     * @throws Exception If the AI API request fails or returns an unexpected response.
     */
    public function performOperation(string $operationIdentifier, array $params = []): string
    {
        // Retrieve the configuration for the specified operation
        $operationConfig = Config::get("ai.operations.{$operationIdentifier}", []);

        // Use default configuration values if specific ones are not set in operationConfig
        $model = $operationConfig['model'] ?? Config::get('ai.openai_model', 'text-davinci-003');
        $maxTokens = $operationConfig['max_tokens'] ?? Config::get('ai.max_tokens', 500);
        $temperature = $operationConfig['temperature'] ?? Config::get('ai.temperature', 0.5);
        $promptTemplate = $operationConfig['prompt'] ?? '';

        // Ensure that a prompt is provided
        if (empty($params['prompt'])) {
            if (empty($promptTemplate)) {
                $message = "The 'prompt' parameter is required for the '{$operationIdentifier}' operation and no default prompt is set.";
                Log::error($message);
                throw new InvalidArgumentException($message);
            }
            $params['prompt'] = $promptTemplate;
            Log::info("Using default prompt for operation '{$operationIdentifier}'.");
        }

        // Merge default operation parameters with any overrides provided in $params
        $payload = array_merge([
            'model' => $model,
            'prompt' => $params['prompt'],
            'max_tokens' => $maxTokens,
            'temperature' => $temperature,
            // Add other default parameters here if needed
        ], $params);

        try {
            Log::info("Sending request to OpenAI for operation '{$operationIdentifier}'", ['payload' => $payload]);

            // Send the request to the OpenAI API
            $response = OpenAIFacade::completion()->create($payload);

            Log::info("Received response from OpenAI for operation '{$operationIdentifier}'", ['response' => $response]);

            // Check if the response contains the expected data
            if (isset($response['choices'][0]['text'])) {
                return trim($response['choices'][0]['text']);
            }

            // Log unexpected response structure
            Log::error("Unexpected response structure from OpenAI for operation '{$operationIdentifier}'", ['response' => $response]);
            throw new Exception("Unexpected response from OpenAI API.");
        } catch (Exception $e) {
            // Log the exception and rethrow it for further handling
            Log::error("Exception occurred during OpenAI operation '{$operationIdentifier}': " . $e->getMessage(), ['exception' => $e]);
            throw $e;
        }
    }
}
