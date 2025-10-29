<?php
/**
 * Script de prueba para verificar conexión y errores
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Test de Conexión ===\n\n";

try {
    // Test 1: Cargar configuración
    echo "1. Cargando configuración...\n";
    require_once __DIR__ . '/config/database.php';
    require_once __DIR__ . '/config/config.php';
    echo "   ✅ Configuración cargada\n\n";
    
    // Test 2: Conectar a BD
    echo "2. Conectando a base de datos...\n";
    require_once __DIR__ . '/models/Database.php';
    $pdo = Database::getConnection();
    echo "   ✅ Conexión exitosa\n\n";
    
    // Test 3: Verificar tabla users
    echo "3. Verificando tabla users...\n";
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $result = $stmt->fetch();
    echo "   ✅ Tabla users existe con {$result['total']} registros\n\n";
    
    // Test 4: Cargar funciones
    echo "4. Cargando funciones...\n";
    require_once __DIR__ . '/includes/functions.php';
    echo "   ✅ Funciones cargadas\n";
    echo "   ✅ APP_URL = " . APP_URL . "\n";
    echo "   ✅ base_url() = " . base_url() . "\n\n";
    
    // Test 5: Cargar auth
    echo "5. Cargando auth...\n";
    require_once __DIR__ . '/includes/auth.php';
    echo "   ✅ Auth cargado\n\n";
    
    // Test 6: Verificar sesión
    echo "6. Verificando sesión...\n";
    if (isset($_SESSION)) {
        echo "   ✅ Sesión iniciada\n";
        echo "   ℹ️  user_id: " . ($_SESSION['user_id'] ?? 'no definido') . "\n";
    } else {
        echo "   ⚠️  Sesión no iniciada\n";
    }
    
    echo "\n=== Todos los tests pasaron ===\n";
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
    echo "\nStack trace:\n" . $e->getTraceAsString() . "\n";
    exit(1);
}

