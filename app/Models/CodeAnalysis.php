<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * CodeAnalysis model represents the analysis of a single PHP file.
 */
class CodeAnalysis extends Model
{
    use HasFactory;

    protected $table = 'code_analyses';

    protected $fillable = [
        'file_path',
        'relative_file_path',
        'ast',
        'analysis',
        'ai_output',
        'current_pass',
        'completed_passes',
    ];

    /**
     * Accessor to get the absolute file path.
     *
     * @param  string  $value
     */
    protected function filePath(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(get: function ($value) {
            $basePath = realpath(Config::get('filesystems.base_path')) ?: base_path();
            $absolutePath = realpath($basePath.DIRECTORY_SEPARATOR.$value);

            return $absolutePath ?: $value;
        }, set: function (string $value) {
            $basePath = realpath(Config::get('filesystems.base_path')) ?: base_path();
            // Ensure both paths use forward slashes
            $value = str_replace(['\\'], '/', $value);
            $basePath = str_replace(['\\'], '/', $basePath);
            if (Str::startsWith($value, $basePath)) {
                $relativePath = Str::replaceFirst($basePath.'/', '', $value);
                $this->attributes['file_path'] = $relativePath;
                Log::debug("CodeAnalysis Model: Set 'file_path' to relative path '{$relativePath}'.");
            } else {
                $this->attributes['file_path'] = $value;
                Log::warning("CodeAnalysis Model: The file path '{$value}' does not start with base path '{$basePath}'. Stored as is.");
            }

            return ['file_path' => $value];
        });
    }

    /**
     * Get the AI results associated with this code analysis.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function aiResults()
    {
        return $this->hasMany(AIResult::class);
    }

    /**
     * Get the AI scores associated with this code analysis.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function aiScores()
    {
        return $this->hasMany(AIScore::class);
    }

    protected function casts(): array
    {
        return [
            'file_path' => 'string',
            'relative_file_path' => 'string',
            'ast' => 'array',
            'analysis' => 'array',
            'ai_output' => 'array',
            'completed_passes' => 'array',
        ];
    }
}
