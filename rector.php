<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->paths([
        __DIR__ . '/app',
        __DIR__ . '/routes',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/resources',
        __DIR__ . '/tests',
        __DIR__ . '/public',
    ])
    ->skip([
        __DIR__ . '/storage',
        __DIR__ . '/bootstrap/cache',
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
        __DIR__ . '/public/vendor',
        __DIR__ . '/public/js',
        __DIR__ . '/public/css',
        __DIR__ . '/resources/js',
        __DIR__ . '/resources/css',
        __DIR__ . '/tests/Fixtures', // Skipping fixture files
        __DIR__ . '/tests/Temporary', // Skipping temporary test files
    ])
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_110, // Adjust for your Laravel version
        LaravelSetList::LARAVEL_CODE_QUALITY,    // Code quality improvements
        LaravelSetList::LARAVEL_COLLECTION,      // Laravel Collection-related rules
        // Additional sets can be added here
    ]);
