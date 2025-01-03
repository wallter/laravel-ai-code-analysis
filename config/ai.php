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
        'multi_pass_analysis' => [
            'multi_pass_analysis' => [

                /*
                |--------------------------------------------------------------------------
                | 1) Comprehensive Documentation Generation
                |--------------------------------------------------------------------------
                |
                | Purpose:
                |   - Provide an all-encompassing, highly detailed class documentation.
                |   - Include sections for "Class Overview," "Properties," "Methods," 
                |     "Usage Context," etc.
                | Format:
                |   - Use Markdown or a structured format with headings for each section.
                | Focus:
                |   - Emulate the style you observed: thoroughly describe properties, 
                |     methods, parameters, usage, potential return values, and exceptions.
                */
                'doc_generation' => [
                    'operation'   => 'code_analysis',
                    'type'        => 'both',
                    'max_tokens'  => 1000,
                    'temperature' => 0.3,
                    'prompt'      => implode("\n", [
                        "You are a concise documentation generator for a PHP codebase.",
                        "Create a short but clear doc from the AST data + raw code:",
                        "- Summarize only essential info: class or trait's purpose, key methods, parameters, usage context.",
                        "- Mention custom annotations (@url, etc.), but keep it under ~200 words.",
                        "Be succinct and well-structured."
                    ]),
                ],

            ]
        ]
    ],
];


// /*
// |--------------------------------------------------------------------------
// | 2) Refactor Suggestions
// |--------------------------------------------------------------------------
// |
// | Purpose:
// |   - Identify structural/design improvements.
// | Format:
// |   - Short paragraphs or bullet points.
// | Focus:
// |   - Single Responsibility Principle (SRP), code modularity, naming, 
// |     general best practices.
// */
// 'refactor_suggestions' => [
//     'operation'   => 'code_improvements',
//     'type'        => 'raw',
//     'prompt'      => implode("\n", [
//         "You are a senior PHP engineer analyzing the raw code. Provide actionable refactoring suggestions:",
//         "- Focus on structural changes (class splitting, design patterns)",
//         "- Emphasize SOLID principles, especially SRP",
//         "- Discuss how to reduce duplication, enhance naming clarity, and improve maintainability",
//         "Write your suggestions in a concise list or short paragraphs."
//     ]),
//     'max_tokens'  => 1800,
//     'temperature' => 0.6,
// ],

// /*
// |--------------------------------------------------------------------------
// | 3) Complexity Analysis
// |--------------------------------------------------------------------------
// |
// | Purpose:
// |   - Dive into the code’s complexity: method count, potential nesting, 
// |     code smells, etc.
// | Format:
// |   - Summarize complexity issues, provide bullet points or short paragraphs.
// | Focus:
// |   - Single 'God Class' issues, deeply nested logic, code smell identification.
// */
// 'complexity_analysis' => [
//     'operation'   => 'code_analysis',
//     'type'        => 'both',
//     'prompt'      => implode("\n", [
//         "Perform a deep complexity analysis of the code, referencing both AST structure and raw code:",
//         "- Note the total number of methods, lines, or potential 'God Class' traits",
//         "- Identify code smells (e.g., repeated logic, overly large methods)",
//         "- Provide specific suggestions for reducing complexity or code duplication."
//     ]),
//     'max_tokens'  => 1200,
//     'temperature' => 0.4,
// ],

// /*
// |--------------------------------------------------------------------------
// | 4) Security Assessment
// |--------------------------------------------------------------------------
// |
// | Purpose:
// |   - Inspect raw code for potential security holes.
// | Format:
// |   - Provide a structured overview of vulnerabilities, then recommended fixes.
// | Focus:
// |   - Input validation, environment usage, caching manipulation, 
// |     headers, etc.
// */
// 'security_assessment' => [
//     'operation'   => 'code_improvements',
//     'type'        => 'raw',
//     'prompt'      => implode("\n", [
//         "You're a security specialist reviewing this raw PHP code. Focus on potential vulnerabilities:",
//         "- Look for insecure handling of user input, potential injection, or direct usage of \$_SERVER variables",
//         "- Identify any questionable logging or environment variable usage",
//         "- Provide explicit remediation steps (e.g., sanitization, prepared statements).",
//         "Organize your findings in short paragraphs with subheadings if needed."
//     ]),
//     'max_tokens'  => 1200,
//     'temperature' => 0.5,
// ],

// /*
// |--------------------------------------------------------------------------
// | 5) Performance Tips
// |--------------------------------------------------------------------------
// |
// | Purpose:
// |   - Inspect both AST & raw code for performance bottlenecks 
// |     (redundant calls, large loops, etc.).
// | Format:
// |   - Bullet points or short paragraphs detailing performance enhancements.
// | Focus:
// |   - Caching repeated operations, lazy loading, efficient data handling.
// */
// 'performance_tips' => [
//     'operation'   => 'code_improvements',
//     'type'        => 'both',
//     'prompt'      => implode("\n", [
//         "Act as a performance consultant. Evaluate the code for optimization opportunities:",
//         "- Identify places where caching repeated function calls or data retrieval might help",
//         "- Suggest efficient ways to handle loops, string operations, or memory usage",
//         "- Provide a concise list of performance tips."
//     ]),
//     'max_tokens'  => 1500,
//     'temperature' => 0.5,
// ],

// /*
// |--------------------------------------------------------------------------
// | 6) Documentation Summary
// |--------------------------------------------------------------------------
// |
// | Purpose:
// |   - Provide a succinct overview, focusing on major classes/methods.
// | Format:
// |   - Short bullet points or a simplified markdown layout.
// | Focus:
// |   - Keep it minimal: class purpose, key methods, ~1-2 lines each.
// */
// 'doc_summary' => [
//     'operation' => 'code_analysis',
//     'type'      => 'ast',
//     'prompt'    => implode("\n", [
//         "Generate a concise documentation summary from the AST. Keep it short and to the point:",
//         "- Mention each class and a brief purpose",
//         "- List top-level methods with minimal detail (1-2 lines each)",
//         "Use brief bullet points or a short markdown block. This is NOT comprehensive—just a quick reference."
//     ]),
// ],

// /*
// |--------------------------------------------------------------------------
// | 7) Minor Improvements
// |--------------------------------------------------------------------------
// |
// | Purpose:
// |   - Quick pass to evaluate just the raw code for smaller improvements 
// |     (naming, style, small design tweaks).
// | Format:
// |   - A concise list or short paragraphs.
// | Focus:
// |   - Minor improvements, coding style, naming, or small structural changes.
// */
// 'improvements' => [
//     'operation' => 'code_improvements',
//     'type'      => 'raw',
//     'prompt'    => implode("\n", [
//         "Review the raw PHP code for smaller-scale improvements:",
//         "- Suggest more consistent naming conventions, coding style, small refactors",
//         "- Provide concise bullet points or short paragraphs with each suggestion."
//     ]),
// ],