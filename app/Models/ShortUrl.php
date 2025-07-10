<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Request;
use Jaybizzle\CrawlerDetect\CrawlerDetect;

class ShortUrl extends Model
{
    protected $fillable = [
        'original_url',
        'short_code',
        'click_count',
        'is_custom',
        'expires_at',
    ];
    
    protected $dates = [
        'expires_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'is_custom' => 'boolean',
    ];

    protected $attributes = [
        'click_count' => 0,
        'is_custom' => false,
    ];
    
    /**
     * Vérifie si l'URL a expiré
     *
     * @return bool
     */
    public function hasExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Obtenez les clics pour cette URL courte.
     */
    public function clicks(): HasMany
    {
        return $this->hasMany(Click::class);
    }

    /**
     * Enregistre un nouveau clic pour cette URL courte.
     */
    public function registerClick(): Click
    {
        $this->increment('click_count');
        
        $crawlerDetect = new CrawlerDetect();
        
        return $this->clicks()->create([
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'referer' => Request::header('referer'),
            'device' => $this->getDeviceType(),
            'platform' => $this->getPlatform(),
            'browser' => $this->getBrowser(),
            'country' => $this->getCountryFromIP(Request::ip()),
        ]);
    }

    /**
     * Détermine le type d'appareil à partir du user agent.
     */
    protected function getDeviceType(): string
    {
        $userAgent = Request::userAgent();
        
        if (preg_match('/(tablet|ipad|playbook|silk)|(android(?!.*mobile))/i', $userAgent)) {
            return 'Tablet';
        }
        
        if (preg_match('/Mobile|Android|iPhone|iPod|BlackBerry|IEMobile|Opera Mini/i', $userAgent)) {
            return 'Mobile';
        }
        
        return 'Desktop';
    }

    /**
     * Récupère le navigateur à partir du user agent.
     */
    protected function getBrowser(): string
    {
        $userAgent = Request::userAgent();
        
        if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident/') !== false) {
            return 'Internet Explorer';
        } elseif (strpos($userAgent, 'Firefox') !== false) {
            return 'Mozilla Firefox';
        } elseif (strpos($userAgent, 'Chrome') !== false) {
            return 'Google Chrome';
        } elseif (strpos($userAgent, 'Safari') !== false) {
            return 'Safari';
        } elseif (strpos($userAgent, 'Opera') !== false || strpos($userAgent, 'OPR/') !== false) {
            return 'Opera';
        } elseif (strpos($userAgent, 'Edge') !== false) {
            return 'Microsoft Edge';
        }
        
        return 'Inconnu';
    }

    /**
     * Récupère la plateforme à partir du user agent.
     */
    protected function getPlatform(): string
    {
        $userAgent = Request::userAgent();
        
        if (preg_match('/windows|win32|win64/i', $userAgent)) {
            return 'Windows';
        } elseif (preg_match('/macintosh|mac os x|mac_powerpc/i', $userAgent)) {
            return 'Mac OS';
        } elseif (preg_match('/linux/i', $userAgent)) {
            return 'Linux';
        } elseif (preg_match('/android/i', $userAgent)) {
            return 'Android';
        } elseif (preg_match('/iphone|ipad|ipod/i', $userAgent)) {
            return 'iOS';
        }
        
        return 'Inconnu';
    }

    /**
     * Tente de déterminer le pays à partir de l'adresse IP (version simplifiée).
     * En production, utilisez un service comme MaxMind GeoIP ou ipinfo.io.
     */
    protected function getCountryFromIP(?string $ip): ?string
    {
        if (empty($ip) || $ip === '127.0.0.1') {
            return 'Localhost';
        }
        
        // En production, vous devriez utiliser un service comme:
        // - MaxMind GeoIP
        // - ipinfo.io
        // - ip-api.com
        
        // Exemple simplifié qui retourne null mais qui peut être étendu
        return null;
    }
}