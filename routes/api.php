<?php
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortUrlController;

Route::get('/test', function () {
    return 'API OK';
});
Route::post('/shorten', [ShortUrlController::class, 'shorten']);
Route::get('/stats/{short_code}', [ShortUrlController::class, 'stats']);