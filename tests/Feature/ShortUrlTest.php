<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\ShortUrl;

class ShortUrlTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_shorten_a_url()
    {
        $response = $this->postJson('/api/shorten', [
            'url' => 'https://laravel.com',
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure([
                     'success',
                     'message',
                     'data' => [
                         'short_url',
                         'original_url',
                         'short_code',
                         'is_custom',
                     ]
                 ]);
    }
    
    /** @test */
    public function it_tracks_clicks_with_details()
    {
        // Créer une URL courte
        $shortUrl = ShortUrl::create([
            'original_url' => 'https://example.com',
            'short_code' => 'abc123',
        ]);
        
        // Simuler une requête avec un user agent
        $response = $this->withHeaders([
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Referer' => 'https://google.com',
        ])->get('/abc123');
        
        $response->assertRedirect('https://example.com');
        
        // Vérifier que le clic a été enregistré
        $this->assertDatabaseHas('clicks', [
            'short_url_id' => $shortUrl->id,
            'browser' => 'Google Chrome',
            'platform' => 'Windows',
            'referer' => 'https://google.com',
        ]);
    }
    
    /** @test */
    public function it_shows_detailed_statistics()
    {
        // Créer une URL courte avec des clics
        $shortUrl = ShortUrl::create([
            'original_url' => 'https://example.com',
            'short_code' => 'test123',
            'click_count' => 5,
        ]);
        
        // Ajouter des clics de test
        $shortUrl->clicks()->createMany([
            ['browser' => 'Chrome', 'platform' => 'Windows', 'referer' => 'https://google.com'],
            ['browser' => 'Chrome', 'platform' => 'Windows', 'referer' => 'https://google.com'],
            ['browser' => 'Firefox', 'platform' => 'Mac', 'referer' => 'https://twitter.com'],
            ['browser' => 'Safari', 'platform' => 'iOS', 'referer' => null],
            ['browser' => 'Chrome', 'platform' => 'Android', 'referer' => 'https://facebook.com'],
        ]);
        
        $response = $this->getJson('/api/stats/test123');
        
        $response->assertStatus(200)
                 ->assertJsonStructure([
                     'success',
                     'data' => [
                         'original_url',
                         'short_code',
                         'click_count',
                         'browsers',
                         'platforms',
                         'top_referrers',
                         'clicks_by_day',
                         'first_click',
                         'last_click',
                     ]
                 ]);
                 
        // Vérifier les statistiques des navigateurs
        $response->assertJsonFragment([
            'browser' => 'Chrome',
            'count' => 3,
        ]);
    }

    /** @test */
    public function it_can_use_custom_code()
    {
        $response = $this->postJson('/api/shorten', [
            'url' => 'https://laravel.com',
            'custom_code' => 'laravel-news'
        ]);

        $response->assertStatus(201)
                 ->assertJson([
                     'success' => true,
                     'data' => [
                         'short_code' => 'laravel-news',
                         'is_custom' => true,
                     ]
                 ]);
    }

    /** @test */
    public function it_prevents_duplicate_urls()
    {
        // Première requête
        $this->postJson('/api/shorten', ['url' => 'https://laravel.com']);
        
        // Deuxième requête avec la même URL
        $response = $this->postJson('/api/shorten', ['url' => 'https://laravel.com']);
        
        $response->assertStatus(200)
                 ->assertJson([
                     'success' => true,
                     'message' => 'URL déjà raccourcie'
                 ]);
    }

    /** @test */
    public function it_validates_url_format()
    {
        $response = $this->postJson('/api/shorten', [
            'url' => 'not-a-valid-url',
        ]);

        $response->assertStatus(422)
                 ->assertJsonValidationErrors(['url']);
    }

    /** @test */
    public function it_checks_custom_code_availability()
    {
        // Vérifier un code disponible
        $response = $this->getJson('/api/check/available-code');
        $response->assertStatus(200)
                 ->assertJson([
                     'available' => true,
                     'message' => 'Ce code est disponible.'
                 ]);

        // Créer une URL avec un code personnalisé
        ShortUrl::create([
            'original_url' => 'https://example.com',
            'short_code' => 'taken-code',
            'is_custom' => true,
        ]);

        // Vérifier un code déjà pris
        $response = $this->getJson('/api/check/taken-code');
        $response->assertStatus(200)
                 ->assertJson([
                     'available' => false,
                 ]);
    }

    /** @test */
    public function it_redirects_short_code()
    {
        $shortUrl = ShortUrl::create([
            'original_url' => 'https://laravel.com',
            'short_code' => 'laravel',
            'click_count' => 0,
        ]);

        $response = $this->get('/laravel');
        $response->assertRedirect('https://laravel.com');
        
        // Vérifier que le compteur a été incrémenté
        $this->assertEquals(1, $shortUrl->fresh()->click_count);
    }

    /** @test */
    public function it_returns_404_for_nonexistent_short_code()
    {
        $response = $this->get('/nonexistent');
        $response->assertStatus(404);
    }
}