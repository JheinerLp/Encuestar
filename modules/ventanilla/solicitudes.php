<?php
/**
 * Módulo de Ventanilla - Solicitudes Radicadas
 * Listado, filtros y semáforo SLA de 15 días calendario
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['ventanilla', 'administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Filtros
$puntoFiltro = isset($_GET['punto']) && $_GET['punto'] !== '' ? (int)$_GET['punto'] : ($currentUser['rol'] === 'ventanilla' ? $currentUser['punto_atencion_id'] : null);
$estadoFiltro = trim($_GET['estado'] ?? '');
$busqueda = trim($_GET['q'] ?? '');

$sql = '
    SELECT s.*, p.nombre AS punto_nombre, p.comuna AS punto_comuna,
           u.nombre_completo AS ventanilla_nombre
    FROM solicitudes s
    INNER JOIN puntos_atencion p ON s.punto_atencion_id = p.id
    INNER JOIN usuarios u ON s.usuario_ventanilla_id = u.id
    WHERE 1=1
';
$params = [];

if ($puntoFiltro) {
    $sql .= ' AND s.punto_atencion_id = :punto_id';
    $params[':punto_id'] = $puntoFiltro;
}

if ($estadoFiltro !== '') {
    $sql .= ' AND s.estado = :estado';
    $params[':estado'] = $estadoFiltro;
}

if ($busqueda !== '') {
    $sql .= ' AND (s.numero_radicado LIKE :busq OR s.nombres LIKE :busq OR s.apellidos LIKE :busq OR s.documento_identidad LIKE :busq OR s.barrio LIKE :busq)';
    $params[':busq'] = '%' . $busqueda . '%';
}

$sql .= ' ORDER BY s.fecha_radicacion DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$solicitudes = $stmt->fetchAll();

// Puntos de atención para el filtro
$puntosList = $pdo->query('SELECT id, nombre, comuna, es_principal FROM puntos_atencion WHERE activo = 1 ORDER BY es_principal DESC, comuna ASC')->fetchAll();

$pageTitle = 'Solicitudes Radicadas en Ventanilla';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Solicitudes de Encuesta Radicadas</h4>
        <p class="text-muted small mb-0">Control de Radicaciones &bull; Monitoreo de SLA de 15 Días Calendario</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= url('modules/ventanilla/radicar.php') ?>" class="btn btn-primary btn-sm fw-semibold" style="background-color: #0e2a47; border-color: #0e2a47;">
            <i class="bi bi-plus-circle me-1"></i> Nueva Radicación Presencial
        </a>
    </div>
</div>

<!-- Filtros de Búsqueda -->
<div class="app-card mb-3">
    <div class="app-card-body p-3">
        <form method="GET" action="solicitudes.php" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label small mb-1">Buscar por Radicado, Solicitante o Barrio</label>
                <div class="input-group input-group-sm">
                    <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                    <input type="text" class="form-control" name="q" value="<?= e($busqueda) ?>" placeholder="Buscar...">
                </div>
            </div>

            <?php if ($currentUser['rol'] === 'administrador'): ?>
                <div class="col-md-3">
                    <label class="form-label small mb-1">Punto de Atención Receptor</label>
                    <select class="form-select form-select-sm" name="punto">
                        <option value="">-- Todos los Puntos --</option>
                        <?php foreach ($puntosList as $pl): ?>
                            <option value="<?= $pl['id'] ?>" <?= $puntoFiltro == $pl['id'] ? 'selected' : '' ?>>
                                <?= e($pl['nombre']) ?> (C<?= $pl['comuna'] ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>

            <div class="col-md-3">
                <label class="form-label small mb-1">Estado Operativo</label>
                <select class="form-select form-select-sm" name="estado">
                    <option value="">-- Todos los Estados --</option>
                    <option value="radicada" <?= $estadoFiltro === 'radicada' ? 'selected' : '' ?>>Radicada</option>
                    <option value="enrutada" <?= $estadoFiltro === 'enrutada' ? 'selected' : '' ?>>Enrutada (En Hoja de Ruta)</option>
                    <option value="en_campo" <?= $estadoFiltro === 'en_campo' ? 'selected' : '' ?>>En Campo</option>
                    <option value="encuestada" <?= $estadoFiltro === 'encuestada' ? 'selected' : '' ?>>Encuestada</option>
                    <option value="en_revision" <?= $estadoFiltro === 'en_revision' ? 'selected' : '' ?>>En Revisión Calidad</option>
                    <option value="aprobada_apto" <?= $estadoFiltro === 'aprobada_apto' ? 'selected' : '' ?>>Aprobada (Apto)</option>
                    <option value="rechazada_no_apto" <?= $estadoFiltro === 'rechazada_no_apto' ? 'selected' : '' ?>>Rechazada (No Apto)</option>
                    <option value="vencida" <?= $estadoFiltro === 'vencida' ? 'selected' : '' ?>>Vencida (>15 Días)</option>
                </select>
            </div>

            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                    <i class="bi bi-funnel me-1"></i> Filtrar
                </button>
                <a href="solicitudes.php" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                    <i class="bi bi-x-circle"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de Solicitudes -->
<div class="app-card">
    <div class="app-card-header">
        <h5 class="app-card-title">
            <i class="bi bi-table me-2"></i>Registros de Solicitudes (Total: <?= count($solicitudes) ?>)
        </h5>
        <span class="badge bg-light text-dark border small">Políticas: Respuesta máxima 15 días calendario</span>
    </div>
    <div class="app-card-body p-0">
        <div class="table-responsive">
            <table class="table table-app table-hover mb-0">
                <thead>
                    <tr>
                        <th>N° Radicado</th>
                        <th>Solicitante</th>
                        <th>Ubicación (Cali)</th>
                        <th>Contacto Celular</th>
                        <th>Punto de Atención</th>
                        <th>Fecha Radicación</th>
                        <th>Estado SLA (15 Días)</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($solicitudes)): ?>
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox fs-3 d-block mb-1"></i>
                                No se encontraron solicitudes con los criterios de búsqueda seleccionados.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($solicitudes as $sol): ?>
                            <?php $sla = calcularEstadoSLA($sol['fecha_radicacion'], $sol['estado']); ?>
                            <tr>
                                <td>
                                    <strong class="text-primary font-monospace"><?= e($sol['numero_radicado']) ?></strong>
                                </td>
                                <td>
                                    <div class="fw-semibold text-dark"><?= e($sol['nombres']) ?> <?= e($sol['apellidos']) ?></div>
                                    <small class="text-muted"><?= e($sol['tipo_documento']) ?> <?= e($sol['documento_identidad']) ?></small>
                                </td>
                                <td>
                                    <div><strong><?= e($sol['direccion']) ?></strong></div>
                                    <small class="text-muted">
                                        <?= e($sol['barrio']) ?> &bull; 
                                        <?= $sol['zona_tipo'] === 'urbana' ? 'Comuna ' . e((string)$sol['comuna']) : 'Corr. ' . e($sol['corregimiento']) ?>
                                    </small>
                                </td>
                                <td>
                                    <a href="tel:<?= e($sol['celular']) ?>" class="text-decoration-none text-dark">
                                        <i class="bi bi-telephone text-success me-1"></i><?= e($sol['celular']) ?>
                                    </a>
                                </td>
                                <td>
                                    <small class="fw-semibold"><?= e($sol['punto_nombre']) ?></small>
                                </td>
                                <td>
                                    <small><?= formatearFecha($sol['fecha_radicacion'], true) ?></small>
                                </td>
                                <td>
                                    <span class="badge-sla <?= $sla['badge_class'] ?>">
                                        <?php if ($sla['estado_sla'] === 'vencido'): ?>
                                            <i class="bi bi-exclamation-octagon-fill"></i>
                                        <?php elseif ($sla['estado_sla'] === 'alerta'): ?>
                                            <i class="bi bi-exclamation-triangle-fill"></i>
                                        <?php elseif ($sla['estado_sla'] === 'finalizada'): ?>
                                            <i class="bi bi-check-circle-fill"></i>
                                        <?php else: ?>
                                            <i class="bi bi-clock-history"></i>
                                        <?php endif; ?>
                                        <?= e($sla['etiqueta']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="status-tag status-<?= e($sol['estado']) ?>">
                                        <?= str_replace('_', ' ', e($sol['estado'])) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <button type="button" class="btn btn-xs btn-outline-info btn-sm" 
                                            onclick="verGeorreferencia(<?= e(json_encode($sol)) ?>)" 
                                            title="Ver Georreferenciación en Mapa">
                                        <i class="bi bi-geo-alt"></i> Ver Mapa
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para ver georreferenciación -->
<div class="modal fade" id="modalMapaDetalle" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-2 bg-light">
                <h6 class="modal-title fw-bold text-navy" id="modalTitulo">Georreferenciación de Solicitud</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-3">
                <div id="mapaDetalle" style="height: 350px; width:100%; border-radius: 6px; border: 1px solid #cbd5e1;"></div>
                <div class="mt-2 bg-light p-2 rounded small d-flex justify-content-between align-items-center">
                    <div>
                        <strong id="modalDireccion"></strong> &bull; <span id="modalBarrio"></span>
                    </div>
                    <div>
                        Coordenadas: <code id="modalCoords"></code>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    let modalMap;
    let modalMarker;

    function verGeorreferencia(sol) {
        document.getElementById('modalTitulo').innerText = `Georreferencia: ${sol.numero_radicado} - ${sol.nombres} ${sol.apellidos}`;
        document.getElementById('modalDireccion').innerText = sol.direccion;
        document.getElementById('modalBarrio').innerText = `${sol.barrio} (Comuna ${sol.comuna || sol.corregimiento})`;
        document.getElementById('modalCoords').innerText = `${sol.latitud}, ${sol.longitud}`;

        const modalEl = document.getElementById('modalMapaDetalle');
        const bsModal = new bootstrap.Modal(modalEl);
        bsModal.show();

        modalEl.addEventListener('shown.bs.modal', function () {
            const lat = parseFloat(sol.latitud);
            const lng = parseFloat(sol.longitud);

            if (!modalMap) {
                modalMap = L.map('mapaDetalle').setView([lat, lng], 15);
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19
                }).addTo(modalMap);

                modalMarker = L.marker([lat, lng]).addTo(modalMap);
            } else {
                modalMap.invalidateSize();
                modalMap.setView([lat, lng], 15);
                modalMarker.setLatLng([lat, lng]);
            }

            modalMarker.bindPopup(`<strong>${sol.numero_radicado}</strong><br>${sol.direccion}<br>${sol.barrio}`).openPopup();
        }, { once: true });
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
