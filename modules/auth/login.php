<?php
/**
 * Módulo de Autenticación - Login de Usuarios
 * Requerimiento 1: Validación de perfiles: administrador, ventanilla, encuestador, calidad, enrutador
 */

declare(strict_types=1);

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/auth.php';

if (isAuthenticated()) {
    redirect('index.php');
}

$error = null;
$username = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Por favor ingrese su usuario institucional y contraseña.';
    } else {
        $user = authenticateUser($username, $password);
        if ($user) {
            loginSession($user);
            setFlash('success', 'Bienvenido al sistema SIRCE, ' . htmlspecialchars($user['nombre_completo']) . ' (' . htmlspecialchars(strtoupper($user['rol'])) . ').');
            redirect('index.php');
        } else {
            $error = 'Credenciales no válidas o usuario inactivo. Verifique sus datos o use los accesos de prueba.';
        }
    }
}

$pageTitle = 'Ingreso al Sistema';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="row justify-content-center my-4">
    <div class="col-md-9 col-lg-7 col-xl-6">

        <div class="text-center mb-4">
            <div class="d-inline-flex align-items-center justify-content-center bg-white p-3 rounded-circle shadow-sm mb-2" style="width: 70px; height: 70px;">
                <i class="bi bi-shield-lock text-primary fs-1"></i>
            </div>
            <h3 class="fw-bold text-navy mb-1" style="color: #0e2a47;">Acceso al Sistema SIRCE</h3>
            <p class="text-muted small">Alcaldía de Santiago de Cali &bull; Control y Levantamiento de Encuestas</p>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger d-flex align-items-center gap-2 mb-3 shadow-sm">
                <i class="bi bi-exclamation-triangle-fill fs-5"></i>
                <div><?= e($error) ?></div>
            </div>
        <?php endif; ?>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body p-4">
                <form method="POST" action="login.php" autocomplete="off">
                    <div class="mb-3">
                        <label for="username" class="form-label">Usuario Institucional</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" class="form-control" id="username" name="username" value="<?= e($username) ?>" placeholder="Ej: admin, ventanilla1, enrutador1" required autofocus>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="password" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-key"></i></span>
                            <input type="password" class="form-control" id="password" name="password" placeholder="Contraseña de acceso" required>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 py-2 fw-semibold" style="background-color: #0e2a47; border-color: #0e2a47;">
                        <i class="bi bi-box-arrow-in-right me-1"></i> Ingresar al Sistema
                    </button>
                </form>
            </div>
        </div>

        <!-- Panel de Accesos Rápidos para Sustentación / Evaluación de los 5 Roles -->
        <div class="card border border-light-subtle shadow-sm bg-white mb-3">
            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                <span class="fw-bold small text-dark">
                    <i class="bi bi-people-fill text-primary me-1"></i> Perfiles Requeridos (Acceso Rápido para Sustentación)
                </span>
                <span class="badge bg-secondary-subtle text-dark border small">Requerimiento 1 (0.44 pts)</span>
            </div>
            <div class="card-body p-3">
                <p class="small text-muted mb-2">
                    Haga clic en cualquiera de los 5 perfiles requeridos para iniciar sesión instantáneamente y evaluar sus funcionalidades:
                </p>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <a href="switch_role.php?demo_user=admin" class="btn btn-sm btn-outline-danger w-100 text-start py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <strong>1. Administrador</strong><br>
                                <span class="small text-muted">admin / admin123</span>
                            </div>
                            <i class="bi bi-arrow-right-circle fs-5"></i>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="switch_role.php?demo_user=ventanilla1" class="btn btn-sm btn-outline-primary w-100 text-start py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <strong>2. Ventanilla</strong><br>
                                <span class="small text-muted">ventanilla1 / ventanilla123</span>
                            </div>
                            <i class="bi bi-arrow-right-circle fs-5"></i>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="switch_role.php?demo_user=enrutador1" class="btn btn-sm btn-outline-warning text-dark w-100 text-start py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <strong>3. Enrutador</strong><br>
                                <span class="small text-muted">enrutador1 / enrutador123</span>
                            </div>
                            <i class="bi bi-arrow-right-circle fs-5"></i>
                        </a>
                    </div>
                    <div class="col-sm-6">
                        <a href="switch_role.php?demo_user=encuestador1" class="btn btn-sm btn-outline-success w-100 text-start py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <strong>4. Encuestador</strong><br>
                                <span class="small text-muted">encuestador1 / encuestador123</span>
                            </div>
                            <i class="bi bi-arrow-right-circle fs-5"></i>
                        </a>
                    </div>
                    <div class="col-12">
                        <a href="switch_role.php?demo_user=calidad1" class="btn btn-sm btn-outline-secondary w-100 text-start py-2 d-flex justify-content-between align-items-center" style="border-color:#7c3aed; color:#7c3aed;">
                            <div>
                                <strong>5. Calidad (Equipo Revisor)</strong><br>
                                <span class="small text-muted">calidad1 / calidad123 &bull; Emisión de dictamen Apto / No Apto</span>
                            </div>
                            <i class="bi bi-arrow-right-circle fs-5"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Descarga Directa del Informe Oficial de Entrega 1 en PDF -->
        <div class="card border-primary-subtle shadow-sm bg-primary-subtle text-dark">
            <div class="card-body p-3 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-file-earmark-pdf-fill text-danger fs-2"></i>
                    <div>
                        <div class="fw-bold text-navy" style="font-size: 0.95rem;">Informe Técnico y Metodológico (Entrega 1)</div>
                        <div class="small text-muted">Scrum, Historias de Usuario, MER 3FN, DevOps (Git, Docker, CI, Jira) & Pruebas</div>
                    </div>
                </div>
                <a href="<?= url('docs/INFORME_ENTREGA_1_SIRCE_CALI.pdf') ?>" target="_blank" class="btn btn-danger btn-sm px-3 py-2 fw-semibold shadow-sm">
                    <i class="bi bi-download me-1"></i> Descargar Informe (PDF)
                </a>
            </div>
        </div>

    </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
