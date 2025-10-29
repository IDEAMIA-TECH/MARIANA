<?php
declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/controllers/AuthController.php';

// Si ya está autenticado, redirigir al index
if (isAuthenticated()) {
    redirect(base_url('index.php'));
}

// Procesar login
AuthController::login();

// Mostrar vista de login
require_once __DIR__ . '/views/auth/login.php';

