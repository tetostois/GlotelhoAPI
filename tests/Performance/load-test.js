import http from 'k6/http';
import { check, sleep } from 'k6';
import { Rate } from 'k6/metrics';

// Configuration du test
const BASE_URL = __ENV.BASE_URL || 'http://localhost:8000';
const VUS = __ENV.VUS || 10;
const DURATION = __ENV.DURATION || '30s';

// Métriques personnalisées
const errorRate = new Rate('errors');

// Options de test
export const options = {
  stages: [
    // Montée en charge progressive
    { duration: '30s', target: VUS / 2 },
    // Charge soutenue
    { duration: DURATION, target: VUS },
    // Désescalade
    { duration: '15s', target: 0 },
  ],
  thresholds: {
    http_req_failed: ['rate<0.1'], // Moins de 10% d'erreurs
    http_req_duration: ['p(95)<500'], // 95% des requêtes en moins de 500ms
  },
};

// Fonction pour générer une URL aléatoire
function generateRandomUrl() {
  const randomString = Math.random().toString(36).substring(2, 15);
  return `${BASE_URL}/test-${randomString}`;
}

// Test de création d'URL courte
export function testCreateShortUrl() {
  const url = generateRandomUrl();
  const payload = JSON.stringify({
    url: url,
    expires_in: 30
  });

  const params = {
    headers: {
      'Content-Type': 'application/json',
    },
  };

  const res = http.post(`${BASE_URL}/api/shorten`, payload, params);
  
  const success = check(res, {
    'status is 201': (r) => r.status === 201,
    'response has short_url': (r) => r.json('data.short_url') !== undefined,
  });

  if (!success) {
    errorRate.add(1);
    console.log(`Error creating short URL: ${res.body}`);
  }

  return res.json('data.short_code');
}

// Test de redirection
export function testRedirect(shortCode) {
  const res = http.get(`${BASE_URL}/${shortCode}`, { redirects: 0 });
  
  const success = check(res, {
    'status is 302 or 200': (r) => [200, 302].includes(r.status),
  });

  if (!success) {
    errorRate.add(1);
    console.log(`Error redirecting: ${res.status} - ${res.body}`);
  }
}

// Test de statistiques
export function testStats(shortCode) {
  const res = http.get(`${BASE_URL}/api/stats/${shortCode}`);
  
  const success = check(res, {
    'status is 200': (r) => r.status === 200,
    'response has stats': (r) => r.json('data') !== undefined,
  });

  if (!success) {
    errorRate.add(1);
    console.log(`Error getting stats: ${res.status} - ${res.body}`);
  }
}

// Scénario principal
export default function () {
  // Test de création d'URL courte
  const shortCode = testCreateShortUrl();
  
  // Si la création a réussi, tester la redirection et les stats
  if (shortCode) {
    testRedirect(shortCode);
    testStats(shortCode);
  }
  
  // Pause aléatoire entre les requêtes
  sleep(Math.random() * 2);
}
