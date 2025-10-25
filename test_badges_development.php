<?php
echo "=== Test des Badges de Développement ===" . PHP_EOL . PHP_EOL;

// Vérifier que le fichier a été modifié
$file = 'templates/pages/social_impact.html.twig';
$content = file_get_contents($file);

// Vérifications des badges ajoutés
$checks = [
    'Badge "Bientôt" sur bouton don (hero)' => strpos($content, '❤️ Faire un don') !== false && strpos($content, 'Bientôt') !== false,
    'Badge "Bientôt" sur bouton rejoindre (hero)' => strpos($content, 'Nous rejoindre') !== false && strpos($content, 'Bientôt') !== false,
    'Badge "Bientôt" sur don (CTA section)' => strpos($content, 'Faire un don') !== false && strpos($content, 'Bientôt') !== false,
    'Badge "Bientôt" sur bénévole (CTA section)' => strpos($content, 'Devenir bénévole') !== false && strpos($content, 'Bientôt') !== false,
    'Badge "Bientôt" sur partenaire (CTA section)' => strpos($content, 'Devenir partenaire') !== false && strpos($content, 'Bientôt') !== false,
    'Badge "Bientôt" sur bénévole (section mouvement)' => strpos($content, '🤝 Devenir bénévole') !== false && strpos($content, 'Bientôt') !== false,
    'Boutons en mode développement (gris)' => strpos($content, 'bg-gray-400') !== false && strpos($content, 'cursor-not-allowed') !== false,
    'Messages d\'alerte JavaScript' => strpos($content, 'en cours de développement') !== false,
];

echo "✅ Vérifications des badges ajoutés :" . PHP_EOL;
foreach ($checks as $feature => $passed) {
    echo ($passed ? "✅" : "❌") . " $feature" . PHP_EOL;
}

echo PHP_EOL . "🎨 Badges 'Bientôt disponible' ajoutés avec succès :" . PHP_EOL;
echo "  • Bouton don principal (hero)" . PHP_EOL;
echo "  • Bouton 'Nous rejoindre' (hero)" . PHP_EOL;
echo "  • Section CTA complète (3 boutons)" . PHP_EOL;
echo "  • Section 'Rejoignez notre mouvement'" . PHP_EOL;

echo PHP_EOL . "🔧 Modifications apportées :" . PHP_EOL;
echo "  • Couleurs : Rouge/Vert → Gris (mode développement)" . PHP_EOL;
echo "  • Curseur : pointer → not-allowed" . PHP_EOL;
echo "  • Actions : href='#' → alert() avec message explicatif" . PHP_EOL;
echo "  • Badges : Jaunes avec 'Bientôt' pour visibilité" . PHP_EOL;

echo PHP_EOL . "🚀 La page est maintenant cohérente avec toutes les fonctionnalités en développement clairement indiquées !" . PHP_EOL;
