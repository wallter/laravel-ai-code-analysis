<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AIResult model represents the results from AI analysis passes.
 */
class AIResult extends Model
{
    use HasFactory;

    protected $table = 'ai_results';

    protected $fillable = [
        'code_analysis_id',
        'pass_name',
        'prompt_text',
        'response_text',
        'metadata',
    ];

    /**
     * Get the CodeAnalysis instance associated with this AIResult.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function codeAnalysis()
    {
        return $this->belongsTo(CodeAnalysis::class);
    }
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
