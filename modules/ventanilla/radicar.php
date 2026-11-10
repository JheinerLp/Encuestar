<?php
/**
 * Módulo de Ventanilla - Radicación de Solicitud con Georreferenciación
 * Requerimientos 2, 3 y 5
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cali_geodata.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['ventanilla', 'administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

// Obtener puntos de atención activos para el selector de punto de radicación
$stmtPuntos = $pdo->query('SELECT * FROM puntos_atencion WHERE activo = 1 ORDER BY es_principal DESC, comuna ASC');
$puntosAtencion = $stmtPuntos->fetchAll();

$errores = [];
$datos = [
    'nombres'             => '',
    'apellidos'           => '',
    'tipo_documento'      => 'CC',
    'documento_identidad' => '',
    'celular'             => '',
    'telefono_secundario' => '',
    'zona_tipo'           => 'urbana',
    'comuna'              => $currentUser['punto_comuna'] ?? 17,
    'corregimiento'       => '',
    'barrio'              => '',
    'direccion'           => '',
    'punto_atencion_id'   => $currentUser['punto_atencion_id'] ?? 8,
    'latitud'             => 3.3850,
    'longitud'            => -76.5330,
    'motivo_solicitud'    => 'Solicitud para beneficio de préstamo de gobierno extranjero',
    'observaciones'       => ''
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['nombres']             = trim($_POST['nombres'] ?? '');
    $datos['apellidos']           = trim($_POST['apellidos'] ?? '');
    $datos['tipo_documento']      = trim($_POST['tipo_documento'] ?? 'CC');
    $datos['documento_identidad'] = trim($_POST['documento_identidad'] ?? '');
    $datos['celular']             = trim($_POST['celular'] ?? '');
    $datos['telefono_secundario'] = trim($_POST['telefono_secundario'] ?? '');
    $datos['zona_tipo']           = trim($_POST['zona_tipo'] ?? 'urbana');
    $datos['comuna']              = !empty($_POST['comuna']) ? (int)$_POST['comuna'] : null;
    $datos['corregimiento']       = trim($_POST['corregimiento'] ?? '');
    $datos['barrio']              = trim($_POST['barrio'] ?? '');
    $datos['direccion']           = trim($_POST['direccion'] ?? '');
    $datos['punto_atencion_id']   = (int)($_POST['punto_atencion_id'] ?? 8);
    $datos['latitud']             = (float)($_POST['latitud'] ?? 0);
    $datos['longitud']            = (float)($_POST['longitud'] ?? 0);
    $datos['motivo_solicitud']    = trim($_POST['motivo_solicitud'] ?? '');
    $datos['observaciones']       = trim($_POST['observaciones'] ?? '');

    // Validaciones
    if (empty($datos['nombres'])) {
        $errores[] = 'El campo Nombres es obligatorio.';
    }
    if (empty($datos['apellidos'])) {
        $errores[] = 'El campo Apellidos es obligatorio.';
    }
    if (empty($datos['documento_identidad'])) {
        $errores[] = 'El número de documento de identidad es obligatorio.';
    }
    if (empty($datos['celular']) || strlen($datos['celular']) < 7) {
        $errores[] = 'Ingrese un número de celular de contacto válido (mínimo 7 a 10 dígitos).';
    }
    if (empty($datos['direccion'])) {
        $errores[] = 'La dirección domiciliaria es obligatoria.';
    }

    if ($datos['zona_tipo'] === 'urbana') {
        if (empty($datos['comuna']) || $datos['comuna'] < 1 || $datos['comuna'] > 22) {
            $errores[] = 'Debe seleccionar una Comuna válida de Cali (Comunas 1 a 22).';
        }
        if (empty($datos['barrio'])) {
            $errores[] = 'Debe especificar el Barrio oficial correspondiente a la comuna seleccionada.';
        }
        $datos['corregimiento'] = null;
    } else {
        if (empty($datos['corregimiento'])) {
            $errores[] = 'Para zona rural debe seleccionar el Corregimiento de Cali.';
        }
        if (empty($datos['barrio'])) {
            $datos['barrio'] = $datos['corregimiento'] . ' (Vereda/Sector)';
        }
        $datos['comuna'] = null;
    }

    // Georreferenciación obligatoria (Requisito 3)
    if ($datos['latitud'] == 0 || $datos['longitud'] == 0) {
        $errores[] = 'Debe georreferenciar la solicitud ubicando el marcador sobre el mapa de Cali.';
    }

    if (empty($errores)) {
        try {
            // Generar número de radicado consecutivo: SOL-AÑO-XXXXX
            $year = date('Y');
            $stmtCount = $pdo->query("SELECT COUNT(*) AS total FROM solicitudes WHERE YEAR(fecha_radicacion) = $year");
            $consecutivo = (int)$stmtCount->fetchColumn() + 1;
            $numeroRadicado = sprintf('SOL-%s-%04d', $year, $consecutivo);

            // Regla de los 15 días calendario de respuesta máxima institucional
            $fechaRadicacion = date('Y-m-d H:i:s');
            $fechaLimite = date('Y-m-d', strtotime('+15 days'));

            $insertStmt = $pdo->prepare('
                INSERT INTO solicitudes (
                    numero_radicado, punto_atencion_id, usuario_ventanilla_id, nombres, apellidos,
                    tipo_documento, documento_identidad, zona_tipo, comuna, corregimiento, barrio,
                    direccion, celular, telefono_secundario, latitud, longitud, motivo_solicitud,
                    fecha_radicacion, fecha_limite_15dias, estado, observaciones
                ) VALUES (
                    :radicado, :punto_id, :ventanilla_id, :nombres, :apellidos,
                    :tipo_doc, :doc, :zona, :comuna, :corregimiento, :barrio,
                    :direccion, :celular, :tel_sec, :lat, :lng, :motivo,
                    :fecha_rad, :fecha_limite, "radicada", :obs
                )
            ');

            $insertStmt->execute([
                ':radicado'       => $numeroRadicado,
                ':punto_id'       => $datos['punto_atencion_id'],
                ':ventanilla_id'  => $currentUser['id'],
                ':nombres'        => $datos['nombres'],
                ':apellidos'      => $datos['apellidos'],
                ':tipo_doc'       => $datos['tipo_documento'],
                ':doc'            => $datos['documento_identidad'],
                ':zona'           => $datos['zona_tipo'],
                ':comuna'         => $datos['comuna'],
                ':corregimiento'  => $datos['corregimiento'],
                ':barrio'         => $datos['barrio'],
                ':direccion'      => $datos['direccion'],
                ':celular'        => $datos['celular'],
                ':tel_sec'        => $datos['telefono_secundario'] ?: null,
                ':lat'            => $datos['latitud'],
                ':lng'            => $datos['longitud'],
                ':motivo'         => $datos['motivo_solicitud'],
                ':fecha_rad'      => $fechaRadicacion,
                ':fecha_limite'   => $fechaLimite,
                ':obs'            => $datos['observaciones'] ?: null,
            ]);

            $nuevoId = (int)$pdo->lastInsertId();

            registrarBitacora(
                $currentUser['id'],
                $currentUser['rol'],
                'VENTANILLA',
                'RADICACION_SOLICITUD',
                "Radicación exitosa de solicitud $numeroRadicado para {$datos['nombres']} {$datos['apellidos']} en {$datos['barrio']} (Comuna " . ($datos['comuna'] ?? 'Rural') . "). Georreferencia: [{$datos['latitud']}, {$datos['longitud']}]."
            );

            setFlash('success', "¡Solicitud radicada con éxito! Número de Radicado: <strong>$numeroRadicado</strong>. Plazo límite de visita (15 días): " . formatearFecha($fechaLimite));
            redirect('modules/ventanilla/solicitudes.php');

        } catch (Exception $e) {
            $errores[] = 'Error al registrar en la base de datos: ' . $e->getMessage();
        }
    }
}

$pageTitle = 'Radicar Solicitud de Encuesta';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Radicación de Solicitud de Encuesta</h4>
        <p class="text-muted small mb-0">Módulo de Ventanilla &bull; Captura y Georreferenciación Sectorial de Cali</p>
    </div>
    <a href="<?= url('modules/ventanilla/solicitudes.php') ?>" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-list-ul me-1"></i> Ver Solicitudes Radicadas
    </a>
</div>

<div class="callout-institutional d-flex align-items-center justify-content-between">
    <div>
        <i class="bi bi-info-circle-fill text-primary me-2"></i>
        <strong>Control Normativo:</strong> Desde el momento en que se radica la solicitud comienza a correr el plazo máximo estricto de <strong>15 días calendario</strong> para la visita domiciliaria y dictamen.
    </div>
    <span class="badge bg-primary">SLA 15 Días</span>
</div>

<?php if (!empty($errores)): ?>
    <div class="alert alert-danger shadow-sm">
        <div class="fw-bold mb-1"><i class="bi bi-exclamation-octagon me-1"></i> Por favor verifique los siguientes campos:</div>
        <ul class="mb-0 ps-3 small">
            <?php foreach ($errores as $err): ?>
                <li><?= e($err) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form method="POST" action="radicar.php" id="formRadicacion">
    <div class="row g-3">
        
        <!-- Columna Izquierda: Datos del Solicitante y División Territorial -->
        <div class="col-lg-6">
            <div class="app-card">
                <div class="app-card-header">
                    <h5 class="app-card-title"><i class="bi bi-person-vcard me-2"></i>1. Datos del Solicitante</h5>
                    <span class="badge bg-light text-dark border">Punto de Atención: <?= e($currentUser['punto_nombre'] ?? 'Oficina Principal') ?></span>
                </div>
                <div class="app-card-body">
                    
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Punto de Atención Receptor <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="punto_atencion_id" id="punto_atencion_id" required>
                                <?php foreach ($puntosAtencion as $pa): ?>
                                    <option value="<?= $pa['id'] ?>" <?= $pa['id'] == $datos['punto_atencion_id'] ? 'selected' : '' ?>>
                                        <?= e($pa['nombre']) ?> (Comuna <?= $pa['comuna'] ?><?= $pa['es_principal'] ? ' - Principal' : '' ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Tipo de Zona Territorial <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="zona_tipo" id="zona_tipo" onchange="toggleTipoZona()" required>
                                <option value="urbana" <?= $datos['zona_tipo'] === 'urbana' ? 'selected' : '' ?>>Urbana (Comunas 1 a 22)</option>
                                <option value="rural" <?= $datos['zona_tipo'] === 'rural' ? 'selected' : '' ?>>Rural (15 Corregimientos)</option>
                            </select>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Nombres <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="nombres" value="<?= e($datos['nombres']) ?>" placeholder="Ej: María Camila" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Apellidos <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="apellidos" value="<?= e($datos['apellidos']) ?>" placeholder="Ej: Rodríguez Gómez" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <label class="form-label">Tipo Documento</label>
                            <select class="form-select form-select-sm" name="tipo_documento">
                                <option value="CC" <?= $datos['tipo_documento'] === 'CC' ? 'selected' : '' ?>>Cédula de Ciudadanía</option>
                                <option value="CE" <?= $datos['tipo_documento'] === 'CE' ? 'selected' : '' ?>>Cédula de Extranjería</option>
                                <option value="PPT" <?= $datos['tipo_documento'] === 'PPT' ? 'selected' : '' ?>>Permiso Protección Temporal</option>
                                <option value="TI" <?= $datos['tipo_documento'] === 'TI' ? 'selected' : '' ?>>Tarjeta de Identidad</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Número de Documento <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="documento_identidad" value="<?= e($datos['documento_identidad']) ?>" placeholder="Ej: 1144123456" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Número de Celular <span class="text-danger">*</span></label>
                            <input type="tel" class="form-control form-control-sm" name="celular" value="<?= e($datos['celular']) ?>" placeholder="Ej: 3157891234" required>
                            <span class="form-text">Indispensable para llamada de confirmación previa del encuestador.</span>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Teléfono Fijo / Secundario</label>
                            <input type="tel" class="form-control form-control-sm" name="telefono_secundario" value="<?= e($datos['telefono_secundario']) ?>" placeholder="Ej: (602) 555-1234">
                        </div>
                    </div>

                    <hr class="my-3">

                    <h6 class="fw-bold text-navy mb-2"><i class="bi bi-pin-map me-1"></i>División Sectorial de Cali (Requerimiento 2)</h6>

                    <!-- Bloque Zona Urbana -->
                    <div id="bloqueUrbano">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Comuna de Cali <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="comuna" id="select_comuna" onchange="cargarBarriosDeComuna()">
                                    <option value="">-- Seleccione Comuna --</option>
                                    <?php foreach (CALI_COMUNAS_BARRIOS as $numComuna => $infoComuna): ?>
                                        <option value="<?= $numComuna ?>" <?= $datos['comuna'] == $numComuna ? 'selected' : '' ?> data-lat="<?= $infoComuna['lat'] ?>" data-lng="<?= $infoComuna['lng'] ?>">
                                            <?= e($infoComuna['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Barrio Oficial <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="barrio" id="select_barrio">
                                    <option value="">-- Seleccione Barrio --</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Bloque Zona Rural -->
                    <div id="bloqueRural" style="display: none;">
                        <div class="row g-2 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Corregimiento de Cali <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm" name="corregimiento" id="select_corregimiento" onchange="actualizarCorregimiento()">
                                    <option value="">-- Seleccione Corregimiento --</option>
                                    <?php foreach (CALI_CORREGIMIENTOS as $nomCorreg => $infoCorreg): ?>
                                        <option value="<?= e($nomCorreg) ?>" <?= $datos['corregimiento'] === $nomCorreg ? 'selected' : '' ?> data-lat="<?= $infoCorreg['lat'] ?>" data-lng="<?= $infoCorreg['lng'] ?>">
                                            Corregimiento <?= e($nomCorreg) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Vereda o Sector</label>
                                <input type="text" class="form-control form-control-sm" name="barrio_rural" id="barrio_rural" placeholder="Ej: Vereda San Francisco">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Dirección Domiciliaria Exacta <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" name="direccion" id="direccion" value="<?= e($datos['direccion']) ?>" placeholder="Ej: Calle 42 # 85-30 Manzana B Casa 12" required>
                        </div>
                        <span class="form-text">Incluya nomenclatura completa de Cali (Calle, Carrera, Avenida, Manzana, Interior).</span>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Observaciones de Radicación</label>
                        <textarea class="form-control form-control-sm" name="observaciones" rows="2" placeholder="Indicaciones para el encuestador, referencias para llegar, etc."><?= e($datos['observaciones']) ?></textarea>
                    </div>

                </div>
            </div>
        </div>

        <!-- Columna Derecha: Georreferenciación en Mapa Interactivo de Cali -->
        <div class="col-lg-6">
            <div class="app-card h-100 d-flex flex-column">
                <div class="app-card-header">
                    <div>
                        <h5 class="app-card-title"><i class="bi bi-geo-alt-fill text-danger me-2"></i>2. Georreferenciación de la Solicitud</h5>
                        <span class="text-muted small">Requerimiento 3 (1.0 ptos) &bull; Arrastre el marcador o haga clic sobre el mapa</span>
                    </div>
                </div>
                <div class="app-card-body d-flex flex-column flex-grow-1">

                    <div class="alert alert-light border small py-2 px-3 mb-2 d-flex justify-content-between align-items-center">
                        <div>
                            <span class="badge bg-danger me-1">&bull;</span> <strong>Marcador Rojo:</strong> Predio a Encuestar.
                            <span class="badge bg-primary ms-2 me-1">&bull;</span> <strong>Puntos Azules:</strong> Las 8 Sedes Estáticas (Comunas 1, 6, 7, 13, 14, 20, 21 y 17).
                        </div>
                        <button type="button" class="btn btn-xs btn-outline-secondary btn-sm" onclick="centrarEnCali()">
                            <i class="bi bi-aspect-ratio me-1"></i> Ver Cali
                        </button>
                    </div>

                    <!-- Contenedor del Mapa Leaflet -->
                    <div id="mapaRadicacion" class="map-container flex-grow-1 mb-3"></div>

                    <!-- Inputs de Coordenadas Geográficas -->
                    <div class="row g-2 bg-light p-2 rounded border">
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Latitud (GPS WGS84)</label>
                            <input type="text" class="form-control form-control-sm bg-white" id="latitud" name="latitud" value="<?= e((string)$datos['latitud']) ?>" readonly required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small mb-1">Longitud (GPS WGS84)</label>
                            <input type="text" class="form-control form-control-sm bg-white" id="longitud" name="longitud" value="<?= e((string)$datos['longitud']) ?>" readonly required>
                        </div>
                        <div class="col-12 mt-2">
                            <div class="small text-muted d-flex align-items-center gap-1">
                                <i class="bi bi-check-circle-fill text-success"></i>
                                <span id="coordenadasTexto">Coordenadas fijadas en: <?= e((string)$datos['latitud']) ?>, <?= e((string)$datos['longitud']) ?></span>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-semibold" style="background-color: #0e2a47; border-color: #0e2a47;">
                            <i class="bi bi-check2-circle me-1"></i> Radicar y Guardar Solicitud
                        </button>
                    </div>

                </div>
            </div>
        </div>

    </div>
</form>

<!-- Datos JavaScript de Cali y Sedes Estáticas -->
<script>
    const PUNTOS_ESTATICOS = <?= json_encode(CALI_PUNTOS_ESTATICOS, JSON_UNESCAPED_UNICODE) ?>;
    const COMUNAS_DATA = <?= json_encode(CALI_COMUNAS_BARRIOS, JSON_UNESCAPED_UNICODE) ?>;
    const CORREGIMIENTOS_DATA = <?= json_encode(CALI_CORREGIMIENTOS, JSON_UNESCAPED_UNICODE) ?>;

    let map;
    let targetMarker;

    function initMap() {
        const initialLat = parseFloat(document.getElementById('latitud').value) || <?= CALI_LAT_DEFAULT ?>;
        const initialLng = parseFloat(document.getElementById('longitud').value) || <?= CALI_LNG_DEFAULT ?>;

        map = L.map('mapaRadicacion').setView([initialLat, initialLng], 13);

        // OpenStreetMap Tile Layer
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors &bull; SIRCE Santiago de Cali'
        }).addTo(map);

        // Icono para Sedes Estáticas (Requerimiento 5: Comunas 1, 6, 7, 13, 14, 20, 21 y 17)
        const sedeIcon = L.divIcon({
            className: 'custom-div-icon',
            html: "<div style='background-color:#0284c7;color:#fff;border:2px solid #fff;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 5px rgba(0,0,0,0.3);font-size:12px;'><i class='bi bi-building'></i></div>",
            iconSize: [30, 30],
            iconAnchor: [15, 15]
        });

        // Dibujar los 8 puntos estáticos en el mapa
        PUNTOS_ESTATICOS.forEach(punto => {
            const marker = L.marker([punto.lat, punto.lng], { icon: sedeIcon }).addTo(map);
            marker.bindPopup(`
                <div style="font-size:12px; min-width:180px;">
                    <strong style="color:#0e2a47;">${punto.nombre}</strong><br>
                    <span style="color:#64748b;">Comuna ${punto.comuna} ${punto.es_principal ? '(Oficina Principal)' : ''}</span><br>
                    <small>${punto.direccion}</small><br>
                    <small>Tel: ${punto.telefono}</small>
                </div>
            `);
        });

        // Marcador para la solicitud (arrastrable)
        const targetIcon = L.divIcon({
            className: 'target-pin-icon',
            html: "<div style='background-color:#dc2626;color:#fff;border:2px solid #fff;border-radius:50%;width:34px;height:34px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,0.4);font-size:16px;'><i class='bi bi-geo-alt-fill'></i></div>",
            iconSize: [34, 34],
            iconAnchor: [17, 34]
        });

        targetMarker = L.marker([initialLat, initialLng], {
            draggable: true,
            icon: targetIcon
        }).addTo(map);

        targetMarker.bindPopup("<strong>Ubicación de la Encuesta</strong><br>Arrastre este marcador al predio exacto.").openPopup();

        // Evento arrastrar marcador
        targetMarker.on('dragend', function (e) {
            const coord = e.target.getLatLng();
            actualizarCoordenadas(coord.lat, coord.lng);
        });

        // Evento clic en cualquier parte del mapa
        map.on('click', function (e) {
            targetMarker.setLatLng(e.latlng);
            actualizarCoordenadas(e.latlng.lat, e.latlng.lng);
        });
    }

    function actualizarCoordenadas(lat, lng) {
        document.getElementById('latitud').value = lat.toFixed(8);
        document.getElementById('longitud').value = lng.toFixed(8);
        document.getElementById('coordenadasTexto').innerText = `Coordenadas fijadas en: ${lat.toFixed(6)}, ${lng.toFixed(6)}`;
    }

    function centrarEnCali() {
        if (map) {
            map.setView([<?= CALI_LAT_DEFAULT ?>, <?= CALI_LNG_DEFAULT ?>], 12);
        }
    }

    function toggleTipoZona() {
        const tipo = document.getElementById('zona_tipo').value;
        const bUrbano = document.getElementById('bloqueUrbano');
        const bRural = document.getElementById('bloqueRural');

        if (tipo === 'urbana') {
            bUrbano.style.display = 'block';
            bRural.style.display = 'none';
        } else {
            bUrbano.style.display = 'none';
            bRural.style.display = 'block';
        }
    }

    function cargarBarriosDeComuna() {
        const comunaVal = document.getElementById('select_comuna').value;
        const selectBarrio = document.getElementById('select_barrio');
        selectBarrio.innerHTML = '<option value="">-- Seleccione Barrio --</option>';

        if (COMUNAS_DATA[comunaVal]) {
            const comuna = COMUNAS_DATA[comunaVal];
            comuna.barrios.forEach(b => {
                const opt = document.createElement('option');
                opt.value = b;
                opt.textContent = b;
                selectBarrio.appendChild(opt);
            });

            // Mover mapa al centro aproximado de la comuna seleccionada
            if (map && comuna.lat && comuna.lng) {
                map.setView([comuna.lat, comuna.lng], 14);
                targetMarker.setLatLng([comuna.lat, comuna.lng]);
                actualizarCoordenadas(comuna.lat, comuna.lng);
            }
        }
    }

    function actualizarCorregimiento() {
        const corregVal = document.getElementById('select_corregimiento').value;
        if (CORREGIMIENTOS_DATA[corregVal]) {
            const c = CORREGIMIENTOS_DATA[corregVal];
            if (map && c.lat && c.lng) {
                map.setView([c.lat, c.lng], 13);
                targetMarker.setLatLng([c.lat, c.lng]);
                actualizarCoordenadas(c.lat, c.lng);
            }
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        initMap();
        cargarBarriosDeComuna();

        // Si ya había barrio seleccionado previamente
        const barrioPrevio = <?= json_encode($datos['barrio']) ?>;
        if (barrioPrevio) {
            const selectBarrio = document.getElementById('select_barrio');
            selectBarrio.value = barrioPrevio;
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
