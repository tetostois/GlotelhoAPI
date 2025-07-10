<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

class ShortUrlController extends Controller
{
    // POST /api/shorten
    public function shorten(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'url' => [
                'required',
                'url',
                function ($attribute, $value, $fail) {
                    // Vérification supplémentaire pour les URLs mal formées
                    $parsed = parse_url($value);
                    if (!isset($parsed['scheme']) || !in_array($parsed['scheme'], ['http', 'https'])) {
                        $fail("L'URL doit commencer par http:// ou https://");
                    }
                },
            ],
            'custom_code' => [
                'nullable',
                'alpha_dash',
                'min:3',
                'max:20',
                'unique:short_urls,short_code',
                function ($attribute, $value, $fail) {
                    // Liste de mots réservés
                    $reserved = ['api', 'admin', 'dashboard', 'login', 'register'];
                    if (in_array(strtolower($value), $reserved)) {
                        $fail("Ce code est réservé et ne peut pas être utilisé.");
                    }
                },
            ],
        ], [
            'url.required' => 'Une URL est requise',
            'url.url' => 'Le format de l\'URL est invalide',
            'custom_code.alpha_dash' => 'Le code personnalisé ne peut contenir que des lettres, des chiffres, des tirets et des underscores',
            'custom_code.min' => 'Le code personnalisé doit contenir au moins :min caractères',
            'custom_code.max' => 'Le code personnalisé ne peut pas dépasser :max caractères',
            'custom_code.unique' => 'Ce code est déjà utilisé',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si l'URL existe déjà (optionnel)
        if ($existingUrl = ShortUrl::where('original_url', $request->url)->first()) {
            return response()->json([
                'success' => true,
                'message' => 'URL déjà raccourcie',
                'data' => [
                    'short_url' => url($existingUrl->short_code),
                    'original_url' => $existingUrl->original_url,
                    'short_code' => $existingUrl->short_code,
                ]
            ]);
        }

        // Utiliser le code personnalisé s'il est fourni, sinon en générer un
        $shortCode = $request->custom_code ?? $this->generateUniqueCode();

        $shortUrl = ShortUrl::create([
            'original_url' => $request->url,
            'short_code' => $shortCode,
            'click_count' => 0,
            'is_custom' => (bool)$request->custom_code,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'URL raccourcie avec succès',
            'data' => [
                'short_url' => url($shortCode),
                'original_url' => $shortUrl->original_url,
                'short_code' => $shortUrl->short_code,
                'is_custom' => (bool)$request->custom_code,
            ]
        ], 201); // 201 Created
    }

    /**
     * Redirige vers l'URL originale
     */
    public function redirect($short_code)
    {
        $shortUrl = ShortUrl::where('short_code', $short_code)->first();

        if (!$shortUrl) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'URL courte non trouvée.'
                ], 404);
            }
            
            // Pour les requêtes web, renvoyer une vue d'erreur 404
            abort(404, 'URL courte non trouvée.');
        }

        $shortUrl->increment('click_count');

        return redirect($shortUrl->original_url);
    }

    /**
     * Vérifie la disponibilité d'un code personnalisé
     */
    public function checkCodeAvailability($code): JsonResponse
    {
        $validator = Validator::make(['code' => $code], [
            'code' => [
                'required',
                'alpha_dash',
                'min:3',
                'max:20',
                'unique:short_urls,short_code',
                function ($attribute, $value, $fail) {
                    $reserved = ['api', 'admin', 'dashboard', 'login', 'register'];
                    if (in_array(strtolower($value), $reserved)) {
                        $fail("Ce code est réservé et ne peut pas être utilisé.");
                    }
                },
            ],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'available' => false,
                'message' => $validator->errors()->first('code')
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Ce code est disponible.'
        ]);
    }

    /**
     * Génère un code court unique
     */
    private function generateUniqueCode(int $length = 6): string
    {
        $code = '';
        $maxTries = 10; // Limite de tentatives pour éviter les boucles infinies
        
        do {
            $code = Str::random($length);
            $maxTries--;
        } while (ShortUrl::where('short_code', $code)->exists() && $maxTries > 0);

        if ($maxTries === 0) {
            throw new \RuntimeException('Impossible de générer un code unique après plusieurs tentatives.');
        }

        return $code;
    }

    // GET /api/stats/{short_code}
    public function stats($short_code)
    {
        $shortUrl = ShortUrl::where('short_code', $short_code)->first();

        if (!$shortUrl) {
            return response()->json(['message' => 'Short URL not found.'], 404);
        }

        return response()->json([
            'original_url' => $shortUrl->original_url,
            'short_code' => $shortUrl->short_code,
            'click_count' => $shortUrl->click_count,
            'created_at' => $shortUrl->created_at,
        ]);
    }
}