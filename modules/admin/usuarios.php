<?php
/**
 * Módulo de Administrador - Gestión de Usuarios y Perfiles
 * Requerimiento 1 (0.44 pts): Validación de los 5 perfiles: administrador, ventanilla, encuestador, calidad, enrutador
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

requireRole(['administrador']);

$currentUser = getCurrentUser();
$pdo = getDBConnection();

$errores = [];

// Procesar Creación de Usuario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'crear_usuario') {
    $cedula      = trim($_POST['cedula'] ?? '');
    $nombre      = trim($_POST['nombre_completo'] ?? '');
    $username    = trim($_POST['username'] ?? '');
    $password    = $_POST['password'] ?? '';
    $email       = trim($_POST['email'] ?? '');
    $telefono    = trim($_POST['telefono'] ?? '');
    $rol         = trim($_POST['rol'] ?? '');
    $puntoId     = !empty($_POST['punto_atencion_id']) ? (int)$_POST['punto_atencion_id'] : null;
    $antiguedad  = (int)($_POST['antiguedad_anios'] ?? 4);

    if (empty($cedula) || empty($nombre) || empty($username) || empty($password) || empty($email) || empty($rol)) {
        $errores[] = 'Todos los campos marcados con asterisco (*) son obligatorios.';
    }
    if (!array_key_exists($rol, ROLES_PERMITIDOS)) {
        $errores[] = 'El perfil seleccionado no es válido en el sistema SIRCE.';
    }

    if (empty($errores)) {
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmtInsert = $pdo->prepare('
                INSERT INTO usuarios (
                    cedula, nombre_completo, username, password_hash, email,
                    telefono, rol, punto_atencion_id, antiguedad_anios, activo
                ) VALUES (
                    :cedula, :nombre, :usr, :pwd, :email,
                    :tel, :rol, :punto, :antig, 1
                )
            ');
            $stmtInsert->execute([
                ':cedula' => $cedula,
                ':nombre' => $nombre,
                ':usr'    => $username,
                ':pwd'    => $hash,
                ':email'  => $email,
                ':tel'    => $telefono,
                ':rol'    => $rol,
                ':punto'  => $puntoId,
                ':antig'  => $antiguedad
            ]);

            registrarBitacora(
                $currentUser['id'],
                $currentUser['rol'],
                'ADMINISTRACION',
                'CREACION_USUARIO',
                "Nuevo usuario registrado: $username con perfil $rol."
            );

            setFlash('success', "¡Usuario <strong>$username</strong> registrado exitosamente con perfil <strong>" . strtoupper($rol) . "</strong>!");
            redirect('modules/admin/usuarios.php');

        } catch (Exception $e) {
            $errores[] = 'Error al registrar usuario: ' . $e->getMessage();
        }
    }
}

// Consultar usuarios
$stmtUsers = $pdo->query('
    SELECT u.*, p.nombre AS punto_nombre, p.comuna AS punto_comuna
    FROM usuarios u
    LEFT JOIN puntos_atencion p ON u.punto_atencion_id = p.id
    ORDER BY u.rol ASC, u.nombre_completo ASC
');
$usuarios = $stmtUsers->fetchAll();

// Puntos de atención
$puntosList = $pdo->query('SELECT id, nombre, comuna FROM puntos_atencion WHERE activo = 1 ORDER BY es_principal DESC, comuna ASC')->fetchAll();

$pageTitle = 'Personal & Perfiles de Usuario';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h4 class="fw-bold mb-0 text-navy">Personal Institucional & Perfiles de Usuario</h4>
        <p class="text-muted small mb-0">Requerimiento 1 &bull; Perfiles: Administrador, Ventanilla, Enrutador, Encuestador, Calidad</p>
    </div>
    <button type="button" class="btn btn-primary btn-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalNuevoUsuario" style="background-color: #0e2a47; border-color: #0e2a47;">
        <i class="bi bi-person-plus-fill me-1"></i> Registrar Nuevo Usuario
    </button>
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

<div class="row g-2 mb-3">
    <?php foreach (ROLES_PERMITIDOS as $rolKey => $rolNombre): ?>
        <?php
        $count = count(array_filter($usuarios, fn($u) => $u['rol'] === $rolKey));
        ?>
        <div class="col">
            <div class="bg-white border rounded p-2 text-center shadow-sm">
                <span class="user-role-badge role-<?= $rolKey ?> d-inline-block mb-1"><?= $rolKey ?></span>
                <div class="small fw-semibold text-dark"><?= (int)$count ?> registrados</div>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<div class="app-card">
    <div class="app-card-header">
        <h5 class="app-card-title"><i class="bi bi-people-fill me-2"></i>Directorio de Usuarios del Sistema (<?= count($usuarios) ?>)</h5>
        <span class="badge bg-light text-dark border small">Control RBAC Activo</span>
    </div>
    <div class="app-card-body p-0">
        <div class="table-responsive">
            <table class="table table-app table-hover mb-0">
                <thead>
                    <tr>
                        <th>Cédula</th>
                        <th>Nombre Completo</th>
                        <th>Usuario (Login)</th>
                        <th>Perfil Asignado</th>
                        <th>Sede Asignada</th>
                        <th>Contacto</th>
                        <th>Antigüedad</th>
                        <th>Último Acceso</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td class="font-monospace small"><?= e($u['cedula']) ?></td>
                            <td>
                                <strong><?= e($u['nombre_completo']) ?></strong>
                            </td>
                            <td>
                                <code class="text-primary"><?= e($u['username']) ?></code>
                            </td>
                            <td>
                                <span class="user-role-badge role-<?= e($u['rol']) ?>">
                                    <?= e($u['rol']) ?>
                                </span>
                            </td>
                            <td>
                                <small><?= e($u['punto_nombre'] ?? 'Oficina Principal') ?></small>
                            </td>
                            <td>
                                <small class="d-block"><?= e($u['email']) ?></small>
                                <small class="text-muted"><i class="bi bi-phone me-1"></i><?= e($u['telefono']) ?></small>
                            </td>
                            <td>
                                <small><?= (int)$u['antiguedad_anios'] ?> años</small>
                            </td>
                            <td>
                                <small class="text-muted"><?= formatearFecha($u['ultimo_acceso'], true) ?></small>
                            </td>
                            <td>
                                <span class="badge bg-success-subtle text-success border border-success small">
                                    Activo
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal para Crear Nuevo Usuario -->
<div class="modal fade" id="modalNuevoUsuario" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="usuarios.php">
                <input type="hidden" name="accion" value="crear_usuario">
                <div class="modal-header py-2 bg-light">
                    <h6 class="modal-title fw-bold text-navy"><i class="bi bi-person-plus me-1 text-primary"></i>Registrar Nuevo Usuario</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-3">
                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small">Cédula de Ciudadanía <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="cedula" placeholder="Ej: 1144987654" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small">Nombre Completo <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="nombre_completo" placeholder="Ej: Javier Estupiñán" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small">Nombre de Usuario (Login) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" name="username" placeholder="Ej: javier.estupinan" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small">Contraseña Inicial <span class="text-danger">*</span></label>
                            <input type="password" class="form-control form-control-sm" name="password" placeholder="Contraseña de acceso" required>
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small">Correo Institucional <span class="text-danger">*</span></label>
                            <input type="email" class="form-control form-control-sm" name="email" placeholder="ejemplo@encuestascali.gov.co" required>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small">Celular / Teléfono</label>
                            <input type="tel" class="form-control form-control-sm" name="telefono" placeholder="Ej: 3181234567">
                        </div>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-sm-6">
                            <label class="form-label small">Perfil de Usuario (Rol) <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" name="rol" required>
                                <option value="">-- Seleccione Perfil --</option>
                                <?php foreach (ROLES_PERMITIDOS as $rk => $rNom): ?>
                                    <option value="<?= $rk ?>"><?= $rNom ?> (<?= $rk ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-sm-6">
                            <label class="form-label small">Sede / Punto de Atención</label>
                            <select class="form-select form-select-sm" name="punto_atencion_id">
                                <option value="">-- Oficina Principal (Comuna 17) --</option>
                                <?php foreach ($puntosList as $pl): ?>
                                    <option value="<?= $pl['id'] ?>"><?= e($pl['nombre']) ?> (Comuna <?= $pl['comuna'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-2">
                        <label class="form-label small">Antigüedad en la Empresa (Años)</label>
                        <input type="number" class="form-control form-control-sm" name="antiguedad_anios" min="0" max="30" value="4">
                    </div>

                </div>
                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary fw-semibold" style="background-color: #0e2a47; border-color: #0e2a47;">
                        <i class="bi bi-save me-1"></i> Guardar Usuario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
