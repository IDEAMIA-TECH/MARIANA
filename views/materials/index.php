<?php
// Esta vista recibe $materials, $categorias del controlador
$user = getCurrentUser();
$canCreate = hasAnyRole([ROLE_ADMIN, ROLE_PM]);
$isAdmin = hasRole(ROLE_ADMIN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materiales - <?= h(APP_NAME) ?></title>
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
            <h1><i class="bi bi-box"></i> Catálogo de Materiales</h1>
            <?php if ($canCreate): ?>
                <a href="<?= base_url('materials.php?action=create') ?>" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Nuevo Material
                </a>
            <?php endif; ?>
        </div>

        <!-- Filtros -->
        <div class="card mb-4">
            <div class="card-body">
                <form method="GET" action="<?= base_url('materials.php') ?>" class="row g-3">
                    <div class="col-md-5">
                        <input type="text" name="search" class="form-control" 
                               placeholder="Buscar por SKU o descripción..." 
                               value="<?= h($_GET['search'] ?? '') ?>">
                    </div>
                    <div class="col-md-4">
                        <select name="categoria" class="form-select">
                            <option value="">Todas las categorías</option>
                            <?php foreach ($categorias as $cat): ?>
                                <option value="<?= h($cat) ?>" <?= ($_GET['categoria'] ?? '') === $cat ? 'selected' : '' ?>>
                                    <?= h($cat) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="inactive" value="1" id="showInactive"
                                   <?= isset($_GET['inactive']) ? 'checked' : '' ?>>
                            <label class="form-check-label" for="showInactive">
                                Mostrar inactivos
                            </label>
                        </div>
                    </div>
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-outline-primary">
                            <i class="bi bi-search"></i> Buscar
                        </button>
                        <a href="<?= base_url('materials.php') ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-counterclockwise"></i> Limpiar
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabla de materiales -->
        <div class="card">
            <div class="card-body">
                <?php if (empty($materials)): ?>
                    <div class="text-center py-5">
                        <i class="bi bi-box-x" style="font-size: 3rem; color: #ccc;"></i>
                        <p class="text-muted mt-3">No se encontraron materiales</p>
                        <?php if ($canCreate): ?>
                            <a href="<?= base_url('materials.php?action=create') ?>" class="btn btn-primary">
                                Crear Primer Material
                            </a>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>SKU</th>
                                    <th>Descripción</th>
                                    <th>Unidad</th>
                                    <th>Categoría</th>
                                    <th>Usado en</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materials as $material): ?>
                                    <tr class="<?= !$material['activo'] ? 'table-secondary' : '' ?>">
                                        <td><code><?= h($material['sku']) ?></code></td>
                                        <td><?= h($material['descripcion']) ?></td>
                                        <td><?= h($material['unidad']) ?></td>
                                        <td><?= h($material['categoria'] ?? '-') ?></td>
                                        <td>
                                            <?php if ($material['usado_en_proyectos'] > 0): ?>
                                                <span class="badge bg-info"><?= $material['usado_en_proyectos'] ?> proyecto(s)</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($material['activo']): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactivo</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <?php if ($canCreate): ?>
                                                    <a href="<?= base_url("materials.php?action=edit&id={$material['id']}") ?>" 
                                                       class="btn btn-sm btn-warning" title="Editar">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <?php if ($isAdmin): ?>
                                                    <form method="POST" action="<?= base_url('materials.php') ?>" 
                                                          style="display:inline;" 
                                                          onsubmit="return confirm('¿Estás seguro de eliminar este material?');">
                                                        <input type="hidden" name="_action" value="delete">
                                                        <input type="hidden" name="id" value="<?= $material['id'] ?>">
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

