<?php

namespace App\Services;

use App\Models\ParsedItem;
use Illuminate\Support\Facades\Log;

/**
 * Service responsible for creating ParsedItem models with applied defaults.
 */
class ParsedItemService
{
    /**
     * Create a ParsedItem with provided data, applying defaults where necessary.
     *
     * @param array $data The data for creating the ParsedItem.
     * @return ParsedItem|null The created ParsedItem instance or null on failure.
     */
    public function createParsedItem(array $data): ?ParsedItem
    {
        try {
            // Apply default values
            $data = array_merge([
                'type' => 'Unknown',
                'name' => 'Unnamed',
                'file_path' => 'Unknown',
                'line_number' => 0, // Default line number if not provided
                'annotations' => [],
                'attributes' => [],
                'details' => [],
                'class_name' => null,
                'namespace' => null,
                'visibility' => 'public',
                'is_static' => false,
                'fully_qualified_name' => null,
                'operation_summary' => null,
                'called_methods' => [],
                'ast' => [],
            ], $data);

            // Create and return the ParsedItem
            return ParsedItem::create($data);
        } catch (\Exception $e) {
            Log::error("ParsedItemService: Failed to create ParsedItem.", [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);

            return null;
        }
    }
}
