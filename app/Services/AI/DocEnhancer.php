<?php

namespace App\Services\AI;

use App\Models\ParsedItem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocEnhancer
{
    protected OpenAIService $openAIService;

    public function __construct(OpenAIService $openAIService)
    {
        $this->openAIService = $openAIService;
    }

    /** 
     * Get the operation key for DocEnhancer.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return 'doc_enhancer';
    }

    /**
     * Enhance the description of a ParsedItem using an AI service.
     *
     * @param ParsedItem $item
     * @param array $overrideParams Optional parameters to override defaults.
     * @return string|null
     */
    public function enhanceDescription(ParsedItem $item, array $overrideParams = []): ?string
    {
        $prompt = $this->generatePrompt($item);

        // Merge override parameters if any
        $params = array_merge([
            'prompt' => $prompt,
        ], $overrideParams);

        try {
            return $this->openAIService->performOperation('doc_enhancer', $params);
        } catch (Exception $e) {
            Log::error('Failed to enhance description using OpenAI.', ['exception' => $e]);
            return null;
        }
    }

    /**
     * Generate a prompt based on the ParsedItem details.
     *
     * @param ParsedItem $item
     * @return string
     */
    private function generatePrompt(ParsedItem $item): string
    {
        $type = $item->type;
        $name = $item->name;
        $className = $item->class_name ?? '';
        $namespace = $item->namespace ?? '';
        $params = $item->details['params'] ?? [];
        $operationSummary = $item->operation_summary ?? '';
        $calledMethods = $item->called_methods ?? [];

        $paramList = '';
        foreach ($params as $param) {
            $paramList .= "{$param['type']} {$param['name']}, ";
        }
        $paramList = rtrim($paramList, ', ');

        $calledMethodsList = implode(', ', $calledMethods);

        $prompt = <<<PROMPT
You are an expert software documentation writer.

Please provide a detailed description for the {$type} '{$name}'.

- Type: {$type}
- Name: {$name}
- Class: {$className}
- Namespace: {$namespace}
- Parameters: {$paramList}
- Operation Summary: {$operationSummary}
- Called Methods: {$calledMethodsList}

Your description should explain the purpose of the {$type}, how it works, and any important details. Use the provided information to craft a clear and informative documentation entry.
PROMPT;

        return $prompt;
    }
}
