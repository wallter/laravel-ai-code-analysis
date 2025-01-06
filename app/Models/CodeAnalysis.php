<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
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

    protected function casts(): array
    {
        return [
            'ast' => 'array',
            'analysis' => 'array',
            'ai_output' => 'array',
            'completed_passes' => 'array',
        ];
    }
}
