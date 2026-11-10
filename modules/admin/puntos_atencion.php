<?php
/**
 * Módulo de Administrador - Puntos de Atención Estáticos de Cali
 * Requerimiento 5 (0.44 pts): Comunas 1, 6, 7, 13, 14, 20, 21 y 17 (Principal)
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/cali_geodata.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['administrador', 'ventanilla', 'enrutador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

$stmt = $pdo->query('
    SELECT p.*,
           (SELECT COUNT(*) FROM solicitudes WHERE punto_atencion_id = p.id) AS total_solicitudes,
           (SELECT COUNT(*) FROM hojas_ruta WHERE punto_atencion_id = p.id) AS total_hojas_ruta,
           (SELECT COUNT(*) FROM usuarios WHERE punto_atencion_id = p.id AND activo = 1) AS personal_activo
    FROM puntos_atencion p
    ORDER BY p.es_principal DESC, p.comuna ASC
');
$puntos = $stmt->fetchAll();

$pageTitle = 'Puntos de Atención Estáticos de Cali';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Puntos de Atención Estáticos de Cali</h4>
        <p class="text-muted small mb-0">Requerimiento 5 &bull; Red de Sedes Oficiales en Comunas 1, 6, 7, 13, 14, 20, 21 y Comuna 17 (Principal)</p>
    </div>
    <span class="badge bg-primary p-2">
        <i class="bi bi-geo-alt me-1"></i> 8 Sedes Estáticas Registradas
    </span>
</div>

<div class="callout-institutional">
    <i class="bi bi-info-circle-fill text-primary me-2"></i>
    <strong>Despliegue Estratégico Territorial:</strong> La red de atención presencial se concentra en 7 puntos satélite en comunas de alta demanda y la <strong>Sede Central Pasoancho (Comuna 17)</strong>, la cual opera como oficina principal de distribución logística y punto de atención. Todas las sedes aparecen fijas en el plano cartográfico de Cali.
</div>

<div class="row g-3">
    
    <!-- Mapa de Sedes Estáticas -->
    <div class="col-lg-6">
        <div class="app-card h-100 d-flex flex-column">
            <div class="app-card-header">
                <h5 class="app-card-title"><i class="bi bi-map me-2"></i>Ubicación Cartográfica Estática en Cali</h5>
                <span class="badge bg-light text-dark border small">Cali Georreferenciada</span>
            </div>
            <div class="app-card-body d-flex flex-column flex-grow-1 p-2">
                <div id="mapaSedes" class="map-container flex-grow-1" style="min-height: 480px;"></div>
                <div class="small text-muted p-2 bg-light border-top mt-2 rounded">
                    <span class="badge bg-danger me-1">&bull;</span> Estrella Roja: Oficina Principal y Punto de Atención (Comuna 17).<br>
                    <span class="badge bg-primary me-1">&bull;</span> Círculos Azules: Puntos de Atención Satélite (Comunas 1, 6, 7, 13, 14, 20, 21).
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de Sedes y Carga Operativa -->
    <div class="col-lg-6">
        <div class="app-card h-100">
            <div class="app-card-header">
                <h5 class="app-card-title"><i class="bi bi-buildings me-2"></i>Directorio de Puntos de Atención</h5>
                <span class="badge bg-light text-dark border small">Operación Urbana</span>
            </div>
            <div class="app-card-body p-0" style="max-height: 560px; overflow-y: auto;">
                <div class="list-group list-group-flush">
                    <?php foreach ($puntos as $pt): ?>
                        <div class="list-group-item p-3 <?= $pt['es_principal'] ? 'bg-primary-subtle border-start border-primary border-4' : '' ?>">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <div>
                                    <span class="badge <?= $pt['es_principal'] ? 'bg-danger' : 'bg-primary' ?> me-1">
                                        <?= e($pt['codigo']) ?>
                                    </span>
                                    <strong class="text-navy"><?= e($pt['nombre']) ?></strong>
                                    <?php if ($pt['es_principal']): ?>
                                        <span class="badge bg-warning text-dark border border-warning ms-1 small">
                                            <i class="bi bi-star-fill me-1 text-danger"></i>Oficina Principal
                                        </span>
                                    <?php endif; ?>
                                </div>
                                <span class="badge bg-light text-dark border">
                                    Comuna <?= (int)$pt['comuna'] ?>
                                </span>
                            </div>

                            <div class="small text-muted mb-2">
                                <i class="bi bi-geo-alt text-danger me-1"></i><?= e($pt['direccion']) ?> &bull; Barrio <?= e($pt['barrio']) ?><br>
                                <i class="bi bi-telephone text-success me-1"></i><?= e($pt['telefono']) ?>
                            </div>

                            <div class="d-flex gap-2 small">
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-file-earmark-text me-1"></i><?= (int)$pt['total_solicitudes'] ?> Solicitudes Radicadas
                                </span>
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-truck me-1"></i><?= (int)$pt['total_hojas_ruta'] ?> Hojas de Ruta
                                </span>
                                <span class="badge bg-light text-secondary border">
                                    <i class="bi bi-people me-1"></i><?= (int)$pt['personal_activo'] ?> Personal
                                </span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const map = L.map('mapaSedes').setView([<?= CALI_LAT_DEFAULT ?>, <?= CALI_LNG_DEFAULT ?>], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        maxZoom: 19,
        attribution: '&copy; OpenStreetMap contributors &bull; SIRCE Cali'
    }).addTo(map);

    const puntos = <?= json_encode($puntos, JSON_UNESCAPED_UNICODE) ?>;

    puntos.forEach(p => {
        const isPrincipal = parseInt(p.es_principal) === 1;

        const icon = L.divIcon({
            className: 'custom-sede-pin',
            html: isPrincipal ? 
                "<div style='background-color:#dc2626;color:#fff;border:2px solid #fff;border-radius:50%;width:36px;height:36px;display:flex;align-items:center;justify-content:center;box-shadow:0 3px 8px rgba(0,0,0,0.4);font-size:16px;'><i class='bi bi-star-fill'></i></div>" :
                "<div style='background-color:#0284c7;color:#fff;border:2px solid #fff;border-radius:50%;width:30px;height:30px;display:flex;align-items:center;justify-content:center;box-shadow:0 2px 5px rgba(0,0,0,0.3);font-size:13px;'><i class='bi bi-building'></i></div>",
            iconSize: isPrincipal ? [36, 36] : [30, 30],
            iconAnchor: isPrincipal ? [18, 18] : [15, 15]
        });

        const m = L.marker([parseFloat(p.latitud), parseFloat(p.longitud)], { icon: icon }).addTo(map);
        m.bindPopup(`
            <div style="font-size:12px; min-width:200px;">
                <strong style="color:#0e2a47;">${p.nombre}</strong><br>
                <span class="badge ${isPrincipal ? 'bg-danger' : 'bg-primary'} text-white" style="font-size:10px;">
                    Comuna ${p.comuna} ${isPrincipal ? ' - Sede Central' : ''}
                </span><br>
                <small>${p.direccion}</small><br>
                <small><i class="bi bi-telephone"></i> ${p.telefono}</small><br>
                <hr style="margin:4px 0;">
                <small><strong>${p.total_solicitudes}</strong> solicitudes &bull; <strong>${p.total_hojas_ruta}</strong> hojas de ruta</small>
            </div>
        `);
    });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
