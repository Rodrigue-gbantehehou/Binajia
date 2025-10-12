<?php

// Test rapide pour vérifier que les exigences de route fonctionnent
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;

// Créer une collection de routes avec les mêmes exigences
$routes = new RouteCollection();
$routes->add('test_show', new Route('/admin/cultural-content/{id}', [
    '_controller' => 'App\Controller\Admin\CulturalContentController::show',
], ['id' => '\d+']));

$context = new RequestContext('/');
$matcher = new UrlMatcher($routes, $context);

// Test avec un ID numérique valide
try {
    $parameters = $matcher->match('/admin/cultural-content/123');
    echo "✅ Route avec ID numérique (123) : paramètres valides\n";
    echo "📋 Paramètres: " . json_encode($parameters) . "\n";
} catch (Exception $e) {
    echo "❌ Erreur avec ID numérique: " . $e->getMessage() . "\n";
}

// Test avec un ID non numérique (devrait échouer)
try {
    $parameters = $matcher->match('/admin/cultural-content/abc');
    echo "❌ Route avec ID non numérique acceptée (ne devrait pas arriver)\n";
} catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
    echo "✅ Route avec ID non numérique rejetée correctement\n";
} catch (Exception $e) {
    echo "❌ Erreur inattendue avec ID non numérique: " . $e->getMessage() . "\n";
}

echo "\nTest des exigences de route terminé.\n";
