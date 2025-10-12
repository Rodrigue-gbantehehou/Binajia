<?php

// Test rapide pour vérifier que les exigences de route fonctionnent pour PaymentsController
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;

// Créer une collection de routes avec les mêmes exigences que PaymentsController
$routes = new RouteCollection();
$routes->add('payments_show', new Route('/admin/payments/{id}', [
    '_controller' => 'App\Controller\Admin\PaymentsController::show',
], ['id' => '\d+']));

$routes->add('payments_verify', new Route('/admin/payments/{id}/verify', [
    '_controller' => 'App\Controller\Admin\PaymentsController::verify',
], ['id' => '\d+']));

$context = new RequestContext('/');
$matcher = new UrlMatcher($routes, $context);

// Test avec des IDs numériques valides
$testCases = [
    ['path' => '/admin/payments/123', 'expected' => true],
    ['path' => '/admin/payments/1', 'expected' => true],
    ['path' => '/admin/payments/999', 'expected' => true],
    ['path' => '/admin/payments/abc', 'expected' => false], // Devrait échouer
    ['path' => '/admin/payments/1a', 'expected' => false], // Devrait échouer
];

foreach ($testCases as $i => $testCase) {
    try {
        $parameters = $matcher->match($testCase['path']);
        if ($testCase['expected']) {
            echo "✅ Test " . ($i + 1) . " - Route valide: " . $testCase['path'] . "\n";
            echo "   📋 Paramètres: " . json_encode($parameters) . "\n";
        } else {
            echo "❌ Test " . ($i + 1) . " - Route invalide acceptée: " . $testCase['path'] . "\n";
        }
    } catch (Symfony\Component\Routing\Exception\ResourceNotFoundException $e) {
        if (!$testCase['expected']) {
            echo "✅ Test " . ($i + 1) . " - Route invalide rejetée correctement: " . $testCase['path'] . "\n";
        } else {
            echo "❌ Test " . ($i + 1) . " - Route valide rejetée: " . $testCase['path'] . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Test " . ($i + 1) . " - Erreur inattendue: " . $e->getMessage() . "\n";
    }
}

echo "\nTest des exigences de route pour PaymentsController terminé.\n";
