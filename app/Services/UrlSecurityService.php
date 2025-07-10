<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class UrlSecurityService
{
    /**
     * Vérifie si une URL est potentiellement malveillante
     *
     * @param string $url
     * @return bool Retourne true si l'URL est sûre, false sinon
     */
    public function isSafeUrl(string $url): bool
    {
        $domain = $this->extractDomain($url);
        
        // Vérifier d'abord le cache local
        if (Cache::has("malicious_domain:{$domain}")) {
            return false;
        }

        // Vérifier les listes noires
        if ($this->isInBlacklist($domain)) {
            $this->cacheMaliciousDomain($domain);
            return false;
        }

        // Vérifier les services externes (optionnel, peut être désactivé)
        if (config('services.url_check.enabled', true)) {
            if ($this->checkWithExternalServices($url)) {
                $this->cacheMaliciousDomain($domain);
                return false;
            }
        }

        return true;
    }

    /**
     * Extrait le domaine d'une URL
     */
    protected function extractDomain(string $url): string
    {
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        
        // Supprimer les sous-domaines inutiles (www, m, etc.)
        $host = preg_replace('/^(www\.|m\.)/i', '', $host);
        
        return strtolower($host);
    }

    /**
     * Vérifie si un domaine est dans une liste noire locale
     */
    protected function isInBlacklist(string $domain): bool
    {
        $blacklist = config('security.domain_blacklist', [
            'example-malicious.com',
            'phishing-site.org',
            // Ajoutez d'autres domaines malveillants connus
        ]);

        // Vérifier les correspondances exactes
        if (in_array($domain, $blacklist)) {
            return true;
        }

        // Vérifier les sous-domaines
        foreach ($blacklist as $blacklisted) {
            if (str_ends_with($domain, '.' . $blacklisted)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Vérifie l'URL avec des services externes
     */
    protected function checkWithExternalServices(string $url): bool
    {
        try {
            // Google Safe Browsing API
            if (config('services.google.safe_browsing.enabled', false)) {
                $response = Http::post(
                    'https://safebrowsing.googleapis.com/v4/threatMatches:find?key=' . config('services.google.safe_browsing.key'),
                    [
                        'client' => [
                            'clientId' => config('app.name'),
                            'clientVersion' => '1.0.0',
                        ],
                        'threatInfo' => [
                            'threatTypes' => ['MALWARE', 'SOCIAL_ENGINEERING', 'THREAT_TYPE_UNSPECIFIED'],
                            'platformTypes' => ['ANY_PLATFORM'],
                            'threatEntryTypes' => ['URL'],
                            'threatEntries' => [['url' => $url]],
                        ],
                    ]
                );

                if ($response->successful() && !empty($response->json('matches'))) {
                    return true;
                }
            }

            // Autres services de vérification peuvent être ajoutés ici
            // Par exemple, VirusTotal, PhishTank, etc.

        } catch (\Exception $e) {
            Log::error('Erreur lors de la vérification de l\'URL : ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Met en cache un domaine malveillant
     */
    protected function cacheMaliciousDomain(string $domain): void
    {
        Cache::put("malicious_domain:{$domain}", true, now()->addDays(7));
    }
}
