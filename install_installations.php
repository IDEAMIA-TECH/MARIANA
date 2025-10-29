<?php
declare(strict_types=1);

/**
 * Script para agregar funcionalidad de instalaciones
 */

require_once __DIR__ . '/config/database.php';

echo "=== Instalación de Funcionalidad de Instalaciones ===\n\n";

try {
    $pdo = Database::getConnection();
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);

    $sql_file = __DIR__ . '/database_installations.sql';
    $sql_commands = file_get_contents($sql_file);

    if ($sql_commands === false) {
        die("❌ Error: No se pudo leer el archivo SQL: " . $sql_file . "\n");
    }

    // Dividir el script SQL en comandos individuales
    $commands = array_filter(array_map('trim', explode(';', $sql_commands)));

    echo "Agregando funcionalidad de instalaciones...\n";
    echo "==================================================\n";
    
    foreach ($commands as $command) {
        if (empty($command)) continue;
        try {
            $pdo->exec($command);
            // Extraer información relevante
            if (preg_match('/ALTER TABLE.*ADD COLUMN/i', $command)) {
                echo "✅ Columna agregada a tabla inventory: qty_instalada\n";
            } elseif (preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/i', $command, $matches)) {
                echo "✅ Tabla creada/verificada: " . $matches[1] . "\n";
            }
        } catch (PDOException $e) {
            // Ignorar errores de "column already exists" o "table already exists"
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'Duplicate column name') !== false) {
                if (preg_match('/ADD COLUMN.*qty_instalada/i', $command)) {
                    echo "ℹ️  Columna qty_instalada ya existe\n";
                } elseif (preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/i', $command, $matches)) {
                    echo "ℹ️  Tabla ya existe: " . $matches[1] . "\n";
                }
            } else {
                echo "❌ Error: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "==================================================\n\n";
    echo "🎉 Instalación de funcionalidad de instalaciones completada!\n";
    echo "Ahora puedes marcar materiales como instalados.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

