<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ShortUrl;

class ShortUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_shorten_url()
    {
        $response = $this->withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->postJson('/api/shorten', [
            'url' => 'https://laravel.com',
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'short_url',
                     'original_url',
                     'short_code',
                 ]);
    }

    public function test_redirect_short_code()
    {
        $shortUrl = ShortUrl::create([
            'original_url' => 'https://laravel.com',
            'short_code' => 'laravl',
            'click_count' => 0,
        ]);

        $response = $this->get('/laravl');
        $response->assertRedirect('https://laravel.com');
    }
}