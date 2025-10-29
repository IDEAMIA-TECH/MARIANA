<?php
// Esta vista recibe $projects del controlador
$user = getCurrentUser();
$isAdmin = hasRole(ROLE_ADMIN);
$canCreate = hasAnyRole([ROLE_ADMIN, ROLE_PM]);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Proyectos - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

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

        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1><i class="bi bi-folder"></i> Proyectos</h1>
            <?php if ($canCreate): ?>
                <a href="<?= base_url('projects.php?action=create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nuevo Proyecto
                </a>
            <?php endif; ?>
        </div>

        <!-- Búsqueda -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="<?= base_url('projects.php') ?>" class="row g-3">
                    <div class="col-md-10">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar por nombre o ubicación..." 
                               value="<?= h($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de proyectos -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($projects)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-folder-x" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No hay proyectos registrados</p>
                        <?php if ($canCreate): ?>
                            <a href="<?= base_url('projects.php?action=create') ?>" class="btn btn-primary">
                                Crear Primer Proyecto
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Ubicación</th>
                                    <th>Estado</th>
                                    <th>Fecha Inicio</th>
                                    <th>Fecha Fin</th>
                                    <th>Creado por</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($projects as $project): ?>
                                    <tr>
                                        <td>
                                            <strong><?= h($project['nombre']) ?></strong>
                                            <?php if ($project['descripcion']): ?>
                                                <br><small class="text-muted"><?= h(substr($project['descripcion'], 0, 60)) ?><?= strlen($project['descripcion']) > 60 ? '...' : '' ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= h($project['ubicacion'] ?? '-') ?></td>
                                        <td>
                                            <?php
                                            $estadoBadges = [
                                                'planning' => ['badge' => 'secondary', 'text' => 'Planificación'],
                                                'active' => ['badge' => 'success', 'text' => 'Activo'],
                                                'on_hold' => ['badge' => 'warning', 'text' => 'En Pausa'],
                                                'completed' => ['badge' => 'info', 'text' => 'Completado']
                                            ];
                                            $estado = $estadoBadges[$project['estado']] ?? ['badge' => 'secondary', 'text' => $project['estado']];
                                            ?>
                                            <span class="badge bg-<?= $estado['badge'] ?>"><?= $estado['text'] ?></span>
                                        </td>
                                        <td><?= $project['fecha_inicio'] ? formatDate($project['fecha_inicio'], 'd/m/Y') : '-' ?></td>
                                        <td><?= $project['fecha_fin'] ? formatDate($project['fecha_fin'], 'd/m/Y') : '-' ?></td>
                                        <td><?= h($project['created_by_name'] ?? 'N/A') ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?= base_url("requirements.php?project_id={$project['id']}") ?>" 
                                                   class="btn btn-sm btn-info" title="Requerimientos">
                                                    <i class="bi bi-list-ul"></i>
                                                </a>
                                                <?php if (hasAnyRole([ROLE_ADMIN, ROLE_PM])): ?>
                                                <a href="<?= base_url("purchases.php?project_id={$project['id']}") ?>" 
                                                   class="btn btn-sm btn-success" title="Compras">
                                                    <i class="bi bi-cart-plus"></i>
                                                </a>
                                                <?php endif; ?>
                                                <?php if (hasAnyRole([ROLE_ADMIN, ROLE_PM, ROLE_ALMACEN])): ?>
                                                <a href="<?= base_url("deliveries.php?project_id={$project['id']}") ?>" 
                                                   class="btn btn-sm btn-warning" title="Entregas" style="background-color: #28a745; border-color: #28a745; color: white;">
                                                    <i class="bi bi-truck"></i>
                                                </a>
                                                <?php endif; ?>
                                                <a href="<?= base_url("dashboard.php?id={$project['id']}") ?>" 
                                                   class="btn btn-sm btn-info" title="Dashboard">
                                                    <i class="bi bi-graph-up"></i>
                                                </a>
                                                <a href="<?= base_url("reports.php?project_id={$project['id']}") ?>" 
                                                   class="btn btn-sm btn-primary" title="Reportes">
                                                    <i class="bi bi-file-earmark-text"></i>
                                                </a>
                                                <?php if ($isAdmin || $project['created_by'] == $user['id']): ?>
                                                    <a href="<?= base_url("projects.php?action=edit&id={$project['id']}") ?>" 
                                                       class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($isAdmin): ?>
                                                    <form method="POST" action="<?= base_url('projects.php') ?>" 
                                                          style="display:inline;" 
                                                          onsubmit="return confirm('¿Estás seguro de eliminar este proyecto?');">
                                                        <input type="hidden" name="_action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $project['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

