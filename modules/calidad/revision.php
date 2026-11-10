<?php
/**
 * Módulo de Control de Calidad - Mesa de Revisión Diaria
 * Equipo de 5 revisores &bull; Publicación de resultado oficial (Apto / No Apto)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['calidad', 'administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

$filtroEstado = trim($_GET['estado'] ?? 'pendientes');

$sql = '
    SELECT e.*, 
           s.numero_radicado, s.nombres, s.apellidos, s.barrio, s.comuna, s.corregimiento,
           s.zona_tipo, s.direccion, s.celular, s.fecha_radicacion, s.fecha_limite_15dias,
           s.estado AS estado_solicitud,
           u_enc.nombre_completo AS encuestador_nombre,
           rc.resultado_oficial, rc.fecha_revision, rc.dictamen_justificacion,
           u_cal.nombre_completo AS revisor_nombre
    FROM encuestas e
    INNER JOIN solicitudes s ON e.solicitud_id = s.id
    INNER JOIN usuarios u_enc ON e.encuestador_id = u_enc.id
    LEFT JOIN revisiones_calidad rc ON e.id = rc.encuesta_id
    LEFT JOIN usuarios u_cal ON rc.usuario_calidad_id = u_cal.id
    WHERE 1=1
';

if ($filtroEstado === 'pendientes') {
    $sql .= ' AND rc.id IS NULL';
} elseif ($filtroEstado === 'auditadas') {
    $sql .= ' AND rc.id IS NOT NULL';
}

$sql .= ' ORDER BY s.fecha_radicacion ASC, e.fecha_levantamiento DESC';

$stmt = $pdo->query($sql);
$encuestas = $stmt->fetchAll();

// Conteo rápido
$totalPendientes = (int)$pdo->query('SELECT COUNT(*) FROM encuestas e LEFT JOIN revisiones_calidad rc ON e.id = rc.encuesta_id WHERE rc.id IS NULL')->fetchColumn();
$totalAuditadas = (int)$pdo->query('SELECT COUNT(*) FROM revisiones_calidad')->fetchColumn();
$totalAptos = (int)$pdo->query('SELECT COUNT(*) FROM revisiones_calidad WHERE resultado_oficial = "apto"')->fetchColumn();
$totalNoAptos = (int)$pdo->query('SELECT COUNT(*) FROM revisiones_calidad WHERE resultado_oficial = "no_apto"')->fetchColumn();

$pageTitle = 'Mesa de Control de Calidad';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Mesa de Control y Aseguramiento de Calidad</h4>
        <p class="text-muted small mb-0">Equipo Revisor &bull; Auditoría Diaria y Publicación de Dictamen Oficial (Apto / No Apto)</p>
    </div>
    <div class="btn-group btn-group-sm">
        <a href="revision.php?estado=pendientes" class="btn <?= $filtroEstado === 'pendientes' ? 'btn-primary' : 'btn-outline-primary' ?>">
            <i class="bi bi-clock-history me-1"></i> Pendientes de Revisión (<?= $totalPendientes ?>)
        </a>
        <a href="revision.php?estado=auditadas" class="btn <?= $filtroEstado === 'auditadas' ? 'btn-primary' : 'btn-outline-primary' ?>">
            <i class="bi bi-check2-all me-1"></i> Auditadas (<?= $totalAuditadas ?>)
        </a>
        <a href="revision.php?estado=todas" class="btn <?= $filtroEstado === 'todas' ? 'btn-primary' : 'btn-outline-primary' ?>">
            Ver Todas
        </a>
    </div>
</div>

<!-- Tarjetas KPI de Calidad -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-amber">
            <div class="kpi-title">Pendientes en Mesa</div>
            <div class="kpi-value text-warning"><?= $totalPendientes ?></div>
            <div class="kpi-desc">Esperando dictamen oficial</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-blue">
            <div class="kpi-title">Total Auditadas</div>
            <div class="kpi-value text-primary"><?= $totalAuditadas ?></div>
            <div class="kpi-desc">Revisadas por equipo de calidad</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-green">
            <div class="kpi-title">Dictamen: Apto</div>
            <div class="kpi-value text-success"><?= $totalAptos ?></div>
            <div class="kpi-desc">Beneficiarios habilitados para crédito</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-red">
            <div class="kpi-title">Dictamen: No Apto</div>
            <div class="kpi-value text-danger"><?= $totalNoAptos ?></div>
            <div class="kpi-desc">Rechazados por inconsistencias</div>
        </div>
    </div>
</div>

<div class="callout-institutional">
    <i class="bi bi-shield-check text-primary me-2"></i>
    <strong>Criterio de Verificación de Calidad:</strong> La empresa garantiza que ninguna encuesta física o digital sea publicada sin la verificación de los 4 criterios de consistencia: barrio/comuna, edad de menores, formulario completo e identificación del encuestador.
</div>

<!-- Tabla de Encuestas para Revisión -->
<div class="app-card">
    <div class="app-card-header">
        <h5 class="app-card-title"><i class="bi bi-patch-check me-2"></i>Encuestas en Mesa de Revisión</h5>
        <span class="badge bg-light text-dark border small">Control de Tiempos SLA 15 Días</span>
    </div>
    <div class="app-card-body p-0">
        <div class="table-responsive">
            <table class="table table-app table-hover mb-0">
                <thead>
                    <tr>
                        <th>Radicado</th>
                        <th>Beneficiario & Dirección</th>
                        <th>Sector / Comuna</th>
                        <th>Encuestador Responsable</th>
                        <th>Fecha Levantamiento</th>
                        <th>Control SLA (15 Días)</th>
                        <th>Dictamen Oficial</th>
                        <th class="text-end">Acción de Calidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($encuestas)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No hay encuestas en este listado actualmente.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($encuestas as $enc): ?>
                            <?php $sla = calcularEstadoSLA($enc['fecha_radicacion'], $enc['estado_solicitud']); ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= e($enc['numero_radicado']) ?></strong>
                                    <small class="d-block text-muted">Radicada: <?= formatearFecha($enc['fecha_radicacion']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($enc['nombres']) ?> <?= e($enc['apellidos']) ?></div>
                                    <small class="text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($enc['direccion']) ?></small>
                                </td>
                                <td>
                                    <div><strong><?= e($enc['barrio']) ?></strong></div>
                                    <small class="badge bg-light text-dark border">
                                        <?= $enc['zona_tipo'] === 'urbana' ? 'Comuna ' . e((string)$enc['comuna']) : 'Corr. ' . e($enc['corregimiento']) ?>
                                    </small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-person-badge text-primary me-1"></i><?= e($enc['encuestador_nombre']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= formatearFecha($enc['fecha_levantamiento'], true) ?></small>
                                </td>
                                <td>
                                    <span class="badge-sla <?= $sla['badge_class'] ?>">
                                        <?= e($sla['etiqueta']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($enc['resultado_oficial'] === 'apto'): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check2-circle me-1"></i> APTO (Habilitado)
                                        </span>
                                    <?php elseif ($enc['resultado_oficial'] === 'no_apto'): ?>
                                        <span class="badge bg-danger">
                                            <i class="bi bi-x-circle me-1"></i> NO APTO
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark border border-warning">
                                            <i class="bi bi-hourglass-split me-1"></i> Pendiente Dictamen
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('modules/calidad/evaluar.php?encuesta_id=' . $enc['id']) ?>" class="btn btn-sm <?= empty($enc['resultado_oficial']) ? 'btn-primary' : 'btn-outline-secondary' ?>">
                                        <i class="bi <?= empty($enc['resultado_oficial']) ? 'bi-shield-check' : 'bi-eye' ?> me-1"></i>
                                        <?= empty($enc['resultado_oficial']) ? 'Auditar y Dictaminar' : 'Ver Auditoría' ?>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
