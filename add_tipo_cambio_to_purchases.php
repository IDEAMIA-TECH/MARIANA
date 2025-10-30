<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/models/Database.php';

try {
    $pdo = Database::getConnection();
    $exists = Database::fetchOne(
        "SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'purchases' AND COLUMN_NAME = 'tipo_cambio'"
    );

    if (!$exists) {
        $pdo->exec("ALTER TABLE purchases ADD COLUMN tipo_cambio DECIMAL(12,4) NULL AFTER moneda");
        echo "Column 'tipo_cambio' added to 'purchases' table\n";
    } else {
        echo "Column 'tipo_cambio' already exists\n";
    }
} catch (Throwable $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

echo "Done.\n";
?>


