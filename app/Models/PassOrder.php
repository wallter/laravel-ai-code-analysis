<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PassOrder extends Model
{
    public function aiConfiguration()
    {
        return $this->belongsTo(AIConfiguration::class);
    }

    public function aiPass()
    {
        return $this->belongsTo(AIPass::class);
    }
}
