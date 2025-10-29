<?php
// Vista recibe: $project, $mainTasks, $requirements, $users
$projectId = $project['id'];
$canEdit = hasAnyRole([ROLE_ADMIN, ROLE_PM]);
$isAdmin = hasRole(ROLE_ADMIN);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tareas - <?= h($project['nombre']) ?> - <?= h(APP_NAME) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .task-card {
            transition: transform 0.2s, box-shadow 0.2s;
            border-left: 4px solid #0d6efd;
        }
        .task-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .subtask-item {
            border-left: 3px solid #6c757d;
            margin-left: 2rem;
            padding-left: 1rem;
        }
        .material-badge {
            font-size: 0.75rem;
        }
        .progress-task {
            height: 8px;
            font-size: 10px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../layouts/header.php'; ?>

    <div class="container-fluid mt-4">
        <?php 
        $flash = getFlashMessage();
        if ($flash): 
        ?>
            <div class="alert alert-<?= $flash['type'] === 'error' ? 'danger' : 'success' ?> alert-dismissible fade show" role="alert">
                <?= h($flash['message']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Header -->
        <div class="card mb-4">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2><i class="bi bi-list-check"></i> Gestión de Tareas</h2>
                        <h4 class="text-muted"><?= h($project['nombre']) ?></h4>
                    </div>
                    <div>
                        <a href="<?= base_url('projects.php') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Volver a Proyectos
                        </a>
                        <?php if ($canEdit): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                                <i class="bi bi-plus-circle"></i> Nueva Tarea Principal
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($canEdit): ?>
        <!-- Formulario para agregar tareas (múltiples a la vez) -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Agregar Tareas Principales</h5>
                <div>
                    <button type="button" class="btn btn-sm btn-light" onclick="toggleTaskView()" id="toggleTaskBtn">
                        <i class="bi bi-list-check"></i> Modo Masivo
                    </button>
                </div>
            </div>
            <div class="card-body">
                <!-- Modo Simple (Una tarea a la vez) -->
                <div id="single-task-mode">
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                        <i class="bi bi-plus-circle"></i> Crear Tarea Individual
                    </button>
                    <small class="text-muted ms-2">O usa el botón "Nueva Tarea Principal" de arriba</small>
                </div>

                <!-- Modo Masivo (Varias tareas a la vez) -->
                <div id="multiple-task-mode" style="display: none;">
                    <form method="POST" action="<?= base_url('tasks.php') ?>" id="multipleTaskForm">
                        <input type="hidden" name="_action" value="store">
                        <input type="hidden" name="project_id" value="<?= $projectId ?>">
                        
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label"><strong>Agregar múltiples tareas principales:</strong></label>
                                <div>
                                    <button type="button" class="btn btn-sm btn-success" onclick="addTaskRow()">
                                        <i class="bi bi-plus-lg"></i> Agregar Fila
                                    </button>
                                    <button type="button" class="btn btn-sm btn-danger" onclick="removeLastTaskRow()">
                                        <i class="bi bi-dash-lg"></i> Quitar Fila
                                    </button>
                                </div>
                            </div>
                            
                            <div class="table-responsive" style="max-height: 500px; overflow-y: auto;">
                                <table class="table table-sm table-bordered table-hover">
                                    <thead class="table-light sticky-top">
                                        <tr>
                                            <th style="width: 30px;">#</th>
                                            <th style="width: 250px;">Nombre <span class="text-danger">*</span></th>
                                            <th>Descripción</th>
                                            <th style="width: 120px;">Estado</th>
                                            <th style="width: 130px;">Responsable</th>
                                            <th style="width: 130px;">Fecha Inicio</th>
                                            <th style="width: 130px;">Fecha Fin</th>
                                        </tr>
                                    </thead>
                                    <tbody id="task-rows-container">
                                        <!-- Las filas se agregarán dinámicamente -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div class="mt-3 d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-info" id="task-count">0 tareas</span>
                                    <small class="text-muted ms-2">Usa "Agregar Fila" para crear más tareas</small>
                                </div>
                                <div>
                                    <button type="button" class="btn btn-secondary" onclick="toggleTaskView()">
                                        <i class="bi bi-arrow-left"></i> Modo Simple
                                    </button>
                                    <button type="submit" class="btn btn-primary" id="submit-multiple-tasks">
                                        <i class="bi bi-check-circle"></i> Crear Tareas
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Lista de Tareas Principales -->
        <?php if (empty($mainTasks)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <i class="bi bi-clipboard-check" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">Este proyecto no tiene tareas definidas</p>
                    <?php if ($canEdit): ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTaskModal">
                            <i class="bi bi-plus-circle"></i> Crear Primera Tarea
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <?php foreach ($mainTasks as $task): 
                $estadoColors = [
                    'pending' => 'secondary',
                    'in_progress' => 'primary',
                    'completed' => 'success',
                    'cancelled' => 'danger'
                ];
                $estadoTextos = [
                    'pending' => 'Pendiente',
                    'in_progress' => 'En Progreso',
                    'completed' => 'Completada',
                    'cancelled' => 'Cancelada'
                ];
                $estadoColor = $estadoColors[$task['estado']] ?? 'secondary';
                $estadoTexto = $estadoTextos[$task['estado']] ?? $task['estado'];
                $stats = $task['stats'] ?? [];
            ?>
                <div class="card task-card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div class="flex-grow-1">
                            <h5 class="mb-0">
                                <i class="bi bi-folder2-open text-primary"></i>
                                <?= h($task['nombre']) ?>
                                <?php if ($task['subtareas_count'] > 0): ?>
                                    <span class="badge bg-info"><?= $task['subtareas_count'] ?> subtarea(s)</span>
                                <?php endif; ?>
                                <?php if ($task['materiales_count'] > 0): ?>
                                    <span class="badge bg-warning"><?= $task['materiales_count'] ?> material(es)</span>
                                <?php endif; ?>
                            </h5>
                            <?php if ($task['descripcion']): ?>
                                <small class="text-muted"><?= h($task['descripcion']) ?></small>
                            <?php endif; ?>
                        </div>
                        <div>
                            <span class="badge bg-<?= $estadoColor ?> me-2"><?= $estadoTexto ?></span>
                            <?php if ($canEdit): ?>
                                <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editTaskModal<?= $task['id'] ?>">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <button type="button" class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#addSubtaskModal<?= $task['id'] ?>">
                                    <i class="bi bi-plus"></i> Subtarea
                                </button>
                                <?php if ($isAdmin): ?>
                                    <form method="POST" action="<?= base_url('tasks.php') ?>" style="display:inline;" onsubmit="return confirm('¿Eliminar esta tarea y todas sus subtareas?');">
                                        <input type="hidden" name="_action" value="delete">
                                        <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Estadísticas -->
                        <div class="row mb-3">
                            <?php if (!empty($stats)): ?>
                                <div class="col-md-3">
                                    <small class="text-muted">Avance Materiales</small>
                                    <div class="progress progress-task">
                                        <div class="progress-bar bg-success" style="width: <?= $stats['pct_qty_entregada'] ?>%"></div>
                                    </div>
                                    <small><?= $stats['pct_qty_entregada'] ?>% (<?= $stats['materiales_entregados'] ?>/<?= $stats['total_materiales'] ?>)</small>
                                </div>
                                <?php if ($task['subtareas_count'] > 0): ?>
                                    <div class="col-md-3">
                                        <small class="text-muted">Avance Subtareas</small>
                                        <div class="progress progress-task">
                                            <div class="progress-bar bg-primary" style="width: <?= $stats['pct_subtareas'] ?>%"></div>
                                        </div>
                                        <small><?= $stats['pct_subtareas'] ?>% (<?= $stats['subtareas_completadas'] ?>/<?= $stats['total_subtareas'] ?>)</small>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                            
                            <?php if ($task['fecha_inicio']): ?>
                                <div class="col-md-3">
                                    <small class="text-muted">Inicio</small><br>
                                    <small><?= formatDate($task['fecha_inicio'], 'd/m/Y') ?></small>
                                </div>
                            <?php endif; ?>
                            <?php if ($task['fecha_fin_estimada']): ?>
                                <div class="col-md-3">
                                    <small class="text-muted">Fin Estimado</small><br>
                                    <small><?= formatDate($task['fecha_fin_estimada'], 'd/m/Y') ?></small>
                                </div>
                            <?php endif; ?>
                            <?php if ($task['responsable_nombre']): ?>
                                <div class="col-md-3">
                                    <small class="text-muted">Responsable</small><br>
                                    <small><?= h($task['responsable_nombre']) ?></small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Subtareas -->
                        <?php if (!empty($task['subtareas'])): ?>
                            <div class="mb-3">
                                <h6><i class="bi bi-list-nested"></i> Subtareas:</h6>
                                <?php foreach ($task['subtareas'] as $subtask): 
                                    $subEstadoColor = $estadoColors[$subtask['estado']] ?? 'secondary';
                                    $subEstadoTexto = $estadoTextos[$subtask['estado']] ?? $subtask['estado'];
                                ?>
                                    <div class="subtask-item mb-2 p-2 bg-light rounded">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?= h($subtask['nombre']) ?></strong>
                                                <?php if ($subtask['materiales_count'] > 0): ?>
                                                    <span class="badge bg-warning material-badge ms-2"><?= $subtask['materiales_count'] ?> mat.</span>
                                                <?php endif; ?>
                                                <span class="badge bg-<?= $subEstadoColor ?> ms-2"><?= $subEstadoTexto ?></span>
                                            </div>
                                            <div>
                                                <?php if ($canEdit): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editTaskModal<?= $subtask['id'] ?>">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <?php if ($isAdmin): ?>
                                                        <form method="POST" action="<?= base_url('tasks.php') ?>" style="display:inline;" onsubmit="return confirm('¿Eliminar esta subtarea?');">
                                                            <input type="hidden" name="_action" value="delete">
                                                            <input type="hidden" name="id" value="<?= $subtask['id'] ?>">
                                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                                <i class="bi bi-trash"></i>
                                                            </button>
                                                        </form>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Materiales Asignados -->
                        <?php 
                        $taskMaterials = Task::getMaterials($task['id']);
                        if (!empty($taskMaterials)): 
                        ?>
                            <div class="mb-3">
                                <h6><i class="bi bi-box-seam"></i> Materiales Asignados:</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Material</th>
                                                <th>Cantidad Asignada</th>
                                                <th>Total Proyecto</th>
                                                <th>Entregado</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($taskMaterials as $mat): ?>
                                                <tr>
                                                    <td>
                                                        <strong><?= h($mat['descripcion']) ?></strong><br>
                                                        <small class="text-muted"><code><?= h($mat['sku']) ?></code></small>
                                                    </td>
                                                    <td><?= number_format($mat['qty_asignada'], 2) ?> <?= h($mat['unidad']) ?></td>
                                                    <td><?= number_format($mat['qty_total_proyecto'], 2) ?> <?= h($mat['unidad']) ?></td>
                                                    <td>
                                                        <?php 
                                                        $pctEntregado = $mat['qty_asignada'] > 0 
                                                            ? round(($mat['qty_entregada'] / $mat['qty_asignada']) * 100, 1) 
                                                            : 0;
                                                        ?>
                                                        <span class="badge bg-<?= $pctEntregado >= 100 ? 'success' : ($pctEntregado > 0 ? 'warning' : 'danger') ?>">
                                                            <?= number_format($mat['qty_entregada'], 2) ?> (<?= $pctEntregado ?>%)
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <?php if ($canEdit): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-danger" 
                                                                    data-bs-toggle="modal" 
                                                                    data-bs-target="#unassignMaterialModal<?= $task['id'] ?>_<?= $mat['requirement_id'] ?>">
                                                                <i class="bi bi-x"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>

                                                <!-- Modal Desasignar Material -->
                                                <?php if ($canEdit): ?>
                                                <div class="modal fade" id="unassignMaterialModal<?= $task['id'] ?>_<?= $mat['requirement_id'] ?>" tabindex="-1">
                                                    <div class="modal-dialog">
                                                        <div class="modal-content">
                                                            <form method="POST" action="<?= base_url('tasks.php') ?>">
                                                                <input type="hidden" name="_action" value="unassign_material">
                                                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                                                <input type="hidden" name="requirement_id" value="<?= $mat['requirement_id'] ?>">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Desasignar Material</h5>
                                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p>¿Desasignar <strong><?= h($mat['descripcion']) ?></strong> de esta tarea?</p>
                                                                    <p class="text-muted">La cantidad asignada (<strong><?= number_format($mat['qty_asignada'], 2) ?></strong>) quedará disponible para otras tareas.</p>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                                    <button type="submit" class="btn btn-danger">Desasignar</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Botón para asignar materiales -->
                        <?php if ($canEdit && !empty($requirements)): ?>
                            <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#assignMaterialModal<?= $task['id'] ?>">
                                <i class="bi bi-plus-circle"></i> Asignar Materiales
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Modal Editar Tarea -->
                <?php if ($canEdit): ?>
                <div class="modal fade" id="editTaskModal<?= $task['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST" action="<?= base_url('tasks.php') ?>">
                                <input type="hidden" name="_action" value="update">
                                <input type="hidden" name="id" value="<?= $task['id'] ?>">
                                
                                <div class="modal-header">
                                    <h5 class="modal-title">Editar Tarea</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="nombre_<?= $task['id'] ?>" class="form-label">Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="nombre_<?= $task['id'] ?>" name="nombre" 
                                               value="<?= h($task['nombre']) ?>" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="descripcion_<?= $task['id'] ?>" class="form-label">Descripción</label>
                                        <textarea class="form-control" id="descripcion_<?= $task['id'] ?>" name="descripcion" rows="3"><?= h($task['descripcion'] ?? '') ?></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="estado_<?= $task['id'] ?>" class="form-label">Estado</label>
                                            <select class="form-select" id="estado_<?= $task['id'] ?>" name="estado">
                                                <option value="pending" <?= $task['estado'] === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                                                <option value="in_progress" <?= $task['estado'] === 'in_progress' ? 'selected' : '' ?>>En Progreso</option>
                                                <option value="completed" <?= $task['estado'] === 'completed' ? 'selected' : '' ?>>Completada</option>
                                                <option value="cancelled" <?= $task['estado'] === 'cancelled' ? 'selected' : '' ?>>Cancelada</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="responsable_<?= $task['id'] ?>" class="form-label">Responsable</label>
                                            <select class="form-select" id="responsable_<?= $task['id'] ?>" name="responsable_id">
                                                <option value="">Sin asignar</option>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?= $user['id'] ?>" <?= $task['responsable_id'] == $user['id'] ? 'selected' : '' ?>>
                                                        <?= h($user['nombre']) ?> (<?= h($user['email']) ?>)
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="fecha_inicio_<?= $task['id'] ?>" class="form-label">Fecha Inicio</label>
                                            <input type="date" class="form-control" id="fecha_inicio_<?= $task['id'] ?>" name="fecha_inicio" 
                                                   value="<?= $task['fecha_inicio'] ?? '' ?>">
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="fecha_fin_<?= $task['id'] ?>" class="form-label">Fecha Fin Estimada</label>
                                            <input type="date" class="form-control" id="fecha_fin_<?= $task['id'] ?>" name="fecha_fin_estimada" 
                                                   value="<?= $task['fecha_fin_estimada'] ?? '' ?>">
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Modal Asignar Materiales a Tarea -->
                <?php if ($canEdit && !empty($requirements)): ?>
                <div class="modal fade" id="assignMaterialModal<?= $task['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST" action="<?= base_url('tasks.php') ?>">
                                <input type="hidden" name="_action" value="assign_material">
                                <input type="hidden" name="task_id" value="<?= $task['id'] ?>">
                                
                                <div class="modal-header">
                                    <h5 class="modal-title">Asignar Material a Tarea: <?= h($task['nombre']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="requirement_<?= $task['id'] ?>" class="form-label">Material <span class="text-danger">*</span></label>
                                        <select class="form-select" id="requirement_<?= $task['id'] ?>" name="requirement_id" required>
                                            <option value="">Seleccionar material...</option>
                                            <?php 
                                            // Obtener materiales ya asignados a esta tarea
                                            $assignedMaterialIds = array_column(Task::getMaterials($task['id']), 'requirement_id');
                                            
                                            foreach ($requirements as $req): 
                                                if (in_array($req['id'], $assignedMaterialIds)) continue;
                                                
                                                // Calcular cantidad disponible para asignar
                                                $assignedInOtherTasks = Database::fetchOne(
                                                    "SELECT COALESCE(SUM(qty_asignada), 0) as total 
                                                     FROM task_requirements 
                                                     WHERE requirement_id = ?",
                                                    [$req['id']]
                                                );
                                                $qtyTotal = (float)$req['qty_requerida'];
                                                $qtyAssigned = (float)($assignedInOtherTasks['total'] ?? 0);
                                                $qtyAvailable = $qtyTotal - $qtyAssigned;
                                            ?>
                                                <option value="<?= $req['id'] ?>" 
                                                        data-available="<?= $qtyAvailable ?>"
                                                        data-unidad="<?= h($req['unidad']) ?>">
                                                    <?= h($req['sku']) ?> - <?= h($req['descripcion']) ?> 
                                                    (Disponible: <?= number_format($qtyAvailable, 2) ?> <?= h($req['unidad']) ?>)
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Solo materiales del proyecto que no estén asignados a esta tarea</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="qty_<?= $task['id'] ?>" class="form-label">Cantidad a Asignar <span class="text-danger">*</span></label>
                                        <input type="number" class="form-control" id="qty_<?= $task['id'] ?>" name="qty_asignada" 
                                               required min="0.01" step="0.01" placeholder="0.00">
                                        <small class="text-muted" id="qty-hint-<?= $task['id'] ?>">Máximo disponible según material seleccionado</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="comentarios_<?= $task['id'] ?>" class="form-label">Comentarios</label>
                                        <textarea class="form-control" id="comentarios_<?= $task['id'] ?>" name="comentarios" rows="2"></textarea>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Asignar Material</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Modal Agregar Subtarea -->
                <?php if ($canEdit): ?>
                <div class="modal fade" id="addSubtaskModal<?= $task['id'] ?>" tabindex="-1">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <form method="POST" action="<?= base_url('tasks.php') ?>">
                                <input type="hidden" name="_action" value="store">
                                <input type="hidden" name="project_id" value="<?= $projectId ?>">
                                <input type="hidden" name="parent_id" value="<?= $task['id'] ?>">
                                
                                <div class="modal-header">
                                    <h5 class="modal-title">Crear Subtarea para: <?= h($task['nombre']) ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="mb-3">
                                        <label for="subtask_nombre_<?= $task['id'] ?>" class="form-label">Nombre de Subtarea <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="subtask_nombre_<?= $task['id'] ?>" name="nombre" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="subtask_descripcion_<?= $task['id'] ?>" class="form-label">Descripción</label>
                                        <textarea class="form-control" id="subtask_descripcion_<?= $task['id'] ?>" name="descripcion" rows="2"></textarea>
                                    </div>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="subtask_estado_<?= $task['id'] ?>" class="form-label">Estado</label>
                                            <select class="form-select" id="subtask_estado_<?= $task['id'] ?>" name="estado">
                                                <option value="pending">Pendiente</option>
                                                <option value="in_progress">En Progreso</option>
                                            </select>
                                        </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="subtask_responsable_<?= $task['id'] ?>" class="form-label">Responsable</label>
                                            <select class="form-select" id="subtask_responsable_<?= $task['id'] ?>" name="responsable_id">
                                                <option value="">Sin asignar</option>
                                                <?php foreach ($users as $user): ?>
                                                    <option value="<?= $user['id'] ?>">
                                                        <?= h($user['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                    <button type="submit" class="btn btn-primary">Crear Subtarea</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

            <?php endforeach; ?>
        <?php endif; ?>

        <!-- Modal Crear Tarea Principal -->
        <?php if ($canEdit): ?>
        <div class="modal fade" id="createTaskModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <form method="POST" action="<?= base_url('tasks.php') ?>">
                        <input type="hidden" name="_action" value="store">
                        <input type="hidden" name="project_id" value="<?= $projectId ?>">
                        
                        <div class="modal-header bg-primary text-white">
                            <h5 class="modal-title">Nueva Tarea Principal</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="task_nombre" class="form-label">Nombre de Tarea <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="task_nombre" name="nombre" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="task_descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="task_descripcion" name="descripcion" rows="3"></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="task_estado" class="form-label">Estado</label>
                                    <select class="form-select" id="task_estado" name="estado">
                                        <option value="pending">Pendiente</option>
                                        <option value="in_progress">En Progreso</option>
                                    </select>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="task_responsable" class="form-label">Responsable</label>
                                    <select class="form-select" id="task_responsable" name="responsable_id">
                                        <option value="">Sin asignar</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="<?= $user['id'] ?>">
                                                <?= h($user['nombre']) ?> (<?= h($user['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="task_fecha_inicio" class="form-label">Fecha Inicio</label>
                                    <input type="date" class="form-control" id="task_fecha_inicio" name="fecha_inicio">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="task_fecha_fin" class="form-label">Fecha Fin Estimada</label>
                                    <input type="date" class="form-control" id="task_fecha_fin" name="fecha_fin_estimada">
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i> 
                                <strong>Nota:</strong> Puedes asignar materiales a esta tarea después de crearla.
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Crear Tarea</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Variables para el modo masivo de tareas
        let taskRowCounter = 0;
        const users = <?= json_encode(array_map(function($u) { return ['id' => $u['id'], 'nombre' => $u['nombre'], 'email' => $u['email']]; }, $users)) ?>;
        
        // Toggle entre modo simple y masivo para tareas
        function toggleTaskView() {
            const singleMode = document.getElementById('single-task-mode');
            const multipleMode = document.getElementById('multiple-task-mode');
            const toggleBtn = document.getElementById('toggleTaskBtn');
            
            if (multipleMode.style.display === 'none') {
                singleMode.style.display = 'none';
                multipleMode.style.display = 'block';
                toggleBtn.innerHTML = '<i class="bi bi-list"></i> Modo Simple';
                if (taskRowCounter === 0) {
                    addTaskRow(); // Agregar primera fila automáticamente
                }
            } else {
                singleMode.style.display = 'block';
                multipleMode.style.display = 'none';
                toggleBtn.innerHTML = '<i class="bi bi-list-check"></i> Modo Masivo';
            }
        }
        
        // Agregar nueva fila para tarea
        function addTaskRow() {
            taskRowCounter++;
            const container = document.getElementById('task-rows-container');
            const row = document.createElement('tr');
            row.id = 'task-row-' + taskRowCounter;
            
            const estadoOptions = [
                {value: 'pending', text: 'Pendiente'},
                {value: 'in_progress', text: 'En Progreso'},
                {value: 'completed', text: 'Completada'}
            ];
            
            let responsableOptions = '<option value="">Sin asignar</option>';
            users.forEach(function(user) {
                responsableOptions += `<option value="${user.id}">${user.nombre}</option>`;
            });
            
            row.innerHTML = `
                <td class="text-center">${taskRowCounter}</td>
                <td>
                    <input type="text" class="form-control form-control-sm" 
                           name="tasks[${taskRowCounter}][nombre]" 
                           placeholder="Nombre de tarea" required>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" 
                           name="tasks[${taskRowCounter}][descripcion]" 
                           placeholder="Descripción opcional">
                </td>
                <td>
                    <select class="form-select form-select-sm" name="tasks[${taskRowCounter}][estado]">
                        ${estadoOptions.map(opt => `<option value="${opt.value}">${opt.text}</option>`).join('')}
                    </select>
                </td>
                <td>
                    <select class="form-select form-select-sm" name="tasks[${taskRowCounter}][responsable_id]">
                        ${responsableOptions}
                    </select>
                </td>
                <td>
                    <input type="date" class="form-control form-control-sm" 
                           name="tasks[${taskRowCounter}][fecha_inicio]">
                </td>
                <td>
                    <input type="date" class="form-control form-control-sm" 
                           name="tasks[${taskRowCounter}][fecha_fin_estimada]">
                </td>
            `;
            
            container.appendChild(row);
            updateTaskCount();
        }
        
        // Eliminar última fila
        function removeLastTaskRow() {
            if (taskRowCounter > 0) {
                const row = document.getElementById('task-row-' + taskRowCounter);
                if (row) {
                    row.remove();
                    taskRowCounter--;
                    updateTaskCount();
                }
            }
        }
        
        // Actualizar contador de tareas
        function updateTaskCount() {
            const countBadge = document.getElementById('task-count');
            if (countBadge) {
                countBadge.textContent = taskRowCounter + ' tarea(s)';
            }
        }
        
        // Validación del formulario masivo
        document.getElementById('multipleTaskForm')?.addEventListener('submit', function(e) {
            const rows = document.querySelectorAll('#task-rows-container tr');
            let hasValidTask = false;
            
            rows.forEach(function(row) {
                const nombreInput = row.querySelector('input[name*="[nombre]"]');
                if (nombreInput && nombreInput.value.trim()) {
                    hasValidTask = true;
                }
            });
            
            if (!hasValidTask) {
                e.preventDefault();
                alert('Por favor, ingresa al menos una tarea con nombre válido.');
                return false;
            }
        });
        
        // Actualizar máximo del input de cantidad cuando se selecciona material
        document.querySelectorAll('[id^="requirement_"]').forEach(function(select) {
            select.addEventListener('change', function() {
                const taskId = this.id.split('_')[1];
                const selected = this.options[this.selectedIndex];
                const available = parseFloat(selected.getAttribute('data-available')) || 0;
                const unidad = selected.getAttribute('data-unidad') || '';
                
                const qtyInput = document.getElementById('qty_' + taskId);
                const qtyHint = document.getElementById('qty-hint-' + taskId);
                
                if (qtyInput) {
                    qtyInput.setAttribute('max', available);
                    qtyInput.value = '';
                }
                if (qtyHint) {
                    qtyHint.textContent = 'Máximo disponible: ' + available.toFixed(2) + ' ' + unidad;
                }
            });
        });
    </script>
</body>
</html>

