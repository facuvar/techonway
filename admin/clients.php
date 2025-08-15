<?php
/**
 * Clients Railway - Versión que funciona en Railway
 */

// Manejo de sesiones simplificado para Railway
session_start();

// Si no hay sesión de admin, redirigir al login que funciona
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: /admin/force_login_and_calendar.php');
    exit();
}

require_once '../includes/Database.php';

if (!defined('BASE_URL')) {
    define('BASE_URL', '/');
}

$db = Database::getInstance();
$action = $_GET['action'] ?? 'list';
$message = '';
$error = '';

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['save_client'])) {
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $business_name = trim($_POST['business_name'] ?? '');
            $address = trim($_POST['address'] ?? '');
            $zone = trim($_POST['zone'] ?? '');
            
            if (empty($name)) {
                $error = 'El nombre es requerido';
            } else {
                // Verificar email único si se proporciona
                $emailExists = false;
                if (!empty($email)) {
                    if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
                        // Edición - verificar que no exista en otro cliente
                        $existing = $db->selectOne("SELECT id FROM clients WHERE email = ? AND id != ?", [$email, $_POST['client_id']]);
                    } else {
                        // Nuevo cliente
                        $existing = $db->selectOne("SELECT id FROM clients WHERE email = ?", [$email]);
                    }
                    if ($existing) {
                        $emailExists = true;
                        $error = 'Ya existe un cliente con ese email';
                    }
                }
                
                if (!$emailExists) {
                    if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
                        // Update
                        $clientId = $_POST['client_id'];
                        $db->query("
                            UPDATE clients SET 
                                name = ?, 
                                email = ?, 
                                phone = ?, 
                                business_name = ?,
                                address = ?,
                                zone = ?
                            WHERE id = ?
                        ", [$name, $email, $phone, $business_name, $address, $zone, $clientId]);
                        $message = 'Cliente actualizado exitosamente';
                    } else {
                        // Insert
                        $db->query("
                            INSERT INTO clients (name, email, phone, business_name, address, zone) 
                            VALUES (?, ?, ?, ?, ?, ?)
                        ", [$name, $email, $phone, $business_name, $address, $zone]);
                        $message = 'Cliente creado exitosamente';
                    }
                    
                    // Redirigir para evitar resubmit
                    header('Location: ?action=list&msg=' . urlencode($message));
                    exit();
                }
            }
        }
        
        if (isset($_POST['delete_client'])) {
            $clientId = $_POST['client_id'] ?? null;
            if ($clientId) {
                // Verificar si tiene tickets asociados
                $hasTickets = $db->selectOne("SELECT COUNT(*) as count FROM tickets WHERE client_id = ?", [$clientId]);
                if ($hasTickets['count'] > 0) {
                    $error = 'No se puede eliminar el cliente porque tiene tickets asociados';
                } else {
                    $db->query("DELETE FROM clients WHERE id = ?", [$clientId]);
                    $message = 'Cliente eliminado exitosamente';
                    header('Location: ?action=list&msg=' . urlencode($message));
                    exit();
                }
            }
        }
    } catch (Exception $e) {
        $error = 'Error al procesar: ' . $e->getMessage();
    }
}

// Obtener datos según la acción
$client = null;
$clients = [];

try {
    if ($action === 'edit' && isset($_GET['id'])) {
        $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$_GET['id']]);
        if (!$client) {
            $error = 'Cliente no encontrado';
            $action = 'list';
        }
    }
    
    if ($action === 'list') {
        $search = $_GET['search'] ?? '';
        if ($search) {
            $clients = $db->select("
                SELECT * FROM clients 
                WHERE name LIKE ? OR email LIKE ? OR business_name LIKE ? OR address LIKE ?
                ORDER BY name
            ", ["%$search%", "%$search%", "%$search%", "%$search%"]);
        } else {
            $clients = $db->select("SELECT * FROM clients ORDER BY name");
        }
    }
} catch (Exception $e) {
    $error = 'Error de base de datos: ' . $e->getMessage();
}

