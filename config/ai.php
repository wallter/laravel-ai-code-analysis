<?php

use App\Enums\OperationIdentifier;
use App\Enums\PassType;

/**
 * AI Analysis Configuration
 *
 * This configuration defines multiple AI analysis passes for a PHP application.
 * Each pass has a specific purpose (documentation, functionality, style checks, etc.)
 * and relies on different models and prompts.
 *
 * Environment variables enable flexible parameter changes without code modifications.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    | This key is required to authenticate requests to the OpenAI API.
    | Fallback set to empty if not defined.
    */
    'openai_api_key' => env('OPENAI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Model Configuration
    |--------------------------------------------------------------------------
    | Applied when a pass does not specify a model.
    | Fallbacks help avoid errors if environment variables are missing.
    */
    'default' => [
        'model' => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
        'max_tokens' => env('AI_DEFAULT_MAX_TOKENS', 500),
        'temperature' => env('AI_DEFAULT_TEMPERATURE', 0.5),
        'system_message' => 'You are a helpful AI assistant.',
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI Models Configuration
    |--------------------------------------------------------------------------
    | Each key describes a model with its specific parameters, including
    | whether it supports system messages and which token parameter to use.
    */
    'models' => [
        // Not used for now, but retained for potential future usage.
        'o1-mini' => [
            'model_name' => env('OPENAI_MODEL_O1_MINI', 'o1-mini'),
            'max_tokens' => env('OPENAI_MODEL_O1_MINI_MAX_TOKENS', 1500),
            'temperature' => env('OPENAI_MODEL_O1_MINI_TEMPERATURE', 0.3),
            'supports_system_message' => false,
            'token_limit_parameter' => 'max_completion_tokens',
        ],

        'gpt-4' => [
            'model_name' => env('OPENAI_MODEL_GPT4', 'gpt-4'),
            'max_tokens' => env('OPENAI_MODEL_GPT4_MAX_TOKENS', 2000),
            'temperature' => env('OPENAI_MODEL_GPT4_TEMPERATURE', 0.3),
            'supports_system_message' => true,
            'token_limit_parameter' => 'max_tokens',
        ],

        'gpt-3.5-turbo' => [
            'model_name' => env('OPENAI_MODEL_GPT35_TURBO', 'gpt-3.5-turbo'),
            'max_tokens' => env('OPENAI_MODEL_GPT35_TURBO_MAX_TOKENS', 1500),
            'temperature' => env('OPENAI_MODEL_GPT35_TURBO_TEMPERATURE', 0.4),
            'supports_system_message' => true,
            'token_limit_parameter' => 'max_tokens',
        ],

        'gpt-4o' => [
            'model_name' => env('OPENAI_MODEL_GPT4O', 'gpt-4o'),
            'max_tokens' => env('OPENAI_MODEL_GPT4O_MAX_TOKENS', 2500),
            'temperature' => env('OPENAI_MODEL_GPT4O_TEMPERATURE', 0.3),
            'supports_system_message' => true,
            'token_limit_parameter' => 'max_tokens',
        ],

        'gpt-40-mini' => [
            'model_name' => env('OPENAI_MODEL_GPT40_MINI', 'gpt-40-mini'),
            'max_tokens' => env('OPENAI_MODEL_GPT40_MINI_MAX_TOKENS', 1000),
            'temperature' => env('OPENAI_MODEL_GPT40_MINI_TEMPERATURE', 0.4),
            'supports_system_message' => false,
            'token_limit_parameter' => 'max_completion_tokens',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Static Analysis Tools Configuration
    |--------------------------------------------------------------------------
    | Defines the static analysis tools to be integrated into the system.
    | Each tool has its specific command and configurations.
    */
    'static_analysis_tools' => [
        'PHPStan' => [
            'enabled' => true,
            'command' => 'vendor/bin/phpstan',
            'options' => ['analyse', '--error-format=json'], // Updated option
            'output_format' => 'json',
        ],
        'PHP_CodeSniffer' => [
            'enabled' => true,
            'command' => 'vendor/bin/phpcs',
            'options' => ['--report=json'],
            'output_format' => 'json',
        ],
        'Psalm' => [
            'enabled' => true,
            'command' => 'vendor/bin/psalm',
            'options' => ['--output-format=json'],
            'output_format' => 'json',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Passes Configuration
    |--------------------------------------------------------------------------
    | Each key focuses on a specific analysis task or pass.
    */
    'passes' => [

        /*
        |--------------------------------------------------------------------------
        | Documentation Generation Pass
        |--------------------------------------------------------------------------
        | Summarizes code and AST data. Helpful for human readers and RAG lookups.
        |
        | Updated to use 'gpt-4o' instead of 'o1-mini' to avoid the model limitations.
        */
        'doc_generation' => [
            'operation_identifier' => OperationIdentifier::DOC_GENERATION->value,
            'model' => 'gpt-4o',  // Updated
            // Because 'gpt-4o' uses 'max_tokens', but your pass previously used max_completion_tokens, you can
            // choose to keep it or rename as needed. We'll align with the new model's parameter usage.
            // For consistency with the logs, let's keep 'max_completion_tokens' but map it in your service to 'max_tokens'.
            'max_tokens' => env('AI_DOC_GENERATION_MAX_TOKENS', 1200),
            'temperature' => env('AI_DOC_GENERATION_TEMPERATURE', 0.25),
            'type' => PassType::BOTH->value,
            'system_message' => 'You generate concise PHP documentation from code and AST to complement phpdoc.',
            'prompt_sections' => [
                'base_prompt' => 'Analyze the following code:',
                'guidelines' => [
                    '- Create short but clear documentation from the AST data and raw code.',
                    '- Summarize the purpose, methods, parameters, and usage context.',
                    '- Avoid documenting __construct, getter, setter, and similar functions.',
                    '- Exclude comment code blocks from the documentation.',
                    '- Mention custom annotations, such as @url.',
                    '- Limit the documentation to approximately 200 words.',
                ],
                'response_format' => 'Provide concise, human-readable documentation.',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Functional Analysis Pass
        |--------------------------------------------------------------------------
        | Identifies edge cases, performance issues, and reliability concerns.
        */
        'functional_analysis' => [
            'operation_identifier' => OperationIdentifier::FUNCTIONAL_ANALYSIS->value,
            'model' => 'gpt-4',
            'max_tokens' => env('AI_FUNCTIONAL_ANALYSIS_MAX_TOKENS', 2500),
            'temperature' => env('AI_FUNCTIONAL_ANALYSIS_TEMPERATURE', 0.65),
            'type' => PassType::BOTH->value,
            'system_message' => 'You perform thorough functional analysis based on AST data and raw code.',
            'prompt_sections' => [
                'base_prompt' => 'Analyze the following code:',
                'guidelines' => [
                    '- Evaluate functionality, identify edge cases, and detect performance bottlenecks.',
                    '- Suggest improvements to enhance reliability and testability.',
                ],
                'response_format' => 'Provide concise, structured insights with actionable recommendations.',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Style & Convention Pass
        |--------------------------------------------------------------------------
        | Checks code style consistency against PSR or similar standards.
        */
        'style_convention' => [
            'operation_identifier' => OperationIdentifier::STYLE_CONVENTION->value,
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => env('AI_STYLE_CONVENTION_MAX_TOKENS', 1800),
            'temperature' => env('AI_STYLE_CONVENTION_TEMPERATURE', 0.28),
            'type' => PassType::RAW->value,
            'system_message' => 'You review code style for PSR compliance.',
            'prompt_sections' => [
                'base_prompt' => 'Analyze the following code:',
                'guidelines' => [
                    '- Check formatting, naming conventions, and documentation clarity according to coding standards.',
                    '- Suggest concise improvements to ensure consistency.',
                ],
                'response_format' => 'Provide bullet points or short paragraphs highlighting style issues and suggestions.',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Consolidation Pass
        |--------------------------------------------------------------------------
        | Aggregates results from earlier passes into a single summary.
        */
        'consolidation_pass' => [
            'operation_identifier' => OperationIdentifier::CONSOLIDATION_PASS->value,
            'model' => 'gpt-4',
            'max_tokens' => env('AI_CONSOLIDATION_PASS_MAX_TOKENS', 2500),
            'temperature' => env('AI_CONSOLIDATION_PASS_TEMPERATURE', 0.4),
            'type' => PassType::PREVIOUS->value,
            'system_message' => 'You consolidate prior AI analysis results into a final summary.',
            'prompt_sections' => [
                'base_prompt' => 'Consolidate the following analysis results:',
                'guidelines' => [
                    '- Combine outputs from all previous passes into a cohesive summary.',
                    '- Highlight key findings and provide overall recommendations.',
                ],
                'response_format' => 'Provide a unified summary with actionable recommendations.',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Scoring Pass
        |--------------------------------------------------------------------------
        | Assigns scores (0–100) for documentation, functionality, and style.
        | Outputs JSON for easy parsing.
        */
        'scoring_pass' => [
            'operation_identifier' => OperationIdentifier::SCORING_PASS->value,
            'model' => 'gpt-4',
            'max_tokens' => env('AI_SCORING_PASS_MAX_TOKENS', 500),
            'temperature' => env('AI_SCORING_PASS_TEMPERATURE', 0.3),
            'type' => PassType::PREVIOUS->value,
            'system_message' => 'You analyze previous AI analysis results and assign scores.',
            'prompt_sections' => [
                'base_prompt' => 'Evaluate the following analysis results:',
                'guidelines' => [
                    '- Score documentation, functionality, and style on a scale of 0 to 100.',
                    '- Calculate overall_score as the average of the three scores.',
                ],
                'example' => [
                    '{',
                    '  "documentation_score": 85.0,',
                    '  "functionality_score": 90.0,',
                    '  "style_score": 80.0,',
                    '  "overall_score": 85.0,',
                    '  "summary": "The codebase has excellent documentation and functionality but could improve on coding style consistency."',
                    '}',
                ],
                'response_format' => 'Return a JSON object with "documentation_score", "functionality_score", "style_score", "overall_score", and "summary".',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Laravel Migration Analysis Pass
        |--------------------------------------------------------------------------
        | Checks code for Laravel migration best practices and suggests improvements.
        | Helps developers ensure smooth migrations aligned with Laravel patterns.
        */
        'laravel_migration' => [
            'operation_identifier' => OperationIdentifier::LARAVEL_MIGRATION->value,
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => env('AI_LARAVEL_MIGRATION_MAX_TOKENS', 1000),
            'temperature' => env('AI_LARAVEL_MIGRATION_TEMPERATURE', 0.3),
            'type' => PassType::BOTH->value,
            'system_message' => 'You analyze code for Laravel migration improvements.',
            'prompt_sections' => [
                'base_prompt' => 'Analyze the following Laravel migration code:',
                'guidelines' => [
                    '- Identify Laravel migration best practices applicable to the code.',
                    '- Suggest improvements or code changes to enhance migration patterns.',
                    '- Keep explanations short and actionable for RAG usage.',
                ],
                'response_format' => 'Provide a list of improvements or code changes that enhance Laravel migration patterns.',
            ],
        ],

        /*
        |--------------------------------------------------------------------------
        | Laravel Migration Scoring Pass
        |--------------------------------------------------------------------------
        | Rates migration quality (0–100) and gives a concise explanation.
        */
        'laravel_migration_scoring' => [
            'operation_identifier' => OperationIdentifier::LARAVEL_MIGRATION_SCORING->value,
            'model' => 'gpt-4',
            'max_tokens' => env('AI_LARAVEL_MIGRATION_SCORING_MAX_TOKENS', 500),
            'temperature' => env('AI_LARAVEL_MIGRATION_SCORING_TEMPERATURE', 0.3),
            'type' => PassType::PREVIOUS->value,
            'system_message' => 'You assign a migration_score (0–100) for Laravel migration compliance.',
            'prompt_sections' => [
                'base_prompt' => 'Evaluate the following Laravel migration analysis:',
                'guidelines' => [
                    '- Rate migration quality on a scale of 0 to 100.',
                    '- Provide a short rationale for the score.',
                ],
                'example' => [
                    '{',
                    '  "migration_score": 85.0,',
                    '  "summary": "This Laravel migration analysis shows good compliance with best practices, but there are minor areas for improvement."',
                    '}',
                ],
                'response_format' => 'Return a JSON object with "migration_score" and "summary".',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Pass Analysis Order
    |--------------------------------------------------------------------------
    | Defines the sequence in which passes run.
    | Add or remove steps as needed in this pipeline.
    */
    'operations' => [
        'multi_pass_analysis' => [
            'pass_order' => [
                // Updated to use gpt-4o for doc_generation (and ignoring o1-mini)
                'doc_generation',
                'functional_analysis',
                'style_convention',
                'consolidation_pass',
                'scoring_pass',
                'laravel_migration',
                'laravel_migration_scoring',
            ],
        ],
    ],

];
