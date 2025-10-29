<?php
declare(strict_types=1);

// Configuración de Error Logging
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
require_once __DIR__ . '/models/Project.php';

// Cargar utilidades de autenticación
require_once __DIR__ . '/includes/auth.php';

// Cargar controladores
require_once __DIR__ . '/controllers/ProjectController.php';

// Requerir autenticación
requireAuth();

// Router simple
$action = $_GET['action'] ?? 'index';
$method = $_SERVER['REQUEST_METHOD'];

// Mapear acciones
if ($method === 'POST' && isset($_POST['_action'])) {
    $action = $_POST['_action'];
}

switch ($action) {
    case 'create':
        ProjectController::create();
        break;
    case 'store':
        ProjectController::store();
        break;
    case 'edit':
        ProjectController::edit();
        break;
    case 'update':
        ProjectController::update();
        break;
    case 'delete':
        ProjectController::delete();
        break;
    case 'dashboard':
        ProjectController::dashboard();
        break;
    case 'index':
    default:
        ProjectController::index();
        break;
}

