<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaticAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'code_analysis_id',
        'tool',
        'results',
    ];

    protected $casts = [
        'results' => 'array',
    ];

    public function codeAnalysis()
    {
        return $this->belongsTo(CodeAnalysis::class);
    }
}
