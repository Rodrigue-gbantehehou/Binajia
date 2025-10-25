<?php

require_once __DIR__ . '/vendor/autoload.php';

use App\Service\MembershipCardService;

// Test de la fonction prepareAvatar
$uploadDir = __DIR__ . '/var/uploads';
$membershipCardService = new MembershipCardService(
    null, // PdfGeneratorService (mock)
    null, // EntityManager (mock)
    $uploadDir,
    null  // QrCodeService (mock)
);

// Test avec un avatar existant
$testPath = 'membres/103.jpg';
$result = $membershipCardService->prepareAvatar($testPath);

echo "Test avec avatar existant ($testPath):\n";
echo "Résultat: " . (str_starts_with($result, 'data:image') ? 'SUCCESS' : 'FAILED') . "\n";
echo "Type: " . substr($result, 0, 50) . "...\n\n";

// Test avec un avatar inexistant
$testPath2 = 'membres/999.jpg';
$result2 = $membershipCardService->prepareAvatar($testPath2);

echo "Test avec avatar inexistant ($testPath2):\n";
echo "Résultat: " . (str_starts_with($result2, 'data:image/svg') ? 'SUCCESS (avatar par défaut)' : 'FAILED') . "\n";
echo "Type: " . substr($result2, 0, 50) . "...\n";
