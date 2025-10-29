<?php
/**
 * Script para ver los últimos errores del archivo de log personalizado
 */

$log_file = __DIR__ . '/logs/error.log';

if (!file_exists($log_file)) {
    echo "No existe archivo de log todavía.\n";
    echo "Se creará automáticamente cuando ocurra un error.\n\n";
    echo "Para activar logging personalizado, agrega esto al inicio de index.php:\n\n";
    echo "ini_set('log_errors', 1);\n";
    echo "ini_set('error_log', __DIR__ . '/logs/error.log');\n\n";
    exit;
}

echo "=== Últimas 50 líneas del log ===\n\n";

$lines = file($log_file);
$last_lines = array_slice($lines, -50);

foreach ($last_lines as $line) {
    echo $line;
}

