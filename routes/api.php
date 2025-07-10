<?php
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortUrlController;

Route::middleware('api')->group(function () {
    // Création d'un short URL
    Route::post('/shorten', [ShortUrlController::class, 'shorten']);

    // Statistiques d'un short URL
    Route::get('/stats/{short_code}', [ShortUrlController::class, 'stats']);
});