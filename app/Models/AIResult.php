<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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

    protected $casts = [
        'metadata' => 'array',
    ];

    public function codeAnalysis()
    {
        return $this->belongsTo(CodeAnalysis::class);
    }
}