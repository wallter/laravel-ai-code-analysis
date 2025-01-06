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
     * Create or update a ParsedItem with provided data, applying defaults where necessary.
     *
     * @param  array  $data  The data for creating or updating the ParsedItem.
     * @return ParsedItem|null The ParsedItem instance or null on failure.
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

            // Ensure 'line_number' is not null and is an integer
            if (is_null($data['line_number']) || ! is_int($data['line_number'])) {
                $data['line_number'] = 0;
            }

            // Create or update and return the ParsedItem
            return ParsedItem::updateOrCreate(
                [
                    'type' => $data['type'],
                    'name' => $data['name'],
                    'file_path' => $data['file_path'],
                ],
                $data
            );
        } catch (\Exception $exception) {
            Log::error('ParsedItemService: Failed to create or update ParsedItem.', [
                'error' => $exception->getMessage(),
                'data' => $data,
            ]);

            return null;
        }
    }
}
