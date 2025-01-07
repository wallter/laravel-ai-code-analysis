<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Config;
use Illuminate\Database\Eloquent\Model;

/**
 * CodeAnalysis model represents the analysis of a single PHP file.
 */
class CodeAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'file_path',
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
        return realpath($basePath . DIRECTORY_SEPARATOR . $value) ?: $basePath . DIRECTORY_SEPARATOR . $value;
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
        if (str_starts_with($value, $basePath)) {
            $relativePath = ltrim(str_replace($basePath, '', $value), DIRECTORY_SEPARATOR);
            $this->attributes['file_path'] = $relativePath;
        } else {
            $this->attributes['file_path'] = $value;
        }
    }
}