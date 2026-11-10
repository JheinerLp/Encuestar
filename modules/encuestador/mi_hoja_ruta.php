<?php
/**
 * Módulo de Encuestador - Mi Hoja de Ruta Asignada en Campo
 * Requerimiento 7: Recepción y gestión de visitas, registro de llamada previa indispensable
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['encuestador', 'administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Procesar registro de llamada previa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'registrar_llamada') {
    $itemId = (int)($_POST['item_id'] ?? 0);
    $obsLlamada = trim($_POST['observacion_llamada'] ?? '');

    if ($itemId > 0) {
        $stmtLlamada = $pdo->prepare('
            UPDATE hoja_ruta_items
            SET llamada_previa_realizada = 1,
                fecha_llamada_previa = NOW(),
                observacion_llamada = :obs
            WHERE id = :id
        ');
        $stmtLlamada->execute([
            ':obs' => $obsLlamada ?: 'Llamada previa realizada satisfactoriamente. Usuario confirmó cita.',
            ':id'  => $itemId
        ]);

        setFlash('success', 'Llamada previa de confirmación registrada exitosamente.');
        redirect('modules/encuestador/mi_hoja_ruta.php');
    }
}

// Consultar la hoja de ruta más reciente asignada a este encuestador
$encuestadorId = $currentUser['id'];

$stmtHoja = $pdo->prepare('
    SELECT hr.*, 
           p.nombre AS punto_nombre, p.comuna AS punto_comuna, p.direccion AS punto_dir,
           u_enr.nombre_completo AS enrutador_nombre,
           u_ven.nombre_completo AS ventanilla_nombre
    FROM hojas_ruta hr
    INNER JOIN puntos_atencion p ON hr.punto_atencion_id = p.id
    INNER JOIN usuarios u_enr ON hr.enrutador_id = u_enr.id
    LEFT JOIN usuarios u_ven ON hr.ventanilla_entrega_id = u_ven.id
    WHERE hr.encuestador_id = :eid
    ORDER BY hr.fecha_programada DESC, hr.id DESC
    LIMIT 1
');
$stmtHoja->execute([':eid' => $encuestadorId]);
$hojaActual = $stmtHoja->fetch();

$items = [];
if ($hojaActual) {
    $stmtItems = $pdo->prepare('
        SELECT hri.*, 
               s.numero_radicado, s.nombres, s.apellidos, s.direccion, s.barrio,
               s.comuna, s.corregimiento, s.zona_tipo, s.celular, s.latitud, s.longitud,
               s.estado AS estado_solicitud, s.fecha_radicacion,
               e.id AS encuesta_id
        FROM hoja_ruta_items hri
        INNER JOIN solicitudes s ON hri.solicitud_id = s.id
        LEFT JOIN encuestas e ON s.id = e.solicitud_id
        WHERE hri.hoja_ruta_id = :hid
        ORDER BY hri.orden_visita ASC
    ');
    $stmtItems->execute([':hid' => $hojaActual['id']]);
    $items = $stmtItems->fetchAll();
}

$pageTitle = 'Mi Hoja de Ruta';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Mi Hoja de Ruta de Campo</h4>
        <p class="text-muted small mb-0">Encuestador: <strong><?= e($currentUser['nombre_completo']) ?></strong> &bull; Operación 7:00 a.m. a 12:30 p.m.</p>
    </div>
    <?php if ($hojaActual): ?>
        <a href="<?= url('modules/admin/reportes_pdf.php?tipo=hoja_ruta&id=' . $hojaActual['id']) ?>" target="_blank" class="btn btn-outline-danger btn-sm">
            <i class="bi bi-file-earmark-pdf me-1"></i> Imprimir Hoja de Ruta (PDF)
        </a>
    <?php endif; ?>
</div>

<?php if (!$hojaActual): ?>
    <div class="app-card">
        <div class="app-card-body text-center py-5">
            <i class="bi bi-inbox fs-1 text-muted d-block mb-3"></i>
            <h5 class="fw-bold text-navy">No tiene hojas de ruta asignadas</h5>
            <p class="text-muted small">El usuario enrutador aún no le ha programado una carga de trabajo o la ventanilla no la ha formalizado.</p>
        </div>
    </div>
<?php else: ?>

    <!-- Encabezado de la Hoja de Ruta -->
    <div class="app-card mb-3">
        <div class="app-card-body p-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-3">
                    <span class="text-muted small text-uppercase fw-semibold d-block">Hoja de Ruta</span>
                    <strong class="text-primary font-monospace fs-5"><?= e($hojaActual['codigo_hoja']) ?></strong>
                    <div class="small text-muted">Fecha: <strong><?= formatearFecha($hojaActual['fecha_programada']) ?></strong></div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small text-uppercase fw-semibold d-block">Punto de Entrega & Salida</span>
                    <strong class="text-dark"><?= e($hojaActual['punto_nombre']) ?></strong>
                    <div class="small text-muted"><i class="bi bi-geo-alt me-1"></i><?= e($hojaActual['punto_dir']) ?></div>
                </div>
                <div class="col-md-3">
                    <span class="text-muted small text-uppercase fw-semibold d-block">Transporte & Seguridad</span>
                    <div class="badge bg-light text-dark border p-1 mb-1">
                        <i class="bi bi-truck text-primary me-1"></i><?= e($hojaActual['camioneta_asignada']) ?>
                    </div>
                    <div class="small text-danger fw-semibold">
                        <i class="bi bi-clock-history me-1"></i>Recogida obligatoria: 12:30 PM
                    </div>
                </div>
                <div class="col-md-3 text-md-end">
                    <span class="text-muted small text-uppercase fw-semibold d-block">Carga Asignada</span>
                    <span class="badge bg-primary fs-5 px-3 py-2">
                        <?= count($items) ?> Encuestas
                    </span>
                    <div class="small text-muted mt-1">
                        Punto de encuentro: <strong><?= e($hojaActual['punto_encuentro']) ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Advertencia de llamada previa -->
    <div class="alert alert-warning border-warning d-flex align-items-center gap-2 small mb-3">
        <i class="bi bi-telephone-outbound-fill fs-5 text-warning"></i>
        <div>
            <strong>Llamada Previa Obligatoria:</strong> Debe realizar la llamada telefónica al beneficiario 1 día antes de la visita mediante su celular corporativo financiado para corroborar dirección y disponibilidad horaria.
        </div>
    </div>

    <!-- Lista de Visitas / Carga de Trabajo -->
    <div class="app-card">
        <div class="app-card-header">
            <h5 class="app-card-title"><i class="bi bi-geo me-2"></i>Secuencia de Visitas Programadas</h5>
            <span class="badge bg-light text-dark border small">Carga: <?= count($items) ?> encuestas</span>
        </div>
        <div class="app-card-body p-0">
            <div class="table-responsive">
                <table class="table table-app table-hover mb-0">
                    <thead>
                        <tr>
                            <th style="width: 50px;"># Orden</th>
                            <th>Radicado</th>
                            <th>Beneficiario & Dirección</th>
                            <th>Sector / Comuna</th>
                            <th>Llamada Previa (1 Día Antes)</th>
                            <th>Estado Encuesta</th>
                            <th class="text-end">Diligenciamiento</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $it): ?>
                            <tr>
                                <td class="text-center">
                                    <span class="badge bg-navy text-white rounded-circle p-2" style="background-color: #0e2a47;">
                                        <?= (int)$it['orden_visita'] ?>
                                    </span>
                                </td>
                                <td>
                                    <strong class="text-primary font-monospace"><?= e($it['numero_radicado']) ?></strong>
                                    <small class="d-block text-muted">Radicada: <?= formatearFecha($it['fecha_radicacion']) ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold text-dark"><?= e($it['nombres']) ?> <?= e($it['apellidos']) ?></div>
                                    <div class="small text-secondary"><i class="bi bi-geo-alt text-danger me-1"></i><?= e($it['direccion']) ?></div>
                                    <div class="small text-muted">
                                        <i class="bi bi-phone text-success me-1"></i>
                                        <a href="tel:<?= e($it['celular']) ?>" class="fw-bold text-dark text-decoration-none">
                                            <?= e($it['celular']) ?>
                                        </a>
                                    </div>
                                </td>
                                <td>
                                    <div><strong><?= e($it['barrio']) ?></strong></div>
                                    <small class="badge bg-light text-dark border">
                                        <?= $it['zona_tipo'] === 'urbana' ? 'Comuna ' . e((string)$it['comuna']) : 'Corr. ' . e($it['corregimiento']) ?>
                                    </small>
                                </td>
                                <td>
                                    <?php if ($it['llamada_previa_realizada']): ?>
                                        <span class="badge bg-success-subtle text-success border border-success mb-1">
                                            <i class="bi bi-check2-circle me-1"></i> Confirmada
                                        </span>
                                        <small class="d-block text-muted" style="max-width: 180px; font-size: 0.72rem;">
                                            <?= e($it['observacion_llamada']) ?>
                                        </small>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-xs btn-outline-warning text-dark btn-sm" onclick="abrirModalLlamada(<?= $it['id'] ?>, '<?= addslashes($it['nombres'] . ' ' . $it['apellidos']) ?>', '<?= addslashes($it['celular']) ?>')">
                                            <i class="bi bi-telephone-forward me-1"></i> Registrar Llamada
                                        </button>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($it['encuesta_id']): ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check2-all me-1"></i> Encuesta Diligenciada
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-secondary">
                                            <i class="bi bi-hourglass me-1"></i> Pendiente de Visita
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <?php if ($it['encuesta_id']): ?>
                                        <a href="<?= url('modules/encuestador/diligenciar.php?solicitud_id=' . $it['solicitud_id']) ?>" class="btn btn-sm btn-outline-success">
                                            <i class="bi bi-eye me-1"></i> Ver Datos
                                        </a>
                                    <?php else: ?>
                                        <a href="<?= url('modules/encuestador/diligenciar.php?solicitud_id=' . $it['solicitud_id'] . '&hoja_id=' . $hojaActual['id']) ?>" class="btn btn-sm btn-primary fw-semibold" style="background-color:#0e2a47; border-color:#0e2a47;">
                                            <i class="bi bi-pencil-square me-1"></i> Diligenciar Encuesta
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal para registrar llamada previa -->
    <div class="modal fade" id="modalLlamada" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="mi_hoja_ruta.php">
                    <input type="hidden" name="accion" value="registrar_llamada">
                    <input type="hidden" name="item_id" id="modal_item_id" value="">
                    
                    <div class="modal-header py-2 bg-light">
                        <h6 class="modal-title fw-bold text-navy"><i class="bi bi-telephone-outbound me-2 text-primary"></i>Confirmación de Llamada Previa</h6>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-3">
                        <div class="mb-3 bg-light p-2 rounded border small">
                            Beneficiario: <strong id="modal_nombre_usuario"></strong><br>
                            Teléfono Celular: <strong id="modal_celular_usuario" class="text-primary"></strong>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small">Resultado de la Llamada de Confirmación <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" name="observacion_llamada" rows="3" required placeholder="Ej: Se contactó al usuario a las 5:00 PM. Confirmó estar presente en la vivienda para recibir la encuesta en el horario de la mañana."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-success fw-semibold">
                            <i class="bi bi-check-lg me-1"></i> Guardar Confirmación
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function abrirModalLlamada(itemId, nombre, cel) {
            document.getElementById('modal_item_id').value = itemId;
            document.getElementById('modal_nombre_usuario').innerText = nombre;
            document.getElementById('modal_celular_usuario').innerText = cel;
            new bootstrap.Modal(document.getElementById('modalLlamada')).show();
        }
    </script>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
