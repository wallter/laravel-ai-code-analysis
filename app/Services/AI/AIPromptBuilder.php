<?php

namespace App\Services\AI;

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
    public function __construct(
        protected OperationIdentifier $operationIdentifier,
        protected array $config,
        protected ?array $astData = null,
        protected string $rawCode = '',
        protected string $previousResults = ''
    ) {}

    /**
     * Build the AI prompt based on the pass configuration.
     *
     * @return string The constructed AI prompt as JSON string.
     */
    public function buildPrompt(): string
    {
        $passType = $this->config['type'] ?? PassType::BOTH->value;
        $basePrompt = $this->config['prompt_sections']['base_prompt'] ?? 'Analyze the following code:';

        // Retrieve model configuration to check if 'system' messages are supported
        $modelName = $this->config['model'] ?? config('ai.default.model');
        $modelConfig = config("ai.models.{$modelName}", []);
        $supportsSystemMessage = $modelConfig['supports_system_message'] ?? false;

        // Initialize prompt
        $prompt = Str::of($basePrompt);

        // Append AST data if pass type is 'ast' or 'both'
        if (in_array($passType, [PassType::AST->value, PassType::BOTH->value], true)) {
            if (! empty($this->astData)) {
                $astJson = json_encode($this->astData, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                $prompt = $prompt->append("\n\n".AiDelimiters::GUIDELINES_START->value)
                    ->append("\n[AST DATA]")
                    ->append("\n{$astJson}")
                    ->append("\n".AiDelimiters::END->value);
            }
        }

        // Append raw code if pass type is 'raw' or 'both'
        if (in_array($passType, [PassType::RAW->value, PassType::BOTH->value], true)) {
            if (! empty($this->rawCode)) {
                $prompt = $prompt->append("\n\n".AiDelimiters::GUIDELINES_START->value)
                    ->append("\n[RAW CODE]")
                    ->append("\n{$this->rawCode}")
                    ->append("\n".AiDelimiters::END->value);
            }
        }

        // Append previous pass outputs if pass type is 'previous'
        if ($passType === PassType::PREVIOUS->value) {
            if (! empty($this->previousResults)) {
                $prompt = $prompt->append("\n\n".AiDelimiters::GUIDELINES_START->value)
                    ->append("\n[PREVIOUS ANALYSIS RESULTS]")
                    ->append("\n{$this->previousResults}")
                    ->append("\n".AiDelimiters::END->value);
            }
        }

        // Append guidelines
        if (isset($this->config['prompt_sections']['guidelines'])) {
            $guidelines = implode("\n", $this->config['prompt_sections']['guidelines']);
            $prompt = $prompt->append("\n\n".AiDelimiters::GUIDELINES_START->value)
                ->append("\n{$guidelines}")
                ->append("\n".AiDelimiters::END->value);
        }

        // Append example if exists
        if (isset($this->config['prompt_sections']['example'])) {
            $example = implode("\n", $this->config['prompt_sections']['example']);
            $prompt = $prompt->append("\n\n".AiDelimiters::EXAMPLE_START->value)
                ->append("\n{$example}")
                ->append("\n".AiDelimiters::END->value);
        }

        // Append response format
        if (isset($this->config['prompt_sections']['response_format'])) {
            $responseFormat = $this->config['prompt_sections']['response_format'];
            $prompt = $prompt->append("\n\n".AiDelimiters::RESPONSE_FORMAT_START->value)
                ->append("\n{$responseFormat}")
                ->append("\n".AiDelimiters::END->value);
        }

        // Build the messages array based on model support
        $messages = [];

        if ($supportsSystemMessage && isset($this->config['system_message'])) {
            $messages[] = ['role' => 'system', 'content' => $this->config['system_message']];
        }

        $messages[] = ['role' => 'user', 'content' => $prompt->toString()];

        // Convert to JSON for OpenAI API
        $messagesJson = json_encode($messages, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $messagesJson;
    }
}
