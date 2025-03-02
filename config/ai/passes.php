<?php

use App\Enums\OperationIdentifier;
use App\Enums\PassType;

/*
|--------------------------------------------------------------------------
| AI Passes Configuration
|--------------------------------------------------------------------------
| Each key defines a specific analysis task or pass, with corresponding
| model settings, instructions, and response formatting rules.
*/
return [
    // 1. Documentation Generation Pass
    'doc_generation' => [
        'operation_identifier' => OperationIdentifier::DOC_GENERATION->value,
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => env('AI_DOC_GENERATION_MAX_TOKENS', 1000),
        'temperature' => env('AI_DOC_GENERATION_TEMPERATURE', 0.25),
        'type' => PassType::BOTH->value,
        'system_message' => 'You generate concise PHP documentation from code and AST to complement phpdoc.',
        'prompt_sections' => [
            'base_prompt' => 'Analyze the following code:',
            'guidelines' => [
                '- Create short, clear documentation from AST data and raw code.',
                '- Summarize the purpose, methods, parameters, and usage context briefly.',
                '- Omit routine methods like __construct, getters, and setters.',
                '- Mention any custom annotations.',
                '- Keep documentation under ~200 words.',
            ],
            'response_format' => 'Provide concise, human-readable documentation in short paragraphs or bullet points.',
        ],
    ],

    // 2. Functional Analysis Pass
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
                '- Suggest improvements to enhance reliability, testability, and security.',
            ],
            'response_format' => 'Provide concise, structured insights with actionable recommendations.',
        ],
    ],

    // 3. Style & Convention Pass
    'style_convention' => [
        'operation_identifier' => OperationIdentifier::STYLE_CONVENTION->value,
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => env('AI_STYLE_CONVENTION_MAX_TOKENS', 1800),
        'temperature' => env('AI_STYLE_CONVENTION_TEMPERATURE', 0.28),
        'type' => PassType::RAW->value,
        'system_message' => 'You review code style for compliance with accepted coding standards.',
        'prompt_sections' => [
            'base_prompt' => 'Analyze the following code:',
            'guidelines' => [
                '- Check formatting, naming conventions, and documentation clarity.',
                '- Suggest concise improvements to ensure consistent style.',
            ],
            'response_format' => 'Return short bullet points highlighting style issues and suggested fixes.',
        ],
    ],

    // 4. Static Analysis Pass (Runs external tools, not an AI pass)
    'static_analysis' => [
        'operation_identifier' => OperationIdentifier::STATIC_ANALYSIS->value,
        'model' => null,
        'max_tokens' => null,
        'temperature' => null,
        'type' => PassType::RAW->value,
        'system_message' => null,
        'prompt_sections' => [],
    ],

    // 5. Consolidation Pass
    'consolidation_pass' => [
        'operation_identifier' => OperationIdentifier::CONSOLIDATION_PASS->value,
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => env('AI_CONSOLIDATION_PASS_MAX_TOKENS', 1800),
        'temperature' => env('AI_CONSOLIDATION_PASS_TEMPERATURE', 0.4),
        'type' => PassType::PREVIOUS->value,
        'system_message' => 'You consolidate prior AI analysis results into a final summary.',
        'prompt_sections' => [
            'base_prompt' => 'Consolidate the following analysis results:',
            'guidelines' => [
                '- Combine outputs from all previous passes into a cohesive summary.',
                '- Highlight key findings and provide overall recommendations.',
            ],
            'response_format' => 'Provide a unified summary with actionable recommendations. Use short paragraphs or tables.',
        ],
    ],

    // 6. Scoring Pass
    'scoring_pass' => [
        'operation_identifier' => OperationIdentifier::SCORING_PASS->value,
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => env('AI_SCORING_PASS_MAX_TOKENS', 500),
        'temperature' => env('AI_SCORING_PASS_TEMPERATURE', 0.3),
        'type' => PassType::PREVIOUS->value,
        'system_message' => 'You analyze previous AI analysis results and assign scores.',
        'prompt_sections' => [
            'base_prompt' => 'Evaluate the following analysis results:',
            'guidelines' => [
                '- Score documentation, functionality, and style from 0 to 100.',
                '- Calculate overall_score as the average of those three.',
            ],
            'example' => [
                '{',
                '  "documentation_score": 85.0,',
                '  "functionality_score": 90.0,',
                '  "style_score": 80.0,',
                '  "overall_score": 85.0,',
                '  "summary": "Short explanatory text."',
                '}',
            ],
            'response_format' => 'Return a strict JSON object with the required fields.',
        ],
    ],

    // 7. Laravel Migration Analysis Pass
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
                '- Identify common migration best practices.',
                '- Suggest improvements to enhance migration patterns.',
                '- Keep explanations short and actionable.',
            ],
            'response_format' => 'Return a list or table of improvements related to Laravel migration patterns.',
        ],
    ],

    // 8. Laravel Migration Scoring Pass
    'laravel_migration_scoring' => [
        'operation_identifier' => OperationIdentifier::LARAVEL_MIGRATION_SCORING->value,
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => env('AI_LARAVEL_MIGRATION_SCORING_MAX_TOKENS', 500),
        'temperature' => env('AI_LARAVEL_MIGRATION_SCORING_TEMPERATURE', 0.3),
        'type' => PassType::PREVIOUS->value,
        'system_message' => 'You assign a migration_score (0–100) for Laravel migration compliance.',
        'prompt_sections' => [
            'base_prompt' => 'Evaluate the following Laravel migration analysis:',
            'guidelines' => [
                '- Rate migration quality on a 0–100 scale.',
                '- Provide a short rationale for the score.',
            ],
            'example' => [
                '{',
                '  "migration_score": 85.0,',
                '  "summary": "Short rationale here."',
                '}',
            ],
            'response_format' => 'Return a JSON object with "migration_score" and "summary".',
        ],
    ],

    // 9. Security Analysis Pass (New Complementary Pass)
    'security_analysis' => [
        'operation_identifier' => 'SECURITY_ANALYSIS',
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => env('AI_SECURITY_ANALYSIS_MAX_TOKENS', 1500),
        'temperature' => env('AI_SECURITY_ANALYSIS_TEMPERATURE', 0.35),
        'type' => PassType::BOTH->value,
        'system_message' => 'You analyze code for security vulnerabilities and best practices.',
        'prompt_sections' => [
            'base_prompt' => 'Analyze the following code for security considerations:',
            'guidelines' => [
                '- Identify potential security vulnerabilities (e.g., SQL injection, XSS).',
                '- Recommend secure coding patterns or frameworks for mitigation.',
                '- Keep answers concise and actionable.',
            ],
            'response_format' => 'Return bullet points with security concerns and recommended fixes.',
        ],
    ],

    // 10. Performance Analysis Pass (New Complementary Pass)
    'performance_analysis' => [
        'operation_identifier' => 'PERFORMANCE_ANALYSIS',
        'model' => 'gpt-3.5-turbo',
        'max_tokens' => env('AI_PERFORMANCE_ANALYSIS_MAX_TOKENS', 1500),
        'temperature' => env('AI_PERFORMANCE_ANALYSIS_TEMPERATURE', 0.35),
        'type' => PassType::BOTH->value,
        'system_message' => 'You analyze code for performance and suggest optimizations.',
        'prompt_sections' => [
            'base_prompt' => 'Analyze the following code for performance considerations:',
            'guidelines' => [
                '- Identify possible inefficiencies (e.g., excessive loops, large data structures).',
                '- Suggest optimizations or design patterns for better performance.',
                '- Keep explanations focused and concise.',
            ],
            'response_format' => 'Return bullet points detailing performance issues and recommended optimizations.',
        ],
    ],
];
