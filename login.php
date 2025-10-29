<?php
declare(strict_types=1);

// ============================================
// Configuración de Error Logging
// ============================================
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}
ini_set('log_errors', 1);
ini_set('error_log', $log_dir . '/error.log');
$is_development = true;
ini_set('display_errors', $is_development ? 1 : 0);
ini_set('display_startup_errors', $is_development ? 1 : 0);
error_reporting($is_development ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Cargar configuración
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';

// Cargar funciones auxiliares
require_once __DIR__ . '/includes/functions.php';

// Cargar modelos
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/User.php';

// Cargar utilidades de autenticación
require_once __DIR__ . '/includes/auth.php';

// Cargar controladores
require_once __DIR__ . '/controllers/AuthController.php';

// Si ya está autenticado, redirigir al index
if (isAuthenticated()) {
    redirect(base_url('index.php'));
}

// Procesar login
AuthController::login();

// Mostrar vista de login
require_once __DIR__ . '/views/auth/login.php';

