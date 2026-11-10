<?php
/**
 * Módulo de Enrutador - Bitácora de Solicitudes Diarias de Todos los Puntos
 * Requerimiento 4 (0.44 pts)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['enrutador', 'administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Fecha de consulta (por defecto hoy, o la seleccionada)
$fechaFiltro = trim($_GET['fecha'] ?? date('Y-m-d'));
$puntoFiltro = isset($_GET['punto']) && $_GET['punto'] !== '' ? (int)$_GET['punto'] : null;
$estadoFiltro = trim($_GET['estado'] ?? '');

// Consulta de bitácora general de solicitudes
$sql = '
    SELECT s.*, 
           p.nombre AS punto_nombre, p.comuna AS punto_comuna, p.codigo AS punto_codigo,
           u_ven.nombre_completo AS ventanilla_nombre
    FROM solicitudes s
    INNER JOIN puntos_atencion p ON s.punto_atencion_id = p.id
    INNER JOIN usuarios u_ven ON s.usuario_ventanilla_id = u_ven.id
    WHERE 1=1
';
$params = [];

if (!empty($fechaFiltro)) {
    $sql .= ' AND DATE(s.fecha_radicacion) = :fecha';
    $params[':fecha'] = $fechaFiltro;
}

if ($puntoFiltro) {
    $sql .= ' AND s.punto_atencion_id = :punto_id';
    $params[':punto_id'] = $puntoFiltro;
}

if ($estadoFiltro !== '') {
    $sql .= ' AND s.estado = :estado';
    $params[':estado'] = $estadoFiltro;
}

$sql .= ' ORDER BY s.fecha_radicacion DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$solicitudes = $stmt->fetchAll();

// Estadísticas de la bitácora del día seleccionado
$stmtStats = $pdo->prepare('
    SELECT 
        COUNT(*) AS total_dia,
        SUM(CASE WHEN estado = "radicada" THEN 1 ELSE 0 END) AS pendientes_enrutar,
        SUM(CASE WHEN estado = "enrutada" THEN 1 ELSE 0 END) AS ya_enrutadas,
        SUM(CASE WHEN zona_tipo = "urbana" THEN 1 ELSE 0 END) AS urbanas,
        SUM(CASE WHEN zona_tipo = "rural" THEN 1 ELSE 0 END) AS rurales
    FROM solicitudes
    WHERE DATE(fecha_radicacion) = :fecha
');
$stmtStats->execute([':fecha' => $fechaFiltro]);
$stats = $stmtStats->fetch();

// Puntos de atención para el filtro
$puntosList = $pdo->query('SELECT id, nombre, comuna, es_principal FROM puntos_atencion WHERE activo = 1 ORDER BY es_principal DESC, comuna ASC')->fetchAll();

$pageTitle = 'Bitácora Diaria de Solicitudes';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Bitácora Diaria de Solicitudes</h4>
        <p class="text-muted small mb-0">Requerimiento 4 &bull; Consolidación en tiempo real de todos los puntos de atención de Cali</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('modules/enrutador/crear_hoja_ruta.php') ?>" class="btn btn-warning btn-sm fw-semibold text-dark">
            <i class="bi bi-signpost-split me-1"></i> Armar Nueva Hoja de Ruta (Carga 20)
        </a>
    </div>
</div>

<!-- Tarjetas de Resumen de la Bitácora Diaria -->
<div class="row g-3 mb-3">
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-blue">
            <div class="kpi-title">Radicadas en la Fecha</div>
            <div class="kpi-value text-primary"><?= (int)($stats['total_dia'] ?? 0) ?></div>
            <div class="kpi-desc">Total solicitudes recibidas el <?= formatearFecha($fechaFiltro) ?></div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-amber">
            <div class="kpi-title">Pendientes de Ruteo</div>
            <div class="kpi-value text-warning"><?= (int)($stats['pendientes_enrutar'] ?? 0) ?></div>
            <div class="kpi-desc">Esperando asignación a encuestador</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-green">
            <div class="kpi-title">Enrutadas en Hojas</div>
            <div class="kpi-value text-success"><?= (int)($stats['ya_enrutadas'] ?? 0) ?></div>
            <div class="kpi-desc">Programadas para visita a territorio</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="kpi-card kpi-purple">
            <div class="kpi-title">Distribución Zonal</div>
            <div class="kpi-value fs-4">
                <span class="text-info"><?= (int)($stats['urbanas'] ?? 0) ?> Urb</span> / 
                <span class="text-success"><?= (int)($stats['rurales'] ?? 0) ?> Rur</span>
            </div>
            <div class="kpi-desc">Capacidad: 10 urb/día &bull; 5 rur/día</div>
        </div>
    </div>
</div>

<!-- Filtros de la Bitácora -->
<div class="app-card mb-3">
    <div class="app-card-body p-3">
        <form method="GET" action="bitacora.php" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label class="form-label small mb-1"><i class="bi bi-calendar3 me-1"></i>Fecha de Radicación</label>
                <input type="date" class="form-control form-control-sm" name="fecha" value="<?= e($fechaFiltro) ?>" required>
            </div>

            <div class="col-md-4">
                <label class="form-label small mb-1"><i class="bi bi-building me-1"></i>Punto de Atención Emisor</label>
                <select class="form-select form-select-sm" name="punto">
                    <option value="">-- Todos los 8 Puntos de Atención --</option>
                    <?php foreach ($puntosList as $pl): ?>
                        <option value="<?= $pl['id'] ?>" <?= $puntoFiltro == $pl['id'] ? 'selected' : '' ?>>
                            <?= e($pl['nombre']) ?> (Comuna <?= $pl['comuna'] ?><?= $pl['es_principal'] ? ' - Sede Principal' : '' ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-md-3">
                <label class="form-label small mb-1"><i class="bi bi-flag me-1"></i>Estado de la Solicitud</label>
                <select class="form-select form-select-sm" name="estado">
                    <option value="">-- Todos los Estados --</option>
                    <option value="radicada" <?= $estadoFiltro === 'radicada' ? 'selected' : '' ?>>Radicada (Sin Hoja de Ruta)</option>
                    <option value="enrutada" <?= $estadoFiltro === 'enrutada' ? 'selected' : '' ?>>Enrutada (Asignada)</option>
                    <option value="en_campo" <?= $estadoFiltro === 'en_campo' ? 'selected' : '' ?>>En Campo</option>
                    <option value="encuestada" <?= $estadoFiltro === 'encuestada' ? 'selected' : '' ?>>Encuestada</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary w-100" style="background-color:#0e2a47; border-color:#0e2a47;">
                    <i class="bi bi-search me-1"></i> Consultar
                </button>
                <a href="bitacora.php?fecha=<?= date('Y-m-d') ?>" class="btn btn-sm btn-outline-secondary" title="Ver bitácora de hoy">
                    Hoy
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla Bitácora Detallada -->
<div class="app-card">
    <div class="app-card-header">
        <h5 class="app-card-title">
            <i class="bi bi-journal-check me-2"></i>Registros de la Bitácora Diaria (<?= count($solicitudes) ?> registros)
        </h5>
        <span class="badge bg-light text-dark border small">Monitoreo de SLA 15 Días</span>
    </div>
    <div class="app-card-body p-0">
        <div class="table-responsive">
            <table class="table table-app table-hover mb-0">
                <thead>
                    <tr>
                        <th>Radicado & Hora</th>
                        <th>Punto de Origen</th>
                        <th>Solicitante & Contacto</th>
                        <th>Sector / Comuna / Barrio</th>
                        <th>Dirección Exacta</th>
                        <th>Georreferencia</th>
                        <th>Control SLA (15 Días)</th>
                        <th>Estado</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitudes)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-calendar-x fs-2 d-block mb-2 text-secondary"></i>
                                No se registraron solicitudes de encuesta en la fecha seleccionada (<strong><?= formatearFecha($fechaFiltro) ?></strong>).
                                <div class="mt-2">
                                    <a href="bitacora.php?fecha=" class="btn btn-sm btn-outline-primary">Ver histórico completo</a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudes as $s): ?>
                            <?php $sla = calcularEstadoSLA($s['fecha_radicacion'], $s['estado']); ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= e($s['numero_radicado']) ?></strong>
                                    <small class="d-block text-muted">Hora: <?= date('h:i A', strtotime($s['fecha_radicacion'])) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <?= e($s['punto_codigo']) ?> &bull; Comuna <?= e((string)$s['punto_comuna']) ?>
                                    </span>
                                    <small class="d-block text-muted"><?= e($s['punto_nombre']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($s['nombres']) ?> <?= e($s['apellidos']) ?></div>
                                    <small class="text-muted"><i class="bi bi-phone me-1"></i><?= e($s['celular']) ?></small>
                                </td>
                                <td>
                                    <div><strong><?= e($s['barrio']) ?></strong></div>
                                    <small class="badge bg-secondary-subtle text-dark border">
                                        <?= $s['zona_tipo'] === 'urbana' ? 'Comuna ' . e((string)$s['comuna']) : 'Corr. ' . e($s['corregimiento']) ?>
                                    </small>
                                </td>
                                <td>
                                    <small><?= e($s['direccion']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-info-subtle text-info border border-info font-monospace small">
                                        <?= number_format((float)$s['latitud'], 4) ?>, <?= number_format((float)$s['longitud'], 4) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge-sla <?= $sla['badge_class'] ?>">
                                        <?= e($sla['etiqueta']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-tag status-<?= e($s['estado']) ?>">
                                        <?= str_replace('_', ' ', e($s['estado'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <?php if ($s['estado'] === 'radicada'): ?>
                                        <a href="<?= url('modules/enrutador/crear_hoja_ruta.php?preselect=' . $s['id'] . '&comuna=' . ($s['comuna'] ?? '')) ?>" class="btn btn-xs btn-warning btn-sm text-dark fw-semibold" title="Asignar a Hoja de Ruta">
                                            <i class="bi bi-plus-lg"></i> Enrutar
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="bi bi-check2"></i> Enrutada</span>
                                    <?php endif; ?>
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
