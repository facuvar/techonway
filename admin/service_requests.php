<?php
require_once __DIR__ . '/../includes/init.php';

$auth->requireAdmin();
$db = Database::getInstance();
// Mail config (para fallback de notify_to)
$mailConfig = require __DIR__ . '/../config/mail.php';
require_once __DIR__ . '/../includes/Settings.php';
$settings = new Settings();

$action = $_GET['action'] ?? 'list';
$requestId = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Guardar actualización
    if (isset($_POST['save_request'])) {
        $id = (int)($_POST['id'] ?? 0);
        $data = [
            'type' => trim($_POST['type'] ?? 'Visita técnica'),
            'name' => trim($_POST['name'] ?? ''),
            'phone' => trim($_POST['phone'] ?? ''),
            'address' => trim($_POST['address'] ?? ''),
            'detail' => trim($_POST['detail'] ?? ''),
            'status' => trim($_POST['status'] ?? 'pending'),
        ];

        if ($data['name'] === '' || $data['phone'] === '' || $data['address'] === '') {
            flash('Complete los campos obligatorios.', 'danger');
        } else {
            if ($id > 0) {
                $db->update('service_requests', $data, 'id = ?', [$id]);
                flash('Solicitud actualizada.', 'success');
            } else {
                $db->insert('service_requests', $data);
                flash('Solicitud creada.', 'success');
                // Notificar por email al destinatario configurado
                try {
                    require_once __DIR__ . '/../includes/Mailer.php';
                    $mailer = new Mailer();
                    $to = $settings->get('service_requests_notify_to', $mailConfig['notify_to'] ?? 'admin@example.com');
                    $subject = 'Nueva Solicitud de Servicio';
                    $html = '<h3>Nueva Solicitud de Servicio</h3>' .
                            '<p><strong>Tipo:</strong> ' . htmlspecialchars($data['type']) . '</p>' .
                            '<p><strong>Nombre:</strong> ' . htmlspecialchars($data['name']) . '</p>' .
                            '<p><strong>Teléfono:</strong> ' . htmlspecialchars($data['phone']) . '</p>' .
                            '<p><strong>Dirección:</strong> ' . htmlspecialchars($data['address']) . '</p>' .
                            '<p><strong>Detalle:</strong><br>' . nl2br(htmlspecialchars($data['detail'])) . '</p>' .
                            '<p><em>Enviado desde formulario público.</em></p>';
                    $mailer->send($to, 'Admin', $subject, $html);
                } catch (Exception $e) {
                    error_log('Error enviando email de solicitud: ' . $e->getMessage());
                }
            }
            redirect('admin/service_requests.php');
        }
    }

    // Eliminar
    if (isset($_POST['delete_request'])) {
        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            $db->delete('service_requests', 'id = ?', [$id]);
            flash('Solicitud eliminada.', 'success');
        }
        redirect('admin/service_requests.php');
    }

    // Guardar email de notificaciones
    if (isset($_POST['save_notify_email'])) {
        $notifyTo = trim($_POST['notify_to'] ?? '');
        if ($notifyTo === '' || !filter_var($notifyTo, FILTER_VALIDATE_EMAIL)) {
            flash(__('service_requests.notify.invalid_email', 'Email inválido.'), 'danger');
        } else {
            $settings->set('service_requests_notify_to', $notifyTo);
            flash(__('service_requests.notify.saved', 'Email de notificaciones guardado.'), 'success');
        }
        redirect('admin/service_requests.php');
    }
}

// Cargar datos según acción
$request = null;
if (($action === 'view' || $action === 'edit') && $requestId) {
    $request = $db->selectOne('SELECT * FROM service_requests WHERE id = ?', [$requestId]);
    if (!$request) {
        flash('Solicitud no encontrada.', 'danger');
        redirect('admin/service_requests.php');
    }
}

// Listado
$requests = [];
if ($action === 'list') {
    $requests = $db->select('SELECT * FROM service_requests ORDER BY created_at DESC');
}

