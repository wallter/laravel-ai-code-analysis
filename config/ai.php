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

    'openai_api_key' => env('OPENAI_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Default Model, Tokens, Temperature
    |--------------------------------------------------------------------------
    */
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
    'max_tokens'   => env('OPENAI_MAX_TOKENS', 500),
    'temperature'  => env('OPENAI_TEMPERATURE', 0.5),

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
            'system_message'=> 'You are an assistant that generates comprehensive documentation from AST data. Focus on describing classes, methods, parameters, and the usage context.',
            'prompt'        => '',
        ],

        'code_improvements' => [
            'model'         => 'gpt-4o-mini',
            'max_tokens'    => 2000,
            'temperature'   => 0.7,
            'system_message'=> 'You are an assistant that suggests code improvements, best practices, and refactoring steps.',
            'prompt'        => '',
        ],

        'ast_insights' => [
            'model'         => 'gpt-4o-mini',
            'max_tokens'    => 300,
            'temperature'   => 0.5,
            'system_message'=> 'You provide AST-based insights, focusing on structure and relationships in code.',
            'prompt'        => 'Provide insights based on the given AST.',
        ],

        // Possibly more specialized operations, each with a different system or prompt text:
        'some_other_op' => [
            'model'         => 'gpt-4o-mini',
            'max_tokens'    => 1000,
            'temperature'   => 0.6,
            'system_message'=> 'You are an expert in code security analysis, focusing on vulnerabilities.',
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
        'multi_pass_analysis' => [

            'doc_generation' => [
                'operation'   => 'code_analysis',
                'type'        => 'ast',
                'prompt'      => "Generate comprehensive documentation from the following AST data. Focus on describing classes, methods, parameters, and the usage context.\n",
                'max_tokens'  => 1200,
                'temperature' => 0.3,
            ],

            // 'refactor_suggestions' => [
            //     'operation'   => 'code_improvements',
            //     'type'        => 'raw',
            //     'prompt'      => "Review the raw PHP code and suggest meaningful refactors, best practices, or design improvements.\n",
            //     'max_tokens'  => 1500,
            //     'temperature' => 0.6,
            // ],

            // 'complexity_analysis' => [
            //     'operation'   => 'code_analysis',
            //     'type'        => 'both',
            //     'prompt'      => "Perform a complexity analysis on the code structure (classes, methods) and the raw code.\n",
            //     'max_tokens'  => 800,
            //     'temperature' => 0.4,
            // ],

            // 'security_assessment' => [
            //     'operation'   => 'code_improvements',
            //     'type'        => 'raw',
            //     'prompt'      => "Analyze this PHP code for potential security vulnerabilities or insecure practices.\n",
            //     'max_tokens'  => 1000,
            //     'temperature' => 0.5,
            // ],

            // 'performance_tips' => [
            //     'operation'   => 'code_improvements',
            //     'type'        => 'both',
            //     'prompt'      => "Review the structural AST data and raw code to identify performance bottlenecks.\n",
            //     'max_tokens'  => 1200,
            //     'temperature' => 0.5,
            // ],

            // 'doc_summary' => [
            //     'operation' => 'code_analysis',
            //     'type'      => 'ast',
            //     'prompt'    => "Generate a doc summary from the AST structure.\n",
            // ],

            // 'improvements' => [
            //     'operation' => 'code_improvements',
            //     'type'      => 'raw',
            //     'prompt'    => "Evaluate the raw code for potential improvements.\n",
            // ],
        ],
    ],
];