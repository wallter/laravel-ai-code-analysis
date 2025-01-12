<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIPass extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'ai_passes';

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'prompt_sections' => 'array',
    ];

    /**
     * Get the AIConfiguration that owns the AIPass.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function aiConfiguration()
    {
        return $this->belongsTo(AIConfiguration::class);
    }

    /**
     * Get the AIModel associated with the AIPass.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function model()
    {
        return $this->belongsTo(AIModel::class, 'model_id');
    }

    /**
     * Get the PassOrders for the AIPass.
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function passOrders()
    {
        return $this->hasMany(PassOrder::class);
    }
}
