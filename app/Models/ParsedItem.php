<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParsedItem extends Model
{
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
        'fully_qualified_name',
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
     * Define the one-to-one relationship with CodeAnalysis.
     */
    public function aiResult()
    {
        return $this->hasOne(AiResult::class);
    }

    /**
     * Define the one-to-one relationship with CodeAnalysis.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function codeAnalysis()
    {
        return $this->hasOne(CodeAnalysis::class);
    }
}
