#!/bin/bash

# Script de déploiement de présentation pour BINAJIA
# Utilisation: ./deploy.sh [environment]

set -e

ENVIRONMENT=${1:-production}
PROJECT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
BACKUP_DIR="$PROJECT_DIR/backups/$(date +%Y%m%d_%H%M%S)"

echo "🚀 Déploiement BINAJIA - Environnement: $ENVIRONMENT"

# 1. Créer un backup
echo "📦 Création du backup..."
mkdir -p "$PROJECT_DIR/backups"
cp -r "$PROJECT_DIR" "$BACKUP_DIR"

# 2. Installer les dépendances
echo "📦 Installation des dépendances..."
composer install --no-dev --optimize-autoloader
npm install
npm run build

# 3. Configuration de l'environnement
echo "⚙️ Configuration de l'environnement..."
if [ "$ENVIRONMENT" = "production" ]; then
    cp .env.production .env.local
    composer dump-env prod
fi

# 4. Mettre à jour la base de données
echo "🗄️ Mise à jour de la base de données..."
php bin/console doctrine:migrations:migrate --no-interaction

# 5. Vider le cache
echo "🧹 Nettoyage du cache..."
php bin/console cache:clear
php bin/console cache:warmup

# 6. Configurer les permissions
echo "🔐 Configuration des permissions..."
chmod -R 755 var/
chmod -R 755 public/

# 7. Créer des données de démonstration si nécessaire
echo "🎭 Vérification des données de démonstration..."
php bin/console app:create-demo-data

echo "✅ Déploiement terminé avec succès!"
echo "🌐 L'application est prête pour la présentation"
echo "📁 Backup créé: $BACKUP_DIR"
