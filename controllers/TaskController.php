<?php
declare(strict_types=1);

/**
 * Controlador de Tareas
 */
class TaskController
{
    /**
     * Listar tareas de un proyecto
     */
    public static function index(): void
    {
        $projectId = (int)($_GET['project_id'] ?? 0);
        
        if (!$projectId) {
            setFlashMessage('error', 'Proyecto no especificado');
            redirect(base_url('projects.php'));
            return;
        }

        $project = Project::findById($projectId);
        if (!$project) {
            setFlashMessage('error', 'Proyecto no encontrado');
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        $user = getCurrentUser();
        if (!hasRole(ROLE_ADMIN) && $project['created_by'] != $user['id'] && !hasRole(ROLE_PM)) {
            if (!hasRole(ROLE_VIEWER)) {
                setFlashMessage('error', 'No tienes permisos para ver este proyecto');
                redirect(base_url('projects.php'));
                return;
            }
        }

        $mainTasks = Task::getMainTasks($projectId);
        
        // Obtener subtareas para cada tarea principal
        foreach ($mainTasks as &$task) {
            $task['subtareas'] = Task::getSubtasks($task['id']);
            $task['stats'] = Task::getStats($task['id']);
        }

        // Obtener requerimientos disponibles para asignar
        $requirements = Requirement::getByProject($projectId);
        
        // Obtener usuarios para asignar responsables
        $users = User::all();

        require_once __DIR__ . '/../views/tasks/index.php';
    }

    /**
     * Mostrar detalles de una tarea
     */
    public static function show(): void
    {
        $taskId = (int)($_GET['id'] ?? 0);
        
        if (!$taskId) {
            setFlashMessage('error', 'Tarea no especificada');
            redirect(base_url('projects.php'));
            return;
        }

        $task = Task::findById($taskId);
        if (!$task) {
            setFlashMessage('error', 'Tarea no encontrada');
            redirect(base_url('projects.php'));
            return;
        }

        $project = Project::findById($task['project_id']);
        
        // Verificar permisos
        $user = getCurrentUser();
        if (!hasRole(ROLE_ADMIN) && $project['created_by'] != $user['id'] && !hasRole(ROLE_PM)) {
            if (!hasRole(ROLE_VIEWER)) {
                requireRole(ROLE_ADMIN);
            }
        }

        $materials = Task::getMaterials($taskId);
        $subtareas = Task::getSubtasks($taskId);
        $stats = Task::getStats($taskId);
        $requirements = Requirement::getByProject($task['project_id']);

        require_once __DIR__ . '/../views/tasks/show.php';
    }

    /**
     * Crear nueva tarea
     */
    public static function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);

        if (!$projectId) {
            setFlashMessage('error', 'Proyecto no especificado');
            redirect(base_url('projects.php'));
            return;
        }

        $project = Project::findById($projectId);
        if (!$project) {
            setFlashMessage('error', 'Proyecto no encontrado');
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para crear tareas');
            redirect(base_url("tasks.php?project_id=$projectId"));
            return;
        }

        $user = getCurrentUser();
        
        // MODO MASIVO: Múltiples tareas principales
        if (isset($_POST['tasks']) && is_array($_POST['tasks'])) {
            $created = 0;
            $errors = [];
            
            foreach ($_POST['tasks'] as $idx => $taskData) {
                if (!is_array($taskData)) continue;
                
                $nombre = trim($taskData['nombre'] ?? '');
                if (empty($nombre)) {
                    continue; // Saltar tareas sin nombre
                }
                
                $data = [
                    'project_id' => $projectId,
                    'parent_id' => null, // Siempre tareas principales en modo masivo
                    'nombre' => $nombre,
                    'descripcion' => trim($taskData['descripcion'] ?? ''),
                    'estado' => $taskData['estado'] ?? 'pending',
                    'fecha_inicio' => !empty($taskData['fecha_inicio']) ? $taskData['fecha_inicio'] : null,
                    'fecha_fin_estimada' => !empty($taskData['fecha_fin_estimada']) ? $taskData['fecha_fin_estimada'] : null,
                    'responsable_id' => !empty($taskData['responsable_id']) ? (int)$taskData['responsable_id'] : null,
                    'created_by' => $user['id']
                ];
                
                $taskId = Task::create($data);
                if ($taskId) {
                    $created++;
                } else {
                    $errors[] = "Tarea: " . $nombre;
                }
            }
            
            $message = '';
            if ($created > 0) {
                $message .= "$created tarea(s) creada(s) exitosamente. ";
            }
            if (!empty($errors)) {
                $message .= "Errores: " . count($errors) . " tarea(s).";
            }
            
            if ($created > 0) {
                setFlashMessage('success', trim($message));
            } else {
                setFlashMessage('info', 'No se crearon tareas. Verifica que tengan nombres válidos.');
            }
            
            redirect(base_url("tasks.php?project_id=$projectId"));
            return;
        }
        
