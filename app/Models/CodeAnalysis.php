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
        'ai_output', // Add this line
        'current_pass',      // Added
        'completed_passes',  // Added
    ];

    protected $casts = [
        'ast' => 'array',
        'analysis' => 'array',
        'ai_output' => 'array',
        'completed_passes' => 'array',
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
}
