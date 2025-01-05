<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use RectorLaravel\Set\LaravelLevelSetList;
use RectorLaravel\Set\LaravelSetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/app',
        __DIR__ . '/routes',
        __DIR__ . '/config',
        __DIR__ . '/database',
        __DIR__ . '/resources',
        __DIR__ . '/tests',
        __DIR__ . '/public',
    ])
    ->withSkip([
        __DIR__ . '/storage',
        __DIR__ . '/bootstrap/cache',
        __DIR__ . '/vendor',
        __DIR__ . '/node_modules',
        __DIR__ . '/public/vendor',
        __DIR__ . '/public/js',
        __DIR__ . '/public/css',
        __DIR__ . '/resources/js',
        __DIR__ . '/resources/css',
        __DIR__ . '/tests/Fixtures',
        __DIR__ . '/tests/Temporary',
    ])
    ->withSets([
        LaravelLevelSetList::UP_TO_LARAVEL_110,
        LaravelSetList::LARAVEL_CODE_QUALITY,
        LaravelSetList::LARAVEL_COLLECTION,
    ]);
