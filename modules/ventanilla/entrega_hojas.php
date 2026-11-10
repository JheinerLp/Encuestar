<?php
/**
 * Módulo de Ventanilla - Entrega Matutina de Hojas de Ruta a Encuestadores
 * Requerimiento 7 (0.44 pts)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['ventanilla', 'administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Procesar entrega de hoja de ruta al encuestador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'entregar_hoja') {
    $hojaId = (int)($_POST['hoja_id'] ?? 0);
    $obsEntrega = trim($_POST['observaciones_entrega'] ?? '');

    if ($hojaId > 0) {
        // Actualizar hoja de ruta
        $stmtUpd = $pdo->prepare('
            UPDATE hojas_ruta 
            SET estado = "entregada_a_encuestador",
                fecha_entrega_ventanilla = NOW(),
                ventanilla_entrega_id = :ventanilla_id,
                observaciones_entrega = :obs
            WHERE id = :id AND estado = "programada"
        ');
        $stmtUpd->execute([
            ':ventanilla_id' => $currentUser['id'],
            ':obs'           => $obsEntrega ?: 'Entrega física y digital formalizada en punto de atención a primera hora.',
            ':id'            => $hojaId
        ]);

        // Actualizar estado de las solicitudes asociadas a 'en_campo'
        $stmtUpdateSols = $pdo->prepare('
            UPDATE solicitudes 
            SET estado = "en_campo" 
            WHERE id IN (SELECT solicitud_id FROM hoja_ruta_items WHERE hoja_ruta_id = :hid)
              AND estado IN ("radicada", "enrutada")
        ');
        $stmtUpdateSols->execute([':hid' => $hojaId]);

        registrarBitacora(
            $currentUser['id'],
            $currentUser['rol'],
            'VENTANILLA',
            'ENTREGA_HOJA_RUTA',
            "Ventanilla entregó hoja de ruta ID #$hojaId al encuestador para salida a campo."
        );

        setFlash('success', '¡Hoja de ruta entregada formalmente al encuestador! Las encuestas asociadas han pasado al estado "En Campo".');
        redirect('modules/ventanilla/entrega_hojas.php');
    }
}

// Consultar hojas de ruta asignadas al punto de atención de la ventanilla (o todas si es admin)
$puntoId = $currentUser['rol'] === 'ventanilla' ? $currentUser['punto_atencion_id'] : null;

$sql = '
    SELECT hr.*, 
           p.nombre AS punto_nombre, p.comuna AS punto_comuna,
           u_enc.nombre_completo AS encuestador_nombre, u_enc.telefono AS encuestador_tel,
           u_enr.nombre_completo AS enrutador_nombre,
           u_ven.nombre_completo AS ventanilla_entrego_nombre
    FROM hojas_ruta hr
    INNER JOIN puntos_atencion p ON hr.punto_atencion_id = p.id
    INNER JOIN usuarios u_enc ON hr.encuestador_id = u_enc.id
    INNER JOIN usuarios u_enr ON hr.enrutador_id = u_enr.id
    LEFT JOIN usuarios u_ven ON hr.ventanilla_entrega_id = u_ven.id
    WHERE 1=1
';
$params = [];
if ($puntoId) {
    $sql .= ' AND hr.punto_atencion_id = :pid';
    $params[':pid'] = $puntoId;
}
$sql .= ' ORDER BY hr.fecha_programada DESC, hr.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$hojas = $stmt->fetchAll();

$pageTitle = 'Entrega Matutina de Hojas de Ruta';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Entrega Matutina de Hojas de Ruta</h4>
        <p class="text-muted small mb-0">Requerimiento 7 &bull; Entrega en Punto de Atención al Encuestador de Campo</p>
    </div>
    <span class="badge bg-light text-dark border p-2">
        <i class="bi bi-geo-alt me-1 text-primary"></i> Sede Actual: <?= e($currentUser['punto_nombre'] ?? 'Oficina Principal Sede 17') ?>
    </span>
</div>

<div class="callout-institutional">
    <div class="row align-items-center">
        <div class="col-md-9">
            <h6 class="fw-bold mb-1"><i class="bi bi-clock-history text-primary me-1"></i> Protocolo Operativo Matutino:</h6>
            <p class="mb-0 small">
                Cada encuestador se presenta a primera hora (7:00 a.m.) en su respectivo punto de atención para recibir de manos del usuario de ventanilla su hoja de ruta generada previamente por el enrutador. La camioneta institucional (van) los transporta a territorio y los recoge a las <strong>12:30 p.m.</strong> en el punto de encuentro fijado.
            </p>
        </div>
        <div class="col-md-3 text-md-end mt-2 mt-md-0">
            <span class="badge bg-success-subtle text-success border border-success p-2 small">
                <i class="bi bi-truck me-1"></i> Recogida: 12:30 PM
            </span>
        </div>
    </div>
</div>

<div class="app-card">
    <div class="app-card-header">
        <h5 class="app-card-title"><i class="bi bi-journals me-2"></i>Hojas de Ruta Asignadas a esta Sede</h5>
        <span class="badge bg-light text-dark border small">Carga generada por el Enrutador</span>
    </div>
    <div class="app-card-body p-0">
        <div class="table-responsive">
            <table class="table table-app table-hover mb-0">
                <thead>
                    <tr>
                        <th>Código Ruta</th>
                        <th>Fecha Visita</th>
                        <th>Encuestador Asignado</th>
                        <th>Transporte / Van</th>
                        <th>Punto de Encuentro</th>
                        <th>Carga (Encuestas)</th>
                        <th>Estado de Entrega</th>
                        <th class="text-end">Acción de Entrega</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hojas)): ?>
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                No hay hojas de ruta programadas pendientes de entrega para esta sede en este momento.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hojas as $h): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= e($h['codigo_hoja']) ?></strong>
                                    <small class="d-block text-muted">Enrutado por: <?= e($h['enrutador_nombre']) ?></small>
                                </td>
                                <td>
                                    <strong><?= formatearFecha($h['fecha_programada']) ?></strong>
                                    <small class="d-block text-muted">7:00 AM - 12:30 PM</small>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($h['encuestador_nombre']) ?></div>
                                    <small class="text-muted"><i class="bi bi-phone me-1"></i><?= e($h['encuestador_tel']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        <i class="bi bi-truck me-1 text-primary"></i><?= e($h['camioneta_asignada']) ?>
                                    </span>
                                </td>
                                <td>
                                    <small><?= e($h['punto_encuentro']) ?></small>
                                </td>
                                <td>
                                    <span class="badge bg-primary fs-6 px-2 py-1">
                                        <?= (int)$h['total_encuestas'] ?> visitas
                                    </span>
                                </td>
                                <td>
                                    <?php if ($h['estado'] === 'programada'): ?>
                                        <span class="badge bg-warning text-dark border border-warning">
                                            <i class="bi bi-hourglass-split me-1"></i>Pendiente por Entregar
                                        </span>
                                    <?php elseif ($h['estado'] === 'entregada_a_encuestador'): ?>
                                        <span class="badge bg-success border border-success">
                                            <i class="bi bi-check2-all me-1"></i>Entregada al Encuestador
                                        </span>
                                        <small class="d-block text-muted mt-1">
                                            <?= formatearFecha($h['fecha_entrega_ventanilla'], true) ?>
                                        </small>
                                    <?php else: ?>
                                        <span class="badge bg-info text-dark">
                                            <?= e(strtoupper(str_replace('_', ' ', $h['estado']))) ?>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($h['estado'] === 'programada'): ?>
                                        <form method="POST" action="entrega_hojas.php" class="d-inline" onsubmit="return confirm('¿Confirmar entrega formal de la hoja de ruta física y digital al encuestador <?= addslashes($h['encuestador_nombre']) ?>?');">
                                            <input type="hidden" name="accion" value="entregar_hoja">
                                            <input type="hidden" name="hoja_id" value="<?= $h['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-success fw-semibold">
                                                <i class="bi bi-hand-thumbs-up me-1"></i> Registrar Entrega
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <button class="btn btn-sm btn-outline-secondary" disabled>
                                            <i class="bi bi-check-circle me-1"></i> Entregada
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="<?= url('modules/admin/reportes_pdf.php?tipo=hoja_ruta&id=' . $h['id']) ?>" target="_blank" class="btn btn-sm btn-outline-danger ms-1" title="Descargar Hoja de Ruta en PDF">
                                        <i class="bi bi-file-earmark-pdf"></i> PDF
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
