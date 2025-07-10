<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortUrlController;

// Route de test
Route::get('/test', function () {
    return response()->json(['status' => 'API OK']);
});

// Raccourcir une URL
Route::post('/shorten', [ShortUrlController::class, 'shorten']);

// Vérifier la disponibilité d'un code personnalisé
Route::get('/check/{code}', [ShortUrlController::class, 'checkCodeAvailability']);

// Obtenir les statistiques d'une URL courte
Route::get('/stats/{short_code}', [ShortUrlController::class, 'stats']);