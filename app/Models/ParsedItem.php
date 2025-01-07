<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
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
        'type' => 'Unknown',
        'name' => 'Unnamed',
        'file_path' => 'Unknown',
        'line_number' => 0,
        'annotations' => '[]',
        'attributes' => '[]',
        'details' => '[]',
        'class_name' => null,
        'namespace' => null,
        'visibility' => 'public',
        'is_static' => false,
        'fully_qualified_name' => null,
        'operation_summary' => null,
        'called_methods' => '[]',
        'ast' => '[]',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @return array<string, string>
     */
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

    /**
     * Accessor to get the absolute file path.
     *
     * @param  string  $value
     * @return string
     */
    public function getFilePathAttribute($value): string
    {
        $basePath = Config::get('filesystems.base_path');
        return realpath($basePath . DIRECTORY_SEPARATOR . $value) ?: $value;
    }

    /**
     * Mutator to set the relative file path.
     *
     * @param  string  $value
     * @return void
     */
    public function setFilePathAttribute(string $value): void
    {
        $basePath = Config::get('filesystems.base_path');
        if (Str::startsWith($value, $basePath)) {
            $relativePath = Str::substr($value, strlen($basePath) + 1);
            $this->attributes['file_path'] = $relativePath;
        } else {
            $this->attributes['file_path'] = $value;
        }
    }

    /**
     * Get the relative file path.
     *
     * @return string
     */
    public function getRelativeFilePathAttribute(): string
    {
        $basePath = Config::get('filesystems.base_path');
        return Str::startsWith($this->file_path, $basePath)
            ? Str::substr($this->file_path, strlen($basePath) + 1)
            : $this->file_path;
    }
}
