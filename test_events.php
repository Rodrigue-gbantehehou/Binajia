<?php

require_once __DIR__ . '/vendor/autoload.php';

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Dotenv\Dotenv;

// Load environment variables
$dotenv = new Dotenv();
$dotenv->load(__DIR__ . '/.env');

// Create entity manager
$entityManager = require __DIR__ . '/config/bootstrap.php';

try {
    // Try to get events
    $eventsRepository = $entityManager->getRepository(\App\Entity\Evenement::class);
    $events = $eventsRepository->findBy([], ['startDate' => 'ASC'], 5);

    echo "Found " . count($events) . " events in database:\n";
    foreach ($events as $event) {
        echo "- " . $event->getTitle() . " (" . $event->getLocation() . ")\n";
    }

    if (empty($events)) {
        echo "No events found, will use demo events.\n";
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
