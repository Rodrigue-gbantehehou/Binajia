# 🛠️ Guide de résolution des problèmes - BINAJIA

## 📧 **Problèmes d'emails résolus**

### ✅ **Corrections apportées**

1. **Configuration email centralisée**
   - Nouveau service `ConfigurationService` pour gérer les URLs dynamiquement
   - Service `EmailService` mis à jour pour utiliser la configuration centralisée
   - URLs hardcodées remplacées par des URLs dynamiques

2. **Configuration .env corrigée**
   ```env
   # Ancienne configuration (problématique)
   MAILER_DSN="smtp://contact@efasmartimmobilier.com:..."
   
   # Nouvelle configuration (corrigée)
   MAILER_DSN="smtp://contact@binajia.org:votre_mot_de_passe@mail.binajia.org:465?encryption=ssl"
   ```

### 🔧 **Actions à effectuer**

1. **Mettre à jour vos identifiants SMTP**
   ```bash
   # Éditez le fichier .env et remplacez :
   MAIL_USERNAME=contact@binajia.org
   MAIL_PASSWORD=votre_vraie_mot_de_passe
   MAIL_HOST=mail.binajia.org
   ```

2. **Tester la configuration**
   ```bash
   php bin/console app:test-email-card
   ```

3. **Corriger automatiquement les problèmes**
   ```bash
   php bin/console app:fix-email-card
   ```

## 🎴 **Problèmes de cartes résolus**

### ✅ **Corrections apportées**

1. **Gestion améliorée des avatars**
   - Vérification d'existence des fichiers images
   - Gestion des chemins relatifs/absolus
   - Avatar par défaut si image manquante

2. **Service `MembershipCardService` amélioré**
   - Méthode `prepareAvatar()` pour valider les images
   - Meilleure gestion d'erreurs
   - Logs d'erreur pour le debugging

3. **Création automatique des dossiers**
   - `/public/media/cards`
   - `/public/media/receipts`
   - `/public/media/avatars`

### 🔧 **Actions à effectuer**

1. **Vérifier les permissions**
   ```bash
   # Sur Windows (PowerShell en tant qu'administrateur)
   icacls "c:\projets\Binajia\public\media" /grant Everyone:F /T
   
   # Ou créer les dossiers manuellement
   mkdir public\media\cards
   mkdir public\media\receipts
   mkdir public\media\avatars
   ```

2. **Tester la génération de cartes**
   ```bash
   php bin/console app:test-email-card
   ```

## 🚨 **Problèmes courants et solutions**

### **1. "The asset mapper directory 'assets/' does not exist"**
```bash
# Solution : Vider le cache Symfony
php bin/console cache:clear
# Ou utiliser le script batch
clear_cache.bat
```

### **2. "SMTP connection failed"**
```bash
# Vérifiez votre configuration dans .env
# Testez avec :
php bin/console app:test-email-card
```

### **3. "PDF generation failed"**
```bash
# Vérifiez les permissions des dossiers
# Exécutez :
php bin/console app:fix-email-card
```

### **4. "Avatar image not found"**
```bash
# Les avatars doivent être dans public/media/avatars/
# Un avatar par défaut sera créé automatiquement
```

## 📋 **Checklist de vérification**

### Avant de tester :
- [ ] Configuration SMTP mise à jour dans `.env`
- [ ] Dossiers `media/cards`, `media/receipts`, `media/avatars` créés
- [ ] Permissions d'écriture accordées
- [ ] Cache Symfony vidé

### Tests à effectuer :
- [ ] `php bin/console app:test-email-card`
- [ ] `php bin/console app:fix-email-card`
- [ ] Test d'inscription d'un nouveau membre
- [ ] Test de génération de carte
- [ ] Test d'envoi d'email

## 🔍 **Debugging avancé**

### **Logs à vérifier**
```bash
# Logs Symfony
tail -f var/log/dev.log

# Logs d'erreur PHP (si configurés)
tail -f /path/to/php/error.log
```

### **Variables d'environnement**
```bash
# Vérifier les variables chargées
php bin/console debug:container --env-vars
```

### **Services disponibles**
```bash
# Vérifier les services email
php bin/console debug:container email
```

## 📞 **Support**

Si les problèmes persistent après avoir suivi ce guide :

1. Exécutez `php bin/console app:test-email-card`
2. Copiez les résultats des tests
3. Vérifiez les logs dans `var/log/dev.log`
4. Contactez le support avec ces informations

---

**Note importante :** Assurez-vous d'avoir les bonnes informations SMTP de votre hébergeur avant de configurer les emails.
