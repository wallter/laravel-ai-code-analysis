<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AIResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'code_analysis_id',
        'pass_name',        // e.g. "doc_generation", "performance_analysis"
        'prompt_text',
        'response_text',
        'metadata',         // store tokens, cost, etc. if needed
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public function codeAnalysis()
    {
        return $this->belongsTo(CodeAnalysis::class);
    }
}