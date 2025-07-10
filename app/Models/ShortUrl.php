<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShortUrl extends Model
{
    protected $fillable = [
        'original_url',
        'short_code',
        'click_count',
        'is_custom',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    protected $attributes = [
        'click_count' => 0,
        'is_custom' => false,
    ];
}