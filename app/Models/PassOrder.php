<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PassOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'ai_configuration_id',
        'ai_pass_id',
        'order',
        // Add other necessary fields as necessary
    ];

    public function aiConfiguration()
    {
        return $this->belongsTo(AIConfiguration::class);
    }

    public function aiPass()
    {
        return $this->belongsTo(AIPass::class);
    }
}
