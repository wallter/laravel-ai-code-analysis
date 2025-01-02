<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Files or Directories to Parse
    |--------------------------------------------------------------------------
    |
    | An array of paths (absolute or relative) that should be scanned when
    | no explicit arguments are passed to the parse commands. For example:
    | 'files' => [
    |     base_path('app/Services'), // a directory
    |     base_path('app/Helpers.php'), // single file
    | ],
    |
    */
    'folders' => [
        // Directories to parse recursively
        // base_path('app/Services'),
        '/Users/tylerwall/Documents/programing/unicity/Octopus/components/services/access-app/src/classes/v5/Controller/LoginTokens'
    ],

    'files' => [
        // Individual files to parse
        // base_path('app/Helpers.php'),
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
        // etc.
    ],
];
