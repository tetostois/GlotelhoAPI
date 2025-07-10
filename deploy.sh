#!/bin/bash

# Script de déploiement pour Glotelho
# Utilisation: ./deploy.sh [environnement]
# Exemple: ./deploy.sh production

set -e

ENV=${1:-local}
TIMESTAMP=$(date +%Y%m%d%H%M%S)
BACKUP_DIR="./backups/${TIMESTAMP}"

# Fonction pour afficher les messages d'information
info() {
    echo -e "\033[1;34m[INFO] $1\033[0m"
}

# Fonction pour afficher les messages de succès
success() {
    echo -e "\033[1;32m[SUCCESS] $1\033[0m"
}

# Fonction pour afficher les messages d'erreur et quitter
error() {
    echo -e "\033[1;31m[ERROR] $1\033[0m"
    exit 1
}

# Vérifier l'environnement
case "$ENV" in
    local|staging|production)
        info "Démarrage du déploiement pour l'environnement: $ENV"
        ;;
    *)
        error "Environnement non valide. Utilisation: $0 [local|staging|production]"
        ;;
esac

# Créer un dossier de sauvegarde
mkdir -p "$BACKUP_DIR"

# Sauvegarder l'environnement actuel si le fichier .env existe
if [ -f ".env" ]; then
    info "Sauvegarde de la configuration actuelle..."
    cp .env "${BACKUP_DIR}/.env.backup"
    success "Configuration sauvegardée dans ${BACKUP_DIR}/.env.backup"
fi

# Copier le fichier d'environnement approprié
if [ -f ".env.${ENV}" ]; then
    info "Application de la configuration pour ${ENV}..."
    cp ".env.${ENV}" .env
    success "Configuration appliquée"
else
    error "Fichier de configuration .env.${ENV} introuvable"
fi

# Installer les dépendances
info "Installation des dépendances..."
composer install --no-dev --optimize-autoloader

# Générer la clé d'application si elle n'existe pas
if [ ! -f "bootstrap/cache/config.php" ]; then
    info "Génération de la clé d'application..."
    php artisan key:generate --force
fi

# Mettre à jour la base de données
info "Mise à jour de la base de données..."
php artisan migrate --force

# Optimiser l'application
info "Optimisation de l'application..."
php artisan optimize
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Nettoyer le cache
info "Nettoyage du cache..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Définir les permissions
info "Définition des permissions..."
chmod -R 755 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Redémarrer les services si nécessaire
if [ "$ENV" != "local" ]; then
    info "Redémarrage des services..."
    docker-compose down
    docker-compose up -d --build
    
    info "Vérification de l'état des conteneurs..."
    docker-compose ps
fi

success "Déploiement terminé avec succès pour l'environnement: $ENV"
echo ""
echo "=== RÉSUMÉ DU DÉPLOIEMENT ==="
echo "- Environnement: $ENV"
echo "- Date: $(date)"
echo "- Backup: $BACKUP_DIR"
echo "============================"
echo ""
