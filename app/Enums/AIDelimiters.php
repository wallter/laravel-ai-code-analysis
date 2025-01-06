<?php

namespace App\Enums;

/**
 * Enum AIDelimiters
 *
 * Defines delimiter constants for AI prompt structuring.
 */
enum AIDelimiters: string
{
    case GUIDELINES_START = '<<<<<<< GUIDELINES';

    case RESPONSE_FORMAT_START = '<<<<<<< RESPONSE FORMAT';

    case EXAMPLE_START = '<<<<<<< EXAMPLE';

    case END = '>>>>>>>';
}
