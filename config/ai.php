<?php

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
    | Each key describes a model with specific parameters.
    */
    'models' => [
        'o1-mini' => [
            'model_name' => env('OPENAI_MODEL_O1_MINI', 'o1-mini'),
            'max_tokens' => env('OPENAI_MODEL_O1_MINI_MAX_TOKENS', 1500),
            'temperature' => env('OPENAI_MODEL_O1_MINI_TEMPERATURE', 0.3),
        ],
        'gpt-4' => [
            'model_name' => env('OPENAI_MODEL_GPT4', 'gpt-4'),
            'max_tokens' => env('OPENAI_MODEL_GPT4_MAX_TOKENS', 2000),
            'temperature' => env('OPENAI_MODEL_GPT4_TEMPERATURE', 0.3),
        ],
        'gpt-3.5-turbo' => [
            'model_name' => env('OPENAI_MODEL_GPT35_TURBO', 'gpt-3.5-turbo'),
            'max_tokens' => env('OPENAI_MODEL_GPT35_TURBO_MAX_TOKENS', 1500),
            'temperature' => env('OPENAI_MODEL_GPT35_TURBO_TEMPERATURE', 0.4),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Passes Configuration
    |--------------------------------------------------------------------------
    | Each pass focuses on a specific analysis task.
    */
    'passes' => [
        /*
        |--------------------------------------------------------------------------
        | Documentation Generation Pass
        |--------------------------------------------------------------------------
        | Summarizes code and AST data. Aids human readers and RAG lookups.
        */
        'doc_generation' => [
            'operation_identifier' => 'doc_generation',
            'model' => 'o1-mini',
            'max_tokens' => env('AI_DOC_GENERATION_MAX_TOKENS', 1200),
            'temperature' => env('AI_DOC_GENERATION_TEMPERATURE', 0.25),
            'type' => 'both',
            'system_message' => 'You generate concise PHP documentation from code and AST to complement phpdoc documentation.',
            'prompt' => implode("\n", [
                'Create short but clear documentation from the AST data and raw code:',
                '- Summarize the purpose, methods, parameters, and usage context.',
                '- Avoid __construct, getter, and setter details.',
                '- Exclude comment code blocks.',
                '- Mention custom annotations like @url.',
                'Limit docs to ~200 words.',
            ]),
        ],

        /*
        |--------------------------------------------------------------------------
        | Functional Analysis Pass
        |--------------------------------------------------------------------------
        | Identifies edge cases, performance issues, and reliability concerns.
        */
        'functional_analysis' => [
            'operation_identifier' => 'functional_analysis',
            'model' => 'gpt-4',
            'max_tokens' => env('AI_FUNCTIONAL_ANALYSIS_MAX_TOKENS', 2500),
            'temperature' => env('AI_FUNCTIONAL_ANALYSIS_TEMPERATURE', 0.65),
            'type' => 'both',
            'system_message' => 'You perform thorough functional analysis based on AST data and raw code.',
            'prompt' => implode("\n", [
                'Evaluate functionality, find edge cases, detect performance bottlenecks.',
                'Suggest improvements to boost reliability and testability.',
            ]),
        ],

        /*
        |--------------------------------------------------------------------------
        | Style & Convention Pass
        |--------------------------------------------------------------------------
        | Checks code style consistency against PSR or similar standards.
        */
        'style_convention' => [
            'operation_identifier' => 'style_convention',
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => env('AI_STYLE_CONVENTION_MAX_TOKENS', 1800),
            'temperature' => env('AI_STYLE_CONVENTION_TEMPERATURE', 0.28),
            'type' => 'raw',
            'system_message' => 'You review code style for PSR compliance.',
            'prompt' => implode("\n", [
                'Check formatting, naming conventions, and clarity per coding standards.',
                'Suggest concise improvements to ensure consistency.',
            ]),
        ],

        /*
        |--------------------------------------------------------------------------
        | Consolidation Pass
        |--------------------------------------------------------------------------
        | Aggregates results from earlier passes into a single summary.
        */
        'consolidation_pass' => [
            'operation_identifier' => 'consolidation_pass',
            'model' => 'gpt-4',
            'max_tokens' => env('AI_CONSOLIDATION_PASS_MAX_TOKENS', 2500),
            'temperature' => env('AI_CONSOLIDATION_PASS_TEMPERATURE', 0.4),
            'type' => 'previous',
            'system_message' => 'You consolidate prior AI analysis results into a final summary.',
            'prompt' => implode("\n", [
                'Combine results of previous passes into one concise summary.',
                'Include any ratings or recommendations from prior outputs.',
            ]),
        ],

        /*
        |--------------------------------------------------------------------------
        | Scoring Pass
        |--------------------------------------------------------------------------
        | Assigns scores (0–100) for documentation, functionality, and style.
        | Outputs JSON for easy parsing.
        */
        'scoring_pass' => [
            'operation_identifier' => 'scoring',
            'model' => 'gpt-4',
            'max_tokens' => env('AI_SCORING_PASS_MAX_TOKENS', 500),
            'temperature' => env('AI_SCORING_PASS_TEMPERATURE', 0.3),
            'type' => 'previous',
            'system_message' => 'You analyze previous AI analysis results and assign scores.',
            'prompt' => implode("\n", [
                'Score documentation, functionality, and style (0–100).',
                'Calculate overall_score as their average.',
                'Format response in JSON like:',
                '{',
                '  "documentation_score": 85.0,',
                '  "functionality_score": 90.0,',
                '  "style_score": 80.0,',
                '  "overall_score": 85.0,',
                '  "summary": "Short explanation"',
                '}',
            ]),
        ],

        /*
        |--------------------------------------------------------------------------
        | Laravel Migration Analysis Pass
        |--------------------------------------------------------------------------
        | Checks code for Laravel migration best practices and suggests improvements.
        | Helps developers ensure smooth migrations aligned with Laravel patterns.
        */
        'laravel_migration' => [
            'operation_identifier' => 'laravel_migration',
            // A balanced model for shorter responses and cost-effectiveness.
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => env('AI_LARAVEL_MIGRATION_MAX_TOKENS', 1000),
            'temperature' => env('AI_LARAVEL_MIGRATION_TEMPERATURE', 0.3),
            'type' => 'both',
            'system_message' => 'You analyze code for Laravel migration improvements.',
            'prompt' => implode("\n", [
                'Identify Laravel migration best practices that can be applied.',
                'Keep explanations short and actionable for RAG usage.',
            ]),
        ],

        /*
        |--------------------------------------------------------------------------
        | Laravel Migration Scoring Pass
        |--------------------------------------------------------------------------
        | Rates migration quality (0–100) and gives a concise explanation.
        */
        'laravel_migration_scoring' => [
            'operation_identifier' => 'laravel_migration_scoring',
            'model' => 'gpt-4',
            'max_tokens' => env('AI_LARAVEL_MIGRATION_SCORING_MAX_TOKENS', 500),
            'temperature' => env('AI_LARAVEL_MIGRATION_SCORING_TEMPERATURE', 0.3),
            'type' => 'previous',
            'system_message' => 'You assign a migration_score (0–100) for Laravel migration compliance.',
            'prompt' => implode("\n", [
                'Analyze code based on Laravel migration practices.',
                'Output JSON with "migration_score" and a short "summary".',
                '{',
                '  "migration_score": 85.0,',
                '  "summary": "Brief rationale"',
                '}',
            ]),
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