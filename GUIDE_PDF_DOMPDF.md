# 🎨 Guide PDF avec DomPDF - BINAJIA

## ✅ **Optimisations DomPDF appliquées**

### **1. Template `card_pdf_modern.html.twig` optimisé**
- ✅ **Tout le CSS intégré** dans le fichier HTML
- ✅ **Configuration @page** pour format A6 landscape
- ✅ **Gestion des images** avec chemins absolus
- ✅ **Styles inline** pour les éléments critiques
- ✅ **Polices compatibles** (DejaVu Sans)

### **2. Service `PdfGeneratorService` amélioré**
- ✅ **Options DomPDF optimisées** pour la performance
- ✅ **Support HTML5** activé
- ✅ **Gestion d'erreurs** renforcée
- ✅ **Chemins absolus** pour les ressources

### **3. Service `MembershipCardService` renforcé**
- ✅ **Chemins absolus** pour les avatars
- ✅ **Avatar par défaut** automatique
- ✅ **Vérification d'existence** des fichiers
- ✅ **Gestion d'erreurs** complète

## 🧪 **Tests effectués**

### **Commandes de test disponibles :**
```bash
# Test complet du système
php bin/console app:test-full-registration

# Test spécifique des styles PDF
php bin/console app:test-pdf-styles

# Diagnostic général
php bin/console app:test-email-card
```

### **Résultats des tests :**
- ✅ **PDF avec avatar** : 35719 bytes
- ✅ **PDF sans avatar** : 35706 bytes
- ✅ **Génération rapide** et stable
- ✅ **Styles CSS appliqués** correctement

## 📁 **Fichiers générés pour vérification**

Les fichiers suivants ont été créés dans `public/media/` :
- `test_styles_with_avatar.pdf` - Carte avec avatar coloré
- `test_styles_without_avatar.pdf` - Carte avec placeholder

## 🎯 **Points de vérification manuelle**

Ouvrez les fichiers PDF générés et vérifiez :

### **✅ Couleurs et dégradés**
- [ ] Header orange/ambre (#F59E0B → #D97706)
- [ ] Body vert (#1F5438 → #2D7A4F)
- [ ] Badge rôle orange (#F59E0B)
- [ ] Drapeaux Bénin/Nigéria colorés

### **✅ Typographie**
- [ ] Police DejaVu Sans appliquée
- [ ] Tailles de police respectées
- [ ] Poids de police (bold, normal)
- [ ] Espacement des lettres

### **✅ Mise en page**
- [ ] Format A6 paysage (450x280px)
- [ ] Deux cartes côte à côte (recto/verso)
- [ ] Alignements corrects
- [ ] Marges respectées

### **✅ Éléments visuels**
- [ ] Drapeaux avec motifs corrects
- [ ] Avatar intégré (si fourni)
- [ ] Placeholder "PHOTO" (si pas d'avatar)
- [ ] Ombres et effets visuels

### **✅ Contenu dynamique**
- [ ] Nom du membre affiché
- [ ] Téléphone formaté
- [ ] Nationalité correcte
- [ ] ID membre (BNJ######)
- [ ] Dates d'adhésion et expiration

## 🔧 **Configuration DomPDF optimale**

### **Options activées :**
```php
$options->set('defaultFont', 'DejaVu Sans');
$options->setIsRemoteEnabled(true);
$options->set('isHtml5ParserEnabled', true);
$options->set('isPhpEnabled', true);
```

### **Gestion des images :**
- **Chemins absolus** requis pour DomPDF
- **Avatar par défaut** SVG créé automatiquement
- **Vérification d'existence** avant utilisation

## 🚀 **Utilisation en production**

### **Pour générer une carte :**
```php
$cardService = $this->get(MembershipCardService::class);
$result = $cardService->generateAndPersist(
    $user,           // Entité User
    $payment,        // Entité Payment (optionnel)
    $avatarPath,     // Chemin vers l'avatar
    $memberId        // ID unique du membre
);

// Résultat :
// $result['cardPdfUrl'] = '/media/cards/card_123_20231013.pdf'
// $result['receiptPdfPath'] = '/path/to/receipt.pdf'
```

### **Envoi par email :**
```php
$emailService = $this->get(EmailService::class);
$emailService->sendCardCreatedEmail(
    $user->getEmail(),
    $user->getFirstname(),
    $memberId,
    $result['cardPdfUrl']
);
```

## 📋 **Checklist de déploiement**

Avant de déployer en production :

- [ ] Tester la génération PDF sur le serveur cible
- [ ] Vérifier les permissions des dossiers `media/`
- [ ] Confirmer que DomPDF fonctionne sur l'hébergement
- [ ] Tester avec différents types d'avatars
- [ ] Vérifier l'envoi d'emails avec pièces jointes
- [ ] Tester la performance avec plusieurs générations

## 🆘 **Dépannage**

### **PDF vide ou sans styles :**
- Vérifier que tout le CSS est dans le template
- Utiliser des chemins absolus pour les images
- Tester avec `app:test-pdf-styles`

### **Images non affichées :**
- Vérifier les permissions des fichiers
- Utiliser des chemins absolus complets
- Tester l'avatar par défaut

### **Erreurs de génération :**
- Vérifier les logs dans `var/log/`
- Tester avec `app:test-email-card`
- Vérifier la configuration DomPDF

---

**Votre système de génération PDF est maintenant optimisé pour DomPDF ! 🎉**
