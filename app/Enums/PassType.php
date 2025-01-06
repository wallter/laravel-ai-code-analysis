<?php

namespace App\Enums;

/**
 * Enum PassType
 *
 * Defines the types of AI analysis passes.
 */
enum PassType: string
{
    case RAW = 'raw';
    case AST = 'ast';
    case BOTH = 'both';
    case PREVIOUS = 'previous';
}
