<?php

namespace App\Services;

use App\Enums\AiDelimiters;
use App\Enums\OperationIdentifier;
use App\Enums\PassType;
use Illuminate\Support\Str;

/**
 * Builds AI prompts based on pass configurations and provided data.
 */
class AIPromptBuilder
{
    /**
     * Constructor to initialize the Prompt Builder.
     *
     * @param  OperationIdentifier  $operationIdentifier  The ENUM identifier for the AI operation.
     * @param  array  $config  The AI pass configuration.
     * @param  array|null  $astData  The AST data.
     * @param  string  $rawCode  The raw code.
     * @param  string  $previousResults  The previous analysis results.
     */
    public function __construct(protected OperationIdentifier $operationIdentifier, protected array $config, protected ?array $astData = null, protected string $rawCode = '', protected string $previousResults = '')
    {
    }

    /**
     * Build the AI prompt based on the pass configuration.
     *
     * @return string The constructed AI prompt.
     */
    public function buildPrompt(): string
    {
        $passType = $this->config['type'] ?? PassType::BOTH->value;
        $basePrompt = $this->config['prompt_sections']['base_prompt'] ?? 'Analyze the following code:';

        // Initialize prompt with base prompt using Laravel's str() helper
        $prompt = str($basePrompt);

        // Append AST data if pass type is 'ast' or 'both'
        if (in_array($passType, [PassType::AST->value, PassType::BOTH->value])) {
            if (! empty($this->astData)) {
                $prompt = $prompt->append("\n\n".AiDelimiters::GUIDELINES_START->value)
                    ->append("\n[AST DATA]")
                    ->append("\n")
                    ->append(json_encode($this->astData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT))
                    ->append("\n".AiDelimiters::GUIDELINES_END->value);
            }
        }

        // Append raw code if pass type is 'raw' or 'both'
        if (in_array($passType, [PassType::RAW->value, PassType::BOTH->value])) {
            if (! empty($this->rawCode)) {
                $prompt = $prompt->append("\n\n".AiDelimiters::GUIDELINES_START->value)
                    ->append("\n[RAW CODE]")
                    ->append("\n")
                    ->append($this->rawCode)
                    ->append("\n".AiDelimiters::GUIDELINES_END->value);
            }
        }

        // Append previous pass outputs if pass type is 'previous'
        if ($passType === PassType::PREVIOUS->value) {
            if (! empty($this->previousResults)) {
                $prompt = $prompt->append("\n\n".AiDelimiters::GUIDELINES_START->value)
                    ->append("\n[PREVIOUS ANALYSIS RESULTS]")
                    ->append("\n")
                    ->append($this->previousResults)
                    ->append("\n".AiDelimiters::GUIDELINES_END->value);
            }
        }

        // Append guidelines and response format
        if (isset($this->config['prompt_sections']['guidelines'])) {
            $guidelines = implode("\n", $this->config['prompt_sections']['guidelines']);
            $prompt = $prompt->append("\n\n".AiDelimiters::GUIDELINES_START->value)
                ->append("\n{$guidelines}")
                ->append("\n".AiDelimiters::GUIDELINES_END->value);
        }

        if (isset($this->config['prompt_sections']['example'])) {
            $example = implode("\n", $this->config['prompt_sections']['example']);
            $prompt = $prompt->append("\n\n".AiDelimiters::EXAMPLE_START->value)
                ->append("\n{$example}")
                ->append("\n".AiDelimiters::EXAMPLE_END->value);
        }

        if (isset($this->config['prompt_sections']['response_format'])) {
            $responseFormat = $this->config['prompt_sections']['response_format'];
            $prompt = $prompt->append("\n\n".AiDelimiters::RESPONSE_FORMAT_START->value)
                ->append("\n{$responseFormat}")
                ->append("\n".AiDelimiters::RESPONSE_FORMAT_END->value);
        }

        return $prompt->toString();
    }
}