// Convertir a ticket - preparación de datos
if ($action === 'convert' && $requestId) {
    $request = $db->selectOne('SELECT * FROM service_requests WHERE id = ?', [$requestId]);
    if (!$request) {
        flash('Solicitud no encontrada.', 'danger');
        redirect('admin/service_requests.php');
    }
    // Cargar listas
    $clients = $db->select("SELECT id, name, business_name FROM clients ORDER BY name");
    $technicians = $db->select("SELECT id, name, zone FROM users WHERE role = 'technician' ORDER BY name");
}

$pageTitle = match ($action) {
    'edit' => __('service_requests.title.edit', 'Editar Solicitud'),
    'view' => __('service_requests.title.view', 'Ver Solicitud'),
    'convert' => __('service_requests.title.convert', 'Convertir a Ticket'),
    default => __('service_requests.title.index', 'Solicitudes de Servicio')
};

include_once __DIR__ . '/../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        <?php if ($action === 'list'): ?>
            <a href="?action=edit" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> <?php echo __('service_requests.actions.new', 'Nueva Solicitud'); ?>
            </a>
        <?php else: ?>
            <a href="service_requests.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> <?php echo __('common.back_to_list', 'Volver a la Lista'); ?>
            </a>
        <?php endif; ?>
    </div>

    <?php if ($action === 'list'): ?>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0"><?php echo __('service_requests.notify.title', 'Notificaciones por Email'); ?></h5>
                    </div>
                    <div class="card-body">
                        <form method="post" action="<?php echo BASE_URL; ?>admin/service_requests.php">
                            <div class="mb-3">
                                <label class="form-label" for="notify_to">
                                    <?php echo __('service_requests.notify.to', 'Enviar notificaciones a'); ?>
                                </label>
                                <input type="email" class="form-control" id="notify_to" name="notify_to" placeholder="email@dominio.com" value="<?php echo escape($settings->get('service_requests_notify_to', $mailConfig['notify_to'] ?? '')); ?>" required>
                                <div class="form-text">
                                    <?php echo __('service_requests.notify.help', 'Se enviará un email cada vez que se reciba una nueva solicitud.'); ?>
                                </div>
                            </div>
                            <div class="d-flex justify-content-end">
                                <button type="submit" class="btn btn-primary" name="save_notify_email">
                                    <?php echo __('common.save', 'Guardar'); ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="card">
            <div class="card-body">
                <?php if (count($requests) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo __('common.id', 'ID'); ?></th>
                                    <th><?php echo __('common.type', 'Tipo'); ?></th>
                                    <th><?php echo __('common.name', 'Nombre'); ?></th>
                                    <th><?php echo __('common.phone', 'Teléfono'); ?></th>
                                    <th><?php echo __('common.address', 'Dirección'); ?></th>
                                    <th><?php echo __('common.date', 'Fecha'); ?></th>
                                    <th><?php echo __('common.status', 'Estado'); ?></th>
                                    <th><?php echo __('common.actions', 'Acciones'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($requests as $r): ?>
                                    <tr>
                                        <td><?php echo $r['id']; ?></td>
                                        <td><?php echo escape($r['type']); ?></td>
                                        <td><?php echo escape($r['name']); ?></td>
                                        <td><?php echo escape($r['phone']); ?></td>
                                        <td><?php echo escape($r['address']); ?></td>
                                        <td><?php echo formatDateTime($r['created_at']); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = match ($r['status']) {
                                                'pending' => 'bg-warning',
                                                'in_progress' => 'bg-info',
                                                'closed' => 'bg-success',
                                                default => 'bg-secondary'
                                            };
                                            $statusText = match ($r['status']) {
                                                'pending' => __('service_requests.status.pending', 'Pendiente'),
                                                'in_progress' => __('service_requests.status.in_progress', 'En Progreso'),
                                                'closed' => __('service_requests.status.closed', 'Cerrada'),
                                                default => ucfirst($r['status'] ?? '-')
                                            };
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?>">
                                                <?php echo $statusText; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="?action=view&id=<?php echo $r['id']; ?>" class="btn btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                                <a href="?action=edit&id=<?php echo $r['id']; ?>" class="btn btn-primary">
                                                    <i class="bi bi-pencil"></i>
                                                </a>
                                                <?php if (empty($r['ticket_id'])): ?>
                                                    <a href="?action=convert&id=<?php echo $r['id']; ?>" class="btn btn-success">
                                                        <i class="bi bi-arrow-right-circle"></i>
                                                    </a>
                                                <?php else: ?>
                                                    <a href="<?php echo BASE_URL; ?>admin/tickets.php?action=view&id=<?php echo $r['ticket_id']; ?>" class="btn btn-success">
                                                        <i class="bi bi-ticket-perforated"></i>
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $r['id']; ?>">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </div>

                                            <div class="modal fade" id="deleteModal<?php echo $r['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Confirmar Eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Eliminar la solicitud #<?php echo $r['id']; ?>?
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="post" action="<?php echo BASE_URL; ?>admin/service_requests.php">
                                                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                                                <button type="submit" name="delete_request" class="btn btn-danger">Eliminar</button>
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
                    <p class="text-center">No hay solicitudes registradas</p>
                <?php endif; ?>
            </div>
        </div>

    <?php elseif ($action === 'edit'): ?>
        <div class="card">
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>admin/service_requests.php">
                    <?php if ($request): ?>
                        <input type="hidden" name="id" value="<?php echo $request['id']; ?>">
                    <?php endif; ?>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="type"><?php echo __('service_requests.form.type', 'Tipo'); ?> *</label>
                            <input type="text" class="form-control" id="type" name="type" value="<?php echo escape($request['type'] ?? 'Visita técnica'); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="status">Estado *</label>
                            <select class="form-select" id="status" name="status">
                                <option value="pending" <?php echo (($request['status'] ?? '') === 'pending') ? 'selected' : ''; ?>><?php echo __('service_requests.status.pending', 'Pendiente'); ?></option>
                                <option value="in_progress" <?php echo (($request['status'] ?? '') === 'in_progress') ? 'selected' : ''; ?>><?php echo __('service_requests.status.in_progress', 'En Progreso'); ?></option>
                                <option value="closed" <?php echo (($request['status'] ?? '') === 'closed') ? 'selected' : ''; ?>><?php echo __('service_requests.status.closed', 'Cerrada'); ?></option>
                            </select>
                        </div>
                    </div>

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="name"><?php echo __('service_requests.form.name', 'Nombre'); ?> *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo escape($request['name'] ?? ''); ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="phone"><?php echo __('service_requests.form.phone', 'Teléfono'); ?> *</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo escape($request['phone'] ?? ''); ?>" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="address"><?php echo __('service_requests.form.address', 'Dirección'); ?> *</label>
                        <input type="text" class="form-control" id="address" name="address" value="<?php echo escape($request['address'] ?? ''); ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label" for="detail"><?php echo __('service_requests.form.detail', 'Detalle'); ?></label>
                        <textarea class="form-control" id="detail" name="detail" rows="4"><?php echo escape($request['detail'] ?? ''); ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a class="btn btn-secondary" href="<?php echo BASE_URL; ?>admin/service_requests.php"><?php echo __('common.cancel', 'Cancelar'); ?></a>
                        <button type="submit" name="save_request" class="btn btn-primary"><?php echo __('common.save', 'Guardar'); ?></button>
                    </div>
                </form>
            </div>
        </div>

    <?php elseif ($action === 'view' && $request): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo __('service_requests.view.title', 'Solicitud'); ?> #<?php echo $request['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <p><strong><?php echo __('service_requests.view.type', 'Tipo'); ?>:</strong> <?php echo escape($request['type']); ?></p>
                        <p><strong><?php echo __('service_requests.view.name', 'Nombre'); ?>:</strong> <?php echo escape($request['name']); ?></p>
                        <p><strong><?php echo __('service_requests.view.phone', 'Teléfono'); ?>:</strong> <?php echo escape($request['phone']); ?></p>
                        <p><strong><?php echo __('service_requests.view.address', 'Dirección'); ?>:</strong> <?php echo escape($request['address']); ?></p>
                        <p><strong><?php echo __('service_requests.view.detail', 'Detalle'); ?>:</strong><br><?php echo nl2br(escape($request['detail'])); ?></p>
                        <p><strong><?php echo __('service_requests.view.status', 'Estado'); ?>:</strong>
                            <?php
                            $statusClass = match ($request['status']) {
                                'pending' => 'bg-warning',
                                'in_progress' => 'bg-info',
                                'closed' => 'bg-success',
                                default => 'bg-secondary'
                            };
                            $statusText = match ($request['status']) {
                                'pending' => __('service_requests.status.pending', 'Pendiente'),
                                'in_progress' => __('service_requests.status.in_progress', 'En Progreso'),
                                'closed' => __('service_requests.status.closed', 'Cerrada'),
                                default => ucfirst($request['status'] ?? '-')
                            };
                            ?>
                            <span class="badge <?php echo $statusClass; ?>"><?php echo $statusText; ?></span>
                        </p>
                        <p><strong><?php echo __('service_requests.view.date', 'Fecha'); ?>:</strong> <?php echo formatDateTime($request['created_at']); ?></p>

                        <div class="d-flex gap-2 justify-content-end">
                            <a href="<?php echo BASE_URL; ?>admin/service_requests.php?action=edit&id=<?php echo $request['id']; ?>" class="btn btn-primary">
                                <i class="bi bi-pencil"></i> <?php echo __('service_requests.view.edit', 'Editar'); ?>
                            </a>
                            <a href="<?php echo BASE_URL; ?>admin/service_requests.php?action=convert&id=<?php echo $request['id']; ?>" class="btn btn-success">
                                <i class="bi bi-arrow-right-circle"></i> <?php echo __('service_requests.view.convert_to_ticket', 'Convertir a Ticket'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
    <?php elseif ($action === 'convert' && $request): ?>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title"><?php echo __('service_requests.title.convert', 'Convertir a Ticket'); ?> #<?php echo $request['id']; ?></h5>
            </div>
            <div class="card-body">
                <form method="post" action="<?php echo BASE_URL; ?>admin/service_requests_convert_submit.php">
                    <input type="hidden" name="request_id" value="<?php echo $request['id']; ?>">

                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label for="client_id" class="form-label"><?php echo __('tickets.form.client', 'Cliente'); ?> *</label>
                            <select class="form-select" id="client_id" name="client_id" required>
                                <option value=""><?php echo __('tickets.form.select_client', 'Seleccionar cliente'); ?></option>
                                <?php foreach ($clients as $client): ?>
                                    <option value="<?php echo $client['id']; ?>">
                                        <?php echo escape($client['name']) . ' - ' . escape($client['business_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="technician_id" class="form-label"><?php echo __('tickets.form.technician', 'Técnico'); ?> *</label>
                            <select class="form-select" id="technician_id" name="technician_id" required>
                                <option value=""><?php echo __('tickets.form.select_technician', 'Seleccionar técnico'); ?></option>
                                <?php foreach ($technicians as $tech): ?>
                                    <option value="<?php echo $tech['id']; ?>">
                                        <?php echo escape($tech['name']) . ' (' . __('common.zone', 'Zona') . ': ' . escape($tech['zone']) . ')'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label"><?php echo __('tickets.form.description', 'Descripción del Problema'); ?> *</label>
                        <textarea class="form-control" id="description" name="description" rows="4" required><?php echo escape(($request['type'] ? ($request['type'] . ': ') : '') . ($request['detail'] ?? '')) . "\n\n" . __('service_requests.convert.contact', 'Contactar a') . ": " . escape($request['name']) . " - " . escape($request['phone']) . "\n" . __('common.address', 'Dirección') . ": " . escape($request['address']); ?></textarea>
                    </div>

                    <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                        <a href="<?php echo BASE_URL; ?>admin/service_requests.php?action=view&id=<?php echo $request['id']; ?>" class="btn" style="background-color:#2D3142; border-color:#2D3142; color:#fff;">
                            <?php echo __('common.cancel', 'Cancelar'); ?>
                        </a>
                        <button type="submit" class="btn btn-success"><?php echo __('service_requests.convert.create_ticket', 'Crear Ticket'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include_once __DIR__ . '/../templates/footer.php'; ?>


