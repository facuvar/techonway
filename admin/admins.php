<?php
/**
 * Administrators management page
 */
require_once '../includes/init.php';

// Require admin authentication
$auth->requireAdmin();

// DB
$db = Database::getInstance();

// Action routing
$action = $_GET['action'] ?? 'list';
$adminId = $_GET['id'] ?? null;

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Create/Update admin
    if (isset($_POST['save_admin'])) {
        $data = [
            'name' => trim($_POST['name'] ?? ''),
            'email' => trim($_POST['email'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'role' => 'admin',
        ];

        if ((!isset($_POST['admin_id']) || empty($_POST['admin_id'])) || (isset($_POST['password']) && $_POST['password'] !== '')) {
            $data['password'] = password_hash($_POST['password'] ?? '', PASSWORD_DEFAULT);
        }

        if ($data['name'] === '' || $data['email'] === '') {
            flash(__('admins.flash.required_fields', 'Por favor, complete los campos obligatorios (nombre y email).'), 'danger');
        } else {
            // Uniqueness check
            if (isset($_POST['admin_id']) && !empty($_POST['admin_id'])) {
                $existing = $db->selectOne("SELECT id FROM users WHERE email = ? AND id != ?", [$data['email'], $_POST['admin_id']]);
            } else {
                $existing = $db->selectOne("SELECT id FROM users WHERE email = ?", [$data['email']]);
            }

            if ($existing) {
                flash(__('admins.flash.email_in_use', 'El correo electrónico ya está en uso.'), 'danger');
            } else {
                if (isset($_POST['admin_id']) && !empty($_POST['admin_id'])) {
                    if (!isset($data['password'])) {
                        unset($data['password']);
                    }
                    $db->update('users', $data, 'id = ?', [$_POST['admin_id']]);
                    flash(__('admins.flash.updated', 'Administrador actualizado correctamente.'), 'success');
                } else {
                    $db->insert('users', $data);
                    flash(__('admins.flash.created', 'Administrador creado correctamente.'), 'success');
                }
                redirect('admin/admins.php');
            }
        }
    }

    // Delete admin
    if (isset($_POST['delete_admin'])) {
        $id = (int)($_POST['admin_id'] ?? 0);
        if ($id > 0) {
            // No borrar el propio usuario
            if (isset($_SESSION['user_id']) && $id === (int)$_SESSION['user_id']) {
                flash(__('admins.flash.cannot_delete_self', 'No puede eliminar su propio usuario.'), 'danger');
            } else {
                // No borrar el último admin
                $countAdmins = (int)$db->selectOne("SELECT COUNT(*) AS c FROM users WHERE role = 'admin'")['c'];
                if ($countAdmins <= 1) {
                    flash(__('admins.flash.cannot_delete_last', 'No se puede eliminar el último administrador.'), 'danger');
                } else {
                    $db->delete('users', 'id = ?', [$id]);
                    flash(__('admins.flash.deleted', 'Administrador eliminado correctamente.'), 'success');
                }
            }
        }
        redirect('admin/admins.php');
    }
}

// For edit/view
$admin = null;
if (($action === 'edit' || $action === 'view') && $adminId) {
    $admin = $db->selectOne("SELECT * FROM users WHERE id = ? AND role = 'admin'", [$adminId]);
    if (!$admin) {
        flash(__('admins.flash.not_found', 'Administrador no encontrado.'), 'danger');
        redirect('admin/admins.php');
    }
}

// List
$admins = [];
if ($action === 'list') {
    $admins = $db->select("SELECT id, name, email, phone, created_at FROM users WHERE role = 'admin' ORDER BY name");
}

// Page Title
$pageTitle = match($action) {
    'create' => __('admins.title.create', 'Crear Administrador'),
    'edit' => __('admins.title.edit', 'Editar Administrador'),
    'view' => __('admins.title.view', 'Ver Administrador'),
    default => __('admins.title.index', 'Gestión de Administradores'),
};

include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        <?php if ($action === 'list'): ?>
            <a href="?action=create" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> <?php echo __('admins.actions.new', 'Nuevo Administrador'); ?>
            </a>
        <?php else: ?>
            <a href="admins.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> <?php echo __('common.back_to_list', 'Volver a la Lista'); ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($action === 'list'): ?>
        <div class="card">
            <div class="card-body">
                <?php if (count($admins) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo __('common.id', 'ID'); ?></th>
                                    <th><?php echo __('common.name', 'Nombre'); ?></th>
                                    <th><?php echo __('common.email', 'Email'); ?></th>
                                    <th><?php echo __('common.phone', 'Teléfono'); ?></th>
                                    <th><?php echo __('common.actions', 'Acciones'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $a): ?>
                                    <tr>
                                        <td><?php echo $a['id']; ?></td>
                                        <td><?php echo escape($a['name']); ?></td>
                                        <td><?php echo escape($a['email']); ?></td>
                                        <td><?php echo escape($a['phone']); ?></td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="?action=view&id=<?php echo $a['id']; ?>" class="btn btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $a['id']; ?>" class="btn btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $a['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>

                                            <div class="modal fade" id="deleteModal<?php echo $a['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title"><?php echo __('common.confirm_delete', 'Confirmar Eliminación'); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <?php echo __('admins.delete.confirm_message', '¿Está seguro de que desea eliminar este administrador?'); ?> <strong><?php echo escape($a['name']); ?></strong>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo __('common.cancel', 'Cancelar'); ?></button>
                                                            <form method="post" action="<?php echo BASE_URL; ?>admin/admins.php">
                                                                <input type="hidden" name="admin_id" value="<?php echo $a['id']; ?>">
                                                                <button type="submit" name="delete_admin" class="btn btn-danger"><?php echo __('common.delete', 'Eliminar'); ?></button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center"><?php echo __('admins.list.empty', 'No hay administradores registrados'); ?></p>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($action === 'create' || $action === 'edit'): ?>
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>admin/admins.php">
                    <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="admin_id" value="<?php echo $admin['id']; ?>">
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="name" class="form-label"><?php echo __('admins.form.name', 'Nombre'); ?> *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo $action === 'edit' ? escape($admin['name']) : ''; ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label for="email" class="form-label"><?php echo __('admins.form.email', 'Email'); ?> *</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo $action === 'edit' ? escape($admin['email']) : ''; ?>" required>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="phone" class="form-label"><?php echo __('admins.form.phone', 'Teléfono'); ?></label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo $action === 'edit' ? escape($admin['phone']) : ''; ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="password" class="form-label"><?php echo $action === 'edit' ? __('admins.form.password_hint_edit', 'Contraseña (dejar en blanco para mantener la actual)') : __('admins.form.password_hint_create', 'Contraseña *'); ?></label>
                            <input type="password" class="form-control" id="password" name="password" <?php echo $action === 'create' ? 'required' : ''; ?>>
                        </div>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>admin/admins.php" class="btn btn-outline-secondary"><?php echo __('common.cancel', 'Cancelar'); ?></a>
                        <button type="submit" name="save_admin" class="btn btn-primary"><?php echo __('common.save', 'Guardar'); ?></button>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($action === 'view' && $admin): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><?php echo __('admins.view.info_title', 'Información del Administrador'); ?></h5>
            </div>
            <div class="card-body">
                <table class="table">
                    <tr>
                        <th><?php echo __('admins.view.name', 'Nombre'); ?>:</th>
                        <td><?php echo escape($admin['name']); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo __('admins.view.email', 'Email'); ?>:</th>
                        <td><?php echo escape($admin['email']); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo __('admins.view.phone', 'Teléfono'); ?>:</th>
                        <td><?php echo escape($admin['phone']); ?></td>
                    </tr>
                    <tr>
                        <th><?php echo __('admins.view.registered_at', 'Fecha de Registro'); ?>:</th>
                        <td><?php echo date('d/m/Y', strtotime($admin['created_at'])); ?></td>
                    </tr>
                </table>
                <a href="?action=edit&id=<?php echo $admin['id']; ?>" class="btn btn-primary">
                    <i class="bi bi-pencil"></i> <?php echo __('common.edit', 'Editar'); ?>
                </a>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once '../templates/footer.php'; ?>


