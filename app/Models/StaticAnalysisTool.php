<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaticAnalysisTool extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_configuration_id',
        'name',
        'command',
        'options',
        'enabled',
        // Add other necessary fields
    ];

    public function aiConfiguration()
    {
        return $this->belongsTo(AIConfiguration::class);
    }
    protected function casts(): array
    {
        return [
            'options' => 'array',
        ];
    }
}
