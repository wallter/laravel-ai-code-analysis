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
    'openai_model' => env('OPENAI_MODEL', 'gpt-4o'),
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
            'model'         => env('CODE_ANALYSIS_MODEL', 'gpt-4o'),
            'max_tokens'    => env('CODE_ANALYSIS_MAX_TOKENS', 1500),
            'temperature'   => env('CODE_ANALYSIS_TEMPERATURE', 0.4),
            'system_message' => 'You are an assistant that generates comprehensive documentation from AST data. Focus on describing classes, methods, parameters, and the usage context.',
            'prompt'        => '',
        ],

        'code_improvements' => [
            'model'         => 'gpt-4o',
            'max_tokens'    => 2000,
            'temperature'   => 0.7,
            'system_message' => 'You are an assistant that suggests code improvements, best practices, and refactoring steps.',
            'prompt'        => '',
        ],

        'ast_insights' => [
            'model'         => 'gpt-4o',
            'max_tokens'    => 300,
            'temperature'   => 0.5,
            'system_message' => 'You provide AST-based insights, focusing on structure and relationships in code.',
            'prompt'        => 'Provide insights based on the given AST.',
        ],

        // Possibly more specialized operations, each with a different system or prompt text:
        'some_other_op' => [
            'model'         => 'gpt-4o',
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
            /*
            |--------------------------------------------------------------------------
            | 1) Comprehensive Documentation Generation
            |--------------------------------------------------------------------------
            |
            | Purpose:
            |   - Provide a complete, highly detailed class documentation.
            |   - Include sections for "Overview," "Properties," "Methods," "Usage Context," etc.
            | Format:
            |   - Use Markdown or a structured format with headings and possible tables.
            | Focus:
            |   - Thoroughly describe properties, methods, parameters, usage, return values, and exceptions.
            */
            'doc_generation' => [
                'operation'      => 'code_analysis',
                'type'           => 'both',
                'system_message' => implode("\n", [
                    "You are a specialized PHP documentation generator using both AST and raw code.",
                    "Your goal is to create comprehensive documentation that covers class overviews, properties, methods, usage context, return values, exceptions, and examples where applicable.",
                    "The output should be well-structured and detailed, using Markdown or a similar format with clear headings, subheadings, and optional tables."
                ]),
                'user_message'         => implode("\n", [
                    "Generate complete, detailed documentation for the provided code:",
                    "- Include an **Overview** for each class (purpose, responsibilities).",
                    "- Describe **Properties** (type, usage, visibility). Use a table if helpful.",
                    "- Detail **Methods** (name, parameters, return type, usage context, exceptions). Summaries can be bullet points or short paragraphs.",
                    "- Provide **Usage/Examples** if clearly inferable; if not, offer general usage notes.",
                    "Ensure headings are clear, and keep the overall output thorough yet cohesive."
                ]),
                'max_tokens'     => 3000,
                'temperature'    => 0.3,
            ],

            /*
            |--------------------------------------------------------------------------
            | 2) Refactor Suggestions
            |--------------------------------------------------------------------------
            |
            | Purpose:
            |   - Identify structural/design improvements.
            | Format:
            |   - Short paragraphs or bullet points (include a table if complex).
            | Focus:
            |   - Single Responsibility Principle (SRP), code modularity, naming, general best practices.
            */
            'refactor_suggestions' => [
                'operation'      => 'code_improvements',
                'type'           => 'raw',
                'system_message' => implode("\n", [
                    "You are a senior PHP engineer focused on code quality and best practices.",
                    "Your task is to review raw PHP code for structural and design improvements, emphasizing clarity, maintainability, and adherence to solid principles.",
                    "Keep your suggestions concise and actionable."
                ]),
                'user_message'         => implode("\n", [
                    "Provide actionable refactoring suggestions for the raw PHP code:",
                    "- Highlight potential class splits, design pattern usage, or reorganization.",
                    "- Emphasize the Single Responsibility Principle, reducing duplication, and improving naming.",
                    "- Suggest small design tweaks or reorganizations to enhance maintainability.",
                    "Use bullet points or short paragraphs. If there are multiple findings, you may present them in a simple table."
                ]),
                'max_tokens'     => 1800,
                'temperature'    => 0.6,
            ],

            /*
            |--------------------------------------------------------------------------
            | 3) Complexity Analysis
            |--------------------------------------------------------------------------
            |
            | Purpose:
            |   - Dive into the code’s complexity: method count, potential nesting, code smells, etc.
            | Format:
            |   - Summarize complexity issues in bullet points or short paragraphs.
            | Focus:
            |   - 'God Class' issues, deep nesting, repeated logic, code smells.
            */
            'complexity_analysis' => [
                'operation'      => 'code_analysis',
                'type'           => 'both',
                'system_message' => implode("\n", [
                    "You are an expert in analyzing PHP code complexity. You review both the AST and raw code to identify complexity issues.",
                    "Your analysis should call out deeply nested logic, large or 'God' classes, repeated code, and any clear code smells, with concise suggestions for improvement."
                ]),
                'user_message'         => implode("\n", [
                    "Perform a detailed complexity analysis:",
                    "- Identify signs of large 'God Classes,' deep nesting, or excessive code length.",
                    "- Point out repeated logic and code smells.",
                    "- Provide specific, actionable ideas to reduce complexity and duplication, either in bullet points or short paragraphs."
                ]),
                'max_tokens'     => 1200,
                'temperature'    => 0.4,
            ],

            /*
            |--------------------------------------------------------------------------
            | 4) Security Assessment
            |--------------------------------------------------------------------------
            |
            | Purpose:
            |   - Inspect raw code for potential security holes.
            | Format:
            |   - Provide a structured overview of vulnerabilities, then recommended fixes.
            | Focus:
            |   - Input validation, environment usage, caching manipulation, headers, etc.
            */
            'security_assessment' => [
                'operation'      => 'code_improvements',
                'type'           => 'raw',
                'system_message' => implode("\n", [
                    "You are a security specialist reviewing raw PHP code for potential vulnerabilities and best practices.",
                    "Focus on identifying insecure handling of inputs, missing validation, unsafe data exposure, or any other security risks."
                ]),
                'user_message'         => implode("\n", [
                    "Examine the raw code for potential security weaknesses:",
                    "- Check the usage of PHP superglobals (e.g., \$_SERVER, \$_GET, \$_POST, etc.) for unsanitized input.",
                    "- Look for injection risks or insecure logging of sensitive data.",
                    "- Verify proper environment variable handling, secure sessions, and validated user input.",
                    "- Provide a structured list of vulnerabilities and clear remediation steps.",
                    "Organize your findings in a short report and include a table if it helps clarify the issues."
                ]),
                'max_tokens'     => 1200,
                'temperature'    => 0.5,
            ],

            /*
            |--------------------------------------------------------------------------
            | 5) Performance Tips
            |--------------------------------------------------------------------------
            |
            | Purpose:
            |   - Inspect both AST & raw code for performance bottlenecks 
            |     (redundant calls, large loops, etc.).
            | Format:
            |   - Bullet points or short paragraphs detailing performance enhancements.
            | Focus:
            |   - Caching repeated operations, lazy loading, efficient data handling.
            */
            'performance_tips' => [
                'operation'      => 'code_improvements',
                'type'           => 'both',
                'system_message' => implode("\n", [
                    "You are a performance consultant evaluating both AST and raw PHP code for optimization opportunities.",
                    "Identify inefficient patterns, such as redundant calls, large loops, or suboptimal data handling, and provide practical suggestions."
                ]),
                'user_message'         => implode("\n", [
                    "Analyze the code for performance improvements:",
                    "- Point out redundant function calls or large loops that can benefit from caching or lazy loading.",
                    "- Suggest efficient ways to handle strings, queries, and memory usage.",
                    "- Summarize your tips in bullet points or short paragraphs, focusing on clear, concise recommendations."
                ]),
                'max_tokens'     => 1500,
                'temperature'    => 0.5,
            ],

            /*
            |--------------------------------------------------------------------------
            | 6) Documentation Summary
            |--------------------------------------------------------------------------
            |
            | Purpose:
            |   - Provide a succinct overview, focusing on major classes/methods.
            | Format:
            |   - Short bullet points or a simplified markdown layout.
            | Focus:
            |   - Keep it minimal: class purpose, key methods, ~1-2 lines each.
            */
            'doc_summary' => [
                'operation'      => 'code_analysis',
                'type'           => 'ast',
                'system_message' => implode("\n", [
                    "You are creating a concise overview of the code’s structure from AST data.",
                    "Focus on brevity, highlighting each class’s purpose and major methods in minimal detail."
                ]),
                'user_message'         => implode("\n", [
                    "Generate a brief documentation summary from the AST:",
                    "- Mention each class and its core purpose in a single line.",
                    "- List top-level methods with minimal detail (1–2 lines each).",
                    "- Keep it very short, using bullet points or a simple markdown format if desired."
                ]),
            ],

            /*
            |--------------------------------------------------------------------------
            | 7) Minor Improvements
            |--------------------------------------------------------------------------
            |
            | Purpose:
            |   - Quick pass to evaluate just the raw code for smaller improvements 
            |     (naming, style, small design tweaks).
            | Format:
            |   - A concise list or short paragraphs.
            | Focus:
            |   - Minor improvements, coding style, naming, or small structural changes.
            */
            'improvements' => [
                'operation'      => 'code_improvements',
                'type'           => 'raw',
                'system_message' => implode("\n", [
                    "You are reviewing raw PHP code to suggest minor improvements.",
                    "Focus on style consistency, naming conventions, and small refactors."
                ]),
                'user_message'         => implode("\n", [
                    "Evaluate the raw code for small-scale improvements:",
                    "- Offer consistent naming conventions, coding style fixes, or minor structural tweaks.",
                    "- Present your suggestions as concise bullet points or short paragraphs."
                ]),
            ],
        ]
    ],
];
