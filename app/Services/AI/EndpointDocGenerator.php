<?php

namespace App\Services\AI;

use App\Models\ParsedItem;
use Illuminate\Support\Facades\Http;

class EndpointDocGenerator
{
    /**
     * Generate a detailed summary for the given ParsedItem using AI.
     *
     * @param ParsedItem $item
     * @return string|null
     */
    public function generateSummary(ParsedItem $item): ?string
    {
        $apiKey = config('ai.openai_api_key');
        $model  = config('ai.openai_model', 'text-davinci-003');

        if (!$apiKey) {
            // Log or handle missing API key
            \Log::error('OpenAI API key is missing.');
            return null;
        }

        $prompt = $this->createPrompt($item);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type'  => 'application/json',
            ])->post('https://api.openai.com/v1/completions', [
                'model'        => $model,
                'prompt'       => $prompt,
                'max_tokens'   => 500,
                'temperature'  => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return trim($data['choices'][0]['text']);
            }

            // Handle unsuccessful response
            \Log::error("OpenAI API error: " . $response->body());
            return null;
        } catch (\Exception $e) {
            // Log exceptions
            \Log::error("Exception in EndpointDocGenerator: " . $e->getMessage());
            return null;
        }
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
