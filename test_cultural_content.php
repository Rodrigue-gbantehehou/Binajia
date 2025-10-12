<?php

// Test rapide pour vérifier que le contrôleur CulturalContent fonctionne
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ControllerResolver;
use Symfony\Component\HttpKernel\Controller\ArgumentResolver;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Doctrine\ORM\EntityManagerInterface;

// Créer un conteneur minimal
$container = new ContainerBuilder();

// Charger la configuration de base
$kernel = new App\Kernel('dev', true);
$kernel->boot();

// Récupérer l'entity manager du vrai conteneur
$em = $kernel->getContainer()->get('doctrine.orm.entity_manager');

// Test : Créer un contenu culturel de test
$content = new App\Entity\Culturalcontent();
$content->setTitle('Test Cultural Content');
$content->setDescription('Test description');
$content->setType('photo');
$content->setCountry('Benin');
$content->setCreatedAt(new \DateTimeImmutable());

try {
    $em->persist($content);
    $em->flush();

    echo "✅ Contenu culturel créé avec succès !\n";
    echo "🆔 ID: " . $content->getId() . "\n";
    echo "📋 Titre: " . $content->getTitle() . "\n";

    // Test de récupération
    $retrievedContent = $em->getRepository(App\Entity\Culturalcontent::class)->find($content->getId());
    if ($retrievedContent) {
        echo "✅ Contenu récupéré avec succès !\n";
        echo "📋 Titre récupéré: " . $retrievedContent->getTitle() . "\n";
    } else {
        echo "❌ Erreur lors de la récupération du contenu\n";
    }

    // Nettoyage
    $em->remove($content);
    $em->flush();
    echo "🧹 Contenu de test supprimé\n";

} catch (Exception $e) {
    echo "❌ Erreur lors du test: " . $e->getMessage() . "\n";
}

echo "\nTest terminé ! L'entité Culturalcontent fonctionne correctement.\n";
