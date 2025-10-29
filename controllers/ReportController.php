<?php
declare(strict_types=1);

/**
 * Controlador de Reportes
 */
class ReportController
{
    /**
     * Mostrar reporte HTML
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
                setFlashMessage('error', 'No tienes permisos para ver reportes');
                redirect(base_url('projects.php'));
                return;
            }
        }

        $summary = Report::getExecutiveSummary($projectId);
        $costReport = Report::getCostReport($projectId);

        require_once __DIR__ . '/../views/reports/project.php';
    }

    /**
     * Exportar reporte a Excel
     */
    public static function exportExcel(): void
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
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            requireRole(ROLE_ADMIN);
        }

        try {
            // Verificar si PhpSpreadsheet está disponible
            if (!class_exists('\PhpOffice\PhpSpreadsheet\Spreadsheet')) {
                setFlashMessage('error', 'PhpSpreadsheet no está instalado. Ejecuta: composer require phpoffice/phpspreadsheet');
                redirect(base_url("reports.php?project_id=$projectId"));
                return;
            }

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Reporte Proyecto');

            // Header
            $sheet->setCellValue('A1', 'Reporte del Proyecto: ' . $project['nombre']);
            $sheet->mergeCells('A1:E1');
            $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);

            $row = 3;
            $sheet->setCellValue('A' . $row, 'SKU');
            $sheet->setCellValue('B' . $row, 'Material');
            $sheet->setCellValue('C' . $row, 'Requerido');
            $sheet->setCellValue('D' . $row, 'Comprado');
            $sheet->setCellValue('E' . $row, 'Disponible');
            $sheet->setCellValue('F' . $row, 'Entregado');
            $sheet->setCellValue('G' . $row, '% Avance');
            $sheet->setCellValue('H' . $row, 'Costo Promedio');
            $sheet->setCellValue('I' . $row, 'Total Invertido');

            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '4472C4']]
            ];
            $sheet->getStyle('A' . $row . ':I' . $row)->applyFromArray($headerStyle);

            // Datos
            $costReport = Report::getCostReport($projectId);
            $row++;
            foreach ($costReport as $item) {
                $sheet->setCellValue('A' . $row, $item['sku']);
                $sheet->setCellValue('B' . $row, $item['material']);
                $sheet->setCellValue('C' . $row, $item['qty_requerida']);
                $sheet->setCellValue('D' . $row, $item['total_qty_comprada']);
                $sheet->setCellValue('E' . $row, $item['cantidad_disponible']);
                $sheet->setCellValue('F' . $row, $item['cantidad_entregada']);
                $sheet->setCellValue('G' . $row, $item['pct_entregado'] . '%');
                $sheet->setCellValue('H' . $row, $item['costo_promedio_unitario']);
                $sheet->setCellValue('I' . $row, $item['total_costo']);
                $row++;
            }

            // Auto-size columns
            foreach (range('A', 'I') as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }

            // Descargar
            $filename = 'Reporte_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $project['nombre']) . '_' . date('Y-m-d') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            exit;

        } catch (Exception $e) {
            error_log("Error exportando Excel: " . $e->getMessage());
            setFlashMessage('error', 'Error al exportar a Excel: ' . $e->getMessage());
            redirect(base_url("reports.php?project_id=$projectId"));
        }
    }

    /**
     * Exportar reporte a PDF
     */
    public static function exportPDF(): void
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
        if (!hasAnyRole([ROLE_ADMIN, ROLE_PM])) {
            requireRole(ROLE_ADMIN);
        }

        try {
            // Verificar si TCPDF está disponible
            if (!class_exists('TCPDF')) {
                setFlashMessage('error', 'TCPDF no está instalado. Ejecuta: composer require tecnickcom/tcpdf');
                redirect(base_url("reports.php?project_id=$projectId"));
                return;
            }

            $summary = Report::getExecutiveSummary($projectId);
            $costReport = Report::getCostReport($projectId);

            // Crear PDF
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
            $pdf->SetCreator(APP_NAME);
            $pdf->SetAuthor(APP_NAME);
            $pdf->SetTitle('Reporte - ' . $project['nombre']);
            $pdf->SetSubject('Reporte de Proyecto');
            
            $pdf->SetHeaderData('', 0, APP_NAME, 'Reporte de Materiales');
            $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN]);
            $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA]);
            $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
            $pdf->SetMargins(15, 20, 15);
            $pdf->SetHeaderMargin(5);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(TRUE, 15);
            $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
            $pdf->SetFont('helvetica', '', 10);

            $pdf->AddPage();

            // Contenido HTML
            $html = '<h1>Reporte del Proyecto: ' . htmlspecialchars($project['nombre']) . '</h1>';
            $html .= '<p><strong>Ubicación:</strong> ' . htmlspecialchars($project['ubicacion'] ?? 'N/A') . '</p>';
            $html .= '<p><strong>Estado:</strong> ' . htmlspecialchars($project['estado']) . '</p>';
            $html .= '<p><strong>Fecha de Reporte:</strong> ' . date('d/m/Y H:i') . '</p>';
            $html .= '<hr>';

            // Resumen
            $html .= '<h2>Resumen Ejecutivo</h2>';
            $html .= '<table border="1" cellpadding="5">';
            $html .= '<tr><th>Concepto</th><th>Valor</th></tr>';
            $html .= '<tr><td>Total Materiales</td><td>' . ($summary['totals']['total_materiales'] ?? 0) . '</td></tr>';
            $html .= '<tr><td>Total Requerido</td><td>' . number_format($summary['totals']['total_requerido'] ?? 0, 2) . '</td></tr>';
            $html .= '<tr><td>Total Comprado</td><td>' . number_format($summary['totals']['total_comprado'] ?? 0, 2) . '</td></tr>';
            $html .= '<tr><td>Total Entregado</td><td>' . number_format($summary['totals']['total_entregado'] ?? 0, 2) . '</td></tr>';
            $html .= '<tr><td>Total Invertido</td><td>' . formatCurrency($summary['totals']['total_invertido'] ?? 0) . '</td></tr>';
            $html .= '</table>';

            // Tabla de materiales
            $html .= '<h2>Detalle por Material</h2>';
            $html .= '<table border="1" cellpadding="5" style="font-size:8px;">';
            $html .= '<tr style="background-color:#4472C4;color:#FFFFFF;">
                        <th>Material</th><th>Requerido</th><th>Comprado</th><th>Entregado</th>
                        <th>% Avance</th><th>Costo Prom.</th><th>Total</th>
                      </tr>';
            
            foreach ($costReport as $item) {
                $html .= '<tr>';
                $html .= '<td>' . htmlspecialchars($item['material']) . '<br><small>' . htmlspecialchars($item['sku']) . '</small></td>';
                $html .= '<td>' . number_format($item['qty_requerida'], 2) . '</td>';
                $html .= '<td>' . number_format($item['total_qty_comprada'], 2) . '</td>';
                $html .= '<td>' . number_format($item['cantidad_entregada'], 2) . '</td>';
                $html .= '<td>' . number_format($item['pct_entregado'], 1) . '%</td>';
                $html .= '<td>' . formatCurrency($item['costo_promedio_unitario']) . '</td>';
                $html .= '<td>' . formatCurrency($item['total_costo']) . '</td>';
                $html .= '</tr>';
            }
            $html .= '</table>';

            $pdf->writeHTML($html, true, false, true, false, '');
            
            // Descargar
            $filename = 'Reporte_' . preg_replace('/[^a-zA-Z0-9_\-]/', '_', $project['nombre']) . '_' . date('Y-m-d') . '.pdf';
            $pdf->Output($filename, 'D');
            exit;

        } catch (Exception $e) {
            error_log("Error exportando PDF: " . $e->getMessage());
            setFlashMessage('error', 'Error al exportar a PDF: ' . $e->getMessage());
            redirect(base_url("reports.php?project_id=$projectId"));
        }
    }
}

