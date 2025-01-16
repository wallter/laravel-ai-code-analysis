<?php

use App\Enums\OperationIdentifier;
use App\Enums\PassType;

/**
 * AI Analysis Configuration
 *
 * This configuration file defines multiple AI analysis passes for a PHP application.
 * Each pass serves a specific purpose, such as documentation, functionality checks,
 * style checks, and more. Environment variables are used for flexibility in modifying
 * model parameters without changing the code directly.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | OpenAI API Key
    |--------------------------------------------------------------------------
    | This key is required to authenticate requests to the OpenAI API.
    | A fallback is set to an empty string if not defined.
    */
    'openai_api_key' => env('OPENAI_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | Default Model Configuration
    |--------------------------------------------------------------------------
    | Applied when a pass does not explicitly specify a model.
    | Fallbacks help avoid errors if environment variables are missing.
    */
    'default' => [
        'model_name' => env('AI_DEFAULT_MODEL', 'gpt-3.5-turbo'),
        'max_tokens' => env('AI_DEFAULT_MAX_TOKENS', 500),
        'temperature' => env('AI_DEFAULT_TEMPERATURE', 0.5),
        'supports_system_message' => false,
    ],
];
