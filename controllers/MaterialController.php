<?php
declare(strict_types=1);

/**
 * Controlador de Materiales
 */
class MaterialController
{
    /**
     * Listar materiales
     */
    public static function index(): void
    {
        // Verificar permisos
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            requireRole(ROLE_ADMIN);
        }

        $search = $_GET['search'] ?? null;
        $categoria = $_GET['categoria'] ?? null;
        $includeInactive = isset($_GET['inactive']) && $_GET['inactive'] === '1';

        $materials = Material::all($search, $categoria, $includeInactive);
        $categorias = Material::getCategorias();

        require_once __DIR__ . '/../views/materials/index.php';
    }

    /**
     * Mostrar formulario de crear
     */
    public static function create(): void
    {
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            requireRole(ROLE_ADMIN);
        }

        $categorias = Material::getCategorias();
        require_once __DIR__ . '/../views/materials/create.php';
    }

    /**
     * Procesar creación de material
     */
    public static function store(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('materials.php?action=create'));
            return;
        }

        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            setFlashMessage('error', 'No tienes permisos para crear materiales');
            redirect(base_url('materials.php'));
            return;
        }

        // Validar datos
        $sku = trim($_POST['sku'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $unidad = trim($_POST['unidad'] ?? '');

        if (empty($sku) || empty($descripcion) || empty($unidad)) {
            setFlashMessage('error', 'SKU, descripción y unidad son requeridos');
            redirect(base_url('materials.php?action=create'));
            return;
        }

        // Verificar SKU único
        if (Material::findBySku($sku)) {
            setFlashMessage('error', 'El SKU ya existe. Debe ser único.');
            redirect(base_url('materials.php?action=create'));
            return;
        }

        $data = [
            'sku' => $sku,
            'descripcion' => $descripcion,
            'unidad' => $unidad,
            'categoria' => trim($_POST['categoria'] ?? '')
        ];

        $materialId = Material::create($data);
        
        if ($materialId) {
            setFlashMessage('success', 'Material creado exitosamente');
            redirect(base_url('materials.php'));
        } else {
            setFlashMessage('error', 'Error al crear el material. El SKU puede estar duplicado.');
            redirect(base_url('materials.php?action=create'));
        }
    }

    /**
     * Mostrar formulario de editar
     */
    public static function edit(): void
    {
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            requireRole(ROLE_ADMIN);
        }

        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Material no válido');
            redirect(base_url('materials.php'));
            return;
        }

        $material = Material::findById($id);
        if (!$material) {
            setFlashMessage('error', 'Material no encontrado');
            redirect(base_url('materials.php'));
            return;
        }

        $categorias = Material::getCategorias();
        require_once __DIR__ . '/../views/materials/edit.php';
    }

    /**
     * Procesar actualización
     */
    public static function update(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('materials.php'));
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Material no válido');
            redirect(base_url('materials.php'));
            return;
        }

        $material = Material::findById($id);
        if (!$material) {
            setFlashMessage('error', 'Material no encontrado');
            redirect(base_url('materials.php'));
            return;
        }

        $sku = trim($_POST['sku'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $unidad = trim($_POST['unidad'] ?? '');

        if (empty($sku) || empty($descripcion) || empty($unidad)) {
            setFlashMessage('error', 'SKU, descripción y unidad son requeridos');
            redirect(base_url("materials.php?action=edit&id=$id"));
            return;
        }

        $data = [
            'sku' => $sku,
            'descripcion' => $descripcion,
            'unidad' => $unidad,
            'categoria' => trim($_POST['categoria'] ?? ''),
            'activo' => isset($_POST['activo']) ? 1 : 0
        ];

        if (Material::update($id, $data)) {
            setFlashMessage('success', 'Material actualizado exitosamente');
            redirect(base_url('materials.php'));
        } else {
            setFlashMessage('error', 'Error al actualizar. El SKU puede estar duplicado.');
            redirect(base_url("materials.php?action=edit&id=$id"));
        }
    }

    /**
     * Eliminar material
     */
    public static function delete(): void
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect(base_url('materials.php'));
            return;
        }

        if (!hasRole(ROLE_ADMIN)) {
            setFlashMessage('error', 'Solo los administradores pueden eliminar materiales');
            redirect(base_url('materials.php'));
            return;
        }

        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            setFlashMessage('error', 'Material no válido');
            redirect(base_url('materials.php'));
            return;
        }

        if (Material::delete($id)) {
            setFlashMessage('success', 'Material eliminado exitosamente');
        } else {
            setFlashMessage('error', 'No se puede eliminar el material porque está en uso en algún proyecto');
        }
        
        redirect(base_url('materials.php'));
    }

    /**
     * API: Buscar materiales (para autocomplete)
     */
    public static function searchApi(): void
    {
        header('Content-Type: application/json');
        
        $term = $_GET['q'] ?? '';
        if (empty($term)) {
            echo json_encode([]);
            exit;
        }

        $materials = Material::search($term);
        echo json_encode($materials);
        exit;
    }
}

