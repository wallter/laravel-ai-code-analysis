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

];
