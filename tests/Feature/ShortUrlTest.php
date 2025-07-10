<?php

namespace Tests\Feature;

use App\Models\ShortUrl;
use App\Models\Click;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class, RefreshDatabase::class);

// Fonction utilitaire pour simuler un user agent
function getUserAgent()
{
    $userAgents = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 14_6 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/14.0.3 Safari/605.1.15',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:89.0) Gecko/20100101 Firefox/89.0',
    ];
    
    return $userAgents[array_rand($userAgents)];
}

class ShortUrlTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_shortens_a_url()
    {
        // Test avec une URL simple
        $response = $this->postJson('/api/shorten', [
            'url' => 'https://example.com',
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
                    'expires_at',
                ]
            ]);

        // Vérifier que l'URL est bien enregistrée en base de données
        $this->assertDatabaseHas('short_urls', [
            'original_url' => 'https://example.com',
            'is_custom' => false,
        ]);

        // Test avec un code personnalisé
        $response = $this->postJson('/api/shorten', [
            'url' => 'https://example.com/custom',
            'custom_code' => 'my-custom-code',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'short_code' => 'my-custom-code',
                    'is_custom' => true,
                ]
            ]);

        // Test avec expiration
        $response = $this->postJson('/api/shorten', [
            'url' => 'https://example.com/expires',
            'expires_in' => 30,
        ]);

        $response->assertStatus(201);
        $this->assertNotNull($response->json('data.expires_at'));
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
            'User-Agent' => getUserAgent(),
            'Referer' => 'https://google.com',
        ])->get('/abc123');
        
        $response->assertRedirect('https://example.com');
        
        // Vérifier que le clic a été enregistré
        $this->assertDatabaseHas('clicks', [
            'short_url_id' => $shortUrl->id,
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
        // Code déjà pris
        ShortUrl::factory()->create(['short_code' => 'taken']);
        $response = $this->getJson('/api/check/taken');
        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'available' => false,
                'message' => 'Ce code est déjà utilisé.'
            ]);

        // Code disponible
        $response = $this->getJson('/api/check/available');
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'available' => true
            ]);

        // Code réservé
        $response = $this->getJson('/api/check/api');
        $response->assertStatus(409)
            ->assertJson([
                'success' => false,
                'available' => false
            ]);
    }

    /** @test */
    public function it_redirects_to_original_url()
    {
        // Test de redirection simple
        $url = ShortUrl::factory()->create([
            'short_code' => 'abc123',
            'original_url' => 'https://example.com',
        ]);

        $response = $this->withHeaders([
            'User-Agent' => getUserAgent(),
            'Referer' => 'https://google.com',
        ])->get('/abc123');
        
        $response->assertRedirect('https://example.com');
        
        // Vérifier que le clic a été enregistré
        $this->assertDatabaseHas('clicks', [
            'short_url_id' => $url->id,
        ]);

        // Test avec une URL expirée
        $expiredUrl = ShortUrl::factory()->create([
            'short_code' => 'expired',
            'original_url' => 'https://example.com/expired',
            'expires_at' => now()->subDay(),
        ]);

        $response = $this->get('/expired');
        $response->assertStatus(410);
    }

    /** @test */
    public function it_handles_invalid_short_codes()
    {
        // Code court inexistant
        $response = $this->get('/nonexistent');
        $response->assertStatus(404);

        // Code court invalide
        $response = $this->get('/a!b@c#');
        $response->assertStatus(404);

        // Code court trop court
        $response = $this->get('/ab');
        $response->assertStatus(404);
    }
}