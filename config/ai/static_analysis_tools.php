<?php

/*
|--------------------------------------------------------------------------
| Static Analysis Tools Configuration
|--------------------------------------------------------------------------
| Each tool (like PHPStan, PHP_CodeSniffer, and Psalm) has a command and
| set of options. The output format is used for parsing the tool's results.
*/

return [
    'PHPStan' => [
        'enabled' => true,
        'command' => 'vendor/bin/phpstan',
        'options' => ['analyse', '--no-progress', '--error-format=json'],
        'output_format' => 'json',
    ],
    'PHP_CodeSniffer' => [
        'enabled' => true,
        'command' => 'vendor/bin/phpcs',
        'options' => ['--report=json'],
        'output_format' => 'json',
    ],
    'Psalm' => [
        'enabled' => true,
        'command' => 'vendor/bin/psalm',
        'options' => ['--output-format=json'],
        'output_format' => 'json',
    ],
];