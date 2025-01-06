<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * ParsedItem model represents items parsed from PHP files.
 */
class ParsedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'file_path',
        'line_number',
        'annotations',
        'attributes',
        'details',
        'class_name',
        'namespace',
        'visibility',
        'is_static',
        'fully_qualified_name',
        'operation_summary',
        'called_methods',
        'ast',
    ];

    /**
     * The model's default values for attributes.
     *
     * @var array
     */
    protected $attributes = [
        'type' => 'Unknown',
        'name' => 'Unnamed',
        'file_path' => 'Unknown',
        'line_number' => 0,
        'annotations' => '[]',
        'attributes' => '[]',
        'details' => '[]',
        'visibility' => 'public',
        'is_static' => false,
        'called_methods' => '[]',
        'ast' => '[]',
        'class_name' => null,
        'namespace' => null,
        'fully_qualified_name' => null,
        'operation_summary' => null,
    ];

    protected function casts(): array
    {
        return [
            'type' => 'string',
            'name' => 'string',
            'file_path' => 'string',
            'line_number' => 'integer',
            'annotations' => 'array',
            'attributes' => 'array',
            'details' => 'array',
            'class_name' => 'string',
            'namespace' => 'string',
            'visibility' => 'string',
            'is_static' => 'boolean',
            'fully_qualified_name' => 'string',
            'operation_summary' => 'string',
            'called_methods' => 'array',
            'ast' => 'array',
        ];
    }
}
