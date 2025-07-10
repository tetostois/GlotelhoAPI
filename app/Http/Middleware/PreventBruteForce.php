<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class PreventBruteForce
{
    /**
     * Gère une requête entrante.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $key
     * @param  int  $maxAttempts
     * @param  int  $decayMinutes
     * @return mixed
     */
    public function handle($request, Closure $next, $key = 'default', $maxAttempts = 5, $decayMinutes = 1)
    {
        $ip = $request->ip();
        $user = $request->user();
        $userId = $user ? $user->id : $ip;
        
        $throttleKey = "brute_force:{$key}:{$userId}";
        
        // Vérifier si l'utilisateur est bloqué
        if (Cache::has("blocked:{$throttleKey}")) {
            return $this->blockResponse($throttleKey);
        }
        
        // Vérifier le taux de requêtes
        if (RateLimiter::tooManyAttempts($throttleKey, $maxAttempts)) {
            $this->blockUser($throttleKey, $decayMinutes);
            return $this->blockResponse($throttleKey);
        }
        
        // Enregistrer la tentative
        RateLimiter::hit($throttleKey, $decayMinutes * 60);
        
        // Ajouter les en-têtes de taux limite à la réponse
        $response = $next($request);
        
        return $this->addRateLimitHeaders(
            $response,
            $maxAttempts,
            RateLimiter::remaining($throttleKey, $maxAttempts),
            $decayMinutes * 60
        );
    }
    
    /**
     * Bloque un utilisateur pour une certaine durée.
     */
    protected function blockUser(string $throttleKey, int $minutes): void
    {
        $blockKey = "blocked:{$throttleKey}";
        $blockDuration = now()->addMinutes($minutes);
        
        Cache::put($blockKey, true, $blockDuration);
        
        // Journaliser la tentative de force brute
        if (config('logging.brute_force_attempts', false)) {
            \Illuminate\Support\Facades\Log::warning('Tentative de force brute détectée', [
                'throttle_key' => $throttleKey,
                'blocked_until' => $blockDuration->toDateTimeString(),
                'ip' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
        }
    }
    
    /**
     * Retourne une réponse de blocage.
     */
    protected function blockResponse(string $throttleKey)
    {
        $retryAfter = Cache::get("blocked:{$throttleKey}");
        $retryAfter = $retryAfter ? now()->diffInSeconds($retryAfter) : 60;
        
        return response()->json([
            'message' => 'Trop de tentatives. Veuillez réessayer plus tard.',
            'retry_after' => $retryAfter,
        ], 429)->header('Retry-After', $retryAfter);
    }
    
    /**
     * Ajoute les en-têtes de taux limite à la réponse.
     */
    protected function addRateLimitHeaders($response, $maxAttempts, $remaining, $retryAfter)
    {
        $response->headers->add([
            'X-RateLimit-Limit' => $maxAttempts,
            'X-RateLimit-Remaining' => $remaining,
            'X-RateLimit-Reset' => now()->addSeconds($retryAfter)->timestamp,
            'Retry-After' => $retryAfter,
        ]);
        
        return $response;
    }
}
