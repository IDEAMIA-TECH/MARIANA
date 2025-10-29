<?php
/**
 * Ver errores del log (solo para desarrollo)
 * ⚠️ NO usar en producción
 */

// Validar que estamos en desarrollo
$log_file = __DIR__ . '/logs/error.log';

if (!file_exists($log_file)) {
    die("No existe archivo de log todavía. El log se creará cuando ocurra un error.\n");
}

header('Content-Type: text/plain; charset=utf-8');
?>
=== Últimos Errores del Log ===
Archivo: <?= $log_file ?>

Última actualización: <?= date('Y-m-d H:i:s', filemtime($log_file)) ?>

Tamaño: <?= filesize($log_file) ?> bytes

=== Últimas 100 líneas ===

<?php
$lines = file($log_file);
$last_lines = array_slice($lines, -100);

foreach ($last_lines as $line) {
    echo htmlspecialchars($line);
}

// También mostrar errores recientes si existen
if (function_exists('error_get_last')) {
    $last_error = error_get_last();
    if ($last_error) {
        echo "\n\n=== Último Error en Memoria ===\n";
        print_r($last_error);
    }
}
?>

