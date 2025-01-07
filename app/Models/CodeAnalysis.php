<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Config;
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

    protected $casts = [
        'file_path' => 'string',
        'relative_file_path' => 'string',
        'ast' => 'array',
        'analysis' => 'array',
        'ai_output' => 'array',
        'completed_passes' => 'array',
    ];
    
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
     * Mutator to set the file path.
     *
     * @param  string  $value
     * @return void
     */
    public function setFilePathAttribute(string $value): void
    {
        $basePath = Config::get('filesystems.base_path');
        if (Str::startsWith($value, $basePath)) {
            $relativePath = Str::after($value, rtrim($basePath, '/') . '/');
            $this->attributes['file_path'] = $relativePath;
        } else {
            $this->attributes['file_path'] = $value;
        }
    }
}
