<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'parsed_item_id',
        'code_analysis_id',
        'analysis',
    ];

    protected $casts = [
        'analysis' => 'array',
    ];

    /**
     * Get the CodeAnalysis that owns the AiResult.
     */
    public function codeAnalysis(): BelongsTo
    {
        return $this->belongsTo(CodeAnalysis::class);
    }

    /**
     * Get the ParsedItem that owns the AiResult.
     */
    public function parsedItem(): BelongsTo
    {
        return $this->belongsTo(ParsedItem::class);
    }
}
