<?php
/**
 * Login page for both administrators and technicians
 */
require_once 'includes/init.php';

// Redirect if already logged in
if ($auth->isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? 'admin';
    
    // Validate input
    if (empty($email) || empty($password)) {
        $error = 'Por favor, ingrese su correo electrónico y contraseña.';
    } else {
        // Attempt login
        Logger::info('Intento de login', [
            'email' => $email,
            'role' => $role,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        if ($auth->login($email, $password, $role)) {
            // Redirect based on role
            if ($role === 'admin') {
                redirect('admin/dashboard.php');
            } else {
                redirect('technician/dashboard.php');
            }
        } else {
            $error = 'Credenciales inválidas. Por favor, intente nuevamente.';
        }
    }
}

// Set active role tab
$activeRole = $_GET['role'] ?? 'admin';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('auth.login', 'Iniciar Sesión'); ?> - Sistema de Gestión de Tickets para Ascensores</title>
    <!-- Poppins self-hosted: removed Google Fonts -->
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png">
    <?php
        $poppinsCssV = @filemtime(BASE_PATH . '/assets/css/poppins.css') ?: time();
        $styleCssV = @filemtime(BASE_PATH . '/assets/css/style.css') ?: time();
    ?>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/poppins.css?v=<?php echo $poppinsCssV; ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo $styleCssV; ?>">
</head>
<body class="dark-mode login-page">
    <div class="container">
        <div class="auth-container">
            <div class="auth-logo">
                <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="TecLocator Logo" style="max-height:60px">
            </div>

            <!-- Language switch -->
            <div class="d-flex justify-content-center align-items-center gap-2 mb-3">
                <span class="small"><?php echo __('language.es', 'Español'); ?></span>
                <div class="form-check form-switch m-0">
                    <input class="form-check-input" type="checkbox" id="langSwitchLogin" <?php echo (($_SESSION['lang'] ?? 'es') === 'en') ? 'checked' : ''; ?>>
                </div>
                <span class="small"><?php echo __('language.en', 'Inglés'); ?></span>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" role="alert">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <div class="card">
                <div class="card-body">
                    <!-- Role tabs -->
                    <ul class="nav nav-tabs auth-tabs" id="authTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeRole === 'admin' ? 'active' : ''; ?>" 
                                    id="admin-tab" data-bs-toggle="tab" data-bs-target="#admin-login" 
                                    type="button" role="tab" aria-controls="admin-login" aria-selected="<?php echo $activeRole === 'admin' ? 'true' : 'false'; ?>">
                                <?php echo __('sidebar.role.admin', 'Administrador'); ?>
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link <?php echo $activeRole === 'technician' ? 'active' : ''; ?>" 
                                    id="technician-tab" data-bs-toggle="tab" data-bs-target="#technician-login" 
                                    type="button" role="tab" aria-controls="technician-login" aria-selected="<?php echo $activeRole === 'technician' ? 'true' : 'false'; ?>">
                                <?php echo __('sidebar.role.technician', 'Técnico'); ?>
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab content -->
                    <div class="tab-content" id="authTabsContent">
                        <!-- Admin login form -->
                        <div class="tab-pane fade <?php echo $activeRole === 'admin' ? 'show active' : ''; ?>" 
                             id="admin-login" role="tabpanel" aria-labelledby="admin-tab">
                            <form method="post" action="login.php" class="mt-4">
                                <input type="hidden" name="role" value="admin">
                                
                                <div class="mb-3">
                                    <label for="admin-email" class="form-label"><?php echo __('common.email', 'Correo Electrónico'); ?></label>
                                    <input type="email" class="form-control" id="admin-email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="admin-password" class="form-label"><?php echo __('common.password', 'Contraseña'); ?></label>
                                    <input type="password" class="form-control" id="admin-password" name="password" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary"><?php echo __('auth.login', 'Iniciar Sesión'); ?></button>
                                </div>
                            </form>
                        </div>
                        
                        <!-- Technician login form -->
                        <div class="tab-pane fade <?php echo $activeRole === 'technician' ? 'show active' : ''; ?>" 
                             id="technician-login" role="tabpanel" aria-labelledby="technician-tab">
                            <form method="post" action="login.php" class="mt-4">
                                <input type="hidden" name="role" value="technician">
                                
                                <div class="mb-3">
                                    <label for="technician-email" class="form-label"><?php echo __('common.email', 'Correo Electrónico'); ?></label>
                                    <input type="email" class="form-control" id="technician-email" name="email" required>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="technician-password" class="form-label"><?php echo __('common.password', 'Contraseña'); ?></label>
                                    <input type="password" class="form-control" id="technician-password" name="password" required>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary"><?php echo __('auth.login', 'Iniciar Sesión'); ?></button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Login: Iniciando script de cambio de idioma');
    var sw = document.getElementById('langSwitchLogin');
    console.log('Login: Switch encontrado:', sw);
    if (sw) {
        sw.addEventListener('change', function(){
            var lang = this.checked ? 'en' : 'es';
            console.log('Login: Cambiando idioma a:', lang);
            var url = '<?php echo BASE_URL; ?>set_language.php?lang=' + lang;
            console.log('Login: Navegando a:', url);
            window.location.href = url;
        });
    } else {
        console.error('Login: No se encontró el elemento langSwitchLogin');
    }
});
</script>
</body>
</html>
