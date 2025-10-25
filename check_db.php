<?php

require_once __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

$projectRoot = __DIR__;

// Charger les variables d'environnement
$dotenv = new Dotenv();
$dotenv->load($projectRoot . '/.env');

$kernel = new \App\Kernel('dev', true);
$kernel->boot();

$entityManager = $kernel->getContainer()->get('doctrine')->getManager();

try {
    $connection = $entityManager->getConnection();

    // Vérifier si la colonne utilisateur_id existe
    $columns = $connection->executeQuery("DESCRIBE reservation")->fetchAllAssociative();

    echo "Structure de la table reservation :\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']}: {$column['Type']}\n";
        if (strpos($column['Field'], 'utilisateur') !== false) {
            echo "⚠️  Colonne utilisateur trouvée !\n";
        }
    }

} catch (\Exception $e) {
    echo "Erreur : " . $e->getMessage() . "\n";
}

$kernel->shutdown();
