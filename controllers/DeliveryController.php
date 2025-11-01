<?php
declare(strict_types=1);

/**
 * Controlador de Entregas
 */
class DeliveryController
{
    /**
     * Listar entregas de un proyecto
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

        // Verificar permisos (almacén puede ver y crear entregas)
        $user = getCurrentUser();
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM, ROLE_ALMACEN])) {
            setFlashMessage('error', 'No tienes permisos para ver entregas');
            redirect(base_url('projects.php'));
            return;
        }

        $deliveries = Delivery::getByProject($projectId);
        $totals = Delivery::getTotals($projectId);

        // Obtener materiales del proyecto con inventario disponible
        $requirements = Requirement::getByProject($projectId);
        $materialsWithInventory = [];
        foreach ($requirements as $req) {
            $available = Delivery::getAvailableInventory($projectId, $req['material_id']);
            if ($available > 0) {
                $materialsWithInventory[] = [
                    'id' => $req['material_id'],
                    'sku' => $req['sku'],
                    'descripcion' => $req['descripcion'],
                    'unidad' => $req['unidad'],
                    'qty_disponible' => $available
                ];
            }
        }

        require_once __DIR__ . '/../views/deliveries/index.php';
    }

    /**
     * Mostrar formulario de crear entrega
     */
    public static function create(): void
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

