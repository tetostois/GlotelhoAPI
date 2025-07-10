# Laravel URL Shortener

## Aperçu

Service de raccourcissement d'URL développé avec Laravel, conçu pour évaluer les compétences en développement backend et en architecture d'application web.

## Fonctionnalités principales

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

## Installation

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
