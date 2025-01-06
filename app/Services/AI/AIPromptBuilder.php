<?php

namespace App\Services\AI;

use App\Enums\OperationIdentifier;

class AIPromptBuilder
{
    /**
     * Constructor to initialize the prompt builder.
     */
    public function __construct(protected OperationIdentifier $operationIdentifier, protected array $config, protected array $astData, protected string $rawCode, protected string $previousResults)
    {
    }

    /**
     * Build the AI prompt messages.
     */
    public function buildPrompt(): string
    {
        $messages = [];

        if ($this->config['supports_system_message'] ?? false) {
            $messages[] = [
                'role' => 'system',
                'content' => $this->config['system_message'] ?? '',
            ];
        }

        $messages[] = [
            'role' => 'user',
            'content' => $this->buildUserPrompt(),
        ];

        return json_encode($messages);
    }

    /**
     * Build the user prompt based on pass configuration.
     */
    private function buildUserPrompt(): string
    {
        $promptSections = $this->config['prompt_sections'] ?? [];

        $prompt = $promptSections['base_prompt'] ?? '';
        foreach ($promptSections['guidelines'] ?? [] as $guideline) {
            $prompt .= "\n".$guideline;
        }

        if (isset($promptSections['example'])) {
            $prompt .= "\n\n".implode("\n", $promptSections['example']);
        }

        return $prompt . ("\n\n" . ($promptSections['response_format'] ?? ''));
    }
}