        // Verificar permisos (almacén puede crear entregas)
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM, ROLE_ALMACEN])) {
            setFlashMessage('error', 'No tienes permisos para registrar entregas');
            redirect(base_url('projects.php'));
            return;
        }

        // Obtener materiales del proyecto con inventario disponible > 0
        $requirements = Requirement::getByProject($projectId);
        $materials = [];
        foreach ($requirements as $req) {
            $available = Delivery::getAvailableInventory($projectId, $req['material_id']);
            if ($available > 0) {
                $materials[] = [
                    'id' => $req['material_id'],
                    'sku' => $req['sku'],
                    'descripcion' => $req['descripcion'],
                    'unidad' => $req['unidad'],
                    'qty_disponible' => $available
                ];
            }
        }

        if (empty($materials)) {
            setFlashMessage('error', 'No hay materiales con inventario disponible para entregar. Registra compras primero.');
            redirect(base_url("purchases.php?project_id=$projectId"));
            return;
        }

        require_once __DIR__ . '/../views/deliveries/create.php';
    }

    /**
     * Procesar creación de entrega
     */
    public static function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        // Verificar permisos
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM, ROLE_ALMACEN])) {
            setFlashMessage('error', 'No tienes permisos para registrar entregas');
            redirect(base_url('projects.php'));
            return;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $materialId = (int)($_POST['material_id'] ?? 0);
        $qty = floatval($_POST['qty_entregada'] ?? 0);
        $entregadoA = trim($_POST['entregado_a'] ?? '');

        // Validaciones
        if (!$projectId || !$materialId) {
            setFlashMessage('error', 'Proyecto y material son requeridos');
            redirect(base_url("deliveries.php?project_id=$projectId&action=create"));
            return;
        }

        if ($qty <= 0) {
            setFlashMessage('error', 'La cantidad debe ser mayor a cero');
            redirect(base_url("deliveries.php?project_id=$projectId&action=create"));
            return;
        }

        if (empty($entregadoA)) {
            setFlashMessage('error', 'Debes especificar a quién se entregó el material');
            redirect(base_url("deliveries.php?project_id=$projectId&action=create"));
            return;
        }

        // Verificar inventario disponible
        $available = Delivery::getAvailableInventory($projectId, $materialId);
        if ($available < $qty) {
            setFlashMessage('error', "No hay suficiente inventario disponible. Disponible: " . number_format($available, 2));
            redirect(base_url("deliveries.php?project_id=$projectId&action=create"));
            return;
        }

        $user = getCurrentUser();
        $fecha = $_POST['fecha_entrega'] ?? date('Y-m-d');

        $data = [
            'project_id' => $projectId,
            'material_id' => $materialId,
            'qty_entregada' => $qty,
            'entregado_a' => $entregadoA,
            'entregado_por' => $user['id'],
            'fecha_entrega' => $fecha,
            'comentarios' => trim($_POST['comentarios'] ?? '')
        ];

        try {
            $deliveryId = Delivery::create($data);

            if ($deliveryId) {
                setFlashMessage('success', 'Entrega registrada exitosamente. El inventario se actualizó automáticamente.');
                redirect(base_url("deliveries.php?project_id=$projectId"));
            } else {
                setFlashMessage('error', 'Error al registrar la entrega');
                redirect(base_url("deliveries.php?project_id=$projectId&action=create"));
            }
        } catch (Exception $e) {
            setFlashMessage('error', $e->getMessage());
            redirect(base_url("deliveries.php?project_id=$projectId&action=create"));
        }
    }

    /**
     * Mostrar formulario de edición de entrega
     */
    public static function edit(): void
    {
        $projectId = (int)($_GET['project_id'] ?? 0);
        $id = (int)($_GET['id'] ?? 0);
        if (!$projectId || !$id) {
            setFlashMessage('error', 'Solicitud inválida');
            redirect(base_url('projects.php'));
            return;
        }

        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM, ROLE_ALMACEN])) {
            setFlashMessage('error', 'No tienes permisos para editar entregas');
            redirect(base_url('projects.php'));
            return;
        }

        $project = Project::findById($projectId);
        $delivery = Delivery::findById($id);
        if (!$project || !$delivery || (int)$delivery['project_id'] !== $projectId) {
            setFlashMessage('error', 'Entrega no encontrada');
            redirect(base_url("deliveries.php?project_id=$projectId"));
            return;
        }

        require_once __DIR__ . '/../views/deliveries/edit.php';
    }

    /**
     * Actualizar entrega existente
     */
    public static function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM, ROLE_ALMACEN])) {
            setFlashMessage('error', 'No tienes permisos para editar entregas');
            redirect(base_url('projects.php'));
            return;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $id = (int)($_POST['id'] ?? 0);
        $qty = (float)($_POST['qty_entregada'] ?? 0);
        $entregadoA = trim($_POST['entregado_a'] ?? '');
        $fecha = $_POST['fecha_entrega'] ?? date('Y-m-d');
        $comentarios = trim($_POST['comentarios'] ?? '');

        if (!$projectId || !$id) {
            setFlashMessage('error', 'Solicitud inválida');
            redirect(base_url('projects.php'));
            return;
        }
        if ($qty <= 0) {
            setFlashMessage('error', 'La cantidad debe ser mayor a cero');
            redirect(base_url("deliveries.php?project_id=$projectId&action=edit&id=$id"));
            return;
        }
        if (empty($entregadoA)) {
            setFlashMessage('error', 'Debes especificar a quién se entregó el material');
            redirect(base_url("deliveries.php?project_id=$projectId&action=edit&id=$id"));
            return;
        }

        // Obtener entrega original
        $original = Delivery::findById($id);
        if (!$original) {
            setFlashMessage('error', 'Entrega no encontrada');
            redirect(base_url("deliveries.php?project_id=$projectId"));
            return;
        }

        // Delegar actualización al modelo
        $ok = Delivery::updateFields($id, [
            'qty_entregada' => $qty,
            'entregado_a' => $entregadoA,
            'fecha_entrega' => $fecha,
            'comentarios' => $comentarios,
        ], $original);

        if ($ok) {
            setFlashMessage('success', 'Entrega actualizada correctamente. Inventario recalculado.');
            redirect(base_url("deliveries.php?project_id=$projectId"));
        } else {
            setFlashMessage('error', 'No se pudo actualizar la entrega');
            redirect(base_url("deliveries.php?project_id=$projectId&action=edit&id=$id"));
        }
    }

    /**
     * Borrar entrega (revertir cambios en inventario)
     */
    public static function destroy(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM, ROLE_ALMACEN])) {
            setFlashMessage('error', 'No tienes permisos para borrar entregas');
            redirect(base_url('projects.php'));
            return;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $id = (int)($_POST['id'] ?? 0);
        if (!$projectId || !$id) {
            setFlashMessage('error', 'Solicitud inválida');
            redirect(base_url('projects.php'));
            return;
        }

        $delivery = Delivery::findById($id);
        if (!$delivery || (int)$delivery['project_id'] !== $projectId) {
            setFlashMessage('error', 'Entrega no encontrada');
            redirect(base_url("deliveries.php?project_id=$projectId"));
            return;
        }

        if (Delivery::delete($id)) {
            setFlashMessage('success', 'Entrega borrada exitosamente. El inventario se revirtió automáticamente.');
        } else {
            setFlashMessage('error', 'Error al borrar la entrega');
        }

        redirect(base_url("deliveries.php?project_id=$projectId"));
    }
}

