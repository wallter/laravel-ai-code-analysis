<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials and settings for the AI service
    | used to enhance documentation. Ensure that you set the necessary
    | environment variables in your .env file.
    |
    */

    'openai_api_key' => env('OPENAI_API_KEY'),

    'openai_model' => env('OPENAI_MODEL', 'text-davinci-003'),

    'max_tokens' => env('OPENAI_MAX_TOKENS', 150),

    'temperature' => env('OPENAI_TEMPERATURE', 0.7),

    'default_prompts' => [
        'endpoint_doc_generator' => env('ENDPOINT_DOC_GENERATOR_PROMPT', 'Your default prompt here...'),
        'doc_enhancer' => env('DOC_ENHANCER_PROMPT', 'Your default prompt here...'),
    ],
];
