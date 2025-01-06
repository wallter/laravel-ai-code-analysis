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

    protected $casts = [
        'annotations' => 'array',
        'attributes' => 'array',
        'details' => 'array',
        'is_static' => 'boolean',
        'ast' => 'array',
        'called_methods' => 'array',
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
    ];
}
