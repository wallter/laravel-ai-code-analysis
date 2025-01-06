<?php

namespace App\Enums;

/**
 * Enum ParsedItemType
 *
 * Defines types of parsed items.
 */
enum ParsedItemType: string
{
    case CLASS_TYPE = 'Class';
    case TRAIT_TYPE = 'Trait';
    case INTERFACE_TYPE = 'Interface';
    case FUNCTION_TYPE = 'Function';
    case UNKNOWN = 'Unknown';

    /**
     * Get all enum values as an array.
     *
     * @return array<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
