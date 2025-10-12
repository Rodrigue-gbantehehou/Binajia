<?php

// Test script pour vérifier la configuration cURL et les services
require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\HttpClient\HttpClient;

echo "=== Test de configuration cURL ===\n\n";

// Test 1: Vérification de base cURL
echo "1. Test cURL basique...\n";
try {
    $client = HttpClient::create(['verify_peer' => false, 'verify_host' => false]);
    $response = $client->request('GET', 'https://httpbin.org/get');
    $status = $response->getStatusCode();
    echo "✅ cURL fonctionne (status: $status)\n";
} catch (Exception $e) {
    echo "❌ Erreur cURL: " . $e->getMessage() . "\n";
}

// Test 2: Vérification FedaPay (si clé disponible)
echo "\n2. Test FedaPay API...\n";
if (isset($_ENV['FEDAPAY_SECRET_KEY']) && !empty($_ENV['FEDAPAY_SECRET_KEY'])) {
    try {
        $client = HttpClient::create([
            'verify_peer' => false,
            'verify_host' => false,
            'timeout' => 10
        ]);

        $response = $client->request('GET', 'https://api.fedapay.com/v1/transactions', [
            'headers' => [
                'Authorization' => 'Bearer ' . $_ENV['FEDAPAY_SECRET_KEY'],
                'Accept' => 'application/json',
            ]
        ]);

        $status = $response->getStatusCode();
        echo "✅ FedaPay API accessible (status: $status)\n";
    } catch (Exception $e) {
        echo "❌ Erreur FedaPay: " . $e->getMessage() . "\n";
    }
} else {
    echo "⚠️ Clé FedaPay non configurée\n";
}

echo "\n=== Configuration recommandée ===\n";
echo "• Utilisez les options verify_peer=false et verify_host=false dans HttpClient\n";
echo "• Configurez un timeout approprié (30s recommandé)\n";
echo "• Les services CardPaymentService sont maintenant configurés pour gérer les erreurs\n";
echo "• Installez amphp/http-client si nécessaire: composer require amphp/http-client\n";

echo "\nTest terminé.\n";
