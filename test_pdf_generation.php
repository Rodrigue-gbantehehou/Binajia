<?php

// Test simple de génération PDF
require_once __DIR__ . '/../vendor/autoload.php';

use App\Service\PdfGeneratorService;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Dotenv\Dotenv;

$projectRoot = __DIR__ . '/..';

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load($projectRoot . '/.env');

$kernel = new \App\Kernel('dev', true);
$kernel->boot();

// Récupérer les services nécessaires
$container = $kernel->getContainer();
$twig = $container->get('twig');
$uploadDir = $container->getParameter('kernel.project_dir') . '/var/uploads';

// Créer le service PDF
$pdfService = new PdfGeneratorService($twig, $uploadDir);

try {
    echo "Test de génération PDF...\n";

    // Données de test simples
    $testData = [
        'reservation' => (object) [
            'id' => 1,
            'nom' => 'Test User',
            'email' => 'test@example.com',
            'telephone' => '+2250102030405',
            'commentaire' => 'Test reservation',
            'typereservation' => 'evenement',
            'evenement' => (object) [
                'titre' => 'Événement de test',
                'description' => 'Ceci est un événement de test pour vérifier la génération PDF.'
            ]
        ]
    ];

    $filename = 'test_reservation_' . time() . '.pdf';
    $pdfPath = $pdfService->generatePdf(
        'reservation/reservation_confirmation_pdf.html.twig',
        $testData,
        $filename,
        'A4',
        'portrait'
    );

    echo "✅ PDF généré avec succès : " . $pdfPath . "\n";
    echo "📄 Taille du fichier : " . filesize($pdfPath) . " bytes\n";

} catch (\Exception $e) {
    echo "❌ Erreur lors de la génération du PDF : " . $e->getMessage() . "\n";
    echo "📍 Fichier : " . $e->getFile() . " ligne " . $e->getLine() . "\n";
    echo "🔍 Trace : " . $e->getTraceAsString() . "\n";
}

$kernel->shutdown();
