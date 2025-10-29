<?php
declare(strict_types=1);

/**
 * Script para importar materiales a la base de datos
 * Soporta: CSV, JSON o array PHP directo
 */

// Configuración de Error Logging
$log_dir = __DIR__ . '/logs';
if (!is_dir($log_dir)) {
    @mkdir($log_dir, 0755, true);
}
ini_set('log_errors', 1);
ini_set('error_log', $log_dir . '/error.log');

// Cargar configuración
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Database.php';
require_once __DIR__ . '/models/Material.php';

echo "=== Importador de Materiales ===\n\n";

// ============================================
// OPCIÓN 1: Importar desde Array PHP
// ============================================
function importFromArray(array $materials): array
{
    $results = [
        'inserted' => 0,
        'updated' => 0,
        'errors' => []
    ];

    foreach ($materials as $index => $material) {
        try {
            // Validar campos requeridos
            if (empty($material['sku']) || empty($material['descripcion'])) {
                $results['errors'][] = "Fila " . ($index + 1) . ": SKU y descripción son requeridos";
                continue;
            }

            // Preparar datos
            $data = [
                'sku' => trim($material['sku']),
                'descripcion' => trim($material['descripcion']),
                'unidad' => trim($material['unidad'] ?? 'Pieza'),
                'categoria' => trim($material['categoria'] ?? 'General'),
                'activo' => isset($material['activo']) ? (bool)$material['activo'] : true
            ];

            // Verificar si ya existe
            $existing = Database::fetchOne(
                "SELECT id FROM materials WHERE sku = ?",
                [$data['sku']]
            );

            if ($existing) {
                // Actualizar
                Database::query(
                    "UPDATE materials SET descripcion = ?, unidad = ?, categoria = ?, activo = ? WHERE sku = ?",
                    [$data['descripcion'], $data['unidad'], $data['categoria'], $data['activo'] ? 1 : 0, $data['sku']]
                );
                $results['updated']++;
                echo "✅ Actualizado: {$data['sku']} - {$data['descripcion']}\n";
            } else {
                // Insertar nuevo
                Database::query(
                    "INSERT INTO materials (sku, descripcion, unidad, categoria, activo) VALUES (?, ?, ?, ?, ?)",
                    [$data['sku'], $data['descripcion'], $data['unidad'], $data['categoria'], $data['activo'] ? 1 : 0]
                );
                $results['inserted']++;
                echo "✅ Insertado: {$data['sku']} - {$data['descripcion']}\n";
            }
        } catch (Exception $e) {
            $results['errors'][] = "Fila " . ($index + 1) . ": " . $e->getMessage();
            error_log("Error importando material: " . $e->getMessage());
        }
    }

    return $results;
}

// ============================================
// OPCIÓN 2: Importar desde CSV
// ============================================
function importFromCSV(string $csvFile): array
{
    if (!file_exists($csvFile)) {
        throw new Exception("Archivo CSV no encontrado: $csvFile");
    }

    $materials = [];
    $handle = fopen($csvFile, 'r');
    
    // Leer header
    $headers = fgetcsv($handle);
    if (!$headers) {
        throw new Exception("El archivo CSV está vacío o no tiene headers");
    }

    // Mapear headers (case insensitive)
    $headerMap = [];
    foreach ($headers as $i => $header) {
        $headerMap[strtolower(trim($header))] = $i;
    }

    // Validar headers requeridos (case insensitive)
    $required = ['sku', 'descripcion'];
    $requiredLower = array_map('strtolower', $required);
    
    foreach ($requiredLower as $req) {
        if (!isset($headerMap[$req])) {
            throw new Exception("Header requerido no encontrado: $req. Headers disponibles: " . implode(', ', array_keys($headerMap)));
        }
    }
    
    // Normalizar nombres de unidades comunes
    $unidadMap = [
        'pza' => 'Pieza',
        'tmo' => 'Metros',
        'Metros' => 'Metros',
        'Pieza' => 'Pieza',
        'Rollo' => 'Rollo',
        'kit' => 'Kit'
    ];

    // Leer datos
    $line = 2; // Empezar desde línea 2 (después del header)
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) < count($headers)) {
            continue; // Saltar filas incompletas
        }

        $sku = trim($row[$headerMap['sku']] ?? '');
        $descripcion = trim($row[$headerMap['descripcion']] ?? '');
        $unidadRaw = trim($row[$headerMap['unidad'] ?? 'unidad'] ?? 'Pieza');
        
        // Normalizar unidad
        $unidadNormalized = $unidadMap[strtolower($unidadRaw)] ?? ucfirst(strtolower($unidadRaw));
        
        $material = [
            'sku' => $sku,
            'descripcion' => $descripcion,
            'unidad' => $unidadNormalized,
            'categoria' => trim($row[$headerMap['categoria'] ?? 'categoria'] ?? 'General'),
            'activo' => isset($headerMap['activo']) ? ($row[$headerMap['activo']] ?? '1') !== '0' : true
        ];

        if (!empty($material['sku']) && !empty($material['descripcion'])) {
            $materials[] = $material;
        }
        $line++;
    }

    fclose($handle);
    
    echo "📄 Archivo CSV leído: " . count($materials) . " materiales encontrados\n\n";
    return importFromArray($materials);
}

