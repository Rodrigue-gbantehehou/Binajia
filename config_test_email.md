# Configuration de test pour les emails - BINAJIA

## Pour tester les emails en développement

Créez un fichier `.env.local` avec cette configuration :

```env
# Configuration de test avec Mailtrap ou service similaire
MAILER_DSN="smtp://username:password@sandbox.smtp.mailtrap.io:2525"
MAIL_FROM_ADDRESS="test@binajia.org"
MAIL_FROM_NAME="BINAJIA TEST"

# Ou pour Gmail (nécessite un mot de passe d'application)
# MAILER_DSN="gmail://votre-email@gmail.com:mot-de-passe-app@default"

# Ou pour un serveur SMTP local
# MAILER_DSN="smtp://localhost:1025"
```

## Services de test recommandés

1. **Mailtrap** (gratuit) - https://mailtrap.io
2. **MailHog** (local) - https://github.com/mailhog/MailHog  
3. **Gmail** (avec mot de passe d'application)

## Test rapide

```bash
# 1. Corriger les problèmes
php bin/console app:fix-email-card

# 2. Tester la configuration
php bin/console app:test-email-card

# 3. Vider le cache si nécessaire
php bin/console cache:clear
```
