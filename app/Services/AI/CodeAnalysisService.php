<?php

namespace App\Services\AI;

use App\Services\AI\OpenAIService;
use Illuminate\Support\Facades\Log;
use Exception;

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
        try {
            $responseText = $this->openAIService->performOperation('code_analysis', [
                'prompt' => $input,
            ]);

            return [
                'summary' => $responseText,
                'tokens'  => 0, // Tokens usage not tracked in OpenAIService
            ];
        } catch (Exception $e) {
            Log::error('Failed to get a valid response from OpenAI.', ['exception' => $e]);
            return [];
        }
    }
}
