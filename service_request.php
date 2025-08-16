<?php
require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/Mailer.php';
$mailConfig = require __DIR__ . '/config/mail.php';
require_once __DIR__ . '/includes/Settings.php';
$settings = new Settings();

$db = Database::getInstance();

$pageTitle = 'Solicitud de Visita Técnica';

$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $detail = trim($_POST['detail'] ?? '');

    if ($name === '') { $errors[] = 'El nombre es obligatorio.'; }
    if ($phone === '') { $errors[] = 'El teléfono es obligatorio.'; }
    if ($address === '') { $errors[] = 'La dirección es obligatoria.'; }

    if (empty($errors)) {
        $db->insert('service_requests', [
            'type' => 'Visita técnica',
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'detail' => $detail,
            'status' => 'pending'
        ]);
        // Enviar notificación por email con template mejorado
        try {
            $mailer = new Mailer();
            $to = $settings->get('service_requests_notify_to', $mailConfig['notify_to'] ?? 'admin@example.com');
            $success = $mailer->sendServiceRequestEmail($to, 'Admin TechonWay', $name, $phone, $address, $detail);
        } catch (Exception $e) {
            error_log('Error enviando email de solicitud: ' . $e->getMessage());
        }
        $success = true;
        flash('Tu solicitud fue enviada. Nos pondremos en contacto a la brevedad.', 'success');
        // Limpia campos tras exito
        $_POST = [];
    }
}

// Forzar layout público sin sidebar
$PUBLIC_LAYOUT = true;
include_once __DIR__ . '/templates/header.php';
?>

<style>
/* Page-specific styling for public service request form */
body {
    background-color: #2D3142 !important;
}
.main-content .card,
.main-content .modal-content,
.main-content .list-group-item {
    background-color: #5B6386 !important;
    border-color: #5B6386 !important;
}
.main-content .card-header {
    background-color: #5B6386 !important;
    border-bottom-color: #5B6386 !important;
}
.main-content .form-label,
.main-content label,
.main-content h5,
.main-content h6,
.main-content p {
    color: #ffffff !important;
}
.main-content .form-control,
.main-content .form-select {
    background-color: rgba(0,0,0,0.15);
    color: #ffffff;
    border-color: rgba(255,255,255,0.3);
}
.main-content .form-control::placeholder { color: rgba(255,255,255,0.7); }
.main-content .form-control:focus,
.main-content .form-select:focus {
    background-color: rgba(0,0,0,0.2);
    color: #ffffff;
    border-color: #4F5D75;
    box-shadow: 0 0 0 0.2rem rgba(79,93,117,0.35);
}
</style>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0"><i class="bi bi-clipboard-plus"></i> <?php echo $pageTitle; ?></h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                <?php foreach ($errors as $err): ?>
                                    <li><?php echo escape($err); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form method="post" action="<?php echo BASE_URL; ?>service_request.php">
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre *</label>
                            <input type="text" class="form-control" id="name" name="name" value="<?php echo escape($_POST['name'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Teléfono *</label>
                            <input type="text" class="form-control" id="phone" name="phone" value="<?php echo escape($_POST['phone'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="address" class="form-label">Dirección *</label>
                            <input type="text" class="form-control" id="address" name="address" value="<?php echo escape($_POST['address'] ?? ''); ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="detail" class="form-label">Detalle</label>
                            <textarea class="form-control" id="detail" name="detail" rows="4"><?php echo escape($_POST['detail'] ?? ''); ?></textarea>
                        </div>

                        <div class="d-grid d-md-flex justify-content-md-end gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Enviar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once __DIR__ . '/templates/footer.php'; ?>


