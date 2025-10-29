<?php
// Esta vista recibe $categorias del controlador
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Material - <?= h(APP_NAME) ?></title>
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
                        <h4><i class="bi bi-plus-circle"></i> Nuevo Material</h4>
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

                        <form method="POST" action="<?= base_url('materials.php') ?>">
                            <input type="hidden" name="_action" value="store">

                            <div class="mb-3">
                                <label for="sku" class="form-label">SKU (Código) <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="sku" name="sku" 
                                       required maxlength="50" 
                                       value="<?= h($_POST['sku'] ?? '') ?>"
                                       placeholder="Ej: CAB-CAT6-305M"
                                       style="text-transform: uppercase;">
                                <small class="form-text text-muted">Código único del material (se convertirá a mayúsculas)</small>
                            </div>

                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="descripcion" name="descripcion" 
                                       required maxlength="200" 
                                       value="<?= h($_POST['descripcion'] ?? '') ?>"
                                       placeholder="Ej: Cable UTP Cat 6 - 305m">
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="unidad" class="form-label">Unidad <span class="text-danger">*</span></label>
                                    <select class="form-select" id="unidad" name="unidad" required>
                                        <option value="">Seleccionar...</option>
                                        <option value="Pieza" <?= ($_POST['unidad'] ?? '') === 'Pieza' ? 'selected' : '' ?>>Pieza</option>
                                        <option value="Rollo" <?= ($_POST['unidad'] ?? '') === 'Rollo' ? 'selected' : '' ?>>Rollo</option>
                                        <option value="Metro" <?= ($_POST['unidad'] ?? '') === 'Metro' ? 'selected' : '' ?>>Metro</option>
                                        <option value="Kilogramo" <?= ($_POST['unidad'] ?? '') === 'Kilogramo' ? 'selected' : '' ?>>Kilogramo</option>
                                        <option value="Litro" <?= ($_POST['unidad'] ?? '') === 'Litro' ? 'selected' : '' ?>>Litro</option>
                                        <option value="Caja" <?= ($_POST['unidad'] ?? '') === 'Caja' ? 'selected' : '' ?>>Caja</option>
                                        <option value="Paquete" <?= ($_POST['unidad'] ?? '') === 'Paquete' ? 'selected' : '' ?>>Paquete</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="categoria" class="form-label">Categoría</label>
                                    <input type="text" class="form-control" id="categoria" name="categoria" 
                                           maxlength="50" 
                                           list="categorias-list"
                                           value="<?= h($_POST['categoria'] ?? '') ?>"
                                           placeholder="Ej: Cableado, Equipos, Conectores">
                                    <datalist id="categorias-list">
                                        <?php foreach ($categorias as $cat): ?>
                                            <option value="<?= h($cat) ?>">
                                        <?php endforeach; ?>
                                    </datalist>
                                </div>
                            </div>

                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?= base_url('materials.php') ?>" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Cancelar
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Crear Material
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
        // Convertir SKU a mayúsculas automáticamente
        document.getElementById('sku').addEventListener('input', function() {
            this.value = this.value.toUpperCase();
        });
    </script>
</body>
</html>

