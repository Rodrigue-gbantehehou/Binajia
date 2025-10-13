# 🔍 Diagnostic des problèmes d'emails et de cartes - BINAJIA

## 📧 **Problèmes identifiés avec les emails**

### 1. **Configuration email incohérente**
```env
# Dans .env - Problèmes détectés :
MAILER_DSN="smtp://contact@efasmartimmobilier.com:%40efasmartimmobilier@mail.efasmartimmobilier.com:465?encryption=ssl"
MAIL_FROM_ADDRESS=contact@efasmartimmobilier.com
MAIL_FROM_NAME="BINAJIA"
```

**❌ Problèmes :**
- Utilise un domaine externe (`efasmartimmobilier.com`) au lieu de `binajia.org`
- Le service EmailService utilise `contact@binajia.org` en dur (ligne 15)
- Incohérence entre la config .env et le service

### 2. **URLs hardcodées incorrectes**
Dans `EmailService.php` :
```php
'loginUrl' => 'https://binajia.org/login'        // Ligne 35
'downloadUrl' => 'https://binajia.org' . $pdfUrl // Ligne 66
'dashboardUrl' => 'https://binajia.org/dashboard' // Ligne 129
```

## 🎴 **Problèmes identifiés avec les cartes**

### 1. **Template de carte utilise des chemins absolus**
Dans `card_pdf_modern.html.twig` :
- Ligne 78 : `<img src="{{ avatar }}"` - Le chemin avatar peut être incorrect
- Pas de vérification si le fichier image existe

### 2. **Service PdfGeneratorService**
- Ligne 25 : `$options->setChroot($this->publicDir);` - Peut limiter l'accès aux images
- Pas de gestion d'erreur si l'image avatar n'existe pas

### 3. **Permissions de dossiers**
- Les dossiers `/public/media/cards` et `/public/media/receipts` peuvent ne pas exister
- Permissions d'écriture potentiellement insuffisantes

## 🛠️ **Solutions recommandées**

### Pour les emails :

1. **Corriger la configuration email**
2. **Créer un service de configuration centralisé**
3. **Utiliser des URLs dynamiques**
4. **Tester la connexion SMTP**

### Pour les cartes :

1. **Améliorer la gestion des images**
2. **Ajouter des vérifications d'existence de fichiers**
3. **Créer les dossiers automatiquement**
4. **Améliorer la gestion d'erreurs**

## 📋 **Actions à effectuer**

1. ✅ Corriger la configuration email
2. ✅ Mettre à jour le service EmailService
3. ✅ Améliorer le service de génération de cartes
4. ✅ Créer un script de test
5. ✅ Vérifier les permissions de dossiers
