<?php
declare(strict_types=1);

/**
 * Controlador de Compras
 */
class PurchaseController
{
    /**
     * Listar compras de un proyecto
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
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para ver compras');
            redirect(base_url('projects.php'));
            return;
        }

        $includeCancelled = isset($_GET['cancelled']) && $_GET['cancelled'] === '1';
        $purchases = Purchase::getByProject($projectId, $includeCancelled);
        $totals = Purchase::getTotals($projectId);

        // Obtener materiales del proyecto para el selector
        $requirements = Requirement::getByProject($projectId);
        $materialIds = array_column($requirements, 'material_id');
        $materials = [];
        if (!empty($materialIds)) {
            $placeholders = implode(',', array_fill(0, count($materialIds), '?'));
            $materials = Database::fetchAll(
                "SELECT id, sku, descripcion, unidad FROM materials WHERE id IN ($placeholders) AND activo = 1",
                $materialIds
            );
        }

        require_once __DIR__ . '/../views/purchases/index.php';
    }

    /**
     * Mostrar formulario de crear compra
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

        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            requireRole(ROLE_ADMIN);
        }

        // Obtener materiales del proyecto
        $requirements = Requirement::getByProject($projectId);
        $materialIds = array_column($requirements, 'material_id');
        $materials = [];
        if (!empty($materialIds)) {
            $placeholders = implode(',', array_fill(0, count($materialIds), '?'));
            $materials = Database::fetchAll(
                "SELECT id, sku, descripcion, unidad FROM materials WHERE id IN ($placeholders) AND activo = 1 ORDER BY descripcion",
                $materialIds
            );
        }

        if (empty($materials)) {
            setFlashMessage('error', 'El proyecto no tiene materiales en sus requerimientos. Agrega materiales primero.');
            redirect(base_url("requirements.php?project_id=$projectId"));
            return;
        }

        require_once __DIR__ . '/../views/purchases/create.php';
    }

    /**
     * Procesar creación de compra
     */
    public static function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para registrar compras');
            redirect(base_url('projects.php'));
            return;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $materialId = (int)($_POST['material_id'] ?? 0);
        $qty = floatval($_POST['qty_comprada'] ?? 0);
        $costo = floatval($_POST['costo_unitario'] ?? 0);

        // Validaciones
        if (!$projectId || !$materialId) {
            setFlashMessage('error', 'Proyecto y material son requeridos');
            redirect(base_url("purchases.php?project_id=$projectId&action=create"));
            return;
        }

        if ($qty <= 0) {
            setFlashMessage('error', 'La cantidad debe ser mayor a cero');
            redirect(base_url("purchases.php?project_id=$projectId&action=create"));
            return;
        }

        if ($costo < 0) {
            setFlashMessage('error', 'El costo unitario no puede ser negativo');
            redirect(base_url("purchases.php?project_id=$projectId&action=create"));
            return;
        }

        $user = getCurrentUser();
        $fecha = $_POST['fecha_compra'] ?? date('Y-m-d');

        $data = [
            'project_id' => $projectId,
            'material_id' => $materialId,
            'qty_comprada' => $qty,
            'costo_unitario' => $costo,
            'moneda' => $_POST['moneda'] ?? 'MXN',
            'proveedor' => trim($_POST['proveedor'] ?? ''),
            'numero_factura' => trim($_POST['numero_factura'] ?? ''),
            'comprado_por' => $user['id'],
            'fecha_compra' => $fecha
        ];

        $purchaseId = Purchase::create($data);

        if ($purchaseId) {
            setFlashMessage('success', 'Compra registrada exitosamente. El inventario y costos se actualizaron automáticamente.');
            redirect(base_url("purchases.php?project_id=$projectId"));
        } else {
            setFlashMessage('error', 'Error al registrar la compra. Verifica que el material esté en los requerimientos del proyecto.');
            redirect(base_url("purchases.php?project_id=$projectId&action=create"));
        }
    }

    /**
     * Cancelar compra
     */
    public static function cancel(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        if (!hasRole(ROLE_ADMIN)) {
            setFlashMessage('error', 'Solo los administradores pueden cancelar compras');
            redirect(base_url('projects.php'));
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Compra no válida');
            redirect(base_url('projects.php'));
            return;
        }

        $purchase = Purchase::findById($id);
        if (!$purchase) {
            setFlashMessage('error', 'Compra no encontrada');
            redirect(base_url('projects.php'));
            return;
        }

        $user = getCurrentUser();
        $motivo = trim($_POST['motivo_cancelacion'] ?? '');

        if (Purchase::cancel($id, $user['id'], $motivo)) {
            setFlashMessage('success', 'Compra cancelada exitosamente. Los cambios se revirtieron automáticamente.');
        } else {
            setFlashMessage('error', 'Error al cancelar la compra');
        }

        redirect(base_url("purchases.php?project_id={$purchase['project_id']}"));
    }
}

