# Glotelho - Service de Raccourcissement d'URL

[![Docker](https://img.shields.io/badge/Docker-Supported-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)
[![Laravel](https://img.shields.io/badge/Laravel-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![PHP 8.2+](https://img.shields.io/badge/PHP-8.2+-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![License](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)

## Aperçu

Service de raccourcissement d'URL développé avec Laravel, conçu pour évaluer les compétences en développement backend et en architecture d'application web.

## 🚀 Fonctionnalités principales

### 1. Raccourcissement d'URL avancé
- Génération de codes courts uniques
- Personnalisation des codes courts
- Expiration des URLs configurable
- Limitation du nombre de clics
- Protection par mot de passe

### 2. Redirection intelligente
- Redirection 301/302 personnalisable
- Détection des appareils (mobile/desktop)
- Géolocalisation
- Suivi des référents

### 3. Statistiques détaillées
- Nombre total de clics
- Géolocalisation des visiteurs
- Navigateurs et systèmes d'exploitation
- Détection des robots
- Graphiques d'analyse
- Export des données (CSV/JSON)

### 4. Sécurité renforcée
- Protection contre le spam
- Limitation du taux de requêtes
- Authentification utilisateur
- Rôles et permissions
- Journalisation des activités

### 5. API RESTful
- Documentation Swagger/OpenAPI
- Authentification par jeton
- Limitation des requêtes
- Versioning
- Réponses JSON standardisées

## 🛠 Configuration des environnements

### Variables d'environnement clés

| Variable | Description | Valeur par défaut |
|----------|-------------|-------------------|
| `APP_ENV` | Environnement de l'application | `local` |
| `APP_DEBUG` | Mode débogage | `true` en développement |
| `APP_URL` | URL de base de l'application | `http://localhost` |
| `DB_*` | Configuration de la base de données | - |
| `REDIS_HOST` | Hôte Redis | `redis` |
| `MAIL_*` | Configuration d'envoi d'emails | - |
| `QUEUE_CONNECTION` | Gestionnaire de file d'attente | `database` |

### Fichiers d'environnement

- `.env` - Configuration de développement local
- `.env.staging` - Configuration pour l'environnement de préproduction
- `.env.production` - Configuration pour la production
- `.env.testing` - Configuration pour les tests automatisés

## 🚀 Déploiement

### Déploiement avec Docker

1. Copier le fichier d'environnement de production :
   ```bash
   cp .env.production.example .env.production
   ```

2. Mettre à jour les variables d'environnement :
   ```bash
   nano .env.production
   ```

3. Lancer le script de déploiement :
   ```bash
   chmod +x deploy.sh
   ./deploy.sh production
   ```

### Déploiement manuel

1. Configurer le serveur web (Nginx/Apache)
2. Configurer PHP-FPM
3. Configurer la base de données
4. Configurer Redis
5. Configurer les tâches planifiées (cron jobs)
6. Configurer la surveillance et les logs

### Mises à jour

Pour mettre à jour l'application :

```bash
git pull origin main
docker-compose exec app composer install --no-dev --optimize-autoloader
docker-compose exec app php artisan migrate --force
docker-compose exec app php artisan optimize
docker-compose restart app
```

## 🧪 Tests

### Exécuter les tests unitaires

```bash
docker-compose exec app php artisan test
```

### Tests de performance

```bash
# Installer k6 (https://k6.io/docs/getting-started/installation/)
k6 run tests/Performance/load-test.js
```

## 📊 Monitoring

L'application inclut des tableaux de bord pour le monitoring :

- Horizon : http://votre-domaine/horizon
- Telescope : http://votre-domaine/telescope
- Logs : `storage/logs/laravel.log`

## 🔒 Sécurité

### Bonnes pratiques de sécurité

1. Toujours utiliser HTTPS en production
2. Mettre à jour régulièrement les dépendances
3. Ne pas exposer les fichiers .env
4. Utiliser des mots de passe forts
5. Configurer correctement les permissions des fichiers

### Audit de sécurité

Pour effectuer un audit de sécurité :

```bash
docker-compose exec app php artisan security:check
```

## 🤝 Contribution

1. Forkez le projet
2. Créez une branche (`git checkout -b feature/AmazingFeature`)
3. Committez vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Poussez vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrez une Pull Request

## 📄 Licence

Distribué sous la licence MIT. Voir `LICENSE` pour plus d'informations.

## 📞 Contact

Votre Nom - [@votretwitter](https://twitter.com/votretwitter) - email@example.com

Lien du projet : [https://github.com/votre-utilisateur/glotelho](https://github.com/votre-utilisateur/glotelho)

## 🙏 Remerciements

- [Laravel](https://laravel.com/)
- [Docker](https://www.docker.com/)
- [k6](https://k6.io/)
- [Tous les contributeurs](https://github.com/votre-utilisateur/glotelho/contributors)

### 1. Raccourcissement d'URL
- Accepte une URL longue et génère un code court unique
- Stocke le mapping dans la base de données
- Retourne l'URL raccourcie

### 2. Redirection d'URL
- Redirige vers l'URL d'origine lors de l'accès au code court
- Gestion des erreurs pour les codes invalides

### 3. Statistiques de base
- Compteur de clics pour chaque URL
- Horodatage de création de l'URL

## Prérequis

- Docker 20.10+
- Docker Compose 2.0+
- PHP 8.2+
- Composer 2.0+
- Node.js 16+ (pour les assets frontend)
- MySQL 8.0+ ou MariaDB 10.4+
- Redis 6.0+ (recommandé pour le cache et les files d'attente)

## Installation avec Docker (Recommandé)

### 1. Cloner le dépôt

```bash
git clone https://github.com/votre-utilisateur/glotelho.git
cd glotelho
```

### 2. Configurer l'environnement

Copier le fichier d'environnement d'exemple et l'adapter :

```bash
cp .env.example .env
```

### 3. Démarrer les conteneurs

```bash
docker-compose up -d --build
```

### 4. Installer les dépendances

```bash
docker-compose exec app composer install
docker-compose exec app npm install
docker-compose exec app npm run build
```

### 5. Configurer l'application

Générer la clé d'application :
```bash
docker-compose exec app php artisan key:generate
```

### 6. Exécuter les migrations

```bash
docker-compose exec app php artisan migrate --seed
```

### 7. Accéder à l'application

- Application : http://localhost
- PHPMyAdmin : http://localhost:8080
- MailHog (pour les emails) : http://localhost:8025

## Installation manuelle (sans Docker)

1. Cloner le dépôt :
   ```bash
   git clone https://github.com/votre-utilisateur/url-shortener.git
   cd url-shortener
   ```

2. Installer les dépendances :
   ```bash
   composer install
   ```

3. Configurer l'application :
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. Configurer la base de données dans `.env`

5. Exécuter les migrations :
   ```bash
   php artisan migrate
   ```

6. Démarrer le serveur :
   ```bash
   php artisan serve
   ```

## API Endpoints

### 1. Raccourcir une URL

```
POST /api/shorten
```

**Corps de la requête** :
```json
{
    "url": "https://exemple.com/very/long/url",
    "custom_code": "mon-code",  // Optionnel
    "expires_in": 30,           // Optionnel - Nombre de jours avant expiration (1-365)
    "expires_at": "2025-12-31"  // Optionnel - Date d'expiration spécifique (doit être dans le futur)
}
```

**Réponse** :
```json
{
    "success": true,
    "message": "URL raccourcie avec succès",
    "data": {
        "short_url": "http://localhost/abc123",
        "original_url": "https://exemple.com/very/long/url",
        "short_code": "abc123",
        "is_custom": false,
        "expires_at": "2025-08-10 00:00:00"
    }
}```

> **Note** : Si ni `expires_in` ni `expires_at` n'est spécifié, l'URL n'expirera pas.
```

### 2. Rediriger vers l'URL d'origine

```
GET /{short_code}
```

Redirige vers l'URL d'origine et incrémente le compteur de clics.

**Réponses possibles :**
- `302 Found` - Redirection vers l'URL d'origine
- `404 Not Found` - Code court introuvable
- `410 Gone` - L'URL a expiré et n'est plus disponible

### 3. Obtenir les statistiques d'une URL

```
GET /api/stats/{short_code}
```

**Réponse** (simplifiée) :
```json
{
    "success": true,
    "data": {
        "original_url": "https://exemple.com/very/long/url",
        "short_code": "abc123",
        "click_count": 5,
        "is_custom": false,
        "created_at": "2025-07-10T15:30:00.000000Z",
        "is_expired": false,
        "expires_at": "2025-08-10T00:00:00.000000Z",
        "days_remaining": 30,
        "clicks_by_day": [
            {
                "date": "2025-07-10",
                "count": 5
            }
        ],
        "browsers": [
            {
                "browser": "Google Chrome",
                "count": 3
            },
            {
                "browser": "Mozilla Firefox",
                "count": 1
            },
            {
                "browser": "Safari",
                "count": 1
            }
        ],
        "platforms": [
            {
                "platform": "Windows",
                "count": 3
            },
            {
                "platform": "Mac OS",
                "count": 1
            },
            {
                "platform": "iOS",
                "count": 1
            }
        ],
        "top_referrers": [
            {
                "referer": "https://google.com",
                "count": 2
            },
            {
                "referer": "https://twitter.com",
                "count": 1
            },
            {
                "referer": "https://facebook.com",
                "count": 1
            }
        ],
        "first_click": "2025-07-10T10:00:00.000000Z",
        "last_click": "2025-07-10T15:30:00.000000Z"
    }
}
```

### 4. Vérifier la disponibilité d'un code personnalisé

```
GET /api/check/{code}
```

**Réponse** :
```json
{
    "available": true,
    "message": "Ce code est disponible."
}
```

## Gestion des erreurs

### URL invalide
```json
{
    "success": false,
    "message": "L'URL fournie est invalide",
    "errors": {
        "url": ["Le format de l'URL est invalide"]
    }
}
```

### Code court non trouvé
```json
{
    "success": false,
    "message": "URL courte non trouvée"
}
```

## Structure de la base de données

### Table `short_urls`
- `id` - Identifiant unique
- `original_url` - URL d'origine
- `short_code` - Code court unique
- `click_count` - Nombre de redirections
- `is_custom` - Si le code a été personnalisé
- `created_at` - Date de création
- `updated_at` - Date de mise à jour

## Améliorations futures

- [ ] Expiration des URLs
- [ ] Authentification des utilisateurs
- [ ] Limitation de débit (rate limiting)
- [ ] Interface d'administration
- [ ] Documentation Swagger/OpenAPI

## Licence

Ce projet est sous licence [MIT](LICENSE).
