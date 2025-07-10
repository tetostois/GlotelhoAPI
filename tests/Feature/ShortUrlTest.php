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