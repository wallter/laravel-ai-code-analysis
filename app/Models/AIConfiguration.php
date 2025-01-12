<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AIConfiguration extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'value',
        // Add other fillable fields as necessary
    ];
}
