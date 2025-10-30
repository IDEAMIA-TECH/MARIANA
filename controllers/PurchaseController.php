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
        $user = getCurrentUser();
        $fecha = $_POST['fecha_compra'] ?? date('Y-m-d');
        $proveedor = trim($_POST['proveedor'] ?? '');
        $numeroFactura = trim($_POST['numero_factura'] ?? '');

        // Normalizar a arrays
        $materialIds = $_POST['material_id'] ?? [];
        $cantidades = $_POST['qty_comprada'] ?? [];
        $costos = $_POST['costo_unitario'] ?? [];
        $monedas = $_POST['moneda'] ?? [];
        $tiposCambio = $_POST['tipo_cambio'] ?? [];

        if (!$projectId || !is_array($materialIds) || !is_array($cantidades) || !is_array($costos)) {
            setFlashMessage('error', 'Datos de compra inválidos');
            redirect(base_url("purchases.php?project_id=$projectId&action=create"));
            return;
        }

        $numItems = min(count($materialIds), count($cantidades), count($costos));
        if ($numItems === 0) {
            setFlashMessage('error', 'Agrega al menos un producto');
            redirect(base_url("purchases.php?project_id=$projectId&action=create"));
            return;
        }

        $success = 0;
        $errors = 0;
        for ($i = 0; $i < $numItems; $i++) {
            $materialId = (int)$materialIds[$i];
            $qty = (float)$cantidades[$i];
            $costo = (float)$costos[$i];
            $moneda = is_array($monedas) && isset($monedas[$i]) ? $monedas[$i] : 'MXN';
            $tc = is_array($tiposCambio) && isset($tiposCambio[$i]) ? (float)$tiposCambio[$i] : 0.0;

            if (!$materialId) { $errors++; continue; }
            if ($qty <= 0) { $errors++; continue; }
            if ($costo < 0) { $errors++; continue; }

            $data = [
                'project_id' => $projectId,
                'material_id' => $materialId,
                'qty_comprada' => $qty,
                'costo_unitario' => $costo,
                'moneda' => $moneda,
                'tipo_cambio' => ($moneda === 'USD' && $tc > 0) ? $tc : null,
                'proveedor' => $proveedor,
                'numero_factura' => $numeroFactura,
                'comprado_por' => $user['id'],
                'fecha_compra' => $fecha
            ];

            $purchaseId = Purchase::create($data);
            if ($purchaseId) { $success++; } else { $errors++; }
        }

        if ($success > 0 && $errors === 0) {
            setFlashMessage('success', 'Compra registrada con ' . $success . ' producto(s). Inventario y costos actualizados.');
            redirect(base_url("purchases.php?project_id=$projectId"));
            return;
        }

        if ($success > 0 && $errors > 0) {
            setFlashMessage('error', 'Algunos productos no se registraron (' . $errors . '). Verifica requerimientos y datos.');
            redirect(base_url("purchases.php?project_id=$projectId"));
            return;
        }

        setFlashMessage('error', 'No se pudo registrar la compra. Verifica que los materiales estén en los requerimientos del proyecto.');
        redirect(base_url("purchases.php?project_id=$projectId&action=create"));
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

