<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AiResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'parsed_item_id',
        'analysis',
    ];

    protected $casts = [
        'analysis' => 'array',
    ];

    /**
     * Define the relationship to ParsedItem.
     */
    public function parsedItem()
    {
        return $this->belongsTo(ParsedItem::class);
    }
}