// Mensaje de URL
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
}

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clientes - TechonWay</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <style>
    /* Estilos del panel admin */
    .sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: 250px;
        background: linear-gradient(180deg, #2D3142 0%, #3A3F58 100%);
        color: white;
        z-index: 1000;
        overflow-y: auto;
    }
    .main-content {
        margin-left: 250px;
        min-height: 100vh;
        background: #f8f9fa;
    }
    .sidebar .nav-link {
        color: #bbb;
        padding: 12px 20px;
        text-decoration: none;
    }
    .sidebar .nav-link:hover {
        background: rgba(91, 99, 134, 0.3);
        color: white;
    }
    .sidebar .nav-link.active {
        background: #5B6386;
        color: white;
    }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-3 text-center border-bottom">
            <img src="/assets/img/logo.png" alt="Logo" style="max-height: 50px;">
            <h5 class="mt-2 mb-0">TechonWay</h5>
        </div>
        <div class="p-3 text-center border-bottom">
            <i class="bi bi-person-circle" style="font-size:2.5rem;"></i>
            <div class="mt-2"><?php echo $_SESSION['user_name']; ?></div>
            <small class="text-light">Administrador</small>
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link" href="/admin/dashboard.php">
                    <i class="bi bi-speedometer2"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link active" href="/admin/clients.php">
                    <i class="bi bi-building"></i> Clientes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/technicians.php">
                    <i class="bi bi-person-gear"></i> Técnicos
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/tickets.php">
                    <i class="bi bi-ticket-perforated"></i> Tickets
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/calendar.php">
                    <i class="bi bi-calendar-event"></i> Calendario
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="/admin/visits.php">
                    <i class="bi bi-clipboard-check"></i> Visitas
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>👥 Gestión de Clientes</h1>
                <?php if ($action === 'list'): ?>
                <a href="?action=create" class="btn btn-success">
                    <i class="bi bi-plus"></i> Crear Cliente
                </a>
                <?php endif; ?>
            </div>

            <!-- Mensajes -->
            <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <?php if ($action === 'list'): ?>
            <!-- Búsqueda -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-10">
                            <input type="text" name="search" class="form-control" placeholder="Buscar por nombre, email, empresa o dirección..." value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="bi bi-search"></i> Buscar
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Clientes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Lista de Clientes (<?php echo count($clients); ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($clients)): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Nombre</th>
                                    <th>Email</th>
                                    <th>Teléfono</th>
                                    <th>Empresa</th>
                                    <th>Dirección</th>
                                    <th>Zona</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($clients as $c): ?>
                                <tr>
                                    <td><?php echo $c['id']; ?></td>
                                    <td><?php echo htmlspecialchars($c['name']); ?></td>
                                    <td>
                                        <?php if ($c['email']): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($c['email']); ?>">
                                                <?php echo htmlspecialchars($c['email']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Sin email</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($c['phone'] ?: 'Sin teléfono'); ?></td>
                                    <td><?php echo htmlspecialchars($c['business_name'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($c['address'] ?: '-'); ?></td>
                                    <td><?php echo htmlspecialchars($c['zone'] ?: '-'); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="?action=edit&id=<?php echo $c['id']; ?>" class="btn btn-outline-primary">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['name']); ?>')">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted">No hay clientes registrados.</p>
                    <?php endif; ?>
                </div>
            </div>

            <?php else: ?>
            <!-- Formulario Crear/Editar -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <?php echo $action === 'edit' ? 'Editar Cliente #' . $client['id'] : 'Crear Nuevo Cliente'; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nombre *</label>
                                    <input type="text" name="name" class="form-control" required
                                           value="<?php echo $client ? htmlspecialchars($client['name']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" class="form-control"
                                           value="<?php echo $client ? htmlspecialchars($client['email']) : ''; ?>">
                                    <div class="form-text">Opcional - debe ser único si se proporciona</div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" name="phone" class="form-control"
                                           value="<?php echo $client ? htmlspecialchars($client['phone']) : ''; ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Empresa/Negocio</label>
                                    <input type="text" name="business_name" class="form-control"
                                           value="<?php echo $client ? htmlspecialchars($client['business_name']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Dirección</label>
                                    <textarea name="address" class="form-control" rows="2"><?php echo $client ? htmlspecialchars($client['address']) : ''; ?></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Zona</label>
                                    <input type="text" name="zone" class="form-control"
                                           value="<?php echo $client ? htmlspecialchars($client['zone']) : ''; ?>">
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" name="save_client" class="btn btn-success">
                                <i class="bi bi-check"></i> Guardar Cliente
                            </button>
                            <a href="?action=list" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Volver
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Modal de confirmación de eliminación -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que quieres eliminar al cliente <strong id="clientName"></strong>?</p>
                    <p class="text-danger"><small>Esta acción no se puede deshacer.</small></p>
                </div>
                <div class="modal-footer">
                    <form method="POST" id="deleteForm">
                        <input type="hidden" name="client_id" id="deleteClientId">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" name="delete_client" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function confirmDelete(clientId, clientName) {
        document.getElementById('deleteClientId').value = clientId;
        document.getElementById('clientName').textContent = clientName;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
</body>
</html>
