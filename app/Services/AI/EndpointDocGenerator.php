<?php

namespace App\Services\AI;

use App\Models\ParsedItem;
use Illuminate\Support\Facades\Http;

use App\Services\AI\AbstractAIService;

class EndpointDocGenerator extends AbstractAIService
{
    /**
     * Get the operation key for EndpointDocGenerator.
     *
     * @return string
     */
    protected function getOperationKey(): string
    {
        return 'endpoint_doc_generator';
    }

    /**
     * Generate a detailed summary for the given ParsedItem using AI.
     *
     * @param ParsedItem $item
     * @return string|null
     */
    public function generateSummary(ParsedItem $item): ?string
    {

        $prompt = $this->createPrompt($item);

        return $this->sendRequest($prompt, $overrideParams);
    }

    /**
     * Create a prompt based on the ParsedItem details.
     *
     * @param ParsedItem $item
     * @return string
     */
    private function createPrompt(ParsedItem $item): string
    {
        $methodName   = $item->name;
        $className    = $item->details['class_name'] ?? '';
        $namespace    = $item->details['namespace'] ?? '';
        $annotations  = $item->annotations ?? [];
        $params       = $item->details['params'] ?? [];
        $url          = $annotations['url'] ?? 'unknown';

        $parametersList = '';
        foreach ($params as $param) {
            $paramName = $param['name'];
            $paramType = $param['type'];
            $parametersList .= "- {$paramName} ({$paramType})\n";
        }

        return <<<PROMPT
You are an expert software documentation writer.

Provide a detailed, technical summary for the following API endpoint method to help developers understand its functionality and usage.

Method: {$methodName}
Class: {$className}
Namespace: {$namespace}
URL: {$url}
Parameters:
{$parametersList}

Include any important details from the method's annotations and parameters. The summary should be comprehensive enough to aid in writing tests, performing migrations, and integrating the endpoint into other systems.
PROMPT;
    }
}
