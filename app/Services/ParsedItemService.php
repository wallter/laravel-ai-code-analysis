<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\ParsedItemType;
use App\Models\ParsedItem;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Service responsible for creating and updating ParsedItem records.
 */
class ParsedItemService
{
    /**
     * Constructor to inject the ParsedItem model.
     *
     * @param  ParsedItem  $parsedItem  The ParsedItem model instance.
     */
    public function __construct(protected ParsedItem $parsedItem) {}

    /**
     * Create or update a ParsedItem with provided data.
     *
     * @param  array<string, mixed>  $data  The data for creating or updating the ParsedItem.
     * @return ParsedItem|null The ParsedItem instance or null on failure.
     *
     * @throws ValidationException If validation fails.
     */
    public function createParsedItem(array $data): ?ParsedItem
    {
        // Validate incoming data
        $this->validateData($data);

        try {
            // Create or update the ParsedItem
            $parsedItem = $this->parsedItem->updateOrCreate(
                [
                    'type' => $data['type'],
                    'name' => $data['name'],
                    'file_path' => $data['file_path'],
                ],
                $data
            );

            Log::info('ParsedItemService: ParsedItem created or updated successfully.', [
                'parsed_item_id' => $parsedItem->id,
                'type' => $parsedItem->type,
                'name' => $parsedItem->name,
                'file_path' => $parsedItem->file_path,
            ]);

            return $parsedItem;
        } catch (\Exception $exception) {
            Log::error('ParsedItemService: Failed to create or update ParsedItem.', [
                'error' => $exception->getMessage(),
                'data' => $data,
            ]);

            return null;
        }
    }

    /**
     * Validate the provided data for creating/updating ParsedItem.
     *
     * @param  array<string, mixed>  $data  The data to validate.
     *
     * @throws ValidationException If validation fails.
     */
    protected function validateData(array $data): void
    {
        // Define validation rules
        $rules = [
            'type' => ['required', 'string', 'in:'.implode(',', ParsedItemType::values())],
            'name' => ['required', 'string'],
            'file_path' => ['required', 'string'],
            'line_number' => ['nullable', 'integer', 'min:0'],
            'annotations' => ['nullable', 'array'],
            'attributes' => ['nullable', 'array'],
            'details' => ['nullable', 'array'],
            'class_name' => ['nullable', 'string'],
            'namespace' => ['nullable', 'string'],
            'visibility' => ['nullable', 'string', 'in:public,protected,private'],
            'is_static' => ['nullable', 'boolean'],
            'fully_qualified_name' => ['nullable', 'string'],
            'operation_summary' => ['nullable', 'string'],
            'called_methods' => ['nullable', 'array'],
            'ast' => ['nullable', 'array'],
        ];

        // Perform validation
        $validator = Validator::make($data, $rules);

        // Throw ValidationException if validation fails
        if ($validator->fails()) {
            Log::warning('ParsedItemService: Validation failed while creating ParsedItem.', [
                'errors' => $validator->errors()->toArray(),
                'data' => $data,
            ]);

            throw new ValidationException($validator);
        }
    }
}
