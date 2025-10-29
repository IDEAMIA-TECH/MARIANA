<?php
declare(strict_types=1);

/**
 * Controlador de Instalaciones
 */
class InstallationController
{
    /**
     * Registrar nueva instalación
     */
    public static function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('projects.php'));
            return;
        }

        $projectId = (int)($_POST['project_id'] ?? 0);
        $materialId = (int)($_POST['material_id'] ?? 0);
        $qty = floatval($_POST['qty_instalada'] ?? 0);

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

        // Verificar permisos (PM y Admin pueden marcar instalaciones)
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para registrar instalaciones');
            redirect(base_url("requirements.php?project_id=$projectId"));
            return;
        }

        $user = getCurrentUser();

        // Validar que haya cantidad entregada disponible
        $inventory = Database::fetchOne(
            "SELECT qty_entregada, qty_instalada FROM inventory 
             WHERE project_id = ? AND material_id = ?",
            [$projectId, $materialId]
        );

        if (!$inventory) {
            setFlashMessage('error', 'No existe inventario para este material');
            redirect(base_url("requirements.php?project_id=$projectId"));
            return;
        }

        $qtyEntregada = (float)$inventory['qty_entregada'];
        $qtyYaInstalada = (float)$inventory['qty_instalada'];
        $disponibleParaInstalar = $qtyEntregada - $qtyYaInstalada;

        if ($qty > $disponibleParaInstalar) {
            setFlashMessage('error', "La cantidad a instalar excede la disponible para instalar: " . number_format($disponibleParaInstalar, 2));
            redirect(base_url("requirements.php?project_id=$projectId"));
            return;
        }

        $data = [
            'project_id' => $projectId,
            'material_id' => $materialId,
            'qty_instalada' => $qty,
            'instalado_por' => $user['id'],
            'fecha_instalacion' => $_POST['fecha_instalacion'] ?? date('Y-m-d'),
            'ubicacion' => trim($_POST['ubicacion'] ?? ''),
            'comentarios' => trim($_POST['comentarios'] ?? '')
        ];

        try {
            $installationId = Installation::create($data);
            if ($installationId) {
                setFlashMessage('success', 'Instalación registrada exitosamente');
            } else {
                setFlashMessage('error', 'Error al registrar la instalación');
            }
        } catch (Exception $e) {
            setFlashMessage('error', $e->getMessage());
        }

        redirect(base_url("requirements.php?project_id=$projectId"));
    }
}

