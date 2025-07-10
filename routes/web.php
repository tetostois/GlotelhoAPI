<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ShortUrlController;

Route::get('/', function () {
    return view('welcome');
});


// Redirection par short code (accessible en GET /{short_code})
Route::get('/{short_code}', [ShortUrlController::class, 'redirect'])->where('short_code', '[A-Za-z0-9]+');