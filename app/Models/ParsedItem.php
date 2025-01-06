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

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
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
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type'                 => 'Unknown',
        'name'                 => 'Unnamed',
        'file_path'            => 'Unknown',
        'line_number'          => 0,
        'annotations'          => '[]',
        'attributes'           => '[]',
        'details'              => '[]',
        'class_name'           => null,
        'namespace'            => null,
        'visibility'           => 'public',
        'is_static'            => false,
        'fully_qualified_name' => null,
        'operation_summary'    => null,
        'called_methods'       => '[]',
        'ast'                  => '[]',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'type'                  => 'string',
        'name'                  => 'string',
        'file_path'             => 'string',
        'line_number'           => 'integer',
        'annotations'           => 'array',
        'attributes'            => 'array',
        'details'               => 'array',
        'class_name'            => 'string',
        'namespace'             => 'string',
        'visibility'            => 'string',
        'is_static'             => 'boolean',
        'fully_qualified_name'  => 'string',
        'operation_summary'     => 'string',
        'called_methods'        => 'array',
        'ast'                   => 'array',
    ];
}