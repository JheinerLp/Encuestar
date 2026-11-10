<?php
/**
 * Módulo de Autenticación y Control de Acceso basado en Roles (RBAC)
 * Requerimiento 1
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/config.php';

function authenticateUser(string $username, string $password): ?array {
    $pdo = getDBConnection();
    $stmt = $pdo->prepare('
        SELECT u.*, p.nombre AS punto_nombre, p.comuna AS punto_comuna
        FROM usuarios u
        LEFT JOIN puntos_atencion p ON u.punto_atencion_id = p.id
        WHERE u.username = :username AND u.activo = 1
        LIMIT 1
    ');
    $stmt->execute([':username' => $username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        // Actualizar último acceso
        $upd = $pdo->prepare('UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id');
        $upd->execute([':id' => $user['id']]);

        // Registrar en bitácora
        registrarBitacora(
            (int)$user['id'],
            $user['rol'],
            'AUTENTICACION',
            'LOGIN_EXITOSO',
            'Inicio de sesión del usuario ' . $user['username'] . ' con perfil ' . $user['rol']
        );

        return $user;
    }

    return null;
}

function loginSession(array $user): void {
    $_SESSION['user_id']           = (int)$user['id'];
    $_SESSION['username']          = $user['username'];
    $_SESSION['nombre_completo']   = $user['nombre_completo'];
    $_SESSION['rol']               = $user['rol'];
    $_SESSION['punto_atencion_id'] = $user['punto_atencion_id'] ? (int)$user['punto_atencion_id'] : null;
    $_SESSION['punto_nombre']      = $user['punto_nombre'] ?? 'Sede no asignada';
    $_SESSION['punto_comuna']      = $user['punto_comuna'] ?? null;
    $_SESSION['login_time']        = time();
}

function logoutSession(): void {
    if (isset($_SESSION['user_id'])) {
        registrarBitacora(
            (int)$_SESSION['user_id'],
            $_SESSION['rol'] ?? 'desconocido',
            'AUTENTICACION',
            'LOGOUT',
            'Cierre de sesión voluntario'
        );
    }
    $_SESSION = [];
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    session_destroy();
}

function isAuthenticated(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser(): ?array {
    if (!isAuthenticated()) {
        return null;
    }
    return [
        'id'                => $_SESSION['user_id'],
        'username'          => $_SESSION['username'],
        'nombre_completo'   => $_SESSION['nombre_completo'],
        'rol'               => $_SESSION['rol'],
        'punto_atencion_id' => $_SESSION['punto_atencion_id'],
        'punto_nombre'      => $_SESSION['punto_nombre'] ?? null,
        'punto_comuna'      => $_SESSION['punto_comuna'] ?? null,
    ];
}

function hasRole($roles): bool {
    if (!isAuthenticated()) {
        return false;
    }
    if (is_string($roles)) {
        $roles = [$roles];
    }
    return in_array($_SESSION['rol'], $roles, true);
}

function requireAuth(): void {
    if (!isAuthenticated()) {
        setFlash('warning', 'Debe iniciar sesión para acceder al sistema.');
        redirect('modules/auth/login.php');
    }
}

function requireRole($roles): void {
    requireAuth();
    if (!hasRole($roles)) {
        setFlash('error', 'Acceso denegado: Su perfil actual (' . htmlspecialchars($_SESSION['rol']) . ') no cuenta con los permisos requeridos para esta sección.');
        redirect('index.php');
    }
}

/**
 * Registro de eventos en la bitácora de auditoría
 */
function registrarBitacora(?int $usuarioId, ?string $rol, string $modulo, string $accion, string $detalle): void {
    try {
        $pdo = getDBConnection();
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $stmt = $pdo->prepare('
            INSERT INTO bitacora_eventos (usuario_id, rol_usuario, modulo, accion, detalle, ip_origen)
            VALUES (:uid, :rol, :modulo, :accion, :detalle, :ip)
        ');
        $stmt->execute([
            ':uid'     => $usuarioId,
            ':rol'     => $rol,
            ':modulo'  => $modulo,
            ':accion'  => $accion,
            ':detalle' => $detalle,
            ':ip'      => $ip
        ]);
    } catch (Exception $e) {
        error_log('Error registrando bitacora: ' . $e->getMessage());
    }
}
