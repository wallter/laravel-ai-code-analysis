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

    'max_tokens' => env('OPENAI_MAX_TOKENS', 500), // Increase token limit if necessary

    'temperature' => env('OPENAI_TEMPERATURE', 0.5), // Adjust for desired creativity

    'default_prompts' => [
        'endpoint_doc_generator' => env('ENDPOINT_DOC_GENERATOR_PROMPT', 'Your default prompt here...'),
        'doc_enhancer' => env('DOC_ENHANCER_PROMPT', 'Your default prompt here...'),
    ],
    'operations' => [
        'doc_enhancer' => [
            'model' => env('DOC_ENHANCER_MODEL', 'text-davinci-003'),
            'max_tokens' => env('DOC_ENHANCER_MAX_TOKENS', 500),
            'temperature' => env('DOC_ENHANCER_TEMPERATURE', 0.5),
        ],
        'endpoint_doc_generator' => [
            'model' => env('ENDPOINT_DOC_GENERATOR_MODEL', 'text-davinci-003'),
            'max_tokens' => env('ENDPOINT_DOC_GENERATOR_MAX_TOKENS', 500),
            'temperature' => env('ENDPOINT_DOC_GENERATOR_TEMPERATURE', 0.5),
        ],
    ],
];
