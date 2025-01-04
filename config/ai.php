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

    'operations' => [

        'code_analysis' => [
            'model'         => env('CODE_ANALYSIS_MODEL', 'gpt-4o-mini'),
            'max_tokens'    => env('CODE_ANALYSIS_MAX_TOKENS', 1500),
            'temperature'   => env('CODE_ANALYSIS_TEMPERATURE', 0.4),
            'system_message' => 'You are an assistant that generates comprehensive documentation from AST data. Focus on describing classes, methods, parameters, and the usage context.',
            'prompt'        => '',
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
        |--------------------------------------------------------------------------
        | Analysis Limits (not used directly by the service, but by commands)
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

        /*
        |--------------------------------------------------------------------------
        | Multi-Pass Analysis Configuration
        |--------------------------------------------------------------------------
        |
        | Configure the multi-pass analysis system, defining the order of passes
        | and each pass's specific configuration by referencing the operations
        | defined above.
        |
        */

        'multi_pass_analysis' => [

            /**
             * Define the order in which passes should be executed 
             * this (while technically redundant explicitly configures 
             * ordering of analysis passes rather than relying on the 
             * order of the array keys.
             */
            'pass_order' => [
                'doc_generation',
                'functional_analysis',
                'style_convention_review',
                'performance_analysis',
                'dependency_review',
                // 'refactor_suggestions',
            ],

            // 2. Pass Definitions
            'doc_generation' => [
                'operation'    => 'code_analysis',
                'type'         => 'both', // Options: 'ast', 'raw', 'both'
                'max_tokens'   => 1000,
                'temperature'  => 0.3,
                'prompt'       => implode("\n", [
                    "You are a concise documentation generator for a PHP codebase.",
                    "Create a short but clear doc from the AST data + raw code:",
                    "- Summarize only essential info: class or trait's purpose, key methods, parameters, usage context.",
                    "- Mention custom annotations (@url, etc.)",
                    "Be succinct and well-structured and keep it under ~200 words"
                ]),
                // Optional: Override system message or add additional parameters
            ],

            'functional_analysis' => [
                'operation'    => 'code_analysis',
                'type'         => 'both',
                'max_tokens'   => 2000,
                'temperature'  => 0.7,
                'prompt'       => implode("\n", [
                    "You are a senior software engineer conducting a functionality analysis of the provided code.",
                    "- Evaluate the code for adherence to functional requirements and expected behavior.",
                    "- Highlight any edge cases or scenarios that may break the functionality.",
                    "- Identify performance bottlenecks and suggest optimizations.",
                    "- Provide clear feedback on how to enhance reliability, scalability, and testability.",
                    "Write your analysis in concise bullet points or short, structured paragraphs."
                ]),
            ],

            'style_convention_review' => [
                'operation'    => 'code_analysis',
                'type'         => 'raw',
                'max_tokens'   => 1500,
                'temperature'  => 0.3,
                'prompt'       => implode("\n", [
                    "You are a code style reviewer analyzing the provided PHP code.",
                    "- Ensure adherence to PSR (PHP Standards Recommendations) or other applicable coding standards.",
                    "- Highlight inconsistencies in formatting, naming conventions, and documentation.",
                    "- Suggest improvements for better readability and consistency.",
                    "Provide feedback in concise bullet points."
                ]),
            ],

            'performance_analysis' => [
                'operation'    => 'code_analysis',
                'type'         => 'both',
                'max_tokens'   => 2000,
                'temperature'  => 0.5,
                'prompt'       => implode("\n", [
                    "You are a performance optimization expert analyzing the provided code.",
                    "- Identify inefficient loops, redundant operations, or excessive memory usage.",
                    "- Suggest optimizations, such as caching, algorithmic improvements, or code restructuring.",
                    "- Highlight areas that could benefit from asynchronous processing or parallelism.",
                    "Write your suggestions in concise bullet points or short paragraphs."
                ]),
            ],

            'dependency_review' => [
                'operation'    => 'code_analysis',
                'type'         => 'raw',
                'max_tokens'   => 1500,
                'temperature'  => 0.4,
                'prompt'       => implode("\n", [
                    "You are a dependency auditor reviewing the provided codebase.",
                    "- Evaluate and note external dependencies and compatibility with the PHP (8.1) version and frameworks used.",
                    "- Highlight outdated or insecure dependencies and recommend updates.",
                    "- Suggest alternatives for deprecated or inefficient libraries.",
                    "Provide your insights in a concise and actionable format."
                ]),
            ],

            // 'refactor_suggestions' => [
            //     'operation'    => 'code_improvements',
            //     'type'         => 'raw',
            //     'max_tokens'   => 1800,
            //     'temperature'  => 0.6,
            //     'prompt'       => implode("\n", [
            //         "You are a senior PHP engineer analyzing the raw code. Provide actionable refactoring suggestions:",
            //         "- Focus on structural changes (class splitting, design patterns)",
            //         "- Emphasize SOLID principles, especially SRP",
            //         "- Discuss how to reduce duplication, enhance naming clarity, and improve maintainability",
            //         "Write your suggestions in a concise list or short paragraphs."
            //     ]),
            //     // Optional: Override system message or add additional parameters
            // ],

            // Define additional passes following the same structure...

        ],
    ],
];
