<?php
declare(strict_types=1);

/**
 * Controlador de Proyectos
 */
class ProjectController
{
    /**
     * Listar proyectos
     */
    public static function index(): void
    {
        $user = getCurrentUser();
        $isAdmin = hasRole(ROLE_ADMIN);
        
        $search = $_GET['search'] ?? null;
        
        if ($isAdmin || hasRole(ROLE_PM)) {
            $projects = Project::getUserProjects($user['id'], $isAdmin);
            
            if ($search) {
                $projects = array_filter($projects, function($p) use ($search) {
                    return stripos($p['nombre'], $search) !== false || 
                           stripos($p['ubicacion'] ?? '', $search) !== false;
                });
            }
        } else {
            // Viewer y almacén solo ven proyectos activos
            $projects = Database::fetchAll(
                "SELECT p.*, u.nombre as created_by_name 
                 FROM projects p
                 LEFT JOIN users u ON u.id = p.created_by
                 WHERE p.estado = 'active'
                 ORDER BY p.created_at DESC"
            );
        }
        
        require_once __DIR__ . '/../views/projects/index.php';
    }

    /**
     * Mostrar formulario de crear
     */
    public static function create(): void
    {
        // Verificar permisos
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            requireRole(ROLE_ADMIN);
        }
        
        require_once __DIR__ . '/../views/projects/create.php';
    }

    /**
     * Procesar creación de proyecto
     */
    public static function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php?action=create'));
            return;
        }

        // Verificar permisos
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para crear proyectos');
            redirect(base_url('projects.php'));
            return;
        }

        $user = getCurrentUser();
        
        // Validar datos
        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            setFlashMessage('error', 'El nombre del proyecto es requerido');
            redirect(base_url('projects.php?action=create'));
            return;
        }

        $data = [
            'nombre' => $nombre,
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'ubicacion' => trim($_POST['ubicacion'] ?? ''),
            'estado' => $_POST['estado'] ?? PROJECT_PLANNING,
            'fecha_inicio' => !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null,
            'fecha_fin' => !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null,
            'created_by' => $user['id']
        ];

        $projectId = Project::create($data);
        
        if ($projectId) {
            setFlashMessage('success', 'Proyecto creado exitosamente');
            redirect(base_url("projects.php"));
        } else {
            setFlashMessage('error', 'Error al crear el proyecto. Intenta nuevamente.');
            redirect(base_url('projects.php?action=create'));
        }
    }

    /**
     * Mostrar formulario de editar
     */
    public static function edit(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Proyecto no válido');
            redirect(base_url('projects.php'));
            return;
        }

        $project = Project::findById($id);
        if (!$project) {
            setFlashMessage('error', 'Proyecto no encontrado');
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        $user = getCurrentUser();
        if (!hasRole(ROLE_ADMIN) && $project['created_by'] != $user['id']) {
            setFlashMessage('error', 'No tienes permisos para editar este proyecto');
            redirect(base_url('projects.php'));
            return;
        }

        require_once __DIR__ . '/../views/projects/edit.php';
    }

    /**
     * Mostrar dashboard del proyecto
     */
    public static function dashboard(): void
    {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Proyecto no válido');
            redirect(base_url('projects.php'));
            return;
        }

        // Redirigir al dashboard dedicado
        redirect(base_url("dashboard.php?id=$id"));
    }

    /**
     * Procesar actualización
     */
    public static function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Proyecto no válido');
            redirect(base_url('projects.php'));
            return;
        }

        $project = Project::findById($id);
        if (!$project) {
            setFlashMessage('error', 'Proyecto no encontrado');
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        $user = getCurrentUser();
        if (!hasRole(ROLE_ADMIN) && $project['created_by'] != $user['id']) {
            setFlashMessage('error', 'No tienes permisos para editar este proyecto');
            redirect(base_url('projects.php'));
            return;
        }

        $nombre = trim($_POST['nombre'] ?? '');
        if (empty($nombre)) {
            setFlashMessage('error', 'El nombre del proyecto es requerido');
            redirect(base_url("projects.php?action=edit&id=$id"));
            return;
        }

        $data = [
            'nombre' => $nombre,
            'descripcion' => trim($_POST['descripcion'] ?? ''),
            'ubicacion' => trim($_POST['ubicacion'] ?? ''),
            'estado' => $_POST['estado'] ?? $project['estado'],
            'fecha_inicio' => !empty($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null,
            'fecha_fin' => !empty($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null
        ];

        if (Project::update($id, $data)) {
            setFlashMessage('success', 'Proyecto actualizado exitosamente');
            redirect(base_url('projects.php'));
        } else {
            setFlashMessage('error', 'Error al actualizar el proyecto');
            redirect(base_url("projects.php?action=edit&id=$id"));
        }
    }

    /**
     * Eliminar proyecto
     */
    public static function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Proyecto no válido');
            redirect(base_url('projects.php'));
            return;
        }

        $project = Project::findById($id);
        if (!$project) {
            setFlashMessage('error', 'Proyecto no encontrado');
            redirect(base_url('projects.php'));
            return;
        }

        // Solo admin puede eliminar
        if (!hasRole(ROLE_ADMIN)) {
            setFlashMessage('error', 'Solo los administradores pueden eliminar proyectos');
            redirect(base_url('projects.php'));
            return;
        }

        if (Project::delete($id)) {
            setFlashMessage('success', 'Proyecto eliminado exitosamente');
        } else {
            setFlashMessage('error', 'No se puede eliminar el proyecto porque tiene compras o entregas registradas');
        }
        
        redirect(base_url('projects.php'));
    }
}

