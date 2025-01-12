<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'openai_api_key',
        // Add other necessary fields as per the implementation plan
    ];

    public function models()
    {
        return $this->hasMany(AIModel::class);
    }

    public function staticAnalysisTools()
    {
        return $this->hasMany(StaticAnalysisTool::class);
    }

    public function aiPasses()
    {
        return $this->hasMany(AIPass::class);
    }

    public function passOrders()
    {
        return $this->hasMany(PassOrder::class);
    }
}
