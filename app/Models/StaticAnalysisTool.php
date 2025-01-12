<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaticAnalysisTool extends Model
{
    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'options' => 'array',
    ];

    public function aiConfiguration()
    {
        return $this->belongsTo(AIConfiguration::class);
    }
}
