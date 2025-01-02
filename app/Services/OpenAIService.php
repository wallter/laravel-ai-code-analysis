<?php

namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI as OpenAIFacade;
use Illuminate\Support\Facades\Config;

class OpenAIService
{
    /**
     * Perform an operation based on the given operation identifier.
     *
     * @param string $operationIdentifier
     * @param array $params
     * @return mixed
     *
     * @throws \InvalidArgumentException
     */
    public function performOperation(string $operationIdentifier, array $params = [])
    {
        $operationConfig = Config::get("ai.operations.{$operationIdentifier}");

        if (!$operationConfig) {
            throw new \InvalidArgumentException("Operation identifier '{$operationIdentifier}' not found in config.ai.operations.");
        }

        // Customize the request based on operation config
        $response = OpenAIFacade::completion()->create([
            'model' => $operationConfig['model'],
            'prompt' => $params['prompt'] ?? '',
            'max_tokens' => $operationConfig['max_tokens'],
            'temperature' => $operationConfig['temperature'],
            // Include other parameters as needed
        ]);

        return $response;
    }
}
