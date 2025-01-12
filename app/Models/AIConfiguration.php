<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;
use OwenIt\Auditing\Auditable as AuditableTrait;

class AIConfiguration extends Model implements Auditable
{
    use AuditableTrait;

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
