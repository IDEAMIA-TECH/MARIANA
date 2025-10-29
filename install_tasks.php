<?php
declare(strict_types=1);

/**
 * Script para crear las tablas de tareas en la base de datos
 */

require_once __DIR__ . '/config/database.php';

echo "=== Instalación de Tablas de Tareas ===\n\n";

try {
    $pdo = Database::getConnection();
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, 0);

    $sql_file = __DIR__ . '/database_tasks.sql';
    $sql_commands = file_get_contents($sql_file);

    if ($sql_commands === false) {
        die("❌ Error: No se pudo leer el archivo SQL: " . $sql_file . "\n");
    }

    // Dividir el script SQL en comandos individuales
    $commands = array_filter(array_map('trim', explode(';', $sql_commands)));

    echo "Creando tablas de tareas...\n";
    echo "==================================================\n";
    
    foreach ($commands as $command) {
        if (empty($command)) continue;
        try {
            $pdo->exec($command);
            // Extraer el nombre de la tabla
            if (preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/i', $command, $matches)) {
                echo "✅ Tabla creada/verificada: " . $matches[1] . "\n";
            }
        } catch (PDOException $e) {
            // Ignorar errores de "table already exists"
            if (strpos($e->getMessage(), 'already exists') === false) {
                echo "❌ Error: " . $e->getMessage() . "\n";
            } else {
                if (preg_match('/CREATE TABLE IF NOT EXISTS `?(\w+)`?/i', $command, $matches)) {
                    echo "ℹ️  Tabla ya existe: " . $matches[1] . "\n";
                }
            }
        }
    }
    
    echo "==================================================\n\n";
    echo "🎉 Instalación de tablas de tareas completada!\n";
    echo "Las tablas 'tasks' y 'task_requirements' están listas para usar.\n";

} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
}

