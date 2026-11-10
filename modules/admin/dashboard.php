<?php
/**
 * Módulo de Administrador - Dashboard Gerencial y Analítico
 * Requerimiento 9 (0.44 pts): Métricas e indicadores clave de control operativo y SLA de 15 días
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cali_geodata.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// 1. Métricas Totales
$totalSolicitudes = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes')->fetchColumn();
$totalRadicadas   = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes WHERE estado = "radicada"')->fetchColumn();
$totalEnrutadas   = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes WHERE estado = "enrutada"')->fetchColumn();
$totalEnCampo     = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes WHERE estado = "en_campo"')->fetchColumn();
$totalEnRevision  = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes WHERE estado = "en_revision"')->fetchColumn();
$totalAptas       = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes WHERE estado = "aprobada_apto"')->fetchColumn();
$totalNoAptas     = (int)$pdo->query('SELECT COUNT(*) FROM solicitudes WHERE estado = "rechazada_no_apto"')->fetchColumn();

// Análisis de SLA de 15 días calendario
$stmtSLA = $pdo->query('
    SELECT 
        SUM(CASE WHEN DATEDIFF(NOW(), fecha_radicacion) > 15 AND estado NOT IN ("aprobada_apto", "rechazada_no_apto") THEN 1 ELSE 0 END) AS activas_vencidas,
        SUM(CASE WHEN DATEDIFF(NOW(), fecha_radicacion) BETWEEN 11 AND 15 AND estado NOT IN ("aprobada_apto", "rechazada_no_apto") THEN 1 ELSE 0 END) AS activas_alerta,
        SUM(CASE WHEN DATEDIFF(NOW(), fecha_radicacion) <= 10 AND estado NOT IN ("aprobada_apto", "rechazada_no_apto") THEN 1 ELSE 0 END) AS activas_a_tiempo,
        SUM(CASE WHEN estado IN ("aprobada_apto", "rechazada_no_apto") THEN 1 ELSE 0 END) AS finalizadas_total
    FROM solicitudes
');
$slaStats = $stmtSLA->fetch();

$vencidasTotal = (int)($slaStats['activas_vencidas'] ?? 0);
$alertaTotal   = (int)($slaStats['activas_alerta'] ?? 0);
$aTiempoTotal  = (int)($slaStats['activas_a_tiempo'] ?? 0);
$finalizadasTotal = (int)($slaStats['finalizadas_total'] ?? 0);

$tasaCumplimiento = $totalSolicitudes > 0 ? round((($totalSolicitudes - $vencidasTotal) / $totalSolicitudes) * 100, 1) : 100;
$tasaAprobacion = ($totalAptas + $totalNoAptas) > 0 ? round(($totalAptas / ($totalAptas + $totalNoAptas)) * 100, 1) : 0;

// 2. Solicitudes por Comuna de Cali
$stmtComunas = $pdo->query('
    SELECT 
        COALESCE(comuna, 0) AS num_comuna,
        zona_tipo,
        COUNT(*) AS total
    FROM solicitudes
    GROUP BY comuna, zona_tipo
    ORDER BY total DESC
');
$porComuna = $stmtComunas->fetchAll();

// Preparar arrays para Chart.js
$labelsComunas = [];
$dataComunas = [];
foreach ($porComuna as $pc) {
    $lbl = ($pc['zona_tipo'] === 'rural') ? 'Zona Rural' : 'Comuna ' . $pc['num_comuna'];
    $labelsComunas[] = $lbl;
    $dataComunas[] = (int)$pc['total'];
}

// 3. Solicitudes por Punto de Atención (Las 8 sedes estáticas)
$stmtPuntos = $pdo->query('
    SELECT p.nombre, p.comuna, p.es_principal, COUNT(s.id) AS total_solicitudes
    FROM puntos_atencion p
    LEFT JOIN solicitudes s ON p.id = s.punto_atencion_id
    GROUP BY p.id
    ORDER BY p.es_principal DESC, p.comuna ASC
');
$porPuntos = $stmtPuntos->fetchAll();

$labelsPuntos = [];
$dataPuntos = [];
foreach ($porPuntos as $pp) {
    $labelsPuntos[] = 'C' . $pp['comuna'] . ($pp['es_principal'] ? ' (Principal)' : '');
    $dataPuntos[] = (int)$pp['total_solicitudes'];
}

// 4. Casos Críticos Próximos a Vencer (> 10 días desde radicación sin dictamen final)
$stmtCriticos = $pdo->query('
    SELECT s.*, p.nombre AS punto_nombre
    FROM solicitudes s
    INNER JOIN puntos_atencion p ON s.punto_atencion_id = p.id
    WHERE s.estado NOT IN ("aprobada_apto", "rechazada_no_apto")
      AND DATEDIFF(NOW(), s.fecha_radicacion) >= 10
    ORDER BY s.fecha_radicacion ASC
    LIMIT 6
');
$criticos = $stmtCriticos->fetchAll();

$pageTitle = 'Dashboard Gerencial';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Tablero de Control Gerencial y Analítico</h4>
        <p class="text-muted small mb-0">Requerimiento 9 &bull; Monitoreo de Cumplimiento de Políticas, SLA 15 Días y Operación</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('modules/admin/reportes_pdf.php?tipo=sla_gerencial') ?>" target="_blank" class="btn btn-danger btn-sm fw-semibold">
            <i class="bi bi-file-earmark-pdf me-1"></i> Generar Informe Ejecutivo PDF
        </a>
    </div>
</div>

<!-- Fila 1: KPIs Principales -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-blue">
            <div class="kpi-title">Total Solicitudes</div>
            <div class="kpi-value text-primary"><?= $totalSolicitudes ?></div>
            <div class="kpi-desc">Radicadas en los 8 puntos de atención</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card <?= $tasaCumplimiento >= 85 ? 'kpi-green' : 'kpi-red' ?>">
            <div class="kpi-title">Cumplimiento SLA (15 Días)</div>
            <div class="kpi-value <?= $tasaCumplimiento >= 85 ? 'text-success' : 'text-danger' ?>">
                <?= $tasaCumplimiento ?>%
            </div>
            <div class="kpi-desc">
                <?= $vencidasTotal ?> solicitudes fuera de plazo (>15 d)
            </div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-amber">
            <div class="kpi-title">En Proceso Operativo</div>
            <div class="kpi-value text-warning"><?= $totalRadicadas + $totalEnrutadas + $totalEnCampo + $totalEnRevision ?></div>
            <div class="kpi-desc"><?= $totalRadicadas ?> sin ruteo &bull; <?= $totalEnCampo ?> en campo</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-purple">
            <div class="kpi-title">Dictámenes Oficiales</div>
            <div class="kpi-value text-purple" style="color: #7c3aed;">
                <span class="text-success"><?= $totalAptas ?> A</span> / 
                <span class="text-danger"><?= $totalNoAptas ?> R</span>
            </div>
            <div class="kpi-desc">Tasa Aprobación: <?= $tasaAprobacion ?>%</div>
        </div>
    </div>
</div>

<!-- Fila 2: Gráficos de Análisis Operativo -->
<div class="row g-3 mb-4">
    
    <!-- Gráfico 1: Estado del Embudo Operativo -->
    <div class="col-lg-4">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h5 class="app-card-title"><i class="bi bi-pie-chart me-2"></i>Embudo del Proceso</h5>
            </div>
            <div class="app-card-body d-flex flex-column align-items-center justify-content-center">
                <div style="height: 240px; width: 100%;">
                    <canvas id="chartEstados"></canvas>
                </div>
                <div class="mt-2 small text-muted text-center">
                    Trazabilidad desde Radicación hasta Dictamen de Calidad
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico 2: Distribución por Comunas de Cali -->
    <div class="col-lg-4">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h5 class="app-card-title"><i class="bi bi-bar-chart me-2"></i>Demanda por Comuna</h5>
            </div>
            <div class="app-card-body">
                <div style="height: 240px; width: 100%;">
                    <canvas id="chartComunas"></canvas>
                </div>
                <div class="mt-2 small text-muted text-center">
                    Concentración de solicitudes por sector territorial
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico 3: Desempeño por Puntos de Atención Estáticos -->
    <div class="col-lg-4">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h5 class="app-card-title"><i class="bi bi-building me-2"></i>Carga en Sedes Estáticas</h5>
            </div>
            <div class="app-card-body">
                <div style="height: 240px; width: 100%;">
                    <canvas id="chartPuntos"></canvas>
                </div>
                <div class="mt-2 small text-muted text-center">
                    Comunas 1, 6, 7, 13, 14, 20, 21 y Sede Principal 17
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Fila 3: Semáforo de Alertas Críticas de SLA y Tabla de Monitoreo -->
<div class="row g-3">
    
    <div class="col-lg-4">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h5 class="app-card-title"><i class="bi bi-speedometer me-2"></i>Semáforo SLA 15 Días</h5>
            </div>
            <div class="app-card-body">
                
                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span class="text-success"><i class="bi bi-circle-fill me-1"></i> A Tiempo (&le; 10 días transcurridos)</span>
                        <strong><?= $aTiempoTotal ?></strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: <?= $totalSolicitudes > 0 ? ($aTiempoTotal / $totalSolicitudes)*100 : 0 ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span class="text-warning"><i class="bi bi-circle-fill me-1"></i> Alerta Crítica (11 a 15 días transcurridos)</span>
                        <strong><?= $alertaTotal ?></strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: <?= $totalSolicitudes > 0 ? ($alertaTotal / $totalSolicitudes)*100 : 0 ?>%"></div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between small fw-semibold mb-1">
                        <span class="text-danger"><i class="bi bi-circle-fill me-1"></i> Vencidas (> 15 días calendario)</span>
                        <strong><?= $vencidasTotal ?></strong>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-danger" style="width: <?= $totalSolicitudes > 0 ? ($vencidasTotal / $totalSolicitudes)*100 : 0 ?>%"></div>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border small mt-4">
                    <strong class="d-block text-navy mb-1"><i class="bi bi-exclamation-circle text-primary me-1"></i>Diagnóstico del Caso Operativo:</strong>
                    El 30% de retraso histórico del modelo en papel se debía a la acumulación manual de 5 días para enrutamiento y las inconsistencias en fichas físicas. La automatización digital en SIRCE permite despachar las hojas de ruta en menos de 24 horas y validar datos en tiempo real.
                </div>

            </div>
        </div>
    </div>

    <!-- Tabla de Solicitudes Críticas / Alertas -->
    <div class="col-lg-8">
        <div class="app-card h-100">
            <div class="app-card-header d-flex justify-content-between align-items-center">
                <h5 class="app-card-title text-danger">
                    <i class="bi bi-alarm-fill me-2"></i>Alertas de Solicitudes Próximas a Vencer (&ge; 10 días)
                </h5>
                <span class="badge bg-danger-subtle text-danger border border-danger small">Prioridad Máxima</span>
            </div>
            <div class="app-card-body p-0">
                <div class="table-responsive">
                    <table class="table table-app table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Radicado</th>
                                <th>Solicitante & Contacto</th>
                                <th>Ubicación</th>
                                <th>Días Transcurridos</th>
                                <th>Estado Actual</th>
                                <th class="text-end">Acción</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($criticos)): ?>
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-success">
                                        <i class="bi bi-check-circle fs-3 d-block mb-1"></i>
                                        ¡Excelente! No hay solicitudes críticas en zona de alerta o vencimiento en este momento.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($criticos as $c): ?>
                                    <?php $sla = calcularEstadoSLA($c['fecha_radicacion'], $c['estado']); ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary font-monospace"><?= e($c['numero_radicado']) ?></strong>
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= e($c['nombres']) ?> <?= e($c['apellidos']) ?></div>
                                            <small class="text-muted"><i class="bi bi-phone me-1"></i><?= e($c['celular']) ?></small>
                                        </td>
                                        <td>
                                            <small><?= e($c['barrio']) ?> (C<?= $c['comuna'] ?: 'Rural' ?>)</small>
                                        </td>
                                        <td>
                                            <span class="badge-sla <?= $sla['badge_class'] ?>">
                                                <?= e($sla['etiqueta']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="status-tag status-<?= e($c['estado']) ?>">
                                                <?= str_replace('_', ' ', e($c['estado'])) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="<?= url('modules/enrutador/crear_hoja_ruta.php?preselect=' . $c['id']) ?>" class="btn btn-xs btn-outline-warning text-dark btn-sm" title="Priorizar enrutamiento">
                                                <i class="bi bi-lightning-charge"></i> Enrutar Ya
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Scripts de Inicialización de Chart.js -->
<script>
document.addEventListener('DOMContentLoaded', function () {
    
    // Gráfico 1: Embudo de Estados (Doughnut)
    const ctxEstados = document.getElementById('chartEstados').getContext('2d');
    new Chart(ctxEstados, {
        type: 'doughnut',
        data: {
            labels: ['Radicadas', 'Enrutadas', 'En Campo', 'En Revisión', 'Aptas', 'No Aptas'],
            datasets: [{
                data: [
                    <?= $totalRadicadas ?>,
                    <?= $totalEnrutadas ?>,
                    <?= $totalEnCampo ?>,
                    <?= $totalEnRevision ?>,
                    <?= $totalAptas ?>,
                    <?= $totalNoAptas ?>
                ],
                backgroundColor: [
                    '#94a3b8',
                    '#f59e0b',
                    '#0284c7',
                    '#a855f7',
                    '#16a34a',
                    '#dc2626'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: { boxWidth: 12, font: { size: 10 } }
                }
            }
        }
    });

    // Gráfico 2: Demanda por Comuna (Barra Horizontal)
    const ctxComunas = document.getElementById('chartComunas').getContext('2d');
    new Chart(ctxComunas, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labelsComunas, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Solicitudes',
                data: <?= json_encode($dataComunas) ?>,
                backgroundColor: '#0e2a47',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            indexAxis: 'y',
            plugins: {
                legend: { display: false }
            },
            scales: {
                x: { beginAtZero: true, ticks: { precision: 0 } },
                y: { ticks: { font: { size: 10 } } }
            }
        }
    });

    // Gráfico 3: Sedes Estáticas (Barra Vertical)
    const ctxPuntos = document.getElementById('chartPuntos').getContext('2d');
    new Chart(ctxPuntos, {
        type: 'bar',
        data: {
            labels: <?= json_encode($labelsPuntos, JSON_UNESCAPED_UNICODE) ?>,
            datasets: [{
                label: 'Radicaciones por Sede',
                data: <?= json_encode($dataPuntos) ?>,
                backgroundColor: '#0284c7',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, ticks: { precision: 0 } },
                x: { ticks: { font: { size: 9 } } }
            }
        }
    });

});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
