<?php
/**
 * Cambio Rápido de Rol para Sustentación y Evaluación
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

$demoUser = $_GET['demo_user'] ?? '';

$allowedDemo = ['admin', 'ventanilla1', 'ventanilla2', 'enrutador1', 'encuestador1', 'calidad1'];

if (in_array($demoUser, $allowedDemo, true)) {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('
        SELECT u.*, p.nombre AS punto_nombre, p.comuna AS punto_comuna
        FROM usuarios u
        LEFT JOIN puntos_atencion p ON u.punto_atencion_id = p.id
        WHERE u.username = :username AND u.activo = 1
        LIMIT 1
    ');
    $stmt->execute([':username' => $demoUser]);
    $user = $stmt->fetch();

    if ($user) {
        loginSession($user);
        setFlash('success', 'Sesión iniciada como ' . htmlspecialchars($user['nombre_completo']) . ' [Perfil: ' . strtoupper($user['rol']) . ']');
        redirect('index.php');
    }
}

setFlash('error', 'Usuario demo no válido.');
redirect('modules/auth/login.php');
