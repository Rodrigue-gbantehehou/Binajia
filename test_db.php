<?php

require_once 'vendor/autoload.php';

use PDO;
use Exception;

try {
    // Create PDO connection
    $pdo = new PDO('mysql:host=localhost;dbname=db_binajia', 'root', '');

    echo "Testing database connection...\n";

    // Check if typereservation column exists
    $stmt = $pdo->query('DESCRIBE reservation');
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $hasTypeColumn = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'typereservation') {
            $hasTypeColumn = true;
            echo "Column typereservation exists: " . $column['Type'] . "\n";
            break;
        }
    }

    if (!$hasTypeColumn) {
        echo "ERROR: Column typereservation does not exist!\n";
    }

    // Check existing reservations
    $stmt = $pdo->query('SELECT COUNT(*) as count FROM reservation');
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total reservations in database: " . $count['count'] . "\n";

    echo "Test completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
