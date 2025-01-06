<?php

/**
 * AI Analysis Configuration
 *
 * This configuration defines multiple AI analysis passes for a PHP application.
 * Each pass has a specific purpose (e.g., documentation, functional analysis,
 * style checks) and relies on different models and prompts.
 *
 * The array format allows easy addition of new passes (e.g., security or performance).
 * Environment variables enable flexible parameter changes without code modifications.
 */

return [

    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    | This key is required for authenticating requests to the OpenAI API.
    | Use a secure method (e.g., environment variable) to store your secret.
    */
    'openai_api_key' => env('OPENAI_API_KEY', ''), // Fallback to empty if not defined.

    /*
    |--------------------------------------------------------------------------
    | Default Model Configuration
    |--------------------------------------------------------------------------
    | These settings apply when an AI pass does not explicitly specify a model.
    | The fallback values help avoid errors if environment variables are missing.
    */
    'default' => [
        // Model choice for general usage if none is specified.
        'model' => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
        // Sets the maximum tokens to avoid overly verbose AI responses.
        'max_tokens' => env('AI_DEFAULT_MAX_TOKENS', 500),
        // Balances randomness in AI output. Lower means more deterministic.
        'temperature' => env('AI_DEFAULT_TEMPERATURE', 0.5),
        // System message guides the AI's overall role or tone.
        'system_message' => 'You are a helpful AI assistant.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenAI Models Configuration
    |--------------------------------------------------------------------------
    | Each key describes an AI model or pass with specific parameters.
    | 'model_name' references actual OpenAI model names, while 'max_tokens'
    | and 'temperature' help shape the AI's output.
    */
    'models' => [

        // Example pass for GPT-4 usage with higher token capacity.
        'gpt-4' => [
            'model_name' => env('OPENAI_MODEL_GPT4', 'gpt-4'),
            'max_tokens' => env('OPENAI_MODEL_GPT4_MAX_TOKENS', 2000),
            'temperature' => env('OPENAI_MODEL_GPT4_TEMPERATURE', 0.3),
        ],

        // Example pass for GPT-3.5-turbo usage.
        'gpt-3.5-turbo' => [
            'model_name' => env('OPENAI_MODEL_GPT35_TURBO', 'gpt-3.5-turbo'),
            'max_tokens' => env('OPENAI_MODEL_GPT35_TURBO_MAX_TOKENS', 1500),
            'temperature' => env('OPENAI_MODEL_GPT35_TURBO_TEMPERATURE', 0.4),
        ],

        // Example pass for a Code-Davinci model, useful for code-centric tasks.
        'code-davinci-002' => [
            'model_name' => env('OPENAI_MODEL_CODE_DAVINCI_002', 'code-davinci-002'),
            'max_tokens' => env('OPENAI_MODEL_CODE_DAVINCI_002_MAX_TOKENS', 2500),
            'temperature' => env('OPENAI_MODEL_CODE_DAVINCI_002_TEMPERATURE', 0.2),
        ],

        /*
        |--------------------------------------------------------------------------
        | Documentation Generation Pass
        |--------------------------------------------------------------------------
        | Creates concise documentation from code and AST.
        | Helps developers quickly grasp functionality without reading raw code.
        */
        'doc_generation' => [
            'operation_identifier' => 'doc_generation',
            // Reference the "code-davinci-002" model from above.
            'model' => 'code-davinci-002',
            // Allows more in-depth documentation but stays within a safe limit.
            'max_tokens' => env('AI_DOC_GENERATION_MAX_TOKENS', 1200),
            // Slightly lower temperature to maintain concise output.
            'temperature' => env('AI_DOC_GENERATION_TEMPERATURE', 0.25),
            // 'type' can be 'raw', 'both', or 'previous' to indicate input type.
            'type' => 'both',
            // System message sets context for the documentation tasks.
            'system_message' => 'You generate concise PHP documentation from code and AST to complement phpdoc documentation.',
            // The prompt array forms a multi-line input to the AI.
            'prompt' => implode("\n", [
                'Create short but clear documentation from the AST data and raw code:',
                '- Summarize the purpose, methods, parameters, and usage context.',
                '- Avoid documenting __construct, getter, setter, and similar functions.',
                '- Exclude comment code blocks from the documentation.',
                'Mention custom annotations, such as @url.',
                'Limit the documentation to approximately 200 words.',
            ]),
        ],

        /*
        |--------------------------------------------------------------------------
        | Functional Analysis Pass
        |--------------------------------------------------------------------------
        | Evaluates functionality, edge cases, and performance bottlenecks.
        | Useful for identifying potential bugs and improvement areas.
        */
        'functional_analysis' => [
            'operation_identifier' => 'functional_analysis',
            'model' => 'gpt-4',
            'max_tokens' => env('AI_FUNCTIONAL_ANALYSIS_MAX_TOKENS', 2500),
            'temperature' => env('AI_FUNCTIONAL_ANALYSIS_TEMPERATURE', 0.65),
            'type' => 'both',
            'system_message' => 'You perform thorough functional analysis based on AST data and raw code.',
            'prompt' => implode("\n", [
                'Evaluate the functionality, identify edge cases, and detect performance bottlenecks.',
                'Suggest improvements to enhance reliability and testability.',
            ]),
        ],

        /*
        |--------------------------------------------------------------------------
        | Style & Convention Pass
        |--------------------------------------------------------------------------
        | Checks code style consistency against PSR or other defined standards.
        | Encourages uniform formatting and maintainable code.
        */
        'style_convention' => [
            'operation_identifier' => 'style_convention',
            'model' => 'gpt-3.5-turbo',
            'max_tokens' => env('AI_STYLE_CONVENTION_MAX_TOKENS', 1800),
            'temperature' => env('AI_STYLE_CONVENTION_TEMPERATURE', 0.28),
            'type' => 'raw',
            'system_message' => 'You review code style for PSR compliance.',
            'prompt' => implode("\n", [
                'Check the code for formatting, naming conventions, and documentation clarity according to coding standards.',
                'Suggest concise improvements to ensure consistency.',
            ]),
        ],

        /*
        |--------------------------------------------------------------------------
        | Consolidation Pass
        |--------------------------------------------------------------------------
        | Aggregates results from earlier passes into a single summary.
        | Helps developers see a unified perspective of documentation, functionality, and style.
        */
        'consolidation_pass' => [
            'operation_identifier' => 'consolidation_pass',
            'model' => 'gpt-4',
            'max_tokens' => env('AI_CONSOLIDATION_PASS_MAX_TOKENS', 2500),
            'temperature' => env('AI_CONSOLIDATION_PASS_TEMPERATURE', 0.4),
            'type' => 'previous',
            'system_message' => 'You consolidate prior AI analysis results into a final summary.',
            'prompt' => implode("\n", [
                'Combine the results of previous analysis passes into a comprehensive summary.',
                'Utilize previous pass outputs and optionally include AST or raw code if necessary.',
                'Provide a rating or recommendation based on the aggregated feedback.',
            ]),
        ],

        /*
        |--------------------------------------------------------------------------
        | Scoring Pass
        |--------------------------------------------------------------------------
        | Assigns scores for documentation, functionality, and style, plus an overall score.
        | Produces output in JSON format for easy parsing and integration.
        */
        'scoring_pass' => [
            'operation_identifier' => 'scoring',
            'model' => 'gpt-4',
            'max_tokens' => env('AI_SCORING_PASS_MAX_TOKENS', 500),
            'temperature' => env('AI_SCORING_PASS_TEMPERATURE', 0.3),
            'type' => 'previous',
            'system_message' => 'You analyze previous AI analysis results and assign scores.',
            'prompt' => implode("\n", [
                'Analyze the results of the previous AI analysis passes: Documentation, Functionality, and Style.',
                'Assign a score between 0 and 100 for each category based on the analysis.',
                'Provide an overall score as the average of the three categories.',
                'Include a brief summary explaining each score.',
                'Ensure the response follows the JSON format shown below:',
                '',
                '{',
                '  "documentation_score": 85.0,',
                '  "functionality_score": 90.0,',
                '  "style_score": 80.0,',
                '  "overall_score": 85.0,',
                '  "summary": "The codebase has excellent documentation and functionality but could improve on coding style consistency."',
                '}',
                '',
                'Replace the example values with actual scores and summary based on your analysis.',
            ]),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Multi-Pass Analysis Order
    |--------------------------------------------------------------------------
    | Defines the sequence in which passes run. 'doc_generation' -> 'functional_analysis'
    | -> 'style_convention' -> 'consolidation_pass' -> 'scoring_pass'.
    | This is where you can add or remove steps in the analysis pipeline.
    */
    'operations' => [
        'multi_pass_analysis' => [
        'pass_order' => [
            'doc_generation',
            'functional_analysis',
            'style_convention',
            'consolidation_pass',
            'scoring_pass',
        ],
    ],
];
