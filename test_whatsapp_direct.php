<?php
/**
 * Página de prueba de notificaciones de WhatsApp (con estilo admin)
 */

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/WhatsAppNotifier.php';

// Requerir admin
$auth->requireAdmin();

// Parámetros
$technicianId = $_GET['tech_id'] ?? null;

// DB
$db = Database::getInstance();

// Datos
$technicians = $db->select("SELECT id, name, email, phone FROM users WHERE role = 'technician' ORDER BY name");

// Título
$pageTitle = 'Prueba Directa de WhatsApp';
include_once __DIR__ . '/templates/header.php';
?>

<div class="container-fluid py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <h1><?php echo $pageTitle; ?></h1>
    <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="btn btn-outline-secondary">
      <i class="bi bi-arrow-left"></i> Volver al Dashboard
    </a>
  </div>

  <div class="row">
    <div class="col-lg-6">
      <div class="card mb-4">
        <div class="card-header">
          <h5 class="card-title mb-0"><i class="bi bi-people"></i> Técnicos disponibles</h5>
        </div>
        <div class="card-body">
          <?php if (count($technicians) > 0): ?>
            <div class="table-responsive">
              <table class="table table-striped align-middle">
                <thead>
                  <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Teléfono</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($technicians as $tech): ?>
                  <tr>
                    <td><?php echo $tech['id']; ?></td>
                    <td><?php echo escape($tech['name']); ?></td>
                    <td><?php echo escape($tech['phone'] ?: 'Sin teléfono'); ?></td>
                    <td>
                      <a class="btn btn-sm btn-primary" href="<?php echo BASE_URL; ?>test_whatsapp_direct.php?tech_id=<?php echo $tech['id']; ?>">
                        <i class="bi bi-send"></i> Probar
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <p class="mb-0">No hay técnicos disponibles.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <?php if ($technicianId): ?>
        <div class="card mb-4">
          <div class="card-header">
            <h5 class="card-title mb-0"><i class="bi bi-activity"></i> Resultado de envío</h5>
          </div>
          <div class="card-body">
            <?php
            try {
              $technician = $db->selectOne("SELECT * FROM users WHERE id = ?", [$technicianId]);
              if (!$technician) {
                throw new Exception('No se pudo encontrar el técnico seleccionado.');
              }
              if (empty($technician['phone'])) {
                throw new Exception('El técnico seleccionado no tiene número de teléfono registrado.');
              }

              $ticket = [
                'id' => 'TEST-' . date('YmdHis'),
                'description' => 'Ticket de prueba creado el ' . date('Y-m-d H:i:s')
              ];
              $client = [
                'name' => 'Cliente de Prueba',
                'business_name' => 'Empresa de Prueba'
              ];

              echo '<div class="mb-3">';
              echo '<strong>Técnico:</strong> ' . escape($technician['name']) . ' (' . escape($technician['phone']) . ')<br>';
              echo '<strong>Ticket:</strong> ' . escape($ticket['id']) . '<br>';
              echo '<strong>Descripción:</strong> ' . escape($ticket['description']) . '<br>';
              echo '<strong>Cliente:</strong> ' . escape($client['name']) . ' - ' . escape($client['business_name']);
              echo '</div>';

              $whatsapp = new WhatsAppNotifier(true);
              $result = $whatsapp->sendTicketNotification($technician, $ticket, $client);

              if ($result) {
                echo '<div class="alert alert-success mb-0">✅ Notificación enviada correctamente.</div>';
              } else {
                echo '<div class="alert alert-danger mb-0">❌ Error al enviar la notificación.</div>';
              }
            } catch (Exception $e) {
              echo '<div class="alert alert-danger mb-0">Error: ' . escape($e->getMessage()) . '</div>';
            }
            ?>
          </div>
          <div class="card-footer text-end">
            <a href="<?php echo BASE_URL; ?>test_whatsapp_direct.php" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-counterclockwise"></i> Volver a probar
            </a>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php include_once __DIR__ . '/templates/footer.php'; ?>
