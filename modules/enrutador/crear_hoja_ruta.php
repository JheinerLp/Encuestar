<?php
/**
 * Módulo de Enrutador - Armado y Generación de Hoja de Ruta
 * Requerimiento 6 (0.44 pts): Carga de hasta 20 encuestas para cada encuestador con datos de control
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cali_geodata.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['enrutador', 'administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

$errores = [];

// Obtener lista de encuestadores activos
$stmtEncuestadores = $pdo->query('
    SELECT u.id, u.nombre_completo, u.cedula, u.telefono, u.antiguedad_anios,
           p.nombre AS punto_nombre, p.comuna AS punto_comuna
    FROM usuarios u
    LEFT JOIN puntos_atencion p ON u.punto_atencion_id = p.id
    WHERE u.rol = "encuestador" AND u.activo = 1
    ORDER BY u.nombre_completo ASC
');
$encuestadores = $stmtEncuestadores->fetchAll();

// Obtener puntos de atención donde se entregará la hoja
$puntosAtencion = $pdo->query('
    SELECT id, nombre, comuna, es_principal, direccion 
    FROM puntos_atencion 
    WHERE activo = 1 
    ORDER BY es_principal DESC, comuna ASC
')->fetchAll();

// Filtros para la lista de solicitudes pendientes
$comunaFiltro = isset($_GET['comuna']) && $_GET['comuna'] !== '' ? (int)$_GET['comuna'] : null;
$preselectId = isset($_GET['preselect']) ? (int)$_GET['preselect'] : null;

// Consultar solicitudes pendientes por enrutar (estado 'radicada')
$sqlPendientes = '
    SELECT s.*, p.nombre AS punto_origen_nombre, p.codigo AS punto_origen_codigo
    FROM solicitudes s
    INNER JOIN puntos_atencion p ON s.punto_atencion_id = p.id
    WHERE s.estado = "radicada"
';
$paramsPendientes = [];

if ($comunaFiltro) {
    $sqlPendientes .= ' AND s.comuna = :comuna';
    $paramsPendientes[':comuna'] = $comunaFiltro;
}

$sqlPendientes .= ' ORDER BY s.fecha_radicacion ASC'; // Prioridad a las más antiguas para SLA

$stmtPendientes = $pdo->prepare($sqlPendientes);
$stmtPendientes->execute($paramsPendientes);
$solicitudesPendientes = $stmtPendientes->fetchAll();

// Procesar Creación de Hoja de Ruta
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $encuestadorId   = (int)($_POST['encuestador_id'] ?? 0);
    $puntoEntregaId  = (int)($_POST['punto_atencion_id'] ?? 0);
    $fechaProgramada = trim($_POST['fecha_programada'] ?? '');
    $camioneta       = trim($_POST['camioneta_asignada'] ?? '');
    $puntoEncuentro  = trim($_POST['punto_encuentro'] ?? '');
    $horaSalida      = trim($_POST['hora_salida'] ?? '07:00:00');
    $horaRecogida    = trim($_POST['hora_recogida'] ?? '12:30:00');
    $observaciones   = trim($_POST['observaciones_enrutador'] ?? '');
    $solicitudesSel  = $_POST['solicitudes'] ?? [];

    if ($encuestadorId <= 0) {
        $errores[] = 'Debe seleccionar el encuestador responsable.';
    }
    if ($puntoEntregaId <= 0) {
        $errores[] = 'Debe seleccionar el punto de atención donde la ventanilla entregará la hoja de ruta.';
    }
    if (empty($fechaProgramada)) {
        $errores[] = 'Debe especificar la fecha de programación de la visita.';
    }
    if (empty($camioneta)) {
        $errores[] = 'Debe seleccionar la camioneta/van asignada para el traslado.';
    }
    if (empty($puntoEncuentro)) {
        $errores[] = 'Especifique el punto estratégico de encuentro / bajada.';
    }
    if (empty($solicitudesSel) || !is_array($solicitudesSel)) {
        $errores[] = 'Debe seleccionar al menos una (1) solicitud de encuesta para conformar la carga de trabajo.';
    } elseif (count($solicitudesSel) > 20) {
        $errores[] = 'La carga máxima permitida por política institucional es de hasta 20 encuestas por encuestador.';
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            // Generar código consecutivo: HR-AÑO-MESDIA-XX
            $codePrefix = 'HR-' . date('Ymd');
            $stmtCount = $pdo->prepare('SELECT COUNT(*) FROM hojas_ruta WHERE codigo_hoja LIKE :pref');
            $stmtCount->execute([':pref' => $codePrefix . '%']);
            $numRuta = (int)$stmtCount->fetchColumn() + 1;
            $codigoHoja = sprintf('%s-%02d', $codePrefix, $numRuta);

            $stmtHR = $pdo->prepare('
                INSERT INTO hojas_ruta (
                    codigo_hoja, enrutador_id, encuestador_id, punto_atencion_id,
                    fecha_programada, camioneta_asignada, punto_encuentro, hora_salida,
                    hora_recogida, total_encuestas, estado, observaciones_enrutador
                ) VALUES (
                    :codigo, :enrutador_id, :encuestador_id, :punto_id,
                    :fecha_prog, :camioneta, :punto_enc, :hora_sal,
                    :hora_rec, :total, "programada", :obs
                )
            ');

            $stmtHR->execute([
                ':codigo'         => $codigoHoja,
                ':enrutador_id'   => $currentUser['id'],
                ':encuestador_id' => $encuestadorId,
                ':punto_id'       => $puntoEntregaId,
                ':fecha_prog'     => $fechaProgramada,
                ':camioneta'      => $camioneta,
                ':punto_enc'      => $puntoEncuentro,
                ':hora_sal'       => $horaSalida,
                ':hora_rec'       => $horaRecogida,
                ':total'          => count($solicitudesSel),
                ':obs'            => $observaciones ?: 'Hoja de ruta optimizada territorialmente para jornada 7:00 a.m. a 12:30 p.m.'
            ]);

            $hojaId = (int)$pdo->lastInsertId();

            // Insertar items de la ruta con secuencia de visita 1..N
            $stmtItem = $pdo->prepare('
                INSERT INTO hoja_ruta_items (hoja_ruta_id, solicitud_id, orden_visita, estado_visita)
                VALUES (:hid, :sid, :orden, "pendiente")
            ');

            $stmtUpdSol = $pdo->prepare('
                UPDATE solicitudes SET estado = "enrutada" WHERE id = :sid
            ');

            $orden = 1;
            foreach ($solicitudesSel as $solId) {
                $solId = (int)$solId;
                $stmtItem->execute([
                    ':hid'   => $hojaId,
                    ':sid'   => $solId,
                    ':orden' => $orden++
                ]);
                $stmtUpdSol->execute([':sid' => $solId]);
            }

            $pdo->commit();

            registrarBitacora(
                $currentUser['id'],
                $currentUser['rol'],
                'ENRUTAMIENTO',
                'CREACION_HOJA_RUTA',
                "Generación exitosa de hoja de ruta $codigoHoja con carga de " . count($solicitudesSel) . " encuestas asignada a encuestador ID #$encuestadorId."
            );

            setFlash('success', "¡Hoja de Ruta <strong>$codigoHoja</strong> generada exitosamente con <strong>" . count($solicitudesSel) . "</strong> encuestas asignadas! Lista para ser entregada mañana en ventanilla.");
            redirect('modules/enrutador/hojas_ruta.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = 'Error al generar la hoja de ruta: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Armar Hoja de Ruta (Carga 20)';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Armar Hoja de Ruta de Campo</h4>
        <p class="text-muted small mb-0">Requerimiento 6 &bull; Asignación logística de carga (hasta 20 encuestas) y datos de control</p>
    </div>
    <a href="<?= url('modules/enrutador/hojas_ruta.php') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-card-checklist me-1"></i> Ver Hojas de Ruta Creadas
    </a>
</div>

<div class="callout-institutional">
    <div class="row align-items-center">
        <div class="col-md-9">
            <h6 class="fw-bold mb-1"><i class="bi bi-shield-check text-primary me-1"></i> Reglas de Seguridad y Control Operativo:</h6>
            <p class="mb-0 small">
                • Los encuestadores trabajan en territorio de <strong>7:00 a.m. a 12:00 p.m.</strong> por seguridad en los sectores.<br>
                • La buseta/van asignada pasa a las <strong>12:30 p.m.</strong> por el punto de encuentro fijado para transportarlos a la oficina principal.<br>
                • La carga máxima por hoja de ruta es de hasta <strong>20 encuestas</strong> agrupadas geográficamente para optimizar los recorridos a pie.
            </p>
        </div>
        <div class="col-md-3 text-md-end mt-2 mt-md-0">
            <span class="badge bg-primary fs-6 p-2">Capacidad: 20 Visitas/Ruta</span>
        </div>
    </div>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger shadow-sm">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon me-1"></i> Inconsistencias detectadas:</div>
        <ul class="mb-0 ps-3 small">
            <?php foreach ($errores as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="crear_hoja_ruta.php" id="formHojaRuta">
    <div class="row g-3">
        
        <!-- Panel Izquierdo: Parámetros de Control del Proceso -->
        <div class="col-lg-5">
            <div class="app-card">
                <div class="app-card-header">
                    <h5 class="app-card-title"><i class="bi bi-sliders me-2"></i>1. Parámetros de Control del Proceso</h5>
                    <span class="badge bg-warning text-dark border">Requisito 6</span>
                </div>
                <div class="app-card-body">
                    
                    <div class="mb-3">
                        <label class="form-label">Encuestador Asignado (Planta de 20) <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" name="encuestador_id" required>
                            <option value="">-- Seleccione Encuestador --</option>
                            <?php foreach ($encuestadores as $enc): ?>
                                <option value="<?= $enc['id'] ?>">
                                    <?= e($enc['nombre_completo']) ?> &bull; Antigüedad: <?= $enc['antiguedad_anios'] ?> años (Tel: <?= e($enc['telefono']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-text">Encuestadores con celular corporativo financiado para llamada previa.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Punto de Atención para Entrega Matutina <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" name="punto_atencion_id" required>
                            <option value="">-- Seleccione Sede de Entrega --</option>
                            <?php foreach ($puntosAtencion as $pa): ?>
                                <option value="<?= $pa['id'] ?>">
                                    <?= e($pa['nombre']) ?> (Comuna <?= $pa['comuna'] ?><?= $pa['es_principal'] ? ' - Sede Principal' : '' ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <span class="form-text">Requisito 7: La ventanilla de esta sede entregará la hoja al encuestador.</span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Fecha Programada <span class="text-danger">*</span></label>
                            <input type="date" class="form-control form-control-sm" name="fecha_programada" value="<?= date('Y-m-d', strtotime('+1 day')) ?>" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Vehículo de Traslado <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="camioneta_asignada" required>
                                <option value="Van 1 - Placa WCY-452 (Capacidad 10 encuestadores)">Van 1 - Placa WCY-452</option>
                                <option value="Van 2 - Placa UVL-890 (Capacidad 10 encuestadores)">Van 2 - Placa UVL-890</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Punto de Encuentro y Desembarque <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="punto_encuentro" value="Parque principal del sector / CAI de Policía" placeholder="Ej: CAI Siloé / Parque El Vergel" required>
                        <span class="form-text">Lugar donde la camioneta deja a los encuestadores e inicia su recorrido a pie.</span>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label">Hora Salida</label>
                            <input type="time" class="form-control form-control-sm" name="hora_salida" value="07:00:00" required>
                        </div>
                        <div class="col-6">
                            <label class="form-label">Hora Recogida (Estricta)</label>
                            <input type="time" class="form-control form-control-sm" name="hora_recogida" value="12:30:00" required>
                            <span class="form-text text-danger fw-semibold">12:30 PM (por seguridad)</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Instrucciones de Ruteo / Observaciones</label>
                        <textarea class="form-control form-control-sm" name="observaciones_enrutador" rows="2" placeholder="Recomendaciones de seguridad, secuencia de visita sugerida..."></textarea>
                    </div>

                    <div class="bg-light p-3 rounded border text-center">
                        <div class="small text-muted mb-1 text-uppercase fw-semibold">Encuestas Seleccionadas para la Hoja</div>
                        <div class="display-6 fw-bold text-primary" id="contadorSeleccionadas">0 / 20</div>
                        <div class="small text-muted" id="estadoCargaTexto">Seleccione solicitudes en la lista derecha</div>
                    </div>

                </div>
            </div>
        </div>

        <!-- Panel Derecho: Solicitudes Pendientes para Asignar -->
        <div class="col-lg-7">
            <div class="app-card">
                <div class="app-card-header">
                    <div>
                        <h5 class="app-card-title"><i class="bi bi-list-check me-2"></i>2. Selección de Solicitudes (Carga de Trabajo)</h5>
                        <span class="text-muted small">Disponibles sin enrutar: <?= count($solicitudesPendientes) ?> solicitudes</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-xs btn-outline-primary btn-sm" onclick="seleccionarPrimeras(12)">
                            Seleccionar 12
                        </button>
                        <button type="button" class="btn btn-xs btn-outline-success btn-sm" onclick="seleccionarPrimeras(20)">
                            Seleccionar 20
                        </button>
                    </div>
                </div>

                <div class="p-2 bg-light border-bottom d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-2">
                        <span class="small fw-semibold">Filtrar por Comuna:</span>
                        <select class="form-select form-select-sm" style="width: auto;" onchange="location.href='crear_hoja_ruta.php?comuna=' + this.value">
                            <option value="">-- Todas las comunas --</option>
                            <?php foreach (CALI_COMUNAS_BARRIOS as $nc => $ic): ?>
                                <option value="<?= $nc ?>" <?= $comunaFiltro == $nc ? 'selected' : '' ?>>
                                    Comuna <?= $nc ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <span class="small text-muted">Ordenadas por prioridad SLA (más antiguas primero)</span>
                </div>

                <div class="app-card-body p-0" style="max-height: 520px; overflow-y: auto;">
                    <?php if (empty($solicitudesPendientes)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="bi bi-check-circle-fill text-success fs-1 d-block mb-2"></i>
                            <strong>No hay solicitudes pendientes en este filtro.</strong>
                            <p class="small mb-0">Todas las solicitudes han sido enrutadas o no hay solicitudes en la comuna seleccionada.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($solicitudesPendientes as $sp): ?>
                                <?php 
                                $sla = calcularEstadoSLA($sp['fecha_radicacion'], $sp['estado']); 
                                $isPreselected = ($preselectId === (int)$sp['id']);
                                ?>
                                <label class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3" style="cursor: pointer;">
                                    <input class="form-check-input flex-shrink-0 mt-1 chk-solicitud" type="checkbox" name="solicitudes[]" value="<?= $sp['id'] ?>" <?= $isPreselected ? 'checked' : '' ?> onchange="actualizarContador()">
                                    <div class="d-flex w-100 justify-content-between">
                                        <div>
                                            <div class="d-flex align-items-center gap-2 mb-1">
                                                <strong class="text-primary font-monospace"><?= e($sp['numero_radicado']) ?></strong>
                                                <span class="badge bg-secondary-subtle text-dark border small">
                                                    <?= $sp['zona_tipo'] === 'urbana' ? 'Comuna ' . e((string)$sp['comuna']) : 'Rural' ?> &bull; <?= e($sp['barrio']) ?>
                                                </span>
                                                <span class="badge-sla <?= $sla['badge_class'] ?> small">
                                                    <?= e($sla['etiqueta']) ?>
                                                </span>
                                            </div>
                                            <div class="fw-semibold text-dark"><?= e($sp['nombres']) ?> <?= e($sp['apellidos']) ?></div>
                                            <div class="small text-muted">
                                                <i class="bi bi-geo-alt me-1"></i><?= e($sp['direccion']) ?> &bull;
                                                <i class="bi bi-phone me-1"></i><?= e($sp['celular']) ?>
                                            </div>
                                        </div>
                                        <div class="text-end text-muted small">
                                            <span>Radicada:</span><br>
                                            <strong><?= formatearFecha($sp['fecha_radicacion']) ?></strong>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="app-card-header bg-white border-top text-end">
                    <button type="submit" class="btn btn-warning px-4 py-2 fw-semibold text-dark" id="btnGenerar">
                        <i class="bi bi-signpost-split-fill me-1"></i> Generar y Guardar Hoja de Ruta
                    </button>
                </div>

            </div>
        </div>

    </div>
</form>

<script>
    function actualizarContador() {
        const checkboxes = document.querySelectorAll('.chk-solicitud:checked');
        const total = checkboxes.length;
        const contadorEl = document.getElementById('contadorSeleccionadas');
        const estadoTexto = document.getElementById('estadoCargaTexto');
        const btnGenerar = document.getElementById('btnGenerar');

        contadorEl.innerText = `${total} / 20`;

        if (total === 0) {
            estadoTexto.innerText = 'Seleccione solicitudes en la lista';
            estadoTexto.className = 'small text-muted';
            btnGenerar.disabled = true;
        } else if (total > 20) {
            estadoTexto.innerText = '¡Excede el límite máximo permitido de 20 encuestas!';
            estadoTexto.className = 'small text-danger fw-bold';
            btnGenerar.disabled = true;
        } else {
            estadoTexto.innerText = `Carga adecuada (${total} visitas asignadas)`;
            estadoTexto.className = 'small text-success fw-semibold';
            btnGenerar.disabled = false;
        }
    }

    function seleccionarPrimeras(cantidad) {
        const checkboxes = document.querySelectorAll('.chk-solicitud');
        checkboxes.forEach((cb, index) => {
            cb.checked = (index < cantidad);
        });
        actualizarContador();
    }

    document.addEventListener('DOMContentLoaded', function () {
        actualizarContador();
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
