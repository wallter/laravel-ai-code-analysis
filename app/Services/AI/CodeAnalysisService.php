<?php

namespace App\Services\AI;

use App\Services\OpenAIService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Log;

class CodeAnalysisService
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /**
     * Send an analysis request to OpenAI API.
     *
     * @param string $input
     * @return array
     */
    public function sendAnalysisRequest(string $input): array
    {
        $response = $this->openAIService->performOperation('code_analysis', [
            'prompt' => $input,
            'model' => config('ai.operations.code_analysis.model'),
            'max_tokens' => config('ai.operations.code_analysis.max_tokens'),
            'temperature' => config('ai.operations.code_analysis.temperature'),
        ]);

        if ($response && isset($response['choices'][0]['text'])) {
            return [
                'summary' => trim($response['choices'][0]['text']),
                'tokens'  => $response['usage']['total_tokens'] ?? 0,
            ];
        }

        Log::error('Failed to get a valid response from OpenAI.');
        return [];
    }
}
