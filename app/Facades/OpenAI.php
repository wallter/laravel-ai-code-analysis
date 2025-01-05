<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * OpenAI Facade
 *
 * @see \App\Services\AI\OpenAIService
 */
class OpenAI extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'openai';
    }
}
