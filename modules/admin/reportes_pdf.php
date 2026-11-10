<?php
/**
 * Módulo de Generación de Informes en PDF
 * Requerimiento 8 (0.44 pts): Al menos un informe PDF generado desde el Administrador
 * Utiliza la librería oficial FPDF nativa en PHP
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/fpdf/fpdf.php';

requireRole(['administrador', 'enrutador', 'ventanilla', 'calidad']);

$tipoReporte = $_GET['tipo'] ?? '';
$pdo = getDBConnection();

// Clase extendida de FPDF con cabecera y pie institucionales
class PDFInstitucional extends FPDF {
    public string $tituloReporte = 'INFORME OFICIAL';
    public string $subtituloReporte = 'SISTEMA INTEGRADO DE RADICACIÓN, RUTEO Y CALIDAD (SIRCE)';

    function Header() {
        // Franja azul superior
        $this->SetFillColor(14, 42, 71); // #0e2a47
        $this->Rect(0, 0, 216, 22, 'F');

        // Textos del encabezado
        $this->SetFont('Arial', 'B', 12);
        $this->SetTextColor(255, 255, 255);
        $this->SetXY(10, 4);
        $this->Cell(0, 6, utf8_decode('SANTIAGO DE CALI - ALCALDÍA DISTRITAL'), 0, 1, 'L');

        $this->SetFont('Arial', '', 8);
        $this->SetTextColor(200, 220, 240);
        $this->SetX(10);
        $this->Cell(0, 4, utf8_decode($this->subtituloReporte), 0, 1, 'L');

        // Título del reporte
        $this->SetY(26);
        $this->SetFont('Arial', 'B', 13);
        $this->SetTextColor(14, 42, 71);
        $this->Cell(0, 7, utf8_decode($this->tituloReporte), 0, 1, 'C');

        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(0, 4, utf8_decode('Fecha de Emisión: ' . date('d/m/Y h:i A') . ' | Control SLA: 15 Días Calendario'), 0, 1, 'C');
        $this->Ln(4);

        // Línea divisoria
        $this->SetDrawColor(203, 213, 225);
        $this->Line(10, $this->GetY(), 206, $this->GetY());
        $this->Ln(3);
    }

    function Footer() {
        $this->SetY(-15);
        $this->SetFont('Arial', 'I', 8);
        $this->SetTextColor(100, 116, 139);
        $this->Cell(100, 10, utf8_decode('SIRCE Cali - Software de Gestión de Encuestas'), 0, 0, 'L');
        $this->Cell(0, 10, utf8_decode('Página ' . $this->PageNo() . ' de {nb}'), 0, 0, 'R');
    }
}

// -------------------------------------------------------------
// REPORTE 1: INFORME GERENCIAL DE CUMPLIMIENTO DE POLÍTICAS Y SLA
// -------------------------------------------------------------
if ($tipoReporte === 'sla_gerencial') {
    // Consultar datos
    $totalSol = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes')->fetchColumn();
    $totalAptas = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes WHERE estado = "aprobada_apto"')->fetchColumn();
    $totalNoAptas = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes WHERE estado = "rechazada_no_apto"')->fetchColumn();

    $stmtSLA = $pdo->query('
        SELECT 
            SUM(CASE WHEN DATEDIFF(NOW(), fecha_radicacion) > 15 AND estado NOT IN ("aprobada_apto", "rechazada_no_apto") THEN 1 ELSE 0 END) AS vencidas,
            SUM(CASE WHEN DATEDIFF(NOW(), fecha_radicacion) BETWEEN 11 AND 15 AND estado NOT IN ("aprobada_apto", "rechazada_no_apto") THEN 1 ELSE 0 END) AS alerta,
            SUM(CASE WHEN DATEDIFF(NOW(), fecha_radicacion) <= 10 AND estado NOT IN ("aprobada_apto", "rechazada_no_apto") THEN 1 ELSE 0 END) AS a_tiempo
        FROM solicitudes
    ')->fetch();

    $vencidas = (int)($stmtSLA['vencidas'] ?? 0);
    $alerta   = (int)($stmtSLA['alerta'] ?? 0);
    $aTiempo  = (int)($stmtSLA['a_tiempo'] ?? 0);
    $tasaCumplimiento = $totalSol > 0 ? round((($totalSol - $vencidas) / $totalSol) * 100, 1) : 100;

    $pdf = new PDFInstitucional('P', 'mm', 'Letter');
    $pdf->tituloReporte = 'INFORME GERENCIAL DE CUMPLIMIENTO DE SLA (15 DÍAS)';
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Resumen Ejecutivo
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetTextColor(14, 42, 71);
    $pdf->Cell(0, 6, utf8_decode('1. RESUMEN EJECUTIVO Y POLÍTICA INSTITUCIONAL'), 1, 1, 'L', true);
    $pdf->Ln(2);

    $pdf->SetFont('Arial', '', 8.5);
    $pdf->SetTextColor(30, 41, 59);
    $textoResumen = "La entidad rige sus operaciones bajo la política estricta de dar respuesta a las solicitudes de los usuarios en un plazo máximo de 15 días calendario. El presente informe evalúa el comportamiento de los tiempos de radicación, ruteo, levantamiento y revisión de calidad para evitar demandas y desgaste jurídico.";
    $pdf->MultiCell(0, 4.5, utf8_decode($textoResumen));
    $pdf->Ln(3);

    // Tabla de Indicadores Clave
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetFillColor(14, 42, 71);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(98, 6, utf8_decode('Indicador Operativo'), 1, 0, 'L', true);
    $pdf->Cell(48, 6, utf8_decode('Cantidad / Valor'), 1, 0, 'C', true);
    $pdf->Cell(50, 6, utf8_decode('Estado Normativo'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 8);
    $pdf->SetTextColor(30, 41, 59);

    $pdf->Cell(98, 5.5, utf8_decode('Total de Solicitudes Atendidas en el Sistema'), 1, 0, 'L');
    $pdf->Cell(48, 5.5, $totalSol, 1, 0, 'C');
    $pdf->Cell(50, 5.5, utf8_decode('Cobertura Total'), 1, 1, 'C');

    $pdf->Cell(98, 5.5, utf8_decode('Tasa de Cumplimiento de Plazo (<= 15 Días)'), 1, 0, 'L');
    $pdf->Cell(48, 5.5, $tasaCumplimiento . '%', 1, 0, 'C');
    $pdf->Cell(50, 5.5, utf8_decode($tasaCumplimiento >= 85 ? 'Meta Cumplida' : 'Bajo Meta (Acción req.)'), 1, 1, 'C');

    $pdf->Cell(98, 5.5, utf8_decode('Solicitudes Fuera de Plazo (> 15 Días)'), 1, 0, 'L');
    $pdf->Cell(48, 5.5, $vencidas, 1, 0, 'C');
    $pdf->Cell(50, 5.5, utf8_decode($vencidas === 0 ? 'Sin Riesgo' : 'Riesgo Jurídico Activo'), 1, 1, 'C');

    $pdf->Cell(98, 5.5, utf8_decode('Solicitudes en Alerta Preventiva (Días 11 a 15)'), 1, 0, 'L');
    $pdf->Cell(48, 5.5, $alerta, 1, 0, 'C');
    $pdf->Cell(50, 5.5, utf8_decode('Prioridad de Ruteo'), 1, 1, 'C');

    $pdf->Cell(98, 5.5, utf8_decode('Dictámenes de Calidad Emitidos (Aptos)'), 1, 0, 'L');
    $pdf->Cell(48, 5.5, $totalAptas, 1, 0, 'C');
    $pdf->Cell(50, 5.5, utf8_decode('Habilitados Préstamo Ext.'), 1, 1, 'C');

    $pdf->Ln(4);

    // Sección 2: Desglose por Puntos de Atención
    $pdf->SetFont('Arial', 'B', 10);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetTextColor(14, 42, 71);
    $pdf->Cell(0, 6, utf8_decode('2. DESGLOSE POR PUNTOS DE ATENCIÓN ESTÁTICOS DE CALI'), 1, 1, 'L', true);
    $pdf->Ln(2);

    $stmtPuntos = $pdo->query('
        SELECT p.nombre, p.comuna, p.es_principal,
               COUNT(s.id) AS total,
               SUM(CASE WHEN DATEDIFF(NOW(), s.fecha_radicacion) > 15 AND s.estado NOT IN ("aprobada_apto", "rechazada_no_apto") THEN 1 ELSE 0 END) AS vencidas_punto
        FROM puntos_atencion p
        LEFT JOIN solicitudes s ON p.id = s.punto_atencion_id
        GROUP BY p.id
        ORDER BY p.es_principal DESC, p.comuna ASC
    ')->fetchAll();

    $pdf->SetFont('Arial', 'B', 8);
    $pdf->SetFillColor(14, 42, 71);
    $pdf->SetTextColor(255, 255, 255);
    $pdf->Cell(90, 6, utf8_decode('Sede de Atención'), 1, 0, 'L', true);
    $pdf->Cell(35, 6, utf8_decode('Comuna'), 1, 0, 'C', true);
    $pdf->Cell(35, 6, utf8_decode('Radicadas'), 1, 0, 'C', true);
    $pdf->Cell(36, 6, utf8_decode('Fuera de Plazo'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetTextColor(30, 41, 59);

    foreach ($stmtPuntos as $pRow) {
        $pdf->Cell(90, 5, utf8_decode($pRow['nombre'] . ($pRow['es_principal'] ? ' (Principal)' : '')), 1, 0, 'L');
        $pdf->Cell(35, 5, 'Comuna ' . $pRow['comuna'], 1, 0, 'C');
        $pdf->Cell(35, 5, (string)$pRow['total'], 1, 0, 'C');
        $pdf->Cell(36, 5, (string)($pRow['vencidas_punto'] ?? 0), 1, 1, 'C');
    }

    $pdf->Ln(6);

    // Firmas de Responsabilidad
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(95, 15, '', 'B', 0, 'C');
    $pdf->Cell(6, 15, '', 0, 0);
    $pdf->Cell(95, 15, '', 'B', 1, 'C');

    $pdf->Cell(95, 5, utf8_decode('Coordinación General SIRCE'), 0, 0, 'C');
    $pdf->Cell(6, 5, '', 0, 0);
    $pdf->Cell(95, 5, utf8_decode('Auditoría y Control de Calidad'), 0, 1, 'C');

    $pdf->Output('I', 'Informe_Gerencial_SLA_SIRCE_Cali.pdf');
    exit;
}

// -------------------------------------------------------------
// REPORTE 2: HOJA DE RUTA OFICIAL PARA ENCUESTADOR (PDF)
// -------------------------------------------------------------
if ($tipoReporte === 'hoja_ruta') {
    $hojaId = (int)($_GET['id'] ?? 0);
    if ($hojaId <= 0) die('ID de hoja de ruta requerido.');

    $stmtH = $pdo->prepare('
        SELECT hr.*, 
               p.nombre AS punto_nombre, p.comuna AS punto_comuna, p.direccion AS punto_dir,
               u_enc.nombre_completo AS encuestador_nombre, u_enc.cedula AS encuestador_cedula,
               u_enc.telefono AS encuestador_tel,
               u_enr.nombre_completo AS enrutador_nombre
        FROM hojas_ruta hr
        INNER JOIN puntos_atencion p ON hr.punto_atencion_id = p.id
        INNER JOIN usuarios u_enc ON hr.encuestador_id = u_enc.id
        INNER JOIN usuarios u_enr ON hr.enrutador_id = u_enr.id
        WHERE hr.id = :id
    ');
    $stmtH->execute([':id' => $hojaId]);
    $hoja = $stmtH->fetch();

    if (!$hoja) die('Hoja de ruta no encontrada.');

    // Items de la hoja
    $stmtIt = $pdo->prepare('
        SELECT hri.*, s.numero_radicado, s.nombres, s.apellidos, s.direccion, s.barrio,
               s.comuna, s.corregimiento, s.zona_tipo, s.celular
        FROM hoja_ruta_items hri
        INNER JOIN solicitudes s ON hri.solicitud_id = s.id
        WHERE hri.hoja_ruta_id = :id
        ORDER BY hri.orden_visita ASC
    ');
    $stmtIt->execute([':id' => $hojaId]);
    $items = $stmtIt->fetchAll();

    $pdf = new PDFInstitucional('L', 'mm', 'Letter'); // Horizontal para tabla ancha de visitas
    $pdf->tituloReporte = 'HOJA DE RUTA Y CARGA DE TRABAJO EN TERRITORIO';
    $pdf->AliasNbPages();
    $pdf->AddPage();

    // Bloque Datos de Control
    $pdf->SetFont('Arial', 'B', 8.5);
    $pdf->SetFillColor(241, 245, 249);
    $pdf->SetTextColor(14, 42, 71);

    $pdf->Cell(65, 5.5, utf8_decode('Código: ' . $hoja['codigo_hoja']), 1, 0, 'L', true);
    $pdf->Cell(65, 5.5, utf8_decode('Fecha Visita: ' . formatearFecha($hoja['fecha_programada'])), 1, 0, 'L', true);
    $pdf->Cell(65, 5.5, utf8_decode('Horario: 7:00 a.m. - 12:30 p.m.'), 1, 0, 'L', true);
    $pdf->Cell(65, 5.5, utf8_decode('Carga: ' . count($items) . ' encuestas'), 1, 1, 'L', true);

    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(130, 5, utf8_decode('Encuestador: ' . $hoja['encuestador_nombre'] . ' (CC ' . $hoja['encuestador_cedula'] . ' - Tel: ' . $hoja['encuestador_tel'] . ')'), 1, 0, 'L');
    $pdf->Cell(130, 5, utf8_decode('Vehículo: ' . $hoja['camioneta_asignada'] . ' | Encuentro: ' . $hoja['punto_encuentro']), 1, 1, 'L');

    $pdf->Cell(130, 5, utf8_decode('Sede de Entrega Matutina: ' . $hoja['punto_nombre'] . ' (' . $hoja['punto_dir'] . ')'), 1, 0, 'L');
    $pdf->Cell(130, 5, utf8_decode('Enrutador Maestro: ' . $hoja['enrutador_nombre'] . ' | Recogida en Buseta: 12:30 PM OBLIGATORIA'), 1, 1, 'L');

    $pdf->Ln(3);

    // Tabla de Encuestas Asignadas
    $pdf->SetFont('Arial', 'B', 7.5);
    $pdf->SetFillColor(14, 42, 71);
    $pdf->SetTextColor(255, 255, 255);

    $pdf->Cell(10, 6, '#', 1, 0, 'C', true);
    $pdf->Cell(28, 6, utf8_decode('Radicado'), 1, 0, 'C', true);
    $pdf->Cell(52, 6, utf8_decode('Beneficiario / Jefe Hogar'), 1, 0, 'L', true);
    $pdf->Cell(70, 6, utf8_decode('Dirección Domiciliaria Exacta'), 1, 0, 'L', true);
    $pdf->Cell(35, 6, utf8_decode('Barrio / Comuna'), 1, 0, 'L', true);
    $pdf->Cell(28, 6, utf8_decode('Celular'), 1, 0, 'C', true);
    $pdf->Cell(37, 6, utf8_decode('Firma / Estado'), 1, 1, 'C', true);

    $pdf->SetFont('Arial', '', 7.5);
    $pdf->SetTextColor(30, 41, 59);

    foreach ($items as $it) {
        $pdf->Cell(10, 5.5, (string)$it['orden_visita'], 1, 0, 'C');
        $pdf->Cell(28, 5.5, $it['numero_radicado'], 1, 0, 'C');
        $pdf->Cell(52, 5.5, utf8_decode(substr($it['nombres'] . ' ' . $it['apellidos'], 0, 30)), 1, 0, 'L');
        $pdf->Cell(70, 5.5, utf8_decode(substr($it['direccion'], 0, 42)), 1, 0, 'L');
        $pdf->Cell(35, 5.5, utf8_decode(substr($it['barrio'] . ' (C' . ($it['comuna'] ?? 'R') . ')', 0, 22)), 1, 0, 'L');
        $pdf->Cell(28, 5.5, $it['celular'], 1, 0, 'C');
        $pdf->Cell(37, 5.5, utf8_decode($it['estado_visita'] === 'efectiva' ? 'EFECTIVA' : '[               ]'), 1, 1, 'C');
    }

    $pdf->Ln(6);

    // Firmas de entrega de ventanilla y recibido del encuestador
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(120, 12, '', 'B', 0, 'C');
    $pdf->Cell(20, 12, '', 0, 0);
    $pdf->Cell(120, 12, '', 'B', 1, 'C');

    $pdf->Cell(120, 5, utf8_decode('Entregado por: Usuario de Ventanilla en Punto de Atención'), 0, 0, 'C');
    $pdf->Cell(20, 5, '', 0, 0);
    $pdf->Cell(120, 5, utf8_decode('Recibido por Encuestador: ' . $hoja['encuestador_nombre']), 0, 1, 'C');

    $pdf->Output('I', 'Hoja_Ruta_' . $hoja['codigo_hoja'] . '.pdf');
    exit;
}

// -------------------------------------------------------------
// VISTA WEB: CATÁLOGO DE INFORMES EN PDF
// -------------------------------------------------------------
$pageTitle = 'Módulo de Informes en PDF';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Módulo de Generación de Informes en PDF</h4>
        <p class="text-muted small mb-0">Requerimiento 8 &bull; Generación oficial de reportes con validez institucional</p>
    </div>
</div>

<div class="callout-institutional">
    <i class="bi bi-file-earmark-pdf-fill text-danger me-2 fs-5"></i>
    <strong>Estándar de Generación PDF:</strong> Los reportes se compilan dinámicamente en formato PDF estándar vectorizado sin dependencias externas, permitiendo auditoría inmediata del cumplimiento del SLA de 15 días y control de hojas de ruta de campo.
</div>

<div class="row g-3">
    
    <!-- Reporte Académico: Informe Entrega 1 (Scrum, DevOps & Modelo de Datos) -->
    <div class="col-12">
        <div class="app-card border-primary border-2 bg-light">
            <div class="app-card-body p-3 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div class="p-3 bg-white rounded shadow-sm text-danger fs-2 border">
                        <i class="bi bi-file-earmark-pdf-fill"></i>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h5 class="fw-bold mb-0 text-navy">Documento Oficial: Informe Técnico y Metodológico - Entrega 1</h5>
                            <span class="badge bg-primary">Entrega 1 (10 Nov 2026)</span>
                        </div>
                        <p class="small text-muted mb-0">
                            Documento formal completo: Metodología Scrum (fases y roles), 9 Historias de Usuario con formato BDD/Gherkin, Stack tecnológico (PHP 8.2, MySQL, Leaflet), Modelo de Base de Datos 3FN y MER, Herramientas DevOps (GitFlow, Docker, GitHub Actions CI, Jira) y Matriz de Pruebas.
                        </p>
                    </div>
                </div>
                <div class="text-md-end flex-shrink-0">
                    <a href="<?= url('docs/INFORME_ENTREGA_1_SIRCE_CALI.pdf') ?>" target="_blank" class="btn btn-danger fw-semibold px-4 py-2 shadow-sm">
                        <i class="bi bi-download me-1"></i> Descargar Informe Entrega 1 (PDF)
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Reporte 1: Informe Gerencial de Cumplimiento -->
    <div class="col-md-6 col-lg-4">
        <div class="app-card h-100 d-flex flex-column">
            <div class="app-card-header bg-danger-subtle border-danger-subtle">
                <h5 class="app-card-title text-danger">
                    <i class="bi bi-file-earmark-bar-graph-fill me-2"></i>Informe Gerencial de SLA
                </h5>
                <span class="badge bg-danger">Oficial</span>
            </div>
            <div class="app-card-body d-flex flex-column flex-grow-1">
                <p class="small text-muted flex-grow-1">
                    Informe ejecutivo para alta gerencia y entes de control con el balance de cumplimiento de la política estricta de <strong>15 días calendario</strong>, análisis de riesgo jurídico por vencimientos y desglose por los 8 puntos de atención estáticos.
                </p>
                <div class="d-grid mt-3">
                    <a href="reportes_pdf.php?tipo=sla_gerencial" target="_blank" class="btn btn-outline-danger fw-semibold">
                        <i class="bi bi-file-pdf me-1"></i> Descargar Informe SLA (PDF)
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Reporte 2: Hojas de Ruta de Campo -->
    <div class="col-md-6 col-lg-4">
        <div class="app-card h-100 d-flex flex-column">
            <div class="app-card-header bg-primary-subtle border-primary-subtle">
                <h5 class="app-card-title text-primary">
                    <i class="bi bi-file-earmark-spreadsheet-fill me-2"></i>Hojas de Ruta de Campo
                </h5>
                <span class="badge bg-primary">Logística</span>
            </div>
            <div class="app-card-body d-flex flex-column flex-grow-1">
                <p class="small text-muted flex-grow-1">
                    Formato oficial de entrega matutina de la carga de trabajo (hasta 20 encuestas), asignación de vehículos (Van 1 y Van 2), punto de desembarque y retorno obligatorio a las 12:30 p.m. con firmas de entrega.
                </p>
                <div class="d-grid mt-3">
                    <a href="<?= url('modules/enrutador/hojas_ruta.php') ?>" class="btn btn-outline-primary fw-semibold">
                        <i class="bi bi-list-check me-1"></i> Seleccionar Hoja para PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Reporte 3: Auditoría de Calidad y Dictámenes -->
    <div class="col-md-6 col-lg-4">
        <div class="app-card h-100 d-flex flex-column">
            <div class="app-card-header bg-success-subtle border-success-subtle">
                <h5 class="app-card-title text-success">
                    <i class="bi bi-file-earmark-check-fill me-2"></i>Consolidado de Calidad
                </h5>
                <span class="badge bg-success">Aseguramiento</span>
            </div>
            <div class="app-card-body d-flex flex-column flex-grow-1">
                <p class="small text-muted flex-grow-1">
                    Listado de encuestas auditadas por el equipo de 5 revisores con los dictámenes oficiales de "Apto" o "No Apto" para acceder al beneficio de préstamos de cooperación de gobierno extranjero.
                </p>
                <div class="d-grid mt-3">
                    <a href="<?= url('modules/calidad/revision.php?estado=auditadas') ?>" class="btn btn-outline-success fw-semibold">
                        <i class="bi bi-patch-check me-1"></i> Ver Mesa de Calidad
                    </a>
                </div>
            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
