<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ShortUrlController extends Controller
{
    // POST /api/shorten
    public function shorten(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);

        // Générer un code unique
        do {
            $shortCode = Str::random(6);
        } while (ShortUrl::where('short_code', $shortCode)->exists());

        $shortUrl = ShortUrl::create([
            'original_url' => $request->url,
            'short_code' => $shortCode,
            'click_count' => 0,
        ]);

        return response()->json([
            'short_url' => url($shortCode),
            'original_url' => $shortUrl->original_url,
            'short_code' => $shortUrl->short_code,
        ]);
    }

    // GET /{short_code}
    public function redirect($short_code)
    {
        $shortUrl = ShortUrl::where('short_code', $short_code)->first();

        if (!$shortUrl) {
            return response()->json(['message' => 'Short URL not found.'], 404);
        }

        $shortUrl->increment('click_count');
        $shortUrl->save();

        return redirect($shortUrl->original_url);
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