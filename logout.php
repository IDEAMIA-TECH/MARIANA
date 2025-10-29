<?php
declare(strict_types=1);

// Cargar configuración
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';

// Cargar funciones auxiliares
require_once __DIR__ . '/includes/functions.php';

// Cargar utilidades de autenticación
require_once __DIR__ . '/includes/auth.php';

// Cargar controladores
require_once __DIR__ . '/controllers/AuthController.php';

AuthController::logout();

