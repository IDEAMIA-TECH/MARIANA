<?php
// Esta vista recibe $project del controlador
$user = getCurrentUser();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Proyecto - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4><i class="bi bi-pencil"></i> Editar Proyecto</h4>
                    </div>
                    <div class="card-body">
                        <?php 
                        $flash = getFlashMessage();
                        if ($flash): 
                        ?>
                            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                                <?= h($flash['message']) ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="<?= base_url('projects.php') ?>">
                            <input type="hidden" name="_action" value="update">
                            <input type="hidden" name="id" value="<?= $project['id'] ?>">

                            <div class="mb-3">
                                <label for="nombre" class="form-label">Nombre del Proyecto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" 
                                       required maxlength="150" value="<?= h($project['nombre']) ?>">
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" 
                                          rows="3"><?= h($project['descripcion'] ?? '') ?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="ubicacion" class="form-label">Ubicación</label>
                                <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                                       maxlength="200" value="<?= h($project['ubicacion'] ?? '') ?>">
                            </div>

                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="estado" class="form-label">Estado</label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="planning" <?= $project['estado'] === 'planning' ? 'selected' : '' ?>>Planificación</option>
                                        <option value="active" <?= $project['estado'] === 'active' ? 'selected' : '' ?>>Activo</option>
                                        <option value="on_hold" <?= $project['estado'] === 'on_hold' ? 'selected' : '' ?>>En Pausa</option>
                                        <option value="completed" <?= $project['estado'] === 'completed' ? 'selected' : '' ?>>Completado</option>
                                    </select>
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="fecha_inicio" class="form-label">Fecha de Inicio</label>
                                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                           value="<?= $project['fecha_inicio'] ?? '' ?>">
                                </div>

                                <div class="col-md-4 mb-3">
                                    <label for="fecha_fin" class="form-label">Fecha de Fin</label>
                                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                           value="<?= $project['fecha_fin'] ?? '' ?>">
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= base_url('projects.php') ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Guardar Cambios
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('fecha_fin').addEventListener('change', function() {
            const inicio = document.getElementById('fecha_inicio').value;
            const fin = this.value;
            if (inicio && fin && fin < inicio) {
                alert('La fecha de fin no puede ser anterior a la fecha de inicio');
                this.value = '';
            }
        });
    </script>
</body>
</html>

