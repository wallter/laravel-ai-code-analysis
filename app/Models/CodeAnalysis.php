<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    protected $casts = [
        'ast' => 'array',
        'analysis' => 'array',
        'ai_output' => 'array',
        'completed_passes' => 'array',
    ];

    /**
     * Get the ParsedItem that owns the CodeAnalysis.
     */
    public function parsedItem(): BelongsTo
    {
        return $this->belongsTo(ParsedItem::class);
    }

    /**
     * Get the AiResults associated with the CodeAnalysis.
     */
    public function aiResults(): HasMany
    {
        return $this->hasMany(AiResult::class);
    }
}
