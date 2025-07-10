<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Click extends Model
{
    /**
     * Les attributs qui sont mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'short_url_id',
        'ip_address',
        'user_agent',
        'referer',
        'country',
        'device',
        'platform',
        'browser',
    ];

    /**
     * Obtenez l'URL courte associée à ce clic.
     */
    public function shortUrl(): BelongsTo
    {
        return $this->belongsTo(ShortUrl::class);
    }
}
