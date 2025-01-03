<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | This file stores credentials and settings for the AI service used
    | to enhance documentation, code analysis, etc. Ensure that you set
    | the necessary env variables in your .env file.
    |
    */

    // 1. API Credentials
    'openai_api_key' => env('OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Model, Tokens, Temperature
    |--------------------------------------------------------------------------
    */
    /*
    |--------------------------------------------------------------------------
    | Default AI Model Settings
    |--------------------------------------------------------------------------
    |
    | These settings serve as the default configuration for AI operations,
    | unless overridden by specific operation configurations.
    |
    */

    'default' => [
        'model'       => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
        'max_tokens'  => env('AI_DEFAULT_MAX_TOKENS', 500),
        'temperature' => env('AI_DEFAULT_TEMPERATURE', 0.5),
        'system_message' => 'You are a helpful AI assistant.',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Operations
    |--------------------------------------------------------------------------
    |
    | Each key is an "operationIdentifier". Each defines its own:
    |  - model
    |  - max_tokens
    |  - temperature
    |  - system_message (optional)
    |  - prompt (fallback text if user doesn't supply one)
    |
    */
    /*
    |--------------------------------------------------------------------------
    | AI Operations
    |--------------------------------------------------------------------------
    |
    | Define each AI operation your application will utilize. Each operation
    | can inherit default settings or override them as needed.
    |
    | Available Drivers:
    | - 'chat': For chat-based models like ChatGPT.
    | - 'completion': For standard completion models.
    |
    */

    'operations' => [

        'code_analysis' => [
            'model'         => env('CODE_ANALYSIS_MODEL', 'gpt-4o-mini'),
            'max_tokens'    => env('CODE_ANALYSIS_MAX_TOKENS', 1500),
            'temperature'   => env('CODE_ANALYSIS_TEMPERATURE', 0.4),
            'system_message' => 'You are an assistant that generates comprehensive documentation from AST data. Focus on describing classes, methods, parameters, and the usage context.',
            'prompt'        => '',
        ],

        'multi_pass_analysis' => [

            'doc_generation' => [
                'operation'    => 'code_analysis',
                'type'         => 'both',
                'max_tokens'   => 1000,
                'temperature'  => 0.3,
                'prompt'       => implode("\n", [
                    "You are a concise documentation generator for a PHP codebase.",
                    "Create a short but clear doc from the AST data + raw code:",
                    "- Summarize only essential info: class or trait's purpose, key methods, parameters, usage context.",
                    "- Mention custom annotations (@url, etc.), but keep it under ~200 words.",
                    "Be succinct and well-structured."
                ]),
            ],

            'refactor_suggestions' => [
                'operation'    => 'code_improvements',
                'type'         => 'raw',
                'max_tokens'   => 1800,
                'temperature'  => 0.6,
                'prompt'       => implode("\n", [
                    "You are a senior PHP engineer analyzing the raw code. Provide actionable refactoring suggestions:",
                    "- Focus on structural changes (class splitting, design patterns)",
                    "- Emphasize SOLID principles, especially SRP",
                    "- Discuss how to reduce duplication, enhance naming clarity, and improve maintainability",
                    "Write your suggestions in a concise list or short paragraphs."
                ]),
            ],

            // Add additional passes here following the same structure

            'pass_order' => [ // Define the execution order
                'doc_generation',
                'refactor_suggestions',
                // Add additional pass names in desired order
            ],
        ],

        'code_improvements' => [
            'model'         => 'gpt-4o-mini',
            'max_tokens'    => 2000,
            'temperature'   => 0.7,
            'system_message' => 'You are an assistant that suggests code improvements, best practices, and refactoring steps.',
            'prompt'        => '',
        ],

        'ast_insights' => [
            'model'         => 'gpt-4o-mini',
            'max_tokens'    => 300,
            'temperature'   => 0.5,
            'system_message' => 'You provide AST-based insights, focusing on structure and relationships in code.',
            'prompt'        => 'Provide insights based on the given AST.',
        ],

        // Possibly more specialized operations, each with a different system or prompt text:
        'some_other_op' => [
            'model'         => 'gpt-4o-mini',
            'max_tokens'    => 1000,
            'temperature'   => 0.6,
            'system_message' => 'You are an expert in code security analysis, focusing on vulnerabilities.',
            'prompt'        => '',
        ],

        /*
        |----------------------------------------------------------------------
        | Analysis Limits (not used directly by the service, but by commands)
        |----------------------------------------------------------------------
        */
        'analysis_limits' => [
            'limit_class'  => env('ANALYSIS_LIMIT_CLASS', 0),
            'limit_method' => env('ANALYSIS_LIMIT_METHOD', 0),
        ],

        /*
        |--------------------------------------------------------------------------
        | Multi-Pass Analysis
        |--------------------------------------------------------------------------
        |
        | Each pass references an existing operation (like "code_analysis" or
        | "code_improvements"), plus a "type" (e.g. 'ast', 'raw', 'both') and
        | optionally a custom prompt or system_message override.
        |
        */
    /*
    |--------------------------------------------------------------------------
    | Analysis Limits
    |--------------------------------------------------------------------------
    |
    | Settings to limit the scope of analysis, such as the maximum number of
    | classes or methods to analyze per file. A value of 0 means no limit.
    |
    */

    'analysis_limits' => [
        'limit_class'  => env('ANALYSIS_LIMIT_CLASS', 0),  // 0 = no limit
        'limit_method' => env('ANALYSIS_LIMIT_METHOD', 0), // 0 = no limit
    ],
    ],
];
