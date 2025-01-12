<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaticAnalysisTool extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'command',
        'options',
        'enabled',
        // Add other fillable fields as necessary
    ];
}
