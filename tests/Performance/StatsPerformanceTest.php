<?php

namespace Tests\Performance;

use App\Models\Click;
use App\Models\ShortUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StatsPerformanceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_handles_high_volume_of_clicks()
    {
        // Désactiver les événements pour accélérer la création des modèles
        ShortUrl::unsetEventDispatcher();
        
        // Créer une URL courte
        $shortUrl = ShortUrl::factory()->create([
            'short_code' => 'perftest',
            'original_url' => 'https://example.com/performance-test',
        ]);

        $start = microtime(true);
        
        // Simuler 1000 clics
        $clicks = Click::factory()->count(1000)->make([
            'short_url_id' => $shortUrl->id,
        ]);n
        
        // Insérer les clics par lots pour de meilleures performances
        $chunks = $clicks->chunk(200);
        foreach ($chunks as $chunk) {
            Click::insert($chunk->toArray());
        }
        
        $insertTime = microtime(true) - $start;
        
        // Tester la récupération des statistiques
        $start = microtime(true);
        
        $response = $this->getJson("/api/stats/perftest");
        
        $queryTime = microtime(true) - $start;
        
        $response->assertStatus(200);
        
        // Afficher les métriques de performance
        $this->assertLessThan(1.0, $queryTime, "La requête de statistiques est trop lente: {$queryTime}s");
        
        fwrite(STDERR, "\nPerformance Metrics:\n");
        fwrite(STDERR, "- Insertion de 1000 clics: " . number_format($insertTime, 4) . "s\n");
        fwrite(STDERR, "- Récupération des statistiques: " . number_format($queryTime, 4) . "s\n");
        
        // Vérifier les statistiques
        $data = $response->json('data');
        $this->assertEquals(1000, $data['click_count']);
    }
    
    /** @test */
    public function it_handles_multiple_concurrent_requests()
    {
        // Créer 10 URLs courtes
        $shortUrls = ShortUrl::factory()->count(10)->create();
        
        $start = microtime(true);
        
        // Simuler 100 requêtes concurrentes
        $responses = [];
        for ($i = 0; $i < 100; $i++) {
            $url = $shortUrls->random();
            $responses[] = $this->getJson("/api/stats/{$url->short_code}");
        }
        
        $totalTime = microtime(true) - $start;
        
        // Vérifier que toutes les réponses sont valides
        foreach ($responses as $response) {
            $response->assertStatus(200);
        }
        
        fwrite(STDERR, "\nConcurrent Requests:\n");
        fwrite(STDERR, "- 100 requêtes concurrentes: " . number_format($totalTime, 4) . "s\n");
        fwrite(STDERR, "- Moyenne par requête: " . number_format($totalTime / 100, 4) . "s\n");
    }
}
