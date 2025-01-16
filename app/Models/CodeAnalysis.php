<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
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
        'language',
    ];

    /**
     * Accessor and Mutator for the file_path attribute.
     * Ensures that both file_path and language are set correctly.
     */
    protected function filePath(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                $basePath = realpath(Config::get('filesystems.base_path')) ?: base_path();
                $absolutePath = realpath($basePath.DIRECTORY_SEPARATOR.$value);

                return $absolutePath ?: $value;
            },
            set: function (string $value) {
                $basePath = realpath(Config::get('filesystems.base_path')) ?: base_path();
                // Ensure both paths use forward slashes
                $value = str_replace(['\\'], '/', $value);
                $basePath = str_replace(['\\'], '/', $basePath);
                if (Str::startsWith($value, $basePath)) {
                    $relativePath = Str::replaceFirst($basePath.'/', '', $value);
                    $this->attributes['file_path'] = $relativePath;
                    Log::debug("CodeAnalysis Model: Set 'file_path' to relative path '{$relativePath}'.");

                    // Set language based on file extension
                    $extension = strtolower(pathinfo($value, PATHINFO_EXTENSION));
                    $languageMap = [
                        'php' => 'php',
                        'js' => 'javascript',
                        'ts' => 'typescript',
                        'py' => 'python',
                        'go' => 'go',
                        'ex' => 'elixir',
                        'exs' => 'elixir',
                    ];
                    $language = $languageMap[$extension] ?? 'unknown';
                    $this->attributes['language'] = $language;

                    Log::debug("CodeAnalysis Model: Set 'language' to '{$language}'.");
                } else {
                    $this->attributes['file_path'] = $value;
                    Log::warning("CodeAnalysis Model: The file path '{$value}' does not start with base path '{$basePath}'. Stored as is.");
                }
            }
        );
    }

    /**
     * Boot the model and attach event listeners.
     */
    protected static function booted()
    {
        static::creating(function ($codeAnalysis) {
            $codeAnalysis->language = $codeAnalysis->determineLanguage();
        });

        static::updating(function ($codeAnalysis) {
            $codeAnalysis->language = $codeAnalysis->determineLanguage();
        });
    }

    /**
     * Determine the language based on the file extension.
     */
    protected function determineLanguage(): string
    {
        $extension = strtolower(pathinfo($this->file_path, PATHINFO_EXTENSION));
        $languageMap = [
            'php' => 'php',
            'js' => 'javascript',
            'ts' => 'typescript',
            'py' => 'python',
            'go' => 'go',
            'ex' => 'elixir',
            'exs' => 'elixir',
        ];

        return $languageMap[$extension] ?? 'unknown';
    }

    public function staticAnalyses()
    {
        return $this->hasMany(StaticAnalysis::class);
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
