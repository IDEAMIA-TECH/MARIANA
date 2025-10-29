<?php
/**
 * Limpiar el archivo de log
 */

$log_file = __DIR__ . '/logs/error.log';

if (file_exists($log_file)) {
    file_put_contents($log_file, '');
    echo "✅ Log limpiado exitosamente\n";
} else {
    echo "ℹ️  No existe archivo de log\n";
}

