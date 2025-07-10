<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Api\ApiController;
use OpenApi\Annotations as OA;

use App\Models\ShortUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;

/**
 * @OA\Tag(name="URLs")
 */
class ShortUrlController extends ApiController
{
    // POST /api/shorten
    /**
     * Raccourcir une URL
     *
     * @OA\Post(
     *     path="/api/shorten",
     *     summary="Raccourcir une URL",
     *     tags={"URLs"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"url"},
     *             @OA\Property(property="url", type="string", format="url", example="https://exemple.com/very/long/url"),
     *             @OA\Property(property="custom_code", type="string", minLength=3, maxLength=20, example="mon-site"),
     *             @OA\Property(property="expires_in", type="integer", description="Nombre de jours avant expiration", minimum=1, maximum=365, example=30),
     *             @OA\Property(property="expires_at", type="string", format="date-time", description="Date d'expiration spécifique", example="2025-12-31T23:59:59Z")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="URL raccourcie avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="URL raccourcie avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 ref="#/components/schemas/ShortUrl"
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Erreur de validation",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
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
            'expires_in' => ['nullable', 'integer', 'min:1', 'max:365'], // Nombre de jours avant expiration
            'expires_at' => ['nullable', 'date', 'after:now'], // Date d'expiration spécifique
        ], [
            'url.required' => 'Une URL est requise',
            'url.url' => 'Le format de l\'URL est invalide',
            'custom_code.alpha_dash' => 'Le code personnalisé ne peut contenir que des lettres, des chiffres, des tirets et des underscores',
            'custom_code.min' => 'Le code personnalisé doit contenir au moins :min caractères',
            'custom_code.max' => 'Le code personnalisé ne peut pas dépasser :max caractères',
            'custom_code.unique' => 'Ce code est déjà utilisé',
            'expires_in.integer' => 'La durée d\'expiration doit être un nombre de jours.',
            'expires_in.min' => 'La durée d\'expiration minimale est de 1 jour.',
            'expires_in.max' => 'La durée d\'expiration maximale est de 365 jours.',
            'expires_at.date' => 'La date d\'expiration n\'est pas valide.',
            'expires_at.after' => 'La date d\'expiration doit être dans le futur.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422);
        }

        // Vérifier si l'URL existe déjà (optionnel)
        if ($existingUrl = ShortUrl::where('original_url', $request->url)
            ->where(function($query) {
                $query->whereNull('expires_at')
                      ->orWhere('expires_at', '>', now());
            })
            ->first()) {
            return response()->json([
                'success' => true,
                'message' => 'URL déjà raccourcie',
                'data' => [
                    'short_url' => url($existingUrl->short_code),
                    'original_url' => $existingUrl->original_url,
                    'short_code' => $existingUrl->short_code,
                    'is_custom' => $existingUrl->is_custom,
                    'expires_at' => $existingUrl->expires_at?->toDateTimeString(),
                ]
            ]);
        }

        // Utiliser le code personnalisé s'il est fourni, sinon en générer un
        $shortCode = $request->custom_code ?? $this->generateUniqueCode();

        // Calculer la date d'expiration si nécessaire
        $expiresAt = null;
        if ($request->expires_at) {
            $expiresAt = now()->parse($request->expires_at);
        } elseif ($request->expires_in) {
            $expiresAt = now()->addDays($request->expires_in);
        }

        $shortUrl = ShortUrl::create([
            'original_url' => $request->url,
            'short_code' => $shortCode,
            'click_count' => 0,
            'is_custom' => (bool)$request->custom_code,
            'expires_at' => $expiresAt,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'URL raccourcie avec succès',
            'data' => [
                'short_url' => url($shortCode),
                'original_url' => $shortUrl->original_url,
                'short_code' => $shortUrl->short_code,
                'is_custom' => (bool)$request->custom_code,
                'expires_at' => $shortUrl->expires_at?->toDateTimeString(),
            ]
        ], 201); // 201 Created
    }

    /**
     * Redirige vers l'URL originale
     */
    /**
     * Rediriger vers l'URL d'origine
     *
     * @OA\Get(
     *     path="/{short_code}",
     *     summary="Rediriger vers l'URL d'origine",
     *     tags={"URLs"},
     *     @OA\Parameter(
     *         name="short_code",
     *         in="path",
     *         required=true,
     *         description="Le code court de l'URL",
     *         @OA\Schema(type="string", example="abc123")
     *     ),
     *     @OA\Response(
     *         response=302,
     *         description="Redirection vers l'URL d'origine"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="URL non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     ),
     *     @OA\Response(
     *         response=410,
     *         description="URL expirée",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
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

        // Vérifier si l'URL a expiré
        if ($shortUrl->hasExpired()) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cette URL a expiré et n\'est plus disponible.'
                ], 410); // 410 Gone
            }
            
            abort(410, 'Cette URL a expiré et n\'est plus disponible.');
        }

        // Enregistrer le clic avec toutes les informations de suivi
        $shortUrl->registerClick();

        return redirect($shortUrl->original_url);
    }

    /**
     * Vérifie la disponibilité d'un code personnalisé
     */
    /**
     * Vérifier la disponibilité d'un code personnalisé
     *
     * @OA\Get(
     *     path="/api/check/{code}",
     *     summary="Vérifier la disponibilité d'un code personnalisé",
     *     tags={"URLs"},
     *     @OA\Parameter(
     *         name="code",
     *         in="path",
     *         required=true,
     *         description="Le code personnalisé à vérifier",
     *         @OA\Schema(type="string", example="mon-site")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Code disponible",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="available", type="boolean", example=true)
     *         )
     *     ),
     *     @OA\Response(
     *         response=409,
     *         description="Code déjà utilisé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="available", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Ce code est déjà utilisé.")
     *         )
     *     )
     * )
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

    /**
     * Affiche les statistiques d'une URL courte
     *
     * @param string $short_code Le code court de l'URL
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * Obtenir les statistiques d'une URL courte
     *
     * @OA\Get(
     *     path="/api/stats/{short_code}",
     *     summary="Obtenir les statistiques d'une URL courte",
     *     tags={"URLs"},
     *     @OA\Parameter(
     *         name="short_code",
     *         in="path",
     *         required=true,
     *         description="Le code court de l'URL",
     *         @OA\Schema(type="string", example="abc123")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Statistiques de l'URL",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="original_url", type="string"),
     *                 @OA\Property(property="short_code", type="string"),
     *                 @OA\Property(property="click_count", type="integer"),
     *                 @OA\Property(property="is_custom", type="boolean"),
     *                 @OA\Property(property="is_expired", type="boolean"),
     *                 @OA\Property(property="expires_at", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="days_remaining", type="integer", nullable=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="clicks_by_day", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="date", type="string", format="date"),
     *                         @OA\Property(property="count", type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(property="browsers", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="browser", type="string"),
     *                         @OA\Property(property="count", type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(property="platforms", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="platform", type="string"),
     *                         @OA\Property(property="count", type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(property="top_referrers", type="array",
     *                     @OA\Items(type="object",
     *                         @OA\Property(property="referer", type="string"),
     *                         @OA\Property(property="count", type="integer")
     *                     )
     *                 ),
     *                 @OA\Property(property="first_click", type="string", format="date-time", nullable=true),
     *                 @OA\Property(property="last_click", type="string", format="date-time", nullable=true)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="URL non trouvée",
     *         @OA\JsonContent(ref="#/components/schemas/Error")
     *     )
     * )
     */
    public function stats($short_code)
    {
        $shortUrl = ShortUrl::withCount('clicks')
            ->where('short_code', $short_code)
            ->first();

        if (!$shortUrl) {
            return response()->json([
                'success' => false,
                'message' => 'URL courte non trouvée.'
            ], 404);
        }

        // Statistiques de base
        $stats = [
            'original_url' => $shortUrl->original_url,
            'short_code' => $shortUrl->short_code,
            'click_count' => $shortUrl->click_count,
            'is_custom' => $shortUrl->is_custom,
            'is_expired' => $shortUrl->hasExpired(),
            'expires_at' => $shortUrl->expires_at?->toDateTimeString(),
            'days_remaining' => $shortUrl->expires_at ? now()->diffInDays($shortUrl->expires_at, false) : null,
            'created_at' => $shortUrl->created_at,
        ];

        // Statistiques avancées (si des clics existent)
        if ($shortUrl->clicks()->exists()) {
            $clicks = $shortUrl->clicks()
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get();

            $browsers = $shortUrl->clicks()
                ->select('browser', \DB::raw('COUNT(*) as count'))
                ->groupBy('browser')
                ->orderByDesc('count')
                ->get();

            $platforms = $shortUrl->clicks()
                ->select('platform', \DB::raw('COUNT(*) as count'))
                ->groupBy('platform')
                ->orderByDesc('count')
                ->get();

            $referrers = $shortUrl->clicks()
                ->select('referer', \DB::raw('COUNT(*) as count'))
                ->whereNotNull('referer')
                ->groupBy('referer')
                ->orderByDesc('count')
                ->limit(10)
                ->get();

            $stats = array_merge($stats, [
                'clicks_by_day' => $clicks,
                'browsers' => $browsers,
                'platforms' => $platforms,
                'top_referrers' => $referrers,
                'first_click' => $shortUrl->clicks()->orderBy('created_at')->first()->created_at ?? null,
                'last_click' => $shortUrl->clicks()->latest()->first()->created_at ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }
}