<?php
/**
 * Módulo de Control de Calidad - Auditoría Individual y Emisión de Dictamen
 * Dictamen Oficial: Apto / No Apto para Beneficio de Préstamos de Gobierno Extranjero
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['calidad', 'administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

$encuestaId = (int)($_GET['encuesta_id'] ?? 0);

if ($encuestaId <= 0) {
    setFlash('error', 'Identificador de encuesta no válido.');
    redirect('modules/calidad/revision.php');
}

// Consultar datos completos de la encuesta, solicitud, encuestador y revisión
$stmt = $pdo->prepare('
    SELECT e.*, 
           s.numero_radicado, s.nombres, s.apellidos, s.tipo_documento, s.documento_identidad,
           s.direccion, s.barrio, s.comuna, s.corregimiento, s.zona_tipo, s.celular,
           s.fecha_radicacion, s.fecha_limite_15dias, s.estado AS estado_solicitud,
           u_enc.nombre_completo AS encuestador_nombre, u_enc.cedula AS encuestador_cedula,
           u_enc.telefono AS encuestador_tel,
           rc.id AS revision_id, rc.consistencia_barrio_comuna, rc.consistencia_edad_menor,
           rc.formulario_completo, rc.identificacion_encuestador_valida,
           rc.resultado_oficial, rc.dictamen_justificacion, rc.observaciones_auditoria,
           rc.fecha_revision,
           u_rev.nombre_completo AS revisor_nombre
    FROM encuestas e
    INNER JOIN solicitudes s ON e.solicitud_id = s.id
    INNER JOIN usuarios u_enc ON e.encuestador_id = u_enc.id
    LEFT JOIN revisiones_calidad rc ON e.id = rc.encuesta_id
    LEFT JOIN usuarios u_rev ON rc.usuario_calidad_id = u_rev.id
    WHERE e.id = :id
');
$stmt->execute([':id' => $encuestaId]);
$data = $stmt->fetch();

if (!$data) {
    setFlash('error', 'No se encontró la encuesta especificada.');
    redirect('modules/calidad/revision.php');
}

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($data['revision_id'])) {
    $cBarrio       = isset($_POST['consistencia_barrio_comuna']) ? 1 : 0;
    $cEdad         = isset($_POST['consistencia_edad_menor']) ? 1 : 0;
    $cCompleto     = isset($_POST['formulario_completo']) ? 1 : 0;
    $cEncuestador  = isset($_POST['identificacion_encuestador_valida']) ? 1 : 0;
    $dictamen      = trim($_POST['resultado_oficial'] ?? '');
    $justificacion = trim($_POST['dictamen_justificacion'] ?? '');
    $obsAuditoria  = trim($_POST['observaciones_auditoria'] ?? '');

    if (!in_array($dictamen, ['apto', 'no_apto'], true)) {
        $errores[] = 'Debe seleccionar un resultado oficial: Apto o No Apto.';
    }
    if (empty($justificacion)) {
        $errores[] = 'Debe ingresar la justificación oficial del dictamen emitido.';
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            $stmtRev = $pdo->prepare('
                INSERT INTO revisiones_calidad (
                    encuesta_id, usuario_calidad_id, fecha_revision,
                    consistencia_barrio_comuna, consistencia_edad_menor,
                    formulario_completo, identificacion_encuestador_valida,
                    resultado_oficial, dictamen_justificacion, observaciones_auditoria
                ) VALUES (
                    :eid, :uid, NOW(),
                    :c_barrio, :c_edad,
                    :c_comp, :c_enc,
                    :dictamen, :just, :obs
                )
            ');

            $stmtRev->execute([
                ':eid'      => $encuestaId,
                ':uid'      => $currentUser['id'],
                ':c_barrio' => $cBarrio,
                ':c_edad'   => $cEdad,
                ':c_comp'   => $cCompleto,
                ':c_enc'    => $cEncuestador,
                ':dictamen' => $dictamen,
                ':just'     => $justificacion,
                ':obs'      => $obsAuditoria ?: null
            ]);

            // Actualizar estado de la solicitud
            $nuevoEstado = ($dictamen === 'apto') ? 'aprobada_apto' : 'rechazada_no_apto';
            $stmtUpdSol = $pdo->prepare('UPDATE solicitudes SET estado = :est WHERE id = :sid');
            $stmtUpdSol->execute([
                ':est' => $nuevoEstado,
                ':sid' => $data['solicitud_id']
            ]);

            $pdo->commit();

            registrarBitacora(
                $currentUser['id'],
                $currentUser['rol'],
                'CALIDAD',
                'DICTAMEN_OFICIAL',
                "Dictamen emitido para solicitud {$data['numero_radicado']}: " . strtoupper($dictamen) . ". Justificación: $justificacion"
            );

            setFlash('success', "¡Auditoría de calidad finalizada con éxito! Dictamen oficial publicado: <strong>" . strtoupper($dictamen) . "</strong>.");
            redirect('modules/calidad/revision.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = 'Error al registrar auditoría de calidad: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Auditoría y Dictamen de Calidad';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Auditoría de Encuesta & Emisión de Dictamen</h4>
        <p class="text-muted small mb-0">Radicado: <strong class="text-primary font-monospace"><?= e($data['numero_radicado']) ?></strong> &bull; Verificación para Beneficio Extranjero</p>
    </div>
    <a href="<?= url('modules/calidad/revision.php') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a la Mesa de Revisión
    </a>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger shadow-sm">
        <ul class="mb-0 ps-3 small">
            <?php foreach ($errores as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-3">
    
    <!-- Columna Izquierda: Datos Levantados en Campo -->
    <div class="col-lg-7">
        <div class="app-card mb-3">
            <div class="app-card-header">
                <h5 class="app-card-title"><i class="bi bi-file-text me-2"></i>Datos Capturados en Campo</h5>
                <span class="badge bg-light text-dark border small">Levantada: <?= formatearFecha($data['fecha_levantamiento'], true) ?></span>
            </div>
            <div class="app-card-body">
                
                <div class="row g-2 mb-3 bg-light p-2 rounded border">
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Jefe de Hogar</small>
                        <strong><?= e($data['jefe_hogar_nombre']) ?></strong>
                    </div>
                    <div class="col-sm-6">
                        <small class="text-muted d-block">Identificación Solicitante</small>
                        <strong><?= e($data['tipo_documento']) ?> <?= e($data['documento_identidad']) ?></strong>
                    </div>
                    <div class="col-sm-6 mt-2">
                        <small class="text-muted d-block">Ubicación Territorial</small>
                        <strong><?= e($data['direccion']) ?></strong> (<?= e($data['barrio']) ?>, Comuna <?= e((string)$data['comuna']) ?>)
                    </div>
                    <div class="col-sm-6 mt-2">
                        <small class="text-muted d-block">Encuestador Registrado</small>
                        <strong class="text-primary"><?= e($data['encuestador_nombre']) ?></strong> (CC <?= e($data['encuestador_cedula']) ?>)
                    </div>
                </div>

                <div class="row g-2 mb-3">
                    <div class="col-sm-4">
                        <div class="border p-2 rounded">
                            <small class="text-muted d-block">Integrantes</small>
                            <span class="fs-5 fw-bold text-dark"><?= (int)$data['numero_integrantes'] ?></span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="border p-2 rounded">
                            <small class="text-muted d-block">Estrato</small>
                            <span class="fs-5 fw-bold text-dark">Estrato <?= (int)$data['estrato'] ?></span>
                        </div>
                    </div>
                    <div class="col-sm-4">
                        <div class="border p-2 rounded">
                            <small class="text-muted d-block">Vivienda</small>
                            <span class="fs-5 fw-bold text-dark text-capitalize"><?= e($data['tipo_vivienda']) ?></span>
                        </div>
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block fw-semibold mb-1">Información de Menores de Edad</small>
                    <?php if ($data['tiene_menores']): ?>
                        <div class="alert alert-light border small py-2">
                            <strong>Menor:</strong> <?= e($data['menor_nombre']) ?><br>
                            <strong>Fecha Nacimiento:</strong> <?= formatearFecha($data['menor_fecha_nacimiento']) ?><br>
                            <strong>Edad Calculada:</strong> <span class="badge bg-primary"><?= (int)$data['menor_edad_calculada'] ?> años</span>
                        </div>
                    <?php else: ?>
                        <span class="badge bg-secondary">No residen menores de edad en el hogar</span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block fw-semibold mb-1">Ingresos Mensuales Reportados</small>
                    <strong class="fs-5 text-success">$ <?= number_format((float)$data['ingreso_mensual'], 2, ',', '.') ?> COP</strong>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block fw-semibold mb-1">Servicios Básicos Disponibles</small>
                    <div><?= e($data['servicios_basicos']) ?></div>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block fw-semibold mb-1">Destino del Préstamo de Gobierno Extranjero</small>
                    <div class="bg-light p-2 rounded border small fst-italic">
                        "<?= e($data['motivo_prestamo_detalle']) ?>"
                    </div>
                </div>

                <div class="mb-2">
                    <small class="text-muted d-block fw-semibold mb-1">Constancia de Firma en Terreno</small>
                    <div class="small text-dark font-monospace"><i class="bi bi-pen me-1 text-primary"></i><?= e($data['firma_digital']) ?></div>
                </div>

            </div>
        </div>
    </div>

    <!-- Columna Derecha: Formulario de Evaluación y Emisión de Dictamen -->
    <div class="col-lg-5">
        <div class="app-card">
            <div class="app-card-header">
                <h5 class="app-card-title"><i class="bi bi-patch-check-fill text-primary me-2"></i>Criterios de Calidad & Dictamen</h5>
                <span class="badge bg-primary">Equipo de 5</span>
            </div>
            <div class="app-card-body">
                
                <?php if ($data['revision_id']): ?>
                    <!-- Vista de solo lectura si ya fue revisada -->
                    <div class="alert <?= $data['resultado_oficial'] === 'apto' ? 'alert-success' : 'alert-danger' ?> mb-3">
                        <h6 class="fw-bold mb-1">
                            <i class="bi <?= $data['resultado_oficial'] === 'apto' ? 'bi-check-circle-fill' : 'bi-x-circle-fill' ?> me-1"></i>
                            Dictamen Emitido: <?= strtoupper($data['resultado_oficial']) ?>
                        </h6>
                        <div class="small">
                            Auditado por: <strong><?= e($data['revisor_nombre']) ?></strong> el <?= formatearFecha($data['fecha_revision'], true) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Justificación Oficial del Dictamen</label>
                        <div class="bg-light p-2 rounded border small">
                            <?= nl2br(e($data['dictamen_justificacion'])) ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Observaciones Técnicas</label>
                        <div class="bg-light p-2 rounded border small text-muted">
                            <?= nl2br(e($data['observaciones_auditoria'] ?? 'Sin observaciones adicionales.')) ?>
                        </div>
                    </div>
                <?php else: ?>
                    <!-- Formulario de Evaluación -->
                    <form method="POST" action="evaluar.php?encuesta_id=<?= $encuestaId ?>">
                        
                        <div class="mb-3">
                            <label class="form-label fw-semibold small">Verificación de Criterios (Mitigación de Errores Históricos)</label>
                            
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="consistencia_barrio_comuna" id="c_barrio" value="1" checked required>
                                <label class="form-check-label small" for="c_barrio">
                                    <strong>1. Consistencia Territorial:</strong> Barrio corresponde exactamente a la Comuna de Cali (sin confusión con ciudades vecinas).
                                </label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="consistencia_edad_menor" id="c_edad" value="1" checked required>
                                <label class="form-check-label small" for="c_edad">
                                    <strong>2. Consistencia Cronológica:</strong> Fecha de nacimiento del menor concordante con edad real.
                                </label>
                            </div>

                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="formulario_completo" id="c_comp" value="1" checked required>
                                <label class="form-check-label small" for="c_comp">
                                    <strong>3. Formulario Completo:</strong> Cero preguntas sin responder o mal diligenciadas.
                                </label>
                            </div>

                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="identificacion_encuestador_valida" id="c_enc" value="1" checked required>
                                <label class="form-check-label small" for="c_enc">
                                    <strong>4. Encuestador Identificado:</strong> Encuesta con nombre y matrícula oficial de encuestador.
                                </label>
                            </div>
                        </div>

                        <hr class="my-3">

                        <div class="mb-3">
                            <label class="form-label fw-bold">Resultado Oficial del Beneficio Extranjero <span class="text-danger">*</span></label>
                            <div class="d-flex gap-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="resultado_oficial" id="res_apto" value="apto" checked>
                                    <label class="form-check-label fw-bold text-success" for="res_apto">
                                        <i class="bi bi-check-circle me-1"></i> APTO (Aprobado)
                                    </label>
                                </div>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="resultado_oficial" id="res_no_apto" value="no_apto">
                                    <label class="form-check-label fw-bold text-danger" for="res_no_apto">
                                        <i class="bi bi-x-circle me-1"></i> NO APTO (Rechazado)
                                    </label>
                                </div>
                            </div>
                            <span class="form-text">Dictamen oficial que será publicado ante la entidad de cooperación internacional.</span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Justificación Técnica del Dictamen <span class="text-danger">*</span></label>
                            <textarea class="form-control form-control-sm" name="dictamen_justificacion" rows="3" required placeholder="Explique la sustentación técnica del dictamen (vulnerabilidad, consistencia de documentos, etc.).">Cumple con la totalidad de los requisitos normativos y socioeconómicos exigidos por el fondo de cooperación internacional. Información territorial y de hogar validada con total consistencia.</textarea>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small">Observaciones Internas de Auditoría</label>
                            <textarea class="form-control form-control-sm" name="observaciones_auditoria" rows="2" placeholder="Notas de trazabilidad interna para el equipo revisor..."></textarea>
                        </div>

                        <div class="d-grid mt-4">
                            <button type="submit" class="btn btn-primary py-2 fw-semibold" style="background-color: #0e2a47; border-color: #0e2a47;">
                                <i class="bi bi-stamp me-1"></i> Emitir y Publicar Dictamen Oficial
                            </button>
                        </div>

                    </form>
                <?php endif; ?>

            </div>
        </div>
    </div>

</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
