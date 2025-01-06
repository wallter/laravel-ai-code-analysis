<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services.
    | It provides a conventional location for this type of information.
    |
    */

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4'),
        'temperature' => env('OPENAI_TEMPERATURE', 0.2),
        'max_tokens' => env('OPENAI_MAX_TOKENS', 1024),
    ],

    'aider' => [
        'api_key' => env('AIDER_API_KEY', 'AIDER_INTEGRATION_NOT_COMPLETE_YET'),
        'endpoint' => env('AIDER_ENDPOINT', 'https://api.aider.com'),
    ],

    'parsing' => [
        'files' => [],
    ],
];
