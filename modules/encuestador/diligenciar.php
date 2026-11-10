<?php
/**
 * Módulo de Encuestador - Diligenciamiento Digital de Encuesta en Campo
 * Mitiga los 4 errores humanos históricos del formato físico en papel:
 * 1. Control de consistencia territorial en Cali.
 * 2. Validación estricta de edad de menores vs. fecha de nacimiento.
 * 3. Formulario 100% completo (sin preguntas sin responder).
 * 4. Asignación automática inalterable del nombre e identificación del encuestador.
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['encuestador', 'administrador', 'calidad']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

$solicitudId = (int)($_GET['solicitud_id'] ?? 0);
$hojaId = isset($_GET['hoja_id']) ? (int)$_GET['hoja_id'] : null;

if ($solicitudId <= 0) {
    setFlash('error', 'Solicitud no válida.');
    redirect('modules/encuestador/mi_hoja_ruta.php');
}

// Consultar solicitud
$stmtSol = $pdo->prepare('
    SELECT s.*, p.nombre AS punto_nombre
    FROM solicitudes s
    INNER JOIN puntos_atencion p ON s.punto_atencion_id = p.id
    WHERE s.id = :id
');
$stmtSol->execute([':id' => $solicitudId]);
$solicitud = $stmtSol->fetch();

if (!$solicitud) {
    setFlash('error', 'La solicitud especificada no existe.');
    redirect('modules/encuestador/mi_hoja_ruta.php');
}

// Consultar si ya existe encuesta diligenciada
$stmtEnc = $pdo->prepare('
    SELECT e.*, u.nombre_completo AS nombre_encuestador_grabado
    FROM encuestas e
    INNER JOIN usuarios u ON e.encuestador_id = u.id
    WHERE e.solicitud_id = :sid
');
$stmtEnc->execute([':sid' => $solicitudId]);
$encuestaExistente = $stmtEnc->fetch();

$errores = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$encuestaExistente) {
    $jefeHogar        = trim($_POST['jefe_hogar_nombre'] ?? '');
    $numIntegrantes   = (int)($_POST['numero_integrantes'] ?? 1);
    $tieneMenores     = isset($_POST['tiene_menores']) && $_POST['tiene_menores'] === '1' ? 1 : 0;
    $menorNombre      = trim($_POST['menor_nombre'] ?? '');
    $menorFechaNac    = trim($_POST['menor_fecha_nacimiento'] ?? '');
    $menorEdadCalc    = !empty($_POST['menor_edad_calculada']) ? (int)$_POST['menor_edad_calculada'] : null;
    $ingresoMensual   = (float)($_POST['ingreso_mensual'] ?? 0);
    $tipoVivienda     = trim($_POST['tipo_vivienda'] ?? 'arrendada');
    $estrato          = (int)($_POST['estrato'] ?? 1);
    $serviciosArr     = $_POST['servicios'] ?? [];
    $serviciosStr     = implode(', ', $serviciosArr);
    $motivoPrestamo   = trim($_POST['motivo_prestamo_detalle'] ?? '');
    $firmaDigital     = trim($_POST['firma_digital'] ?? '');
    $obsEncuestador   = trim($_POST['observaciones_encuestador'] ?? '');
    $latVisita        = (float)($_POST['latitud_visita'] ?? $solicitud['latitud']);
    $lngVisita        = (float)($_POST['longitud_visita'] ?? $solicitud['longitud']);

    // Validaciones para erradicar errores del formato papel
    if (empty($jefeHogar)) {
        $errores[] = 'El nombre completo del jefe o jefa de hogar es obligatorio.';
    }
    if ($numIntegrantes < 1) {
        $errores[] = 'El número de integrantes del hogar debe ser de al menos 1 persona.';
    }
    if ($tieneMenores) {
        if (empty($menorNombre)) {
            $errores[] = 'Especifique el nombre del menor de edad residente.';
        }
        if (empty($menorFechaNac)) {
            $errores[] = 'La fecha de nacimiento del menor es obligatoria para verificar consistencia.';
        } else {
            // Validación matemática estricta: calcular edad exacta
            $fn = new DateTime($menorFechaNac);
            $hoy = new DateTime();
            if ($fn > $hoy) {
                $errores[] = 'La fecha de nacimiento del menor no puede ser una fecha futura.';
            } else {
                $edadReal = $fn->diff($hoy)->y;
                if ($edadReal >= 18) {
                    $errores[] = "Inconsistencia de menores: La persona nacida en $menorFechaNac tiene $edadReal años y es mayor de edad.";
                }
                $menorEdadCalc = $edadReal;
            }
        }
    } else {
        $menorNombre = null;
        $menorFechaNac = null;
        $menorEdadCalc = null;
    }

    if ($ingresoMensual <= 0) {
        $errores[] = 'Debe registrar un ingreso mensual aproximado del hogar.';
    }
    if (empty($serviciosStr)) {
        $errores[] = 'Debe seleccionar al menos un servicio público con que cuenta la vivienda.';
    }
    if (empty($motivoPrestamo) || strlen($motivoPrestamo) < 10) {
        $errores[] = 'Describa el motivo de solicitud para el préstamo internacional (mínimo 10 caracteres).';
    }
    if (empty($firmaDigital)) {
        $errores[] = 'Debe ingresar el nombre y número de documento de quien atiende la visita como constancia/firma digital.';
    }

    if (empty($errores)) {
        try {
            $pdo->beginTransaction();

            // Insertar encuesta garantizando el ID del encuestador logueado
            $stmtInsert = $pdo->prepare('
                INSERT INTO encuestas (
                    solicitud_id, hoja_ruta_id, encuestador_id, fecha_levantamiento,
                    jefe_hogar_nombre, numero_integrantes, tiene_menores, menor_nombre,
                    menor_fecha_nacimiento, menor_edad_calculada, ingreso_mensual,
                    tipo_vivienda, estrato, servicios_basicos, motivo_prestamo_detalle,
                    latitud_visita, longitud_visita, firma_digital, observaciones_encuestador
                ) VALUES (
                    :sid, :hid, :eid, NOW(),
                    :jefe, :integrantes, :tiene_menores, :m_nombre,
                    :m_fnac, :m_edad, :ingreso,
                    :vivienda, :estrato, :servicios, :motivo,
                    :lat, :lng, :firma, :obs
                )
            ');

            $stmtInsert->execute([
                ':sid'           => $solicitudId,
                ':hid'           => $hojaId,
                ':eid'           => $currentUser['id'],
                ':jefe'          => $jefeHogar,
                ':integrantes'   => $numIntegrantes,
                ':tiene_menores' => $tieneMenores,
                ':m_nombre'      => $menorNombre,
                ':m_fnac'        => $menorFechaNac,
                ':m_edad'        => $menorEdadCalc,
                ':ingreso'       => $ingresoMensual,
                ':vivienda'      => $tipoVivienda,
                ':estrato'       => $estrato,
                ':servicios'     => $serviciosStr,
                ':motivo'        => $motivoPrestamo,
                ':lat'           => $latVisita,
                ':lng'           => $lngVisita,
                ':firma'         => $firmaDigital,
                ':obs'           => $obsEncuestador ?: null
            ]);

            // Actualizar estado de la solicitud a 'en_revision' para la mesa de calidad
            $stmtUpdSol = $pdo->prepare('UPDATE solicitudes SET estado = "en_revision" WHERE id = :id');
            $stmtUpdSol->execute([':id' => $solicitudId]);

            // Actualizar estado de visita en el item de la hoja de ruta
            if ($hojaId) {
                $stmtUpdItem = $pdo->prepare('
                    UPDATE hoja_ruta_items 
                    SET estado_visita = "efectiva" 
                    WHERE hoja_ruta_id = :hid AND solicitud_id = :sid
                ');
                $stmtUpdItem->execute([':hid' => $hojaId, ':sid' => $solicitudId]);
            }

            $pdo->commit();

            registrarBitacora(
                $currentUser['id'],
                $currentUser['rol'],
                'ENCUESTADOR',
                'DILIGENCIAMIENTO_ENCUESTA',
                "Encuesta levantada satisfactoriamente en campo para solicitud {$solicitud['numero_radicado']} por encuestador {$currentUser['nombre_completo']}."
            );

            setFlash('success', "¡Encuesta digital para el radicado <strong>{$solicitud['numero_radicado']}</strong> registrada con total consistencia técnica! Ha sido remitida al equipo de calidad para dictamen.");
            redirect('modules/encuestador/mi_hoja_ruta.php');

        } catch (Exception $e) {
            $pdo->rollBack();
            $errores[] = 'Error al registrar encuesta: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Formulario Digital de Encuesta';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Formulario Digital de Encuesta Domiciliaria</h4>
        <p class="text-muted small mb-0">Captura en Campo &bull; Beneficio de Préstamos de Gobierno Extranjero</p>
    </div>
    <a href="<?= url('modules/encuestador/mi_hoja_ruta.php') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i> Volver a Mi Hoja de Ruta
    </a>
</div>

<!-- Tarjeta de Referencia Territorial y Auditoría Previa -->
<div class="app-card mb-3">
    <div class="app-card-body p-3 bg-light">
        <div class="row g-3 align-items-center">
            <div class="col-md-3">
                <span class="text-muted small text-uppercase fw-semibold d-block">Número de Radicado</span>
                <strong class="text-primary font-monospace fs-5"><?= e($solicitud['numero_radicado']) ?></strong>
                <div class="small text-muted">Radicado: <?= formatearFecha($solicitud['fecha_radicacion']) ?></div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small text-uppercase fw-semibold d-block">Beneficiario Registrado</span>
                <strong class="text-dark"><?= e($solicitud['nombres']) ?> <?= e($solicitud['apellidos']) ?></strong>
                <div class="small text-muted"><?= e($solicitud['tipo_documento']) ?> <?= e($solicitud['documento_identidad']) ?> &bull; Tel: <?= e($solicitud['celular']) ?></div>
            </div>
            <div class="col-md-3">
                <span class="text-muted small text-uppercase fw-semibold d-block">Ubicación Territorial Cali</span>
                <strong class="text-dark"><?= e($solicitud['direccion']) ?></strong>
                <div class="small text-muted">
                    <?= e($solicitud['barrio']) ?> &bull; 
                    <?= $solicitud['zona_tipo'] === 'urbana' ? 'Comuna ' . e((string)$solicitud['comuna']) : 'Corr. ' . e($solicitud['corregimiento']) ?>
                </div>
            </div>
            <div class="col-md-3 text-md-end">
                <span class="text-muted small text-uppercase fw-semibold d-block">Encuestador Responsable</span>
                <div class="badge bg-navy text-white p-2" style="background-color: #0e2a47;">
                    <i class="bi bi-person-badge me-1"></i><?= e($currentUser['nombre_completo']) ?>
                </div>
                <div class="small text-success mt-1"><i class="bi bi-shield-check me-1"></i>Identificación vinculada</div>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger shadow-sm">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon me-1"></i> Verifique los siguientes campos:</div>
        <ul class="mb-0 ps-3 small">
            <?php foreach ($errores as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($encuestaExistente): ?>
    <div class="alert alert-info border-info d-flex align-items-center gap-2 mb-3">
        <i class="bi bi-info-circle-fill fs-4 text-info"></i>
        <div>
            <strong>Esta encuesta ya fue levantada</strong> el <?= formatearFecha($encuestaExistente['fecha_levantamiento'], true) ?> por el encuestador <strong><?= e($encuestaExistente['nombre_encuestador_grabado']) ?></strong>. Estado actual: En mesa de revisión de calidad.
        </div>
    </div>
<?php endif; ?>

<form method="POST" action="diligenciar.php?solicitud_id=<?= $solicitudId ?><?= $hojaId ? '&hoja_id=' . $hojaId : '' ?>" id="formEncuesta">
    <div class="row g-3">
        
        <!-- Bloque 1: Composición del Hogar -->
        <div class="col-lg-6">
            <div class="app-card h-100">
                <div class="app-card-header">
                    <h5 class="app-card-title"><i class="bi bi-people me-2"></i>1. Composición del Hogar y Menores</h5>
                    <span class="badge bg-light text-dark border small">Control Antierrores</span>
                </div>
                <div class="app-card-body">
                    
                    <div class="mb-3">
                        <label class="form-label">Nombre Completo del Jefe / Jefa de Hogar <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="jefe_hogar_nombre" value="<?= e($encuestaExistente['jefe_hogar_nombre'] ?? ($solicitud['nombres'] . ' ' . $solicitud['apellidos'])) ?>" <?= $encuestaExistente ? 'disabled' : '' ?> required>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Número de Integrantes del Hogar <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" name="numero_integrantes" min="1" max="25" value="<?= (int)($encuestaExistente['numero_integrantes'] ?? 3) ?>" <?= $encuestaExistente ? 'disabled' : '' ?> required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">¿Habitan Menores de Edad? <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="tiene_menores" id="tiene_menores" onchange="toggleMenores()" <?= $encuestaExistente ? 'disabled' : '' ?>>
                                <option value="0" <?= ($encuestaExistente && !$encuestaExistente['tiene_menores']) ? 'selected' : '' ?>>No</option>
                                <option value="1" <?= ($encuestaExistente && $encuestaExistente['tiene_menores']) ? 'selected' : '' ?>>Sí, residen menores</option>
                            </select>
                        </div>
                    </div>

                    <!-- Panel Validación Menores (Soluciona inconsistencia de edad vs fecha nacimiento) -->
                    <div id="bloqueMenor" class="bg-light p-3 rounded border mb-3" style="display: none;">
                        <h6 class="fw-bold text-navy mb-2 small"><i class="bi bi-person-badge text-primary me-1"></i>Verificación de Menor de Edad (Control Cruzado)</h6>
                        <div class="mb-2">
                            <label class="form-label small">Nombre Completo del Menor <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="menor_nombre" id="menor_nombre" value="<?= e($encuestaExistente['menor_nombre'] ?? '') ?>" placeholder="Ej: Mateo Gómez Zapata" <?= $encuestaExistente ? 'disabled' : '' ?>>
                        </div>
                        <div class="row g-2">
                            <div class="col-sm-7">
                                <label class="form-label small">Fecha de Nacimiento del Menor <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm" name="menor_fecha_nacimiento" id="menor_fecha_nacimiento" value="<?= e($encuestaExistente['menor_fecha_nacimiento'] ?? '') ?>" onchange="calcularEdadMenor()" <?= $encuestaExistente ? 'disabled' : '' ?>>
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label small">Edad Calculada</label>
                                <input type="text" class="form-control form-control-sm bg-white fw-bold text-primary" id="menor_edad_calculada" name="menor_edad_calculada" value="<?= e((string)($encuestaExistente['menor_edad_calculada'] ?? '')) ?>" readonly placeholder="Auto">
                            </div>
                        </div>
                        <div id="avisoEdadMenor" class="small mt-2"></div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Ingreso Mensual Aproximado del Hogar ($ COP) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text">$</span>
                            <input type="number" class="form-control" name="ingreso_mensual" step="10000" min="0" value="<?= e((string)($encuestaExistente['ingreso_mensual'] ?? '1300000')) ?>" <?= $encuestaExistente ? 'disabled' : '' ?> required>
                        </div>
                        <span class="form-text">Para evaluación socioeconómica del beneficio internacional.</span>
                    </div>

                </div>
            </div>
        </div>

        <!-- Bloque 2: Vivienda y Motivo del Préstamo -->
        <div class="col-lg-6">
            <div class="app-card h-100">
                <div class="app-card-header">
                    <h5 class="app-card-title"><i class="bi bi-house-door me-2"></i>2. Condiciones de Vivienda y Proyecto</h5>
                    <span class="badge bg-light text-dark border small">Preguntas Obligatorias</span>
                </div>
                <div class="app-card-body">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label">Tenencia de Vivienda <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="tipo_vivienda" <?= $encuestaExistente ? 'disabled' : '' ?> required>
                                <option value="propia" <?= ($encuestaExistente['tipo_vivienda'] ?? '') === 'propia' ? 'selected' : '' ?>>Propia</option>
                                <option value="arrendada" <?= ($encuestaExistente['tipo_vivienda'] ?? 'arrendada') === 'arrendada' ? 'selected' : '' ?>>Arrendada</option>
                                <option value="familiar" <?= ($encuestaExistente['tipo_vivienda'] ?? '') === 'familiar' ? 'selected' : '' ?>>Familiar / Cedida</option>
                                <option value="posesion" <?= ($encuestaExistente['tipo_vivienda'] ?? '') === 'posesion' ? 'selected' : '' ?>>Posesión sin título</option>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label">Estrato Socioeconómico <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="estrato" <?= $encuestaExistente ? 'disabled' : '' ?> required>
                                <option value="1" <?= ($encuestaExistente['estrato'] ?? 1) == 1 ? 'selected' : '' ?>>Estrato 1 (Bajo-bajo)</option>
                                <option value="2" <?= ($encuestaExistente['estrato'] ?? 2) == 2 ? 'selected' : '' ?>>Estrato 2 (Bajo)</option>
                                <option value="3" <?= ($encuestaExistente['estrato'] ?? 3) == 3 ? 'selected' : '' ?>>Estrato 3 (Medio-bajo)</option>
                                <option value="4" <?= ($encuestaExistente['estrato'] ?? 4) == 4 ? 'selected' : '' ?>>Estrato 4 (Medio)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Servicios Públicos Disponibles <span class="text-danger">*</span></label>
                        <?php 
                        $serviciosSeleccionados = $encuestaExistente ? explode(', ', $encuestaExistente['servicios_basicos']) : ['Agua potable', 'Energía eléctrica', 'Gas natural'];
                        ?>
                        <div class="d-flex flex-wrap gap-3 small">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="servicios[]" value="Agua potable" id="srv_agua" <?= in_array('Agua potable', $serviciosSeleccionados) ? 'checked' : '' ?> <?= $encuestaExistente ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="srv_agua">Agua Potable</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="servicios[]" value="Energía eléctrica" id="srv_energia" <?= in_array('Energía eléctrica', $serviciosSeleccionados) ? 'checked' : '' ?> <?= $encuestaExistente ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="srv_energia">Energía Eléctrica</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="servicios[]" value="Gas natural" id="srv_gas" <?= in_array('Gas natural', $serviciosSeleccionados) ? 'checked' : '' ?> <?= $encuestaExistente ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="srv_gas">Gas Natural</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="servicios[]" value="Internet" id="srv_internet" <?= in_array('Internet', $serviciosSeleccionados) ? 'checked' : '' ?> <?= $encuestaExistente ? 'disabled' : '' ?>>
                                <label class="form-check-label" for="srv_internet">Internet / Conectividad</label>
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Destino y Justificación del Préstamo Internacional <span class="text-danger">*</span></label>
                        <textarea class="form-control form-control-sm" name="motivo_prestamo_detalle" rows="3" placeholder="Detalle la actividad productiva o mejora habitacional a financiar..." <?= $encuestaExistente ? 'disabled' : '' ?> required><?= e($encuestaExistente['motivo_prestamo_detalle'] ?? '') ?></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Firma / Constancia Digital del Beneficiario <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="firma_digital" value="<?= e($encuestaExistente['firma_digital'] ?? ($solicitud['nombres'] . ' ' . $solicitud['apellidos'] . ' - CC ' . $solicitud['documento_identidad'])) ?>" placeholder="Nombre y Documento de quien atiende" <?= $encuestaExistente ? 'disabled' : '' ?> required>
                        <span class="form-text">Constancia formal de visita domiciliaria en territorio.</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones de Campo del Encuestador</label>
                        <textarea class="form-control form-control-sm" name="observaciones_encuestador" rows="2" placeholder="Notas sobre el acceso, verificación de documentos físicos..." <?= $encuestaExistente ? 'disabled' : '' ?>><?= e($encuestaExistente['observaciones_encuestador'] ?? '') ?></textarea>
                    </div>

                    <?php if (!$encuestaExistente): ?>
                        <div class="text-end mt-4">
                            <button type="submit" class="btn btn-success px-4 py-2 fw-semibold">
                                <i class="bi bi-cloud-arrow-up-fill me-1"></i> Guardar y Enviar a Calidad
                            </button>
                        </div>
                    <?php endif; ?>

                </div>
            </div>
        </div>

    </div>
</form>

<script>
    function toggleMenores() {
        const val = document.getElementById('tiene_menores').value;
        const b = document.getElementById('bloqueMenor');
        b.style.display = (val === '1') ? 'block' : 'none';
        if (val === '0') {
            document.getElementById('menor_nombre').value = '';
            document.getElementById('menor_fecha_nacimiento').value = '';
            document.getElementById('menor_edad_calculada').value = '';
            document.getElementById('avisoEdadMenor').innerHTML = '';
        }
    }

    function calcularEdadMenor() {
        const fnVal = document.getElementById('menor_fecha_nacimiento').value;
        const outEdad = document.getElementById('menor_edad_calculada');
        const aviso = document.getElementById('avisoEdadMenor');

        if (!fnVal) {
            outEdad.value = '';
            aviso.innerHTML = '';
            return;
        }

        const fn = new Date(fnVal);
        const hoy = new Date();

        if (fn > hoy) {
            outEdad.value = 'Error';
            aviso.innerHTML = '<span class="text-danger fw-bold"><i class="bi bi-x-circle me-1"></i>La fecha no puede ser futura.</span>';
            return;
        }

        let edad = hoy.getFullYear() - fn.getFullYear();
        const m = hoy.getMonth() - fn.getMonth();
        if (m < 0 || (m === 0 && hoy.getDate() < fn.getDate())) {
            edad--;
        }

        outEdad.value = `${edad} años`;

        if (edad >= 18) {
            aviso.innerHTML = `<span class="text-danger fw-bold"><i class="bi bi-exclamation-triangle-fill me-1"></i>Inconsistencia: ${edad} años ya es mayor de edad en Colombia.</span>`;
        } else {
            aviso.innerHTML = `<span class="text-success"><i class="bi bi-check-circle me-1"></i>Consistente: menor de edad (${edad} años).</span>`;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        const tieneMenoresVal = document.getElementById('tiene_menores').value;
        if (tieneMenoresVal === '1') {
            toggleMenores();
            calcularEdadMenor();
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
