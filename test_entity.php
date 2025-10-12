<?php

// Test rapide pour vérifier que l'entité Culturalcontent est accessible
require_once __DIR__ . '/vendor/autoload.php';

try {
    // Test de chargement de l'entité
    $entityClass = 'App\Entity\Culturalcontent';
    if (class_exists($entityClass)) {
        echo "✅ Classe {$entityClass} trouvée\n";

        // Créer une instance pour vérifier les méthodes
        $reflection = new ReflectionClass($entityClass);
        $instance = $reflection->newInstanceWithoutConstructor();

        echo "✅ Instance créée avec succès\n";
        echo "📋 Méthodes disponibles:\n";

        foreach ($reflection->getMethods() as $method) {
            if ($method->isPublic()) {
                echo "   - " . $method->getName() . "\n";
            }
        }

    } else {
        echo "❌ Classe {$entityClass} non trouvée\n";
    }

} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
}

echo "\nTest terminé.\n";
