<?php
declare(strict_types=1);

/**
 * Controlador de Requerimientos
 */
class RequirementController
{
    /**
     * Listar requerimientos de un proyecto
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
            setFlashMessage('error', 'No tienes permisos para ver este proyecto');
            redirect(base_url('projects.php'));
            return;
        }

        $requirements = Requirement::getByProject($projectId);
        $availableMaterials = Material::all(null, null, false); // Solo activos

        require_once __DIR__ . '/../views/requirements/index.php';
    }

    /**
     * Procesar creación de requerimiento (AJAX o POST)
     */
    public static function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $materialId = (int)($_POST['material_id'] ?? 0);
        $qty = floatval($_POST['qty_requerida'] ?? 0);

        if (!$projectId || !$materialId || $qty <= 0) {
            setFlashMessage('error', 'Datos inválidos');
            redirect(base_url("requirements.php?project_id=$projectId"));
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
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para agregar requerimientos');
            redirect(base_url("requirements.php?project_id=$projectId"));
            return;
        }

        // Verificar que no exista ya
        if (Requirement::exists($projectId, $materialId)) {
            setFlashMessage('error', 'Este material ya está en los requerimientos del proyecto');
            redirect(base_url("requirements.php?project_id=$projectId"));
            return;
        }

        $data = [
            'project_id' => $projectId,
            'material_id' => $materialId,
            'qty_requerida' => $qty,
            'comentarios' => trim($_POST['comentarios'] ?? '')
        ];

        $requirementId = Requirement::create($data);

        if ($requirementId) {
            setFlashMessage('success', 'Material agregado a los requerimientos exitosamente');
        } else {
            setFlashMessage('error', 'Error al agregar el requerimiento. Puede que el material ya esté en la lista.');
        }

        redirect(base_url("requirements.php?project_id=$projectId"));
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
            setFlashMessage('error', 'Requerimiento no válido');
            redirect(base_url('projects.php'));
            return;
        }

        $requirement = Requirement::findById($id);
        if (!$requirement) {
            setFlashMessage('error', 'Requerimiento no encontrado');
            redirect(base_url('projects.php'));
            return;
        }

        $project = Project::findById($requirement['project_id']);
        
        // Verificar permisos
        $user = getCurrentUser();
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para editar requerimientos');
            redirect(base_url("requirements.php?project_id={$requirement['project_id']}"));
            return;
        }

        $qty = floatval($_POST['qty_requerida'] ?? 0);
        if ($qty <= 0) {
            setFlashMessage('error', 'La cantidad requerida debe ser mayor a cero');
            redirect(base_url("requirements.php?project_id={$requirement['project_id']}"));
            return;
        }

        $data = [
            'qty_requerida' => $qty,
            'comentarios' => trim($_POST['comentarios'] ?? '')
        ];

        if (Requirement::update($id, $data)) {
            setFlashMessage('success', 'Requerimiento actualizado exitosamente');
        } else {
            setFlashMessage('error', 'Error al actualizar el requerimiento');
        }

        redirect(base_url("requirements.php?project_id={$requirement['project_id']}"));
    }

    /**
     * Eliminar requerimiento
     */
    public static function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Requerimiento no válido');
            redirect(base_url('projects.php'));
            return;
        }

        $requirement = Requirement::findById($id);
        if (!$requirement) {
            setFlashMessage('error', 'Requerimiento no encontrado');
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        $user = getCurrentUser();
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para eliminar requerimientos');
            redirect(base_url("requirements.php?project_id={$requirement['project_id']}"));
            return;
        }

        if (Requirement::delete($id)) {
            setFlashMessage('success', 'Requerimiento eliminado exitosamente');
        } else {
            setFlashMessage('error', 'No se puede eliminar el requerimiento porque tiene compras o entregas registradas');
        }

        redirect(base_url("requirements.php?project_id={$requirement['project_id']}"));
    }
}

