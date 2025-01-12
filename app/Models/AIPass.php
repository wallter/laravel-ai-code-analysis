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
}
