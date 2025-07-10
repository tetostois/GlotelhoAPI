<?php

namespace Tests\Performance;

use App\Models\ShortUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LoadTest extends TestCase
{
    use RefreshDatabase;

    private const BATCH_SIZE = 100;
    private const TOTAL_REQUESTS = 1000;
    private const CONCURRENT_USERS = 10;

    /** @test */
    public function it_handles_high_load_of_url_creation()
    {
        $this->markTestSkipped('Décommentez cette ligne pour exécuter le test de charge (peut être long)');
        
        $urls = [];
        for ($i = 0; $i < self::TOTAL_REQUESTS; $i++) {
            $urls[] = 'https://example.com/load-test-' . uniqid();
        }
        
        $startTime = microtime(true);
        $successful = 0;
        $failed = 0;
        
        // Exécuter les requêtes par lots pour simuler une charge réelle
        $chunks = array_chunk($urls, self::BATCH_SIZE);
        
        foreach ($chunks as $chunk) {
            $responses = [];
            
            // Exécuter des requêtes en parallèle
            foreach ($chunk as $url) {
                $responses[] = $this->asyncRequest('POST', '/api/shorten', [
                    'url' => $url,
                ]);
                
                // Limiter le nombre de requêtes simultanées
                if (count($responses) >= self::CONCURRENT_USERS) {
                    $this->processResponses($responses, $successful, $failed);
                    $responses = [];
                }
            }
            
            // Traiter les réponses restantes
            if (!empty($responses)) {
                $this->processResponses($responses, $successful, $failed);
            }
        }
        
        $totalTime = microtime(true) - $startTime;
        $requestsPerSecond = $successful / $totalTime;
        
        // Afficher les résultats
        fwrite(STDERR, "\nRésultats du test de charge :\n");
        fwrite(STDERR, "- Requêtes totales : " . self::TOTAL_REQUESTS . "\n");
        fwrite(STDERR, "- Réussies : $successful\n");
        fwrite(STDERR, "- Échouées : $failed\n");
        fwrite(STDERR, "- Temps total : " . number_format($totalTime, 2) . "s\n");
        fwrite(STDERR, "- Requêtes par seconde : " . number_format($requestsPerSecond, 2) . "\n");
        
        // Vérifier que le taux d'échec est acceptable (< 5%)
        $failureRate = ($failed / self::TOTAL_REQUESTS) * 100;
        $this->assertLessThan(5, $failureRate, "Le taux d'échec est trop élevé : {$failureRate}%");
    }
    
    /**
     * Exécute une requête de manière asynchrone
     */
    private function asyncRequest(string $method, string $uri, array $data = [])
    {
        return $this->json($method, $uri, $data);
    }
    
    /**
     * Traite un lot de réponses et met à jour les compteurs
     */
    private function processResponses(array $responses, int &$successful, int &$failed): void
    {
        foreach ($responses as $response) {
            $status = $response->getStatusCode();
            
            if ($status >= 200 && $status < 300) {
                $successful++;
            } else {
                $failed++;
                fwrite(STDERR, "Erreur: " . $response->getContent() . "\n");
            }
        }
    }
    
    /** @test */
    public function it_handles_concurrent_redirects()
    {
        // Créer 100 URLs courtes
        $shortUrls = ShortUrl::factory()
            ->count(100)
            ->create()
            ->pluck('short_code')
            ->toArray();
        
        $start = microtime(true);
        $total = 1000;
        $successful = 0;
        
        // Simuler des redirections concurrentes
        for ($i = 0; $i < $total; $i++) {
            $code = $shortUrls[array_rand($shortUrls)];
            $response = $this->get("/$code");
            
            if ($response->isRedirect()) {
                $successful++;
            }
            
            // Afficher la progression
            if (($i + 1) % 100 === 0) {
                fwrite(STDERR, "Traitement de $i requêtes...\n");
            }
        }
        
        $totalTime = microtime(true) - $start;
        $rps = $total / $totalTime;
        
        fwrite(STDERR, "\nTest de redirection :\n");
        fwrite(STDERR, "- Redirections réussies : $successful/$total\n");
        fwrite(STDERR, "- Temps total : " . number_format($totalTime, 2) . "s\n");
        fwrite(STDERR, "- Requêtes par seconde : " . number_format($rps, 2) . "\n");
        
        $this->assertEquals($total, $successful, "Certaines redirections ont échoué");
    }
}
