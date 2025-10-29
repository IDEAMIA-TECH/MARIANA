<?php
declare(strict_types=1);

// ============================================
// Configuración de Error Logging
// ============================================
// Crear directorio de logs si no existe
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}

// Configurar logging personalizado
ini_set('log_errors', 1);
ini_set('error_log', $log_dir . '/error.log');

// En desarrollo: mostrar errores en pantalla
// En producción: cambiar display_errors a 0
$is_development = true; // Cambiar a false en producción
ini_set('display_errors', $is_development ? 1 : 0);
ini_set('display_startup_errors', $is_development ? 1 : 0);
error_reporting($is_development ? E_ALL : E_ALL & ~E_DEPRECATED & ~E_STRICT);

// Manejar errores fatales
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        $message = sprintf(
            "FATAL ERROR: %s en %s línea %d",
            $error['message'],
            $error['file'],
            $error['line']
        );
        error_log($message);
        
        if (ini_get('display_errors')) {
            echo "<div style='background:#f8d7da;color:#721c24;padding:20px;margin:20px;border:1px solid #f5c6cb;border-radius:5px;'>";
            echo "<h3>Error Fatal</h3>";
            echo "<p><strong>" . htmlspecialchars($error['message']) . "</strong></p>";
            echo "<p><small>Archivo: " . htmlspecialchars($error['file']) . "</small></p>";
            echo "<p><small>Línea: " . $error['line'] . "</small></p>";
            echo "</div>";
        }
    }
});

// Cargar configuración
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';

// Cargar funciones auxiliares
require_once __DIR__ . '/includes/functions.php';

// Cargar modelos
require_once __DIR__ . '/models/Database.php';

// Cargar utilidades de autenticación
require_once __DIR__ . '/includes/auth.php';

// Requerir autenticación
requireAuth();

// Obtener usuario actual
$user = getCurrentUser();
if (!$user) {
    // Si no se puede obtener el usuario, cerrar sesión
    logout();
    setFlashMessage('error', 'Tu sesión expiró. Por favor inicia sesión nuevamente.');
    redirect(base_url('login.php'));
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inicio - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/views/layouts/header.php'; ?>

    <div class="container mt-4">
        <?php 
        $flash = getFlashMessage();
        if ($flash): 
        ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= h($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h1 class="card-title">
                            <i class="bi bi-house-door"></i> Bienvenido, <?= h($user['nombre']) ?>
                        </h1>
                        <p class="card-text">Sistema de Control de Materiales para Proyectos</p>

                        <div class="row mt-4">
                            <div class="col-md-4 mb-3">
                                <div class="card border-primary">
                                    <div class="card-body text-center">
                                        <i class="bi bi-folder text-primary" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">Proyectos</h5>
                                        <p class="text-muted">Gestiona tus proyectos</p>
                                        <a href="<?= base_url('projects.php') ?>" class="btn btn-primary btn-sm mt-2">
                                            <i class="bi bi-arrow-right"></i> Ver Proyectos
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <i class="bi bi-box text-success" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">Materiales</h5>
                                        <p class="text-muted">Catálogo de materiales</p>
                                        <?php if (hasAnyRole([ROLE_ADMIN, ROLE_PM])): ?>
                                            <a href="<?= base_url('materials.php') ?>" class="btn btn-success btn-sm mt-2">
                                                <i class="bi bi-arrow-right"></i> Ver Materiales
                                            </a>
                                        <?php else: ?>
                                            <small class="text-muted">Solo Admin/PM</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <i class="bi bi-graph-up text-info" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">Dashboard & Reportes</h5>
                                        <p class="text-muted">Ver avance y estadísticas</p>
                                        <a href="<?= base_url('projects.php') ?>" class="btn btn-info btn-sm mt-2">
                                            <i class="bi bi-arrow-right"></i> Ver Proyectos
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

