<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AIConfiguration extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string|null
     */
    protected $table = 'ai_configurations';
    public function aiPasses()
    {
        return $this->hasMany(AIPass::class);
    }

    public function passOrders()
    {
        return $this->hasMany(PassOrder::class);
    }

    public function aiModels()
    {
        return $this->hasMany(AIModel::class);
    }

    public function staticAnalysisTools()
    {
        return $this->hasMany(StaticAnalysisTool::class);
    }
}
