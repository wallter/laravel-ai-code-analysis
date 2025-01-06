<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AIScore model represents individual scores from AI analysis.
 */
class AIScore extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ai_scores';

    protected $fillable = [
        'code_analysis_id',
        'operation',
        'score',
    ];

    /**
     * Get the CodeAnalysis instance associated with this AIScore.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function codeAnalysis()
    {
        return $this->belongsTo(CodeAnalysis::class);
    }
}
