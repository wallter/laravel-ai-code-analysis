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

    public function aiResults()
    {
        return $this->hasMany(AIResult::class);
    }
    protected function casts(): array
    {
        return [
            'ast' => 'array',
            'analysis' => 'array',
            'ai_output' => 'array',
            'completed_passes' => 'array',
        ];
    }
}