<?php
/**
 * Script de prueba que genera un error controlado
 * Úsalo para verificar que el logging funciona
 */

// Habilitar mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar log personalizado
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}
ini_set('log_errors', 1);
ini_set('error_log', $log_dir . '/error.log');

echo "<h1>Test de Errores</h1>";

// Generar diferentes tipos de errores
echo "<h2>1. Notice</h2>";
$undefined_var = $non_existent;

echo "<h2>2. Warning</h2>";
$file = fopen('archivo_inexistente.txt', 'r');

echo "<h2>3. Error fatal (manejado)</h2>";
try {
    trigger_error("Este es un error de prueba", E_USER_ERROR);
} catch (Exception $e) {
    echo "Error capturado: " . $e->getMessage();
}

echo "<h2>4. Errores escritos en log</h2>";
echo "<p>Revisa el archivo: <strong>logs/error.log</strong></p>";
echo "<p>O ejecuta: <strong>php error_log.php</strong></p>";