        // MODO SIMPLE: Una sola tarea (subtarea o principal)
        $parentId = !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            setFlashMessage('error', 'El nombre de la tarea es requerido');
            redirect(base_url("tasks.php?project_id=$projectId"));
            return;
        }
        
        $data = [
            'project_id' => $projectId,
            'parent_id' => $parentId,
            'nombre' => $nombre,
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'estado' => $_POST['estado'] ?? 'pending',
            'fecha_inicio' => !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null,
            'fecha_fin_estimada' => !empty($_POST['fecha_fin_estimada']) ? $_POST['fecha_fin_estimada'] : null,
            'responsable_id' => !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null,
            'created_by' => $user['id']
        ];

        $taskId = Task::create($data);

        if ($taskId) {
            // Asignar materiales si se enviaron
            if (isset($_POST['materials']) && is_array($_POST['materials'])) {
                foreach ($_POST['materials'] as $matData) {
                    if (!empty($matData['requirement_id']) && !empty($matData['qty'])) {
                        Task::assignMaterial(
                            $taskId,
                            (int)$matData['requirement_id'],
                            (float)$matData['qty'],
                            trim($matData['comentarios'] ?? '')
                        );
                    }
                }
            }

            $message = $parentId ? 'Subtarea creada exitosamente' : 'Tarea principal creada exitosamente';
            setFlashMessage('success', $message);
        } else {
            setFlashMessage('error', 'Error al crear la tarea');
        }

        redirect(base_url("tasks.php?project_id=$projectId"));
    }

    /**
     * Actualizar tarea
     */
    public static function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Tarea no válida');
            redirect(base_url('projects.php'));
            return;
        }

        $task = Task::findById($id);
        if (!$task) {
            setFlashMessage('error', 'Tarea no encontrada');
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para editar tareas');
            redirect(base_url("tasks.php?project_id={$task['project_id']}"));
            return;
        }

        $data = [
            'nombre' => trim($_POST['nombre'] ?? ''),
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'estado' => $_POST['estado'] ?? 'pending',
            'fecha_inicio' => !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null,
            'fecha_fin_estimada' => !empty($_POST['fecha_fin_estimada']) ? $_POST['fecha_fin_estimada'] : null,
            'responsable_id' => !empty($_POST['responsable_id']) ? (int)$_POST['responsable_id'] : null
        ];

        if (Task::update($id, $data)) {
            setFlashMessage('success', 'Tarea actualizada exitosamente');
        } else {
            setFlashMessage('error', 'Error al actualizar la tarea');
        }

        redirect(base_url("tasks.php?project_id={$task['project_id']}"));
    }

    /**
     * Eliminar tarea
     */
    public static function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Tarea no válida');
            redirect(base_url('projects.php'));
            return;
        }

        $task = Task::findById($id);
        if (!$task) {
            setFlashMessage('error', 'Tarea no encontrada');
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        if (!hasRole(ROLE_ADMIN)) {
            setFlashMessage('error', 'Solo los administradores pueden eliminar tareas');
            redirect(base_url("tasks.php?project_id={$task['project_id']}"));
            return;
        }

        if (Task::delete($id)) {
            setFlashMessage('success', 'Tarea eliminada exitosamente');
        } else {
            setFlashMessage('error', 'Error al eliminar la tarea');
        }

        redirect(base_url("tasks.php?project_id={$task['project_id']}"));
    }

    /**
     * Asignar material a tarea
     */
    public static function assignMaterial(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        $taskId = (int)($_POST['task_id'] ?? 0);
        $requirementId = (int)($_POST['requirement_id'] ?? 0);
        $qty = floatval($_POST['qty_asignada'] ?? 0);

        if (!$taskId || !$requirementId || $qty <= 0) {
            setFlashMessage('error', 'Datos inválidos');
            redirect(base_url('projects.php'));
            return;
        }

        $task = Task::findById($taskId);
        if (!$task) {
            setFlashMessage('error', 'Tarea no encontrada');
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para asignar materiales');
            redirect(base_url("tasks.php?project_id={$task['project_id']}"));
            return;
        }

        $comentarios = trim($_POST['comentarios'] ?? '');

        if (Task::assignMaterial($taskId, $requirementId, $qty, $comentarios)) {
            setFlashMessage('success', 'Material asignado exitosamente');
        } else {
            setFlashMessage('error', 'Error al asignar material. Verifica que la cantidad no exceda la disponible.');
        }

        redirect(base_url("tasks.php?project_id={$task['project_id']}"));
    }

    /**
     * Desasignar material de tarea
     */
    public static function unassignMaterial(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        $taskId = (int)($_POST['task_id'] ?? 0);
        $requirementId = (int)($_POST['requirement_id'] ?? 0);

        if (!$taskId || !$requirementId) {
            setFlashMessage('error', 'Datos inválidos');
            redirect(base_url('projects.php'));
            return;
        }

        $task = Task::findById($taskId);
        if (!$task) {
            setFlashMessage('error', 'Tarea no encontrada');
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para desasignar materiales');
            redirect(base_url("tasks.php?project_id={$task['project_id']}"));
            return;
        }

        if (Task::unassignMaterial($taskId, $requirementId)) {
            setFlashMessage('success', 'Material desasignado exitosamente');
        } else {
            setFlashMessage('error', 'Error al desasignar material');
        }

        redirect(base_url("tasks.php?project_id={$task['project_id']}"));
    }
}

