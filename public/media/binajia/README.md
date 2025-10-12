# Images Binajia - Guide d'installation

## Étapes pour intégrer les images uploadées

### 1. Copier les images dans ce dossier (`public/media/binajia/`)

Renommez et copiez les 5 images uploadées comme suit:

- **Image 1** (Logo Binajia avec slogan) → `logo-hero.jpg`
- **Image 2** (Équipe collaborative autour d'une table) → `team-collaborative.jpg`
- **Image 3** (Réseau Bénin-Nigéria avec carte) → `network-benin-nigeria.jpg`
- **Image 4** (Carte de membre Ambassador) → `membership-card.jpg`
- **Image 5** (Membre souriante tenant sa carte) → `member-smiling.jpg`

### 2. Structure attendue

```
public/media/binajia/
├── logo-hero.jpg
├── team-collaborative.jpg
├── network-benin-nigeria.jpg
├── membership-card.jpg
└── member-smiling.jpg
```

### 3. Où sont utilisées ces images

- **logo-hero.jpg**: Hero section (fond subtil avec parallax)
- **team-collaborative.jpg**: Section "Notre mission" (#mission)
- **network-benin-nigeria.jpg**: Section "Bénin & Nigéria" (#territoire)
- **membership-card.jpg**: Section adhésion (composant carte membre)
- **member-smiling.jpg**: Section CTA (#adhesion) - avatar membre

### 4. Après avoir copié les images

Exécutez:
```bash
npm run build
```

Puis rafraîchissez la page (Ctrl+F5) pour voir les images en place.

### 5. Optimisation (optionnel)

Pour de meilleures performances, vous pouvez compresser les images avec:
- TinyPNG (https://tinypng.com/)
- ImageOptim (macOS)
- Squoosh (https://squoosh.app/)

Cible: ~200-300 KB par image pour un bon équilibre qualité/performance.
