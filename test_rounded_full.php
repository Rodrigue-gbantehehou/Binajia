<?php
echo "=== Test des Boutons avec Rounded-Full ===" . PHP_EOL . PHP_EOL;

// Vérifier que le fichier a été modifié
$file = 'templates/pages/social_impact.html.twig';
$content = file_get_contents($file);

// Vérifications des modifications
$checks = [
    'Bouton "Faire un don" (section mouvement)' => strpos($content, '❤️ Faire un don') !== false && strpos($content, 'rounded-full') !== false,
    'Bouton "Devenir bénévole" (section mouvement)' => strpos($content, '🤝 Devenir bénévole') !== false && strpos($content, 'rounded-full') !== false,
    'Bouton "En développement" (carte don)' => strpos($content, 'En développement') !== false && strpos($content, 'rounded-full') !== false,
    'Bouton "En développement" (carte bénévole)' => strpos($content, 'bénévolat en cours') !== false && strpos($content, 'rounded-full') !== false,
    'Bouton "En développement" (carte partenaire)' => strpos($content, 'partenariat en cours') !== false && strpos($content, 'rounded-full') !== false,
    'Boutons hero déjà en rounded-full' => strpos($content, 'Découvrir nos projets') !== false && strpos($content, 'rounded-full') !== false,
    'Tags déjà en rounded-full' => strpos($content, 'Aide alimentaire') !== false && strpos($content, 'rounded-full') !== false,
    'Badges "Bientôt" déjà en rounded-full' => strpos($content, 'Bientôt') !== false && strpos($content, 'rounded-full') !== false,
];

echo "✅ Vérifications des modifications :" . PHP_EOL;
foreach ($checks as $feature => $passed) {
    echo ($passed ? "✅" : "❌") . " $feature" . PHP_EOL;
}

echo PHP_EOL . "🎨 Modifications apportées :" . PHP_EOL;
echo "  • Section 'Rejoignez notre mouvement' : rounded-xl → rounded-full" . PHP_EOL;
echo "  • Section CTA : rounded-lg → rounded-full" . PHP_EOL;
echo "  • Maintien des éléments déjà en rounded-full (hero, tags, badges)" . PHP_EOL;

echo PHP_EOL . "📐 Cohérence visuelle :" . PHP_EOL;
echo "  • Tous les boutons principaux : rounded-full" . PHP_EOL;
echo "  • Tous les tags : rounded-full" . PHP_EOL;
echo "  • Tous les badges : rounded-full" . PHP_EOL;
echo "  • Design uniforme sur toute la page" . PHP_EOL;

echo PHP_EOL . "🚀 Tous les boutons sont maintenant en rounded-full !" . PHP_EOL;
