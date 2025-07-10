<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecureHeaders
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (!$response instanceof Response) {
            return $response;
        }

        $security = config('security.headers', []);

        // X-Frame-Options
        if (!empty($security['x_frame_options'])) {
            $response->headers->set('X-Frame-Options', $security['x_frame_options']);
        }

        // X-XSS-Protection
        if (!empty($security['x_xss_protection'])) {
            $response->headers->set('X-XSS-Protection', $security['x_xss_protection']);
        }

        // X-Content-Type-Options
        if (!empty($security['x_content_type_options'])) {
            $response->headers->set('X-Content-Type-Options', $security['x_content_type_options']);
        }

        // Content-Security-Policy
        if (!empty($security['content_security_policy'])) {
            $response->headers->set('Content-Security-Policy', $security['content_security_policy']);
        }

        // Referrer-Policy
        if (!empty($security['referrer_policy'])) {
            $response->headers->set('Referrer-Policy', $security['referrer_policy']);
        }

        // Feature-Policy
        if (!empty($security['feature_policy'])) {
            $response->headers->set('Feature-Policy', $security['feature_policy']);
        }

        // HSTS (Strict-Transport-Security)
        if (config('app.env') === 'production') {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        // Supprimer l'en-tête X-Powered-By
        $response->headers->remove('X-Powered-By');

        return $response;
    }
}
