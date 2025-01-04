<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_110, // Adjust for your Laravel version
        LaravelSetList::LARAVEL_CODE_QUALITY,    // Code quality improvements
        LaravelSetList::LARAVEL_COLLECTION,      // Laravel Collection-related rules
    ])
    ->paths([
        __DIR__ . '/app',
        __DIR__ . '/routes',
        __DIR__ . '/config',
        __DIR__ . '/database',
    ])
    ->skip([
        __DIR__ . '/storage',
        __DIR__ . '/bootstrap/cache',
    ]);