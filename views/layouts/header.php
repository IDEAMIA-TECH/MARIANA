<?php
$user = getCurrentUser();
$current_page = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= base_url('index.php') ?>">
            <i class="bi bi-box-seam"></i> <?= h(APP_NAME) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index.php' ? 'active' : '' ?>" 
                       href="<?= base_url('index.php') ?>">
                        <i class="bi bi-house"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'projects.php' ? 'active' : '' ?>" 
                       href="<?= base_url('projects.php') ?>">
                        <i class="bi bi-folder"></i> Proyectos
                    </a>
                </li>
                <?php if (hasAnyRole([ROLE_ADMIN, ROLE_PM])): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'materials.php' ? 'active' : '' ?>" 
                       href="<?= base_url('materials.php') ?>">
                        <i class="bi bi-box"></i> Materiales
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <ul class="navbar-nav">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <i class="bi bi-person-circle"></i> <?= h($user['nombre']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text">
                            <small><strong>Rol:</strong> <?= h($user['rol']) ?></small>
                        </span></li>
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

