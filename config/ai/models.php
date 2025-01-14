<?php 

return [    
    'o1-mini' => [
        'model_name' => env('OPENAI_MODEL_O1_MINI', 'o1-mini'),
        'max_tokens' => env('OPENAI_MODEL_O1_MINI_MAX_TOKENS', 1500),
        'temperature' => env('OPENAI_MODEL_O1_MINI_TEMPERATURE', 0.3),
        'supports_system_message' => false,
        'token_limit_parameter' => 'max_completion_tokens',
    ],

    'gpt-4' => [
        'model_name' => env('OPENAI_MODEL_GPT4', 'gpt-4'),
        'max_tokens' => env('OPENAI_MODEL_GPT4_MAX_TOKENS', 2000),
        'temperature' => env('OPENAI_MODEL_GPT4_TEMPERATURE', 0.3),
        'supports_system_message' => true,
        'token_limit_parameter' => 'max_tokens',
    ],

    'gpt-3.5-turbo' => [
        'model_name' => env('OPENAI_MODEL_GPT35_TURBO', 'gpt-3.5-turbo'),
        'max_tokens' => env('OPENAI_MODEL_GPT35_TURBO_MAX_TOKENS', 1500),
        'temperature' => env('OPENAI_MODEL_GPT35_TURBO_TEMPERATURE', 0.4),
        'supports_system_message' => true,
        'token_limit_parameter' => 'max_tokens',
    ],

    'gpt-4o' => [
        'model_name' => env('OPENAI_MODEL_GPT4O', 'gpt-4o'),
        'max_tokens' => env('OPENAI_MODEL_GPT4O_MAX_TOKENS', 2500),
        'temperature' => env('OPENAI_MODEL_GPT4O_TEMPERATURE', 0.3),
        'supports_system_message' => true,
        'token_limit_parameter' => 'max_tokens',
    ],

    'gpt-40-mini' => [
        'model_name' => env('OPENAI_MODEL_GPT40_MINI', 'gpt-40-mini'),
        'max_tokens' => env('OPENAI_MODEL_GPT40_MINI_MAX_TOKENS', 1000),
        'temperature' => env('OPENAI_MODEL_GPT40_MINI_TEMPERATURE', 0.4),
        'supports_system_message' => false,
        'token_limit_parameter' => 'max_completion_tokens',
    ],
];