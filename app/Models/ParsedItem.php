<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParsedItem extends Model
{
    protected $fillable = [
        'type',
        'name',
        'file_path',
        'line_number',
        'annotations',
        'attributes',
        'details',
    ];

    protected $casts = [
        'annotations' => 'array',
        'attributes'  => 'array',
        'details'     => 'array',
    ];
}
