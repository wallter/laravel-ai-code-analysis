<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIPass extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        // Add other fillable fields as necessary
    ];
}
