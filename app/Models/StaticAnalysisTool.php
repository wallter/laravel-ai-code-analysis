<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticAnalysisTool extends Model
{
    //
    public function aiConfiguration()
    {
        return $this->belongsTo(AIConfiguration::class);
    }
}
