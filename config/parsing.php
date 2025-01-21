<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Files or Directories to Parse
    |--------------------------------------------------------------------------
    |
    | An array of paths (absolute or relative) that should be scanned when
    | no explicit arguments are passed to the parse commands. For example:
    | ('folders' OR 'files') => [
    |     base_path('app/Services'), // a directory
    |     base_path('app/Helpers.php'), // single file
    | ],
    |
    */
    'folders' => [
        // Directories to parse recursively
        // base_path('resources/access-app'),
        base_path('app/'),
    ],

    'files' => [
        // Individual files to parse
        // base_path('app/Models/CodeAnalysis.php'),
        // base_path('app/Services/AI/CodeAnalysisService.php'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Ignore Patterns
    |--------------------------------------------------------------------------
    |
    | Patterns for paths you want excluded (equivalent to your .gitignore or
    | .aiderignore logic). You can adapt or remove if you’re managing this
    | differently.
    |
    */
    'ignore' => [
        '.git',
        'vendor',
        'node_modules',
        'storage',
        'bootstrap/cache',
        // etc.
    ],
];
