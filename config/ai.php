<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Service Configuration
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials and settings for the AI service
    | used to enhance documentation and perform other AI-driven operations.
    | Ensure that you set the necessary environment variables in your .env file.
    |
    */

    /*
    |----------------------------------------------------------------------
    | OpenAI API Key
    |----------------------------------------------------------------------
    |
    | The API key used to authenticate requests to the OpenAI service.
    | Make sure to keep this key secure and do not expose it publicly.
    |
    */
    'openai_api_key' => env('OPENAI_API_KEY'),

    /*
    |----------------------------------------------------------------------
    | Default OpenAI Model
    |----------------------------------------------------------------------
    |
    | Specifies the default AI model to use for operations if not overridden
    | by specific operation configurations. Common models include
    | 'text-davinci-003', 'gpt-3.5-turbo', etc.
    |
    */
    'openai_model' => env('OPENAI_MODEL', 'text-davinci-003'),

    /*
    |----------------------------------------------------------------------
    | Default Maximum Tokens
    |----------------------------------------------------------------------
    |
    | Sets the default maximum number of tokens to generate in the AI response.
    | You can increase this limit if more extensive responses are required.
    |
    */
    'max_tokens' => env('OPENAI_MAX_TOKENS', 500),

    /*
    |----------------------------------------------------------------------
    | Default Temperature
    |----------------------------------------------------------------------
    |
    | Controls the creativity of the AI responses. Lower values make the
    | responses more deterministic, while higher values increase randomness.
    | Typical values range from 0.0 to 1.0.
    |
    */
    'temperature' => env('OPENAI_TEMPERATURE', 0.5),

    /*
    |----------------------------------------------------------------------
    | Default Prompts
    |----------------------------------------------------------------------
    |
    | Contains default prompt templates for various AI operations. These
    | prompts can be overridden or extended as needed for specific use cases.
    |
    */
    'default_prompts' => [
        /*
        |------------------------------------------------------------------
        | Endpoint Documentation Generator Prompt
        |------------------------------------------------------------------
        |
        | The default prompt used to generate documentation for API endpoints.
        | Customize this prompt to guide the AI in producing the desired output.
        |
        */
        'endpoint_doc_generator' => env('ENDPOINT_DOC_GENERATOR_PROMPT', 'Your default prompt here...'),

        /*
        |------------------------------------------------------------------
        | Documentation Enhancer Prompt
        |------------------------------------------------------------------
        |
        | The default prompt used to enhance existing documentation. This helps
        | in refining and improving the clarity and comprehensiveness of docs.
        |
        */
        'doc_enhancer' => env('DOC_ENHANCER_PROMPT', 'Your default prompt here...'),
    ],
    
    /*
    |----------------------------------------------------------------------
    | AI Operations Configuration
    |----------------------------------------------------------------------
    |
    | Defines specific configurations for each AI operation. Each operation
    | can have its own model, token limit, and temperature settings to tailor
    | the AI responses according to the task requirements.
    |
    */
    'operations' => [
        /*
        |------------------------------------------------------------------
        | Documentation Enhancer Operation
        |------------------------------------------------------------------
        |
        | Configuration for the documentation enhancement operation.
        | Adjust the model, max tokens, and temperature as needed to optimize
        | the quality and length of the enhanced documentation.
        |
        */
        'doc_enhancer' => [
            'model' => env('DOC_ENHANCER_MODEL', 'text-davinci-003'),
            'max_tokens' => env('DOC_ENHANCER_MAX_TOKENS', 500),
            'temperature' => env('DOC_ENHANCER_TEMPERATURE', 0.5),
        ],

        /*
        |------------------------------------------------------------------
        | Endpoint Documentation Generator Operation
        |------------------------------------------------------------------
        |
        | Configuration for generating documentation for API endpoints.
        | Customize the model and token limits to ensure detailed and
        | accurate documentation is produced.
        |
        */
        'endpoint_doc_generator' => [
            'model' => env('ENDPOINT_DOC_GENERATOR_MODEL', 'text-davinci-003'),
            'max_tokens' => env('ENDPOINT_DOC_GENERATOR_MAX_TOKENS', 500),
            'temperature' => env('ENDPOINT_DOC_GENERATOR_TEMPERATURE', 0.5),
        ],

        /*
        |------------------------------------------------------------------
        | Code Analysis Operation
        |------------------------------------------------------------------
        |
        | Configuration for the code analysis operation. This setup is used
        | to analyze code snippets, provide summaries, or offer insights
        | based on the provided code.
        |
        */
        'code_analysis' => [
            'model' => env('CODE_ANALYSIS_MODEL', 'text-davinci-003'),
            'max_tokens' => env('CODE_ANALYSIS_MAX_TOKENS', 500),
            'temperature' => env('CODE_ANALYSIS_TEMPERATURE', 0.5),
        ],
    ],
];
