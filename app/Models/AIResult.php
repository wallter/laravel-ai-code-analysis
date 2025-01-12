<?php

namespace App\Models;

use App\Enums\AIResultContentType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'cost_estimate_usd',
        'content_type',
    ];

    /**
     * Get the CodeAnalysis instance associated with this AIResult.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function codeAnalysis()
    {
        return $this->belongsTo(CodeAnalysis::class);
    }

    protected $casts = [
        'metadata' => 'array',
        'response_text' => 'string',
        'cost_estimate_usd' => 'decimal:6',
        'content_type' => AIResultContentType::class,
    ];
}
