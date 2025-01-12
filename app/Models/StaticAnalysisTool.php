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

    protected $casts = [
        'options' => 'array',
    ];

    public function aiConfiguration()
    {
        return $this->belongsTo(AIConfiguration::class);
    }
}
