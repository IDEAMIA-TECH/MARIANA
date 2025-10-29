<?php
declare(strict_types=1);

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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?= base_url('index.php') ?>">
                <i class="bi bi-box-seam"></i> <?= h(APP_NAME) ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= base_url('index.php') ?>">
                            <i class="bi bi-house"></i> Inicio
                        </a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle"></i> <?= h($user['nombre']) ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><span class="dropdown-item-text"><small>Rol: <?= h($user['rol']) ?></small></span></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?= base_url('logout.php') ?>">
                                <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

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
                                        <small class="text-muted">Próximamente</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card border-success">
                                    <div class="card-body text-center">
                                        <i class="bi bi-box text-success" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">Materiales</h5>
                                        <p class="text-muted">Catálogo de materiales</p>
                                        <small class="text-muted">Próximamente</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="card border-info">
                                    <div class="card-body text-center">
                                        <i class="bi bi-graph-up text-info" style="font-size: 2rem;"></i>
                                        <h5 class="mt-2">Dashboard</h5>
                                        <p class="text-muted">Ver avance y estadísticas</p>
                                        <small class="text-muted">Próximamente</small>
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

