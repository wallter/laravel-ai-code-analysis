<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIPass extends Model
{
    use HasFactory;

    protected $table = 'ai_passes';

    protected $fillable = [
        'ai_configuration_id',
        'name',
        'description',
        'operation_identifier',
        'model_id',
        'max_tokens',
        'temperature',
        'type',
        'system_message',
        'prompt_sections',
    ];

    public function aiConfiguration()
    {
        return $this->belongsTo(AIConfiguration::class);
    }

    public function model()
    {
        return $this->belongsTo(AIModel::class, 'model_id');
    }

    public function passOrders()
    {
        return $this->hasMany(PassOrder::class);
    }

    protected function casts(): array
    {
        return [
            'prompt_sections' => 'array',
        ];
    }
}
