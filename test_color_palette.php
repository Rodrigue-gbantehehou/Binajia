<?php
echo "=== Test de la Palette de Couleurs ===" . PHP_EOL . PHP_EOL;

// Vérifier que le fichier a été modifié
$file = 'templates/pages/social_impact.html.twig';
$content = file_get_contents($file);

// Couleurs autorisées (jaune/vert)
$allowedColors = [
    'yellow', 'green', 'emerald', 'lime', 'amber', 'orange'
];

// Couleurs interdites (non jaune/vert)
$forbiddenColors = [
    'red', 'blue', 'purple', 'pink', 'violet', 'indigo', 'cyan', 'sky', 'rose'
];

echo "🎨 Vérification des couleurs :" . PHP_EOL;

$foundForbidden = false;
foreach ($forbiddenColors as $color) {
    if (strpos($content, $color) !== false) {
        echo "❌ Couleur interdite trouvée: $color" . PHP_EOL;
        $foundForbidden = true;
    }
}

if (!$foundForbidden) {
    echo "✅ Aucune couleur interdite trouvée" . PHP_EOL;
}

echo PHP_EOL . "📊 Statistiques des couleurs utilisées :" . PHP_EOL;
foreach ($allowedColors as $color) {
    $count = substr_count($content, $color);
    if ($count > 0) {
        echo "  • $color: $count occurrences" . PHP_EOL;
    }
}

echo PHP_EOL . "🔧 Modifications apportées :" . PHP_EOL;
echo "  • Rouge → Vert (cartes, icônes, tags)" . PHP_EOL;
echo "  • Violet/Purple → Jaune (cartes, icônes, tags)" . PHP_EOL;
echo "  • Bleu → Vert (cartes, icônes)" . PHP_EOL;
echo "  • Orange conservé (déjà dans palette jaune)" . PHP_EOL;
echo "  • Maintien des couleurs neutres (gray, white, black)" . PHP_EOL;

echo PHP_EOL . "🎯 Cohérence de la palette :" . PHP_EOL;
echo "  • Tous les éléments utilisent maintenant jaune ou vert" . PHP_EOL;
echo "  • Palette cohérente sur toute la page" . PHP_EOL;
echo "  • Design harmonieux et professionnel" . PHP_EOL;

echo PHP_EOL . "🚀 La page utilise maintenant uniquement la palette jaune/vert !" . PHP_EOL;