// ============================================
// OPCIÓN 3: Importar desde JSON
// ============================================
function importFromJSON(string $jsonFile): array
{
    if (!file_exists($jsonFile)) {
        throw new Exception("Archivo JSON no encontrado: $jsonFile");
    }

    $content = file_get_contents($jsonFile);
    $data = json_decode($content, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception("Error al decodificar JSON: " . json_last_error_msg());
    }

    // Si es un array de materiales
    if (isset($data[0]) && is_array($data[0])) {
        $materials = $data;
    } 
    // Si tiene una clave 'materials'
    elseif (isset($data['materials']) && is_array($data['materials'])) {
        $materials = $data['materials'];
    }
    // Si es un solo material
    elseif (isset($data['sku'])) {
        $materials = [$data];
    }
    else {
        throw new Exception("Formato JSON no reconocido. Debe ser un array de materiales o {'materials': [...]}");
    }

    echo "📄 Archivo JSON leído: " . count($materials) . " materiales encontrados\n\n";
    return importFromArray($materials);
}

// ============================================
// USO DEL SCRIPT
// ============================================

// MODO 1: Importar desde array PHP (editar aquí)
$materials_array = [
    // Ejemplo de material:
    // [
    //     'sku' => 'MAT-001',
    //     'descripcion' => 'Material de Ejemplo',
    //     'unidad' => 'Pieza',
    //     'categoria' => 'Cableado',
    //     'activo' => true
    // ],
];

// MODO 2: Importar desde CSV
$csv_file = __DIR__ . '/materials_import.csv'; // Cambiar a la ruta de tu archivo CSV

// MODO 3: Importar desde JSON
$json_file = __DIR__ . '/materials_import.json'; // Cambiar a la ruta de tu archivo JSON

// ============================================
// EJECUTAR IMPORTACIÓN
// ============================================

$results = null;

// Prioridad: 1) CSV, 2) JSON, 3) Array PHP
if (isset($_SERVER['argv'][1])) {
    // Desde línea de comandos
    $file = $_SERVER['argv'][1];
    
    if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'csv') {
        echo "📥 Importando desde CSV: $file\n\n";
        $results = importFromCSV($file);
    } elseif (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'json') {
        echo "📥 Importando desde JSON: $file\n\n";
        $results = importFromJSON($file);
    } else {
        echo "❌ Error: Formato de archivo no soportado. Use .csv o .json\n";
        exit(1);
    }
} elseif (!empty($materials_array)) {
    // Desde array PHP
    echo "📥 Importando desde array PHP\n\n";
    $results = importFromArray($materials_array);
} elseif (file_exists($csv_file)) {
    // CSV por defecto
    echo "📥 Importando desde CSV: $csv_file\n\n";
    $results = importFromCSV($csv_file);
} elseif (file_exists($json_file)) {
    // JSON por defecto
    echo "📥 Importando desde JSON: $json_file\n\n";
    $results = importFromJSON($json_file);
} else {
    echo "⚠️  No se encontró ningún archivo para importar.\n\n";
    echo "FORMAS DE USO:\n\n";
    echo "1. Desde línea de comandos:\n";
    echo "   php import_materials.php materials.csv\n";
    echo "   php import_materials.php materials.json\n\n";
    
    echo "2. Editar el array \$materials_array en este archivo\n\n";
    
    echo "3. Colocar un archivo en la raíz del proyecto:\n";
    echo "   - materials_import.csv\n";
    echo "   - materials_import.json\n\n";
    
    echo "FORMATO CSV (separado por comas):\n";
    echo "sku,descripcion,unidad,categoria,activo\n";
    echo "MAT-001,Material de Ejemplo,Pieza,Cableado,1\n\n";
    
    echo "FORMATO JSON:\n";
    echo "[\n";
    echo "  {\"sku\": \"MAT-001\", \"descripcion\": \"Material de Ejemplo\", \"unidad\": \"Pieza\", \"categoria\": \"Cableado\", \"activo\": true}\n";
    echo "]\n\n";
    exit(0);
}

// Mostrar resultados
if ($results) {
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "RESUMEN:\n";
    echo "✅ Insertados: {$results['inserted']}\n";
    echo "🔄 Actualizados: {$results['updated']}\n";
    
    if (!empty($results['errors'])) {
        echo "❌ Errores: " . count($results['errors']) . "\n";
        foreach ($results['errors'] as $error) {
            echo "   - $error\n";
        }
    } else {
        echo "✅ Sin errores\n";
    }
    echo str_repeat("=", 50) . "\n";
}

