<?php

return [

    'openai_api_key' => env('OPENAI_API_KEY'),

    'default' => [
        'model'          => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
        'max_tokens'     => env('AI_DEFAULT_MAX_TOKENS', 500),
        'temperature'    => env('AI_DEFAULT_TEMPERATURE', 0.5),
        'system_message' => 'You are a helpful AI assistant.',
    ],

    'operations' => [

        'code_analysis' => [
            'model'         => 'gpt-4o-mini',
            'max_tokens'    => 1500,
            'temperature'   => 0.4,
            'system_message' => 'You generate thorough analysis from AST data + raw code.',
            'prompt'        => '',
        ],

        'doc_generation' => [
            'model'         => 'gpt-4o-mini',
            'max_tokens'    => 1000,
            'temperature'   => 0.3,
            'system_message' => 'You generate concise PHP documentation from code and AST to compliment phpdoc documentation.',
            'prompt'        => implode("\n", [
                "Create short but clear docs from the AST data + raw code:",
                "- Summarize the purpose, methods, parameters, usage context.",
                "- Avoid documenting __construct/getter/setter/etc functions.",
                "Mention custom annotations, like @url.",
                "Limit to ~200 words max."
            ]),
        ],

        'style_review' => [
            'model'         => 'gpt-4o-mini',
            'max_tokens'    => 1000,
            'temperature'   => 0.3,
            'system_message' => 'Code style reviewer analyzing PSR compliance.',
            'prompt'        => implode("\n", [
                "Check formatting, naming, doc clarity against coding standards.",
                "Suggest short improvements for consistency."
            ]),
        ],

        'multi_pass_analysis' => [

            'pass_order' => [
                'doc_generation',
                'functional_analysis',
                'style_convention',
                'consolidation_pass',
            ],

            'doc_generation' => [
                'operation'   => 'doc_generation',
                'type'        => 'both',
                'max_tokens'  => 1000,
                'temperature' => 0.3,
            ],

            'functional_analysis' => [
                'operation'   => 'code_analysis',
                'type'        => 'both',
                'max_tokens'  => 2000,
                'temperature' => 0.7,
                'prompt'      => implode("\n", [
                    "Check functionality, edge cases, performance bottlenecks.",
                    "Suggest improvements for reliability & testability."
                ]),
            ],

            'style_convention' => [
                'operation'   => 'style_review',
                'type'        => 'raw',
                'max_tokens'  => 1500,
                'temperature' => 0.3,
            ],

            // The new pass type => "previous"
            'consolidation_pass' => [
                'operation'   => 'code_analysis',
                'type'        => 'previous', // merges prior AI outputs
                'max_tokens'  => 2500,
                'temperature' => 0.4,
                'prompt'      => implode("\n", [
                    "You consolidate prior analysis results into a final summary.",
                    "Use previous pass outputs + optional AST or raw code if needed.",
                    "Assign a rating or recommendation based on prior feedback."
                ]),
            ],
        ],
    ],
];
