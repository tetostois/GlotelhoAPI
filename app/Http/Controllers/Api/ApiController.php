<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="Glotelho API Documentation",
 *     description="API de raccourcissement d'URL avec suivi des statistiques",
 *     @OA\Contact(
 *         email="support@glotelho.com"
 *     ),
 *     @OA\License(
 *         name="Apache 2.0",
 *         url="http://www.apache.org/licenses/LICENSE-2.0.html"
 *     )
 * )
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 * @OA\Tag(
 *     name="URLs",
 *     description="Opérations sur les URLs raccourcies"
 * )
 * @OA\Schema(
 *     schema="ShortUrl",
 *     type="object",
 *     @OA\Property(property="short_url", type="string", example="http://localhost/abc123"),
 *     @OA\Property(property="original_url", type="string", example="https://exemple.com/very/long/url"),
 *     @OA\Property(property="short_code", type="string", example="abc123"),
 *     @OA\Property(property="is_custom", type="boolean", example=false),
 *     @OA\Property(property="expires_at", type="string", format="date-time", nullable=true, example="2025-12-31T23:59:59Z"),
 *     @OA\Property(property="click_count", type="integer", example=42)
 * )
 * @OA\Schema(
 *     schema="Error",
 *     type="object",
 *     @OA\Property(property="success", type="boolean", example=false),
 *     @OA\Property(property="message", type="string", example="Message d'erreur détaillé")
 * )
 */
class ApiController extends Controller
{
    // La classe de base pour les contrôleurs d'API
}
