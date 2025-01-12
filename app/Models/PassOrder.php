<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PassOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'pass_name',
        'order',
        // Add other fillable fields as necessary
    ];
}
