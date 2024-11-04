<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Metric extends Model
{
    protected $fillable = [
        'value',
        'type',
        'created_at'
    ];

    protected $casts = [
        'value' => 'float',
        'created_at' => 'datetime'
    ];
}