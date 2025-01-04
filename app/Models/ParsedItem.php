<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ParsedItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'type',
        'name',
        'file_path',
        'line_number',
        'annotations',
        'attributes',
        'details',
        'class_name',
        'namespace',
        'visibility',
        'is_static',
        'fully_qualified_name',
        'operation_summary',
        'called_methods',
        'ast',
    ];

    protected $casts = [
        'annotations' => 'array',
        'attributes'  => 'array',
        'details'     => 'array',
        'is_static'   => 'boolean',
        'ast'         => 'array',
        'called_methods' => 'array',
    ];

    /**
     * Get the CodeAnalysis associated with the ParsedItem.
     */
    public function codeAnalysis(): HasOne
    {
        return $this->hasOne(CodeAnalysis::class);
    }

    /**
     * Get the AiResults associated with the ParsedItem.
     */
    public function aiResults(): HasMany
    {
        return $this->hasMany(AiResult::class);
    }
}
