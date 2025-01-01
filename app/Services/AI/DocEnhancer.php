<?php

namespace App\Services\AI;

use App\Models\ParsedItem;
use Illuminate\Support\Facades\Http;

class DocEnhancer
{
    /**
     * Enhance the description of a ParsedItem using an AI service.
     *
     * @param ParsedItem $item
     * @return string|null
     */
    public function enhanceDescription(ParsedItem $item): ?string
    {
        // Placeholder for AI API integration
        // Example using OpenAI's API

        $apiKey = config('ai.openai_api_key');
        $model = config('ai.openai_model', 'text-davinci-003');

        if (!$apiKey) {
            // Log or handle missing API key
            return null;
        }

        $prompt = $this->generatePrompt($item);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/completions', [
                'model' => $model,
                'prompt' => $prompt,
                'max_tokens' => 150,
                'temperature' => 0.7,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                return trim($data['choices'][0]['text']);
            }

            // Handle unsuccessful response
            return null;
        } catch (\Exception $e) {
            // Handle exceptions (e.g., log the error)
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
        $details = $item->details;

        // Customize the prompt based on item type
        if ($type === 'Class') {
            return "Provide a detailed description for the class '{$name}' including its purpose and main functionalities.";
        } elseif ($type === 'Function') {
            return "Provide a detailed description for the function '{$name}' including its purpose, parameters, and return value.";
        }

        return "Provide a detailed description for '{$name}'.";
    }
}
