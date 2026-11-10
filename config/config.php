<?php
/**
 * Configuración General del Sistema y Helpers
 * SIRCE Cali - Sistema de Encuestas
 */

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Zona horaria de Colombia
date_default_timezone_set('America/Bogota');

// Constantes generales
define('APP_NAME', 'SIRCE Cali');
define('APP_FULL_NAME', 'Sistema Integrado de Radicación, Ruteo y Calidad de Encuestas');
define('APP_INSTITUTION', 'Alcaldía de Santiago de Cali - Subdirección de Proyectos');
define('BASE_URL', '/Encuesta');
define('SLA_DIAS_CALENDARIO', 15); // Política estricta institucional

// Roles disponibles en el sistema (Punto 1 del requerimiento)
const ROLES_PERMITIDOS = [
    'administrador' => 'Administrador General',
    'ventanilla'    => 'Atención en Ventanilla',
    'enrutador'     => 'Enrutador / Logística',
    'encuestador'   => 'Encuestador de Campo',
    'calidad'       => 'Control y Aseguramiento de Calidad'
];

/**
 * Retorna la URL absoluta dentro de la aplicación
 */
function url(string $path = ''): string {
    $path = ltrim($path, '/');
    return BASE_URL . ($path !== '' ? '/' . $path : '');
}

/**
 * Redirección HTTP segura
 */
function redirect(string $path): void {
    header('Location: ' . url($path));
    exit;
}

/**
 * Mensajes Flash para retroalimentación al usuario
 */
function setFlash(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlash(): ?array {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Evalúa el estado del SLA de 15 días calendario para una solicitud
 * Retorna un array con: dias_transcurridos, dias_restantes, estado_sla ('a_tiempo', 'alerta', 'vencido') y clase CSS
 */
function calcularEstadoSLA(string $fechaRadicacion, ?string $estadoSolicitud = null): array {
    $fechaRad = new DateTime($fechaRadicacion);
    $fechaHoy = new DateTime();
    $fechaLimite = (clone $fechaRad)->modify('+' . SLA_DIAS_CALENDARIO . ' days');

    $intervaloTranscurrido = $fechaRad->diff($fechaHoy);
    $diasTranscurridos = (int)$intervaloTranscurrido->format('%r%a');
    if ($diasTranscurridos < 0) {
        $diasTranscurridos = 0;
    }

    $diasRestantes = SLA_DIAS_CALENDARIO - $diasTranscurridos;

    // Si ya fue completada (aprobada o rechazada por calidad)
    $esFinalizada = in_array($estadoSolicitud, ['aprobada_apto', 'rechazada_no_apto'], true);

    if ($diasRestantes < 0 || $estadoSolicitud === 'vencida') {
        $estado = 'vencido';
        $etiqueta = 'Vencido (' . abs($diasRestantes) . ' días de retraso)';
        $badgeClass = 'badge-danger';
        $borderClass = 'sla-overdue';
    } elseif ($diasRestantes <= 4) {
        $estado = 'alerta';
        $etiqueta = 'Alerta crítica (' . $diasRestantes . ' días restantes)';
        $badgeClass = 'badge-warning';
        $borderClass = 'sla-warning';
    } else {
        $estado = 'a_tiempo';
        $etiqueta = 'A tiempo (' . $diasRestantes . ' días restantes)';
        $badgeClass = 'badge-success';
        $borderClass = 'sla-ok';
    }

    if ($esFinalizada) {
        $estado = 'finalizada';
        $etiqueta = 'Atendida (' . $diasTranscurridos . ' días)';
        $badgeClass = 'badge-neutral';
        $borderClass = 'sla-done';
    }

    return [
        'dias_transcurridos' => $diasTranscurridos,
        'dias_restantes'     => $diasRestantes,
        'fecha_limite'       => $fechaLimite->format('Y-m-d'),
        'estado_sla'         => $estado,
        'etiqueta'           => $etiqueta,
        'badge_class'        => $badgeClass,
        'border_class'       => $borderClass
    ];
}

/**
 * Formatea fechas para visualización en español
 */
function formatearFecha(?string $fecha, bool $incluirHora = false): string {
    if (!$fecha) return '-';
    $timestamp = strtotime($fecha);
    if ($timestamp === false) return $fecha;

    $meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    $mes = $meses[(int)date('n', $timestamp) - 1];

    if ($incluirHora) {
        return sprintf('%s %s %s, %s', date('d', $timestamp), $mes, date('Y', $timestamp), date('h:i A', $timestamp));
    }
    return sprintf('%s %s %s', date('d', $timestamp), $mes, date('Y', $timestamp));
}

/**
 * Sanitizar texto para output HTML
 */
function e(?string $text): string {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}
