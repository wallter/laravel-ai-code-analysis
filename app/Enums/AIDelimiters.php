<?php

namespace App\Enums;

/**
 * Enum AIDelimiters
 *
 * Defines delimiter constants for AI prompt structuring.
 */
enum AIDelimiters: string
{
    case END = '>>>>>>>';
    case START = '<<<<<<<';
}
