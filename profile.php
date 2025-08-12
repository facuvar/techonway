<?php
/**
 * User profile page
 */
require_once 'includes/init.php';

// Require authentication
$auth->requireLogin();

// Get database connection
$db = Database::getInstance();

// Get current user data
$userId = $_SESSION['user_id'];
$user = $db->selectOne("SELECT * FROM users WHERE id = ?", [$userId]);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $userData = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? ''
        ];
        
        // Validate required fields
        if (empty($userData['name']) || empty($userData['email'])) {
            flash('Por favor, complete todos los campos obligatorios.', 'danger');
        } else {
            // Check if email already exists (for email changes)
            $existingUser = $db->selectOne(
                "SELECT * FROM users WHERE email = ? AND id != ?", 
                [$userData['email'], $userId]
            );
            
            if ($existingUser) {
                flash('El correo electrónico ya está en uso.', 'danger');
            } else {
                // Update user data
                $db->update('users', $userData, 'id = ?', [$userId]);
                flash('Perfil actualizado correctamente.', 'success');
                
                // Update session data
                $_SESSION['user_name'] = $userData['name'];
                $_SESSION['user_email'] = $userData['email'];
                
                // Reload user data
                $user = $db->selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
            }
        }
    }
    
    // Handle password change
    if (isset($_POST['change_password'])) {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
            flash('Por favor, complete todos los campos de contraseña.', 'danger');
        } elseif ($newPassword !== $confirmPassword) {
            flash('Las contraseñas nuevas no coinciden.', 'danger');
        } elseif (!password_verify($currentPassword, $user['password'])) {
            flash('La contraseña actual es incorrecta.', 'danger');
        } else {
            // Update password
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
            flash('Contraseña actualizada correctamente.', 'success');
        }
    }
    
    // Handle avatar upload
    if (isset($_POST['upload_avatar'])) {
        if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
            flash('Error al subir la imagen. Intente nuevamente.', 'danger');
        } else {
            $file = $_FILES['avatar'];
            $allowed = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
            $mime = mime_content_type($file['tmp_name']);
            if (!isset($allowed[$mime])) {
                flash('Formato de imagen no válido. Solo JPG, PNG o WEBP.', 'danger');
            } else if ($file['size'] > 2 * 1024 * 1024) {
                flash('La imagen no puede superar los 2 MB.', 'danger');
            } else {
                // En lugar de guardar archivo, convertir a base64 para Railway
                $imageData = file_get_contents($file['tmp_name']);
                $base64 = base64_encode($imageData);
                $dataUri = 'data:' . $mime . ';base64,' . $base64;
                
                // Guardar data URI en la base de datos
                $db->update('users', ['avatar' => $dataUri], 'id = ?', [$userId]);
                flash('Foto de perfil actualizada.', 'success');
                // Refresh user
                $user = $db->selectOne("SELECT * FROM users WHERE id = ?", [$userId]);
            }
        }
    }
}

// Set page title
$pageTitle = __('profile.title', 'Mi Perfil');

// Include header
include_once 'templates/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <h1><?php echo $pageTitle; ?></h1>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <!-- Profile Information -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title"><?php echo __('profile.card.personal_info', 'Información Personal'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo BASE_URL; ?>profile.php">
                        <div class="mb-3">
                            <label for="name" class="form-label"><?php echo __('profile.name', 'Nombre Completo'); ?> *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo escape($user['name']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label"><?php echo __('profile.email', 'Correo Electrónico'); ?> *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo escape($user['email']); ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="phone" class="form-label"><?php echo __('profile.phone', 'Teléfono'); ?></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo escape($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label for="role" class="form-label"><?php echo __('profile.role', 'Rol'); ?></label>
                            <input type="text" class="form-control" id="role" value="<?php echo ucfirst($user['role']); ?>" readonly>
                        </div>
                        
                        <?php if ($user['role'] === 'technician'): ?>
                        <div class="mb-3">
                            <label for="zone" class="form-label"><?php echo __('profile.zone', 'Zona Asignada'); ?></label>
                            <input type="text" class="form-control" id="zone" value="<?php echo escape($user['zone'] ?? ''); ?>" readonly>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-grid">
                            <button type="submit" name="update_profile" class="btn btn-primary"><?php echo __('profile.update_button', 'Actualizar Perfil'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <!-- Avatar Upload -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title"><?php echo __('profile.card.avatar', 'Foto de Perfil'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <?php if (!empty($user['avatar'])): ?>
                            <?php 
                                $avatarSrc = $user['avatar'];
                                // Si no es data URI, agregar BASE_URL
                                if (strpos($avatarSrc, 'data:') !== 0) {
                                    $avatarSrc = BASE_URL . $avatarSrc;
                                }
                            ?>
                            <img src="<?php echo escape($avatarSrc); ?>" alt="Avatar" style="width: 72px; height: 72px; object-fit: cover; border-radius: 50%; border: 2px solid #444;">
                        <?php else: ?>
                            <div style="width: 72px; height: 72px; border-radius: 50%; background:#333; display:flex; align-items:center; justify-content:center; border: 2px solid #444;" class="me-3">
                                <i class="bi bi-person" style="font-size: 2rem;"></i>
                            </div>
                        <?php endif; ?>
                        <div class="ms-3">
                            <small class="text-muted"><?php echo __('profile.avatar.formats', 'Formatos permitidos: JPG, PNG, WEBP. Máx 2MB.'); ?></small>
                        </div>
                    </div>
                    <form method="post" action="<?php echo BASE_URL; ?>profile.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <input type="file" class="form-control" name="avatar" accept="image/jpeg,image/png,image/webp" required>
                        </div>
                        <div class="d-grid">
                            <button type="submit" name="upload_avatar" class="btn btn-primary"><?php echo __('profile.avatar.upload_button', 'Subir Foto'); ?></button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Change Password -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title"><?php echo __('profile.card.change_password', 'Cambiar Contraseña'); ?></h5>
                </div>
                <div class="card-body">
                    <form method="post" action="<?php echo BASE_URL; ?>profile.php">
                        <div class="mb-3">
                            <label for="current_password" class="form-label"><?php echo __('profile.password.current', 'Contraseña Actual'); ?> *</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label"><?php echo __('profile.password.new', 'Nueva Contraseña'); ?> *</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label"><?php echo __('profile.password.confirm', 'Confirmar Nueva Contraseña'); ?> *</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" name="change_password" class="btn btn-warning"><?php echo __('profile.password.change_button', 'Cambiar Contraseña'); ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once 'templates/footer.php'; ?>
