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
        'ai_output', // Add this line
        'current_pass',      // Added
        'completed_passes',  // Added
    ];

    protected $casts = [
        'ast' => 'array',
        'analysis' => 'array',
        'completed_passes' => 'array', // Ensures JSON is cast to an array
}
