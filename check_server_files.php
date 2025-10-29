<?php
/**
 * Script para verificar que todos los archivos necesarios existen en el servidor
 * Ejecutar en el servidor para ver qué falta
 */

echo "=== Verificación de Archivos del Sistema ===\n\n";

$required_files = [
    // Configuración
    'config/database.php',
    'config/config.php',
    'config/constants.php',
    
    // Modelos
    'models/Database.php',
    'models/User.php',
    
    // Includes
    'includes/functions.php',
    'includes/auth.php',
    
    // Controladores
    'controllers/AuthController.php',
    
    // Vistas
    'views/auth/login.php',
    
    // Archivos principales
    'index.php',
    'login.php',
    'logout.php',
];

$missing = [];
$exists = [];

foreach ($required_files as $file) {
    $full_path = __DIR__ . '/' . $file;
    if (file_exists($full_path)) {
        $exists[] = $file;
        echo "✅ $file\n";
    } else {
        $missing[] = $file;
        echo "❌ $file - FALTANTE\n";
    }
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "Resumen:\n";
echo "✅ Archivos existentes: " . count($exists) . "\n";
echo "❌ Archivos faltantes: " . count($missing) . "\n";

if (!empty($missing)) {
    echo "\n⚠️  ARCHIVOS FALTANTES:\n";
    foreach ($missing as $file) {
        echo "   - $file\n";
    }
    
    echo "\n📝 Para crear los archivos faltantes:\n";
    foreach ($missing as $file) {
        $dir = dirname($file);
        if ($dir !== '.') {
            echo "mkdir -p $dir\n";
        }
        echo "# Crear $file\n";
    }
} else {
    echo "\n🎉 Todos los archivos necesarios existen!\n";
}

// Verificar estructura de directorios
echo "\n=== Verificación de Directorios ===\n";
$required_dirs = [
    'config',
    'models',
    'controllers',
    'views',
    'views/auth',
    'includes',
    'logs'
];

foreach ($required_dirs as $dir) {
    $full_path = __DIR__ . '/' . $dir;
    if (is_dir($full_path)) {
        echo "✅ $dir/\n";
    } else {
        echo "❌ $dir/ - FALTANTE (crear con: mkdir -p $dir)\n";
    }
}

