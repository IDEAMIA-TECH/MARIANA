<?php
declare(strict_types=1);

/**
 * Configuración general de la aplicación
 */

require_once __DIR__ . '/database.php';

// Configuración de la aplicación
define('APP_NAME', 'Control de Materiales');
define('APP_URL', 'http://localhost/control-materiales');
define('TIMEZONE', 'America/Mexico_City');
date_default_timezone_set(TIMEZONE);

// Seguridad
define('SESSION_LIFETIME', 3600); // 1 hora
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

