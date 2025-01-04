<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
     * Define the inverse relationship to ParsedItem.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function parsedItem()
    {
        return $this->belongsTo(ParsedItem::class);
    }
}
