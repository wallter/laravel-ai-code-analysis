<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIModel extends Model
{
    use HasFactory;

    protected $table = 'ai_models';

    protected $fillable = [
        'ai_configuration_id',
        'model_name',
        'max_tokens',
        'temperature',
        'supports_system_message',
        'token_limit_parameter',
        // Add other necessary fields
    ];

    public function aiConfiguration()
    {
        return $this->belongsTo(AIConfiguration::class);
    }

    public function aiPasses()
    {
        return $this->hasMany(AIPass::class, 'model_id');
    }
}
