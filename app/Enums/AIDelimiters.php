<?php

namespace App\Enums;

/**
 * Enum AiDelimiters
 *
 * Defines delimiter constants for AI prompt structuring.
 */
enum AIDelimiters: string
{
    case GUIDELINES_START = '<<<<<<< GUIDELINES';
    case GUIDELINES_END = '>>>>>>>';

    case RESPONSE_FORMAT_START = '<<<<<<< RESPONSE FORMAT';
    case RESPONSE_FORMAT_END = '>>>>>>>';

    case EXAMPLE_START = '<<<<<<< EXAMPLE';
    case EXAMPLE_END = '>>>>>>>';
}
