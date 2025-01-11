<?php

return [
    'tools' => [
        'PHPStan' => [
            'enabled' => env('STATIC_ANALYSIS_PHPSTAN_ENABLED', false),
            'command' => env('STATIC_ANALYSIS_PHPSTAN_COMMAND', 'vendor/bin/phpstan'),
            'options' => explode(' ', env('STATIC_ANALYSIS_PHPSTAN_OPTIONS', 'analyse --no-progress --error-format=json')),
            'output_format' => 'json',
            'language' => 'php',
        ],
        'PHP_CodeSniffer' => [
            'enabled' => env('STATIC_ANALYSIS_PHP_CODESNIFFER_ENABLED', false),
            'command' => env('STATIC_ANALYSIS_PHP_CODESNIFFER_COMMAND', 'vendor/bin/phpcs'),
            'options' => explode(' ', env('STATIC_ANALYSIS_PHP_CODESNIFFER_OPTIONS', '--report=json')),
            'output_format' => 'json',
            'language' => 'php',
        ],
        'Psalm' => [
            'enabled' => env('STATIC_ANALYSIS_PSLAM_ENABLED', false),
            'command' => env('STATIC_ANALYSIS_PSLAM_COMMAND', 'vendor/bin/psalm'),
            'options' => explode(' ', env('STATIC_ANALYSIS_PSLAM_OPTIONS', '--output-format=json')),
            'output_format' => 'json',
            'language' => 'php',
        ],
        'Rector' => [
            'enabled' => env('STATIC_ANALYSIS_RECTOR_ENABLED', false),
            'command' => env('STATIC_ANALYSIS_RECTOR_COMMAND', 'vendor/bin/rector'),
            'options' => explode(' ', env('STATIC_ANALYSIS_RECTOR_OPTIONS', 'process')),
            'output_format' => 'json',
            'language' => 'php',
        ],
        'ESLint' => [
            'enabled' => env('STATIC_ANALYSIS_ESLINT_ENABLED', false),
            'command' => env('STATIC_ANALYSIS_ESLINT_COMMAND', 'npx eslint'),
            'options' => explode(' ', env('STATIC_ANALYSIS_ESLINT_OPTIONS', '--format=json')),
            'output_format' => 'json',
            'language' => 'javascript',
        ],
        'Prettier' => [
            'enabled' => env('STATIC_ANALYSIS_PRETTIER_ENABLED', false),
            'command' => env('STATIC_ANALYSIS_PRETTIER_COMMAND', 'npx prettier'),
            'options' => explode(' ', env('STATIC_ANALYSIS_PRETTIER_OPTIONS', '--check')),
            'output_format' => 'json',
            'language' => 'javascript',
        ],
        'Pylint' => [
            'enabled' => env('STATIC_ANALYSIS_PYLINT_ENABLED', false),
            'command' => env('STATIC_ANALYSIS_PYLINT_COMMAND', 'pylint'),
            'options' => explode(' ', env('STATIC_ANALYSIS_PYLINT_OPTIONS', '--output-format=json')),
            'output_format' => 'json',
            'language' => 'python',
        ],
        'GoLint' => [
            'enabled' => env('STATIC_ANALYSIS_GOLINT_ENABLED', false),
            'command' => env('STATIC_ANALYSIS_GOLINT_COMMAND', 'golint'),
            'options' => explode(' ', env('STATIC_ANALYSIS_GOLINT_OPTIONS', './...')),
            'output_format' => 'json',
            'language' => 'go',
        ],
        'ElixirFormatter' => [
            'enabled' => env('STATIC_ANALYSIS_ELIXIR_ENABLED', false),
            'command' => env('STATIC_ANALYSIS_ELIXIR_COMMAND', 'mix format --check-formatted'),
            'options' => [],
            'output_format' => 'text',
            'language' => 'elixir',
        ],
    ],

    'multi_pass_analysis' => [
        'enabled' => env('MULTI_PASS_ANALYSIS_ENABLED', false),
        'passes' => [
            'initial' => [
                'description' => 'Initial static analysis pass',
                'tools' => ['PHPStan', 'Psalm'],
            ],
            'refinement' => [
                'description' => 'Refinement pass after initial analysis',
                'tools' => ['Rector'],
            ],
            // Add more passes as needed
        ],
    ],
];
