<?php

namespace App\Services\AI;

use App\Models\ParsedItem;
use Illuminate\Support\Facades\Http;

use App\Services\AI\AbstractAIService;

class DocEnhancer extends AbstractAIService
{
    /**
     * Enhance the description of a ParsedItem using an AI service.
     *
     * @param ParsedItem $item
     * @return string|null
     */
    public function enhanceDescription(ParsedItem $item, array $overrideParams = []): ?string
    {

        $prompt = $this->generatePrompt($item);

        return $this->sendRequest($prompt, $overrideParams);
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
