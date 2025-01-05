<?php

return [

    'openai_api_key' => env('OPENAI_API_KEY'),

    'default' => [
        'model' => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
        'max_tokens' => env('AI_DEFAULT_MAX_TOKENS', 500),
        'temperature' => env('AI_DEFAULT_TEMPERATURE', 0.5),
        'system_message' => 'You are a helpful AI assistant.',
    ],

    'passes' => [
        'doc_generation' => [
            'operation_identifier' => 'doc_generation',
            'model' => env('AI_DOC_GENERATION_MODEL', 'gpt-4o-mini'),
            'max_tokens' => env('AI_DOC_GENERATION_MAX_TOKENS', 1200),
            'temperature' => env('AI_DOC_GENERATION_TEMPERATURE', 0.25),
            'type' => 'both',
            'system_message' => 'You generate concise PHP documentation from code and AST to complement phpdoc documentation.',
            'prompt' => implode("\n", [
                'Create short but clear documentation from the AST data and raw code:',
                '- Summarize the purpose, methods, parameters, and usage context.',
                '- Avoid documenting __construct, getter, setter, and similar functions.',
                '- Exclude comment code blocks from the documentation.',
                'Mention custom annotations, such as @url.',
                'Limit the documentation to approximately 200 words.',
            ]),
        ],

        'functional_analysis' => [
            'operation_identifier' => 'functional_analysis',
            'model' => env('AI_FUNCTIONAL_ANALYSIS_MODEL', 'gpt-4o-mini'),
            'max_tokens' => env('AI_FUNCTIONAL_ANALYSIS_MAX_TOKENS', 2500),
            'temperature' => env('AI_FUNCTIONAL_ANALYSIS_TEMPERATURE', 0.65),
            'type' => 'both',
            'system_message' => 'You perform thorough functional analysis based on AST data and raw code.',
            'prompt' => implode("\n", [
                'Evaluate the functionality, identify edge cases, and detect performance bottlenecks.',
                'Suggest improvements to enhance reliability and testability.',
            ]),
        ],

        'style_convention' => [
            'operation_identifier' => 'style_convention',
            'model' => env('AI_STYLE_CONVENTION_MODEL', 'gpt-4o-mini'),
            'max_tokens' => env('AI_STYLE_CONVENTION_MAX_TOKENS', 1800),
            'temperature' => env('AI_STYLE_CONVENTION_TEMPERATURE', 0.28),
            'type' => 'raw',
            'system_message' => 'You review code style for PSR compliance.',
            'prompt' => implode("\n", [
                'Check the code for formatting, naming conventions, and documentation clarity according to coding standards.',
                'Suggest concise improvements to ensure consistency.',
            ]),
        ],

        'consolidation_pass' => [
            'operation_identifier' => 'consolidation_pass',
            'model' => env('AI_CONSOLIDATION_PASS_MODEL', 'gpt-4o-mini'),
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

        'scoring_pass' => [
            'operation_identifier' => 'scoring',
            'model' => env('AI_SCORING_PASS_MODEL', 'gpt-4o-mini'),
            'max_tokens' => env('AI_SCORING_PASS_MAX_TOKENS', 500),
            'temperature' => env('AI_SCORING_PASS_TEMPERATURE', 0.3),
            'type' => 'previous',
            'system_message' => 'You analyze previous AI analysis results and assign scores.',
            'prompt' => implode("\n", [
                'Analyze the results of previous AI analysis passes and assign meaningful scores.',
                'Provide a summary and a final score based on documentation, functionality, and style.',
            ]),
        ],
    ],

    'multi_pass_analysis' => [
        'pass_order' => [
            'doc_generation',
            'functional_analysis',
            'style_convention',
            'consolidation_pass',
            'scoring_pass',
        ],
    ],
    ],
];
