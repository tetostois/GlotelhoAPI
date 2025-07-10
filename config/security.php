<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Domain Blacklist
    |--------------------------------------------------------------------------
    |
    | Liste des domaines malveillants connus qui doivent être bloqués.
    |
    */
    'domain_blacklist' => [
        'example-malicious.com',
        'phishing-site.org',
        // Ajoutez d'autres domaines malveillants connus
    ],

    /*
    |--------------------------------------------------------------------------
    | Mots de passe réservés
    |--------------------------------------------------------------------------
    |
    | Codes courts qui ne peuvent pas être utilisés car ils sont réservés pour
    | des fonctionnalités spécifiques du système.
    |
    */
    'reserved_codes' => [
        'admin', 'api', 'dashboard', 'login', 'logout', 'register', 'password',
        'settings', 'profile', 'help', 'support', 'contact', 'about', 'privacy',
        'terms', 'blog', 'news', 'docs', 'documentation', 'status', 'assets',
        'images', 'img', 'css', 'js', 'static', 'media', 'files', 'download',
        'shorten', 'url', 'link', 'go', 'r', 's', 'u', 't', 'i', 'l'
    ],

    /*
    |--------------------------------------------------------------------------
    | Vérification des URLs
    |--------------------------------------------------------------------------
    |
    | Configuration pour la vérification des URLs malveillantes.
    |
    */
    'url_check' => [
        'enabled' => env('URL_CHECK_ENABLED', true),
        'providers' => [
            'google_safe_browsing' => [
                'enabled' => env('GOOGLE_SAFE_BROWSING_ENABLED', false),
                'api_key' => env('GOOGLE_SAFE_BROWSING_API_KEY'),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Protection contre la force brute
    |--------------------------------------------------------------------------
    |
    | Configuration pour la protection contre les attaques par force brute.
    |
    */
    'brute_force_protection' => [
        'enabled' => true,
        'max_attempts' => 5,
        'decay_minutes' => 15,
        'block_duration' => 60, // minutes
    ],

    /*
    |--------------------------------------------------------------------------
    | En-têtes de sécurité HTTP
    |--------------------------------------------------------------------------
    |
    | Configuration des en-têtes de sécurité HTTP.
    |
    */
    'headers' => [
        // Active la protection contre le clickjacking
        'x_frame_options' => 'SAMEORIGIN',
        
        // Active la protection XSS dans les navigateurs modernes
        'x_xss_protection' => '1; mode=block',
        
        // Désactive le MIME-sniffing
        'x_content_type_options' => 'nosniff',
        
        // Politique de sécurité du contenu
        'content_security_policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https:; style-src 'self' 'unsafe-inline' https:; img-src 'self' data: https:; font-src 'self' https: data:;",
        
        // Politique de référenceur
        'referrer_policy' => 'strict-origin-when-cross-origin',
        
        // Feature Policy
        'feature_policy' => "geolocation 'none'; midi 'none'; notifications 'none'; push 'none'; sync-xhr 'none'; microphone 'none'; camera 'none'; magnetometer 'none'; gyroscope 'none'; speaker 'none'; vibrate 'none'; fullscreen 'self'; payment 'none';",
    ],
];
