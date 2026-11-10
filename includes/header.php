<?php
/**
 * Cabecera Común Institucional SIRCE Cali
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/auth.php';

$currentUser = getCurrentUser();
$flash = getFlash();
$currentScript = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' | ' . APP_NAME : APP_NAME . ' - ' . APP_FULL_NAME ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- Leaflet CSS para Mapas -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    <!-- Estilos Institucionales Propios -->
    <link rel="stylesheet" href="<?= url('assets/css/styles.css') ?>">
</head>
<body>

    <!-- Franja Superior Institucional -->
    <div class="top-header-strip d-flex justify-content-between align-items-center">
        <div>
            <i class="bi bi-geo-alt-fill text-danger me-1"></i>
            <strong>SANTIAGO DE CALI</strong> &bull; Sistema Integrado de Radicación, Ruteo y Calidad de Encuestas
        </div>
        <div class="d-none d-md-flex align-items-center gap-3">
            <span><i class="bi bi-clock-history me-1"></i> SLA Normativo: <strong>15 días calendario</strong></span>
            <span>&bull;</span>
            <span><?= date('d/m/Y') ?></span>
        </div>
    </div>

    <!-- Navegación Principal -->
    <nav class="navbar navbar-expand-lg navbar-dark main-navbar">
        <div class="container-fluid px-lg-4">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?= url('index.php') ?>">
                <div class="bg-white text-dark rounded px-2 py-1 fw-bold" style="font-size: 0.9rem; letter-spacing: 0.5px;">
                    SIRCE
                </div>
                <div>
                    <span class="navbar-brand-title">SIRCE CALI</span>
                    <span class="navbar-brand-subtitle">Control Operativo &bull; Comunas 1 a 22 y Zona Rural</span>
                </div>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSirce">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSirce">
                <?php if ($currentUser): ?>
                    <ul class="navbar-nav me-auto mb-2 mb-lg-0 ms-lg-3">
                        
                        <!-- Módulos de Administrador -->
                        <?php if (hasRole(['administrador'])): ?>
                            <li class="nav-nav-item">
                                <a class="nav-link <?= $currentScript === 'dashboard.php' ? 'active' : '' ?>" href="<?= url('modules/admin/dashboard.php') ?>">
                                    <i class="bi bi-speedometer2 me-1"></i> Dashboard
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'puntos_atencion.php' ? 'active' : '' ?>" href="<?= url('modules/admin/puntos_atencion.php') ?>">
                                    <i class="bi bi-geo-alt me-1"></i> Puntos de Atención
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'usuarios.php' ? 'active' : '' ?>" href="<?= url('modules/admin/usuarios.php') ?>">
                                    <i class="bi bi-people me-1"></i> Personal & Perfiles
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'reportes_pdf.php' ? 'active' : '' ?>" href="<?= url('modules/admin/reportes_pdf.php') ?>">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Informes PDF
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Módulos de Ventanilla -->
                        <?php if (hasRole(['ventanilla', 'administrador'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'radicar.php' ? 'active' : '' ?>" href="<?= url('modules/ventanilla/radicar.php') ?>">
                                    <i class="bi bi-file-earmark-plus me-1"></i> Radicar Solicitud
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'solicitudes.php' ? 'active' : '' ?>" href="<?= url('modules/ventanilla/solicitudes.php') ?>">
                                    <i class="bi bi-card-checklist me-1"></i> Solicitudes Sede
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'entrega_hojas.php' ? 'active' : '' ?>" href="<?= url('modules/ventanilla/entrega_hojas.php') ?>">
                                    <i class="bi bi-journal-arrow-down me-1"></i> Entrega Hojas de Ruta
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Módulos de Enrutador -->
                        <?php if (hasRole(['enrutador', 'administrador'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'bitacora.php' ? 'active' : '' ?>" href="<?= url('modules/enrutador/bitacora.php') ?>">
                                    <i class="bi bi-journal-text me-1"></i> Bitácora Diaria
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'crear_hoja_ruta.php' ? 'active' : '' ?>" href="<?= url('modules/enrutador/crear_hoja_ruta.php') ?>">
                                    <i class="bi bi-signpost-split me-1"></i> Armar Hoja de Ruta
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'hojas_ruta.php' ? 'active' : '' ?>" href="<?= url('modules/enrutador/hojas_ruta.php') ?>">
                                    <i class="bi bi-truck me-1"></i> Hojas Programadas
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Módulos de Encuestador -->
                        <?php if (hasRole(['encuestador', 'administrador'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'mi_hoja_ruta.php' ? 'active' : '' ?>" href="<?= url('modules/encuestador/mi_hoja_ruta.php') ?>">
                                    <i class="bi bi-tablet me-1"></i> Mi Hoja de Ruta (Campo)
                                </a>
                            </li>
                        <?php endif; ?>

                        <!-- Módulos de Calidad -->
                        <?php if (hasRole(['calidad', 'administrador'])): ?>
                            <li class="nav-item">
                                <a class="nav-link <?= $currentScript === 'revision.php' ? 'active' : '' ?>" href="<?= url('modules/calidad/revision.php') ?>">
                                    <i class="bi bi-patch-check me-1"></i> Mesa de Control Calidad
                                </a>
                            </li>
                        <?php endif; ?>
                    </ul>

                    <!-- Panel de Usuario / Selector Rápido de Rol para Sustentación -->
                    <div class="d-flex align-items-center gap-2">
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light dropdown-toggle d-flex align-items-center gap-2" type="button" data-bs-toggle="dropdown">
                                <span class="user-role-badge role-<?= e($currentUser['rol']) ?>">
                                    <?= e($currentUser['rol']) ?>
                                </span>
                                <span class="d-none d-xl-inline"><?= e($currentUser['nombre_completo']) ?></span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow">
                                <li><h6 class="dropdown-header">Usuario Conectado</h6></li>
                                <li>
                                    <span class="dropdown-item-text small">
                                        <strong><?= e($currentUser['nombre_completo']) ?></strong><br>
                                        <span class="text-muted"><?= e($currentUser['email'] ?? '') ?></span><br>
                                        <span class="badge bg-light text-dark border mt-1">Sede: <?= e($currentUser['punto_nombre'] ?? 'Oficina Central') ?></span>
                                    </span>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li><h6 class="dropdown-header text-primary"><i class="bi bi-arrow-repeat me-1"></i>Cambio Rápido para Sustentación</h6></li>
                                <li><a class="dropdown-item small" href="<?= url('modules/auth/switch_role.php?demo_user=admin') ?>"><i class="bi bi-shield-lock me-1"></i> Ver como Administrador</a></li>
                                <li><a class="dropdown-item small" href="<?= url('modules/auth/switch_role.php?demo_user=ventanilla1') ?>"><i class="bi bi-person-workspace me-1"></i> Ver como Ventanilla</a></li>
                                <li><a class="dropdown-item small" href="<?= url('modules/auth/switch_role.php?demo_user=enrutador1') ?>"><i class="bi bi-compass me-1"></i> Ver como Enrutador</a></li>
                                <li><a class="dropdown-item small" href="<?= url('modules/auth/switch_role.php?demo_user=encuestador1') ?>"><i class="bi bi-tablet me-1"></i> Ver como Encuestador</a></li>
                                <li><a class="dropdown-item small" href="<?= url('modules/auth/switch_role.php?demo_user=calidad1') ?>"><i class="bi bi-patch-check me-1"></i> Ver como Calidad</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <a class="dropdown-item text-danger small" href="<?= url('modules/auth/logout.php') ?>">
                                        <i class="bi bi-box-arrow-right me-1"></i> Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="ms-auto">
                        <a href="<?= url('modules/auth/login.php') ?>" class="btn btn-sm btn-outline-light">
                            <i class="bi bi-box-arrow-in-right me-1"></i> Iniciar Sesión
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Alertas Flash -->
    <div class="container-fluid px-lg-4 mt-3">
        <?php if ($flash): ?>
            <?php
            $alertClass = match($flash['type']) {
                'success' => 'alert-success',
                'error'   => 'alert-danger',
                'warning' => 'alert-warning',
                default   => 'alert-info'
            };
            $iconClass = match($flash['type']) {
                'success' => 'bi-check-circle-fill text-success',
                'error'   => 'bi-exclamation-triangle-fill text-danger',
                'warning' => 'bi-exclamation-circle-fill text-warning',
                default   => 'bi-info-circle-fill text-info'
            };
            ?>
            <div class="alert <?= $alertClass ?> alert-dismissible fade show d-flex align-items-center gap-2 shadow-sm" role="alert">
                <i class="bi <?= $iconClass ?> fs-5"></i>
                <div><?= e($flash['message']) ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        <?php endif; ?>
    </div>

    <!-- Contenedor Principal de Contenido -->
    <main class="main-content">
        <div class="container-fluid px-lg-4">
