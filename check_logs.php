<?php
/**
 * Script para verificar configuración de logs y mostrar errores
 */

echo "=== Información de Logs PHP ===\n\n";

// Configurar mostrar errores
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "1. Error Reporting: " . error_reporting() . "\n";
echo "2. Display Errors: " . ini_get('display_errors') . "\n";
echo "3. Error Log: " . ini_get('error_log') . "\n\n";

// Intentar crear un error de prueba
echo "=== Probando captura de errores ===\n";
try {
    // Error de prueba
    trigger_error("Test error log", E_USER_NOTICE);
    
    // Intentar acceder a un archivo que no existe
    @include 'archivo_que_no_existe.php';
    
    echo "✅ Errores generados correctamente\n\n";
} catch (Exception $e) {
    echo "❌ Excepción: " . $e->getMessage() . "\n\n";
}

echo "=== Verificar permisos de escritura ===\n";
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    if (mkdir($log_dir, 0755, true)) {
        echo "✅ Directorio logs/ creado\n";
    } else {
        echo "❌ No se pudo crear directorio logs/\n";
    }
} else {
    echo "✅ Directorio logs/ existe\n";
}

if (is_writable($log_dir)) {
    echo "✅ Directorio logs/ tiene permisos de escritura\n";
} else {
    echo "⚠️  Directorio logs/ NO tiene permisos de escritura\n";
}

echo "\n=== Ubicaciones comunes de logs ===\n";
$common_logs = [
    '/var/log/apache2/error.log',
    '/var/log/httpd/error_log',
    '/usr/local/var/log/httpd/error_log',
    '/private/var/log/apache2/error_log',
    __DIR__ . '/logs/error.log',
    __DIR__ . '/error.log'
];

foreach ($common_logs as $log_file) {
    if (file_exists($log_file)) {
        echo "✅ Encontrado: $log_file\n";
        echo "   Tamaño: " . filesize($log_file) . " bytes\n";
        echo "   Última modificación: " . date('Y-m-d H:i:s', filemtime($log_file)) . "\n";
    } else {
        echo "❌ No existe: $log_file\n";
    }
}

