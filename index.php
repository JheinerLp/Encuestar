<?php
/**
 * Punto de Entrada Principal SIRCE Cali
 */

declare(strict_types=1);

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/auth.php';

if (!isAuthenticated()) {
    redirect('modules/auth/login.php');
}

$currentUser = getCurrentUser();
$rol = $currentUser['rol'] ?? 'administrador';

switch ($rol) {
    case 'administrador':
        redirect('modules/admin/dashboard.php');
        break;
    case 'ventanilla':
        redirect('modules/ventanilla/radicar.php');
        break;
    case 'enrutador':
        redirect('modules/enrutador/bitacora.php');
        break;
    case 'encuestador':
        redirect('modules/encuestador/mi_hoja_ruta.php');
        break;
    case 'calidad':
        redirect('modules/calidad/revision.php');
        break;
    default:
        redirect('modules/auth/login.php');
        break;
}
