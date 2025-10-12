# 🚀 Déploiement de Présentation - BINAJIA

Ce guide explique comment déployer l'application BINAJIA pour une présentation/démonstration.

## 📋 Prérequis

- PHP 8.1+
- MySQL 8.0+
- Composer
- Node.js & npm
- Symfony CLI (optionnel)

## ⚡ Déploiement Rapide

### 1. Installation des dépendances

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
```

### 2. Configuration de l'environnement

Copiez le fichier de configuration de production :

```bash
cp .env.production .env.local
```

Modifiez `.env.local` selon vos besoins (base de données, domaine, etc.).

### 3. Configuration de la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate
```

### 4. Création des données de démonstration

```bash
php bin/console app:create-demo-data
```

### 5. Nettoyage et optimisation

```bash
php bin/console cache:clear
php bin/console cache:warmup
chmod -R 755 var/ public/
```

## 🛠️ Script de déploiement automatique

Utilisez le script fourni :

```bash
chmod +x deploy.sh
./deploy.sh production
```

## 📁 Structure du projet

```
binajia/
├── .env.production      # Configuration de production
├── deploy.sh           # Script de déploiement
├── src/
│   ├── Command/
│   │   └── CreateDemoDataCommand.php  # Commande de données de démo
│   └── Entity/
│       └── Culturalcontent.php        # Entité principale
├── templates/
│   └── home/
│       └── index_c.html.twig          # Page d'accueil moderne
└── public/
    └── media/                         # Dossier des médias
```

## 🎭 Fonctionnalités de présentation

### ✅ Page d'accueil dynamique
- Affichage automatique des lieux culturels depuis la base de données
- Design moderne avec animations fluides
- Interface responsive (mobile/desktop)

### ✅ Administration intégrée
- Gestion des contenus culturels via interface web
- Upload d'images automatique
- Catégorisation par type (lieu, photo, vidéo, article)

### ✅ Données de démonstration
- Lieux culturels du Bénin et Nigéria
- Interface fonctionnelle même sans données personnalisées

## 🌐 URLs importantes

- **Page d'accueil** : `/`
- **Administration** : `/admin`
- **Liste des lieux** : `/places`

## 🔧 Configuration pour différents environnements

### Développement local
```env
APP_ENV=dev
APP_DEBUG=1
DATABASE_URL=mysql://user:pass@localhost:3306/binajia_dev
```

### Démonstration/Production
```env
APP_ENV=prod
APP_DEBUG=0
DATABASE_URL=mysql://user:pass@localhost:3306/binajia_demo
CORS_ALLOW_ORIGIN=*
```

## 📊 Données incluses

### Lieux culturels de démonstration :
1. **Porte du Non-Retour** (Bénin)
2. **Musée National de Lagos** (Nigéria)
3. **Palais Royal d'Abomey** (Bénin)
4. **Centre Culturel National Nigérian** (Nigéria)

## 🚨 Notes importantes

- **Sécurité** : Le fichier `.env.local` n'est pas commité (présent dans `.gitignore`)
- **Performances** : Cache optimisé pour la production
- **Assets** : CSS/JS compilés et minifiés
- **Backup** : Un backup est créé automatiquement lors du déploiement

## 🔍 Vérification du déploiement

1. Vérifiez que la page d'accueil s'affiche correctement
2. Testez l'administration (`/admin`)
3. Vérifiez que les lieux culturels s'affichent
4. Testez la responsivité sur mobile

## 🆘 Support

En cas de problème :
1. Consultez les logs : `var/log/prod.log`
2. Vérifiez la configuration : `php bin/console debug:config`
3. Testez la connectivité DB : `php bin/console doctrine:query:sql "SELECT 1"`

---

**🎉 Votre application BINAJIA est prête pour la présentation !**
