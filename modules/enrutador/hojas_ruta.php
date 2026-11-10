<?php
/**
 * Módulo de Enrutador - Listado y Seguimiento de Hojas de Ruta
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['enrutador', 'administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

$stmt = $pdo->query('
    SELECT hr.*, 
           p.nombre AS punto_nombre, p.comuna AS punto_comuna,
           u_enc.nombre_completo AS encuestador_nombre, u_enc.telefono AS encuestador_tel,
           u_enr.nombre_completo AS enrutador_nombre,
           (SELECT COUNT(*) FROM hoja_ruta_items WHERE hoja_ruta_id = hr.id AND estado_visita = "efectiva") AS visitas_efectivas
    FROM hojas_ruta hr
    INNER JOIN puntos_atencion p ON hr.punto_atencion_id = p.id
    INNER JOIN usuarios u_enc ON hr.encuestador_id = u_enc.id
    INNER JOIN usuarios u_enr ON hr.enrutador_id = u_enr.id
    ORDER BY hr.fecha_programada DESC, hr.id DESC
');
$hojas = $stmt->fetchAll();

$pageTitle = 'Hojas de Ruta Programadas';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Hojas de Ruta Programadas</h4>
        <p class="text-muted small mb-0">Requerimiento 6 &bull; Control y Seguimiento Logístico de Cargas de Trabajo</p>
    </div>
    <a href="<?= url('modules/enrutador/crear_hoja_ruta.php') ?>" class="btn btn-warning btn-sm fw-semibold text-dark">
        <i class="bi bi-plus-lg me-1"></i> Armar Nueva Hoja de Ruta
    </a>
</div>

<div class="app-card">
    <div class="app-card-header">
        <h5 class="app-card-title"><i class="bi bi-truck me-2"></i>Historial de Rutas Generadas (<?= count($hojas) ?>)</h5>
        <span class="badge bg-light text-dark border small">Ventana de ejecución: 7:00 AM a 12:30 PM</span>
    </div>
    <div class="app-card-body p-0">
        <div class="table-responsive">
            <table class="table table-app table-hover mb-0">
                <thead>
                    <tr>
                        <th>Código Ruta</th>
                        <th>Fecha Visita</th>
                        <th>Encuestador Asignado</th>
                        <th>Sede de Entrega Matutina</th>
                        <th>Transporte / Van</th>
                        <th>Punto Encuentro</th>
                        <th>Carga</th>
                        <th>Estado</th>
                        <th class="text-end">Informe PDF</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($hojas)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                No se han generado hojas de ruta aún.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($hojas as $h): ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= e($h['codigo_hoja']) ?></strong>
                                    <small class="d-block text-muted">Enrutador: <?= e($h['enrutador_nombre']) ?></small>
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
                                    <small class="fw-semibold"><?= e($h['punto_nombre']) ?></small>
                                    <span class="badge bg-light text-dark border small">Comuna <?= e((string)$h['punto_comuna']) ?></span>
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
                                    <span class="badge bg-primary px-2 py-1">
                                        <?= (int)$h['total_encuestas'] ?> encuestas
                                    </span>
                                </td>
                                <td>
                                    <?php if ($h['estado'] === 'programada'): ?>
                                        <span class="badge bg-warning text-dark border border-warning">
                                            <i class="bi bi-clock me-1"></i>Programada (En Sede)
                                        </span>
                                    <?php elseif ($h['estado'] === 'entregada_a_encuestador'): ?>
                                        <span class="badge bg-info text-dark border border-info">
                                            <i class="bi bi-hand-thumbs-up me-1"></i>En Territorio
                                        </span>
                                    <?php else: ?>
                                        <span class="badge bg-success">
                                            <i class="bi bi-check2-all me-1"></i>Completada
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end">
                                    <a href="<?= url('modules/admin/reportes_pdf.php?tipo=hoja_ruta&id=' . $h['id']) ?>" target="_blank" class="btn btn-sm btn-outline-danger" title="Generar Informe Oficial en PDF">
                                        <i class="bi bi-file-earmark-pdf me-1"></i> Descargar PDF
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
