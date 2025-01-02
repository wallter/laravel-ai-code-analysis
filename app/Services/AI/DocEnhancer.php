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
    public function enhanceDescription(ParsedItem $item): ?string
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
