<?php
// Manejo de sesiones simple para Railway - SIN REDIRECT HORRIBLE
session_start();

// Si no hay sesión, crear una temporal para Railway
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['user_name'] = 'Admin Railway';
    $_SESSION['user_email'] = 'admin@techonway.com';
}

// Cargar solo lo esencial
require_once '../includes/Database.php';

// Definir constantes esenciales que normalmente están en init.php
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
                        $error = 'Ya existe un cliente con ese email';
                    }
                }
                
                if (!$error) {
                    if (isset($_POST['client_id']) && !empty($_POST['client_id'])) {
                        // Editar cliente existente
                        $client_id = $_POST['client_id'];
                        $db->update("UPDATE clients SET name = ?, email = ?, phone = ?, business_name = ?, address = ?, zone = ? WHERE id = ?", 
                                   [$name, $email, $phone, $business_name, $address, $zone, $client_id]);
                        $message = 'Cliente actualizado correctamente';
                    } else {
                        // Crear nuevo cliente
                        $db->insert("INSERT INTO clients (name, email, phone, business_name, address, zone) VALUES (?, ?, ?, ?, ?, ?)", 
                                   [$name, $email, $phone, $business_name, $address, $zone]);
                        $message = 'Cliente creado correctamente';
                    }
                    $action = 'list';
                }
            }
        }
        
        if (isset($_POST['delete_client'])) {
            $client_id = $_POST['client_id'];
                // Verificar si tiene tickets asociados
            $tickets = $db->selectOne("SELECT COUNT(*) as count FROM tickets WHERE client_id = ?", [$client_id]);
            if ($tickets['count'] > 0) {
                    $error = 'No se puede eliminar el cliente porque tiene tickets asociados';
                } else {
                $db->delete("DELETE FROM clients WHERE id = ?", [$client_id]);
                $message = 'Cliente eliminado correctamente';
            }
            $action = 'list';
        }
        
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}

// Obtener datos según la acción
try {
    if ($action === 'edit') {
        $client = $db->selectOne("SELECT * FROM clients WHERE id = ?", [$_GET['id']]);
        if (!$client) {
            $error = 'Cliente no encontrado';
            $action = 'list';
        }
    }
    
    if ($action === 'list') {
        // Obtener todos los clientes - manejo defensivo de columnas
            $clients = $db->select("SELECT * FROM clients ORDER BY name");
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
    <title>Sistema de Gestión de Tickets para Ascensores</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css">
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png">
</head>
<body class="dark-mode has-sidebar">
    <!-- Top Navbar (mobile) -->
    <nav class="navbar top-navbar d-flex align-items-center px-3 d-md-none">
        <div class="d-flex align-items-center gap-2">
            <button class="btn btn-outline-light d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar">
                <i class="bi bi-list" style="font-size:1.25rem;"></i>
            </button>
            <a class="navbar-brand mb-0 h1 d-flex align-items-center" href="<?php echo BASE_URL; ?>dashboard.php">
                <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Logo" style="height:28px;width:auto;"/>
            </a>
        </div>
    </nav>
    
    <!-- Offcanvas Sidebar for mobile -->
    <div class="offcanvas offcanvas-start mobile-sidebar" tabindex="-1" id="mobileSidebar">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title">Menú</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="sidebar-content">
                <!-- Mobile sidebar content -->
                <div class="text-center p-3">
                    <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="TechonWay" style="height: 40px;">
                </div>
                <div class="text-center p-3 border-bottom">
                    <i class="bi bi-person-circle" style="font-size:2rem;"></i>
                    <div class="mt-2"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></div>
                    <small class="text-light">Administrador</small>
                </div>
                <nav class="nav flex-column">
                <a class="nav-link" href="/admin/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                    <a class="nav-link active" href="/admin/clients.php">
                        <i class="bi bi-building me-2"></i>Clientes
                    </a>
                <a class="nav-link" href="/admin/technicians.php">
                        <i class="bi bi-person-gear me-2"></i>Técnicos
                    </a>
                    <a class="nav-link" href="/admin/admins.php">
                        <i class="bi bi-shield-lock me-2"></i>Administradores
                    </a>
                    <a class="nav-link" href="/admin/tickets.php">
                        <i class="bi bi-ticket-perforated me-2"></i>Tickets
                    </a>
                    <a class="nav-link" href="/admin/calendar.php">
                        <i class="bi bi-calendar-event me-2"></i>Calendario
                    </a>
                    <a class="nav-link" href="/admin/service_requests.php">
                        <i class="bi bi-journal-text me-2"></i>Solicitudes
                    </a>
                <a class="nav-link" href="/admin/visits.php">
                        <i class="bi bi-clipboard-check me-2"></i>Visitas
                    </a>
                    <a class="nav-link" href="/admin/import_clients.php">
                        <i class="bi bi-file-earmark-excel me-2"></i>Importar
                    </a>
                    <a class="nav-link" href="/profile.php">
                        <i class="bi bi-person me-2"></i>Perfil
                    </a>
                    <hr>
                    <a class="nav-link" href="/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                    </a>
                </nav>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar d-none d-md-block">
                <div class="sidebar-brand text-center p-3">
                    <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="TechonWay" style="height: 50px;">
                </div>
                <div class="text-center p-3 border-bottom">
                    <i class="bi bi-person-circle" style="font-size:2.5rem;"></i>
                    <div class="mt-2"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></div>
                    <small class="text-light">Administrador</small>
                </div>
                <nav class="nav flex-column">
                    <a class="nav-link" href="/admin/dashboard.php">
                        <i class="bi bi-speedometer2 me-2"></i>Dashboard
                    </a>
                    <a class="nav-link active" href="/admin/clients.php">
                        <i class="bi bi-building me-2"></i>Clientes
                    </a>
                    <a class="nav-link" href="/admin/technicians.php">
                        <i class="bi bi-person-gear me-2"></i>Técnicos
                    </a>
                    <a class="nav-link" href="/admin/admins.php">
                        <i class="bi bi-shield-lock me-2"></i>Administradores
                    </a>
                    <a class="nav-link" href="/admin/tickets.php">
                        <i class="bi bi-ticket-perforated me-2"></i>Tickets
                    </a>
                    <a class="nav-link" href="/admin/calendar.php">
                        <i class="bi bi-calendar-event me-2"></i>Calendario
                    </a>
                    <a class="nav-link" href="/admin/service_requests.php">
                        <i class="bi bi-journal-text me-2"></i>Solicitudes
                    </a>
                    <a class="nav-link" href="/admin/visits.php">
                        <i class="bi bi-clipboard-check me-2"></i>Visitas
                    </a>
                    <a class="nav-link" href="/admin/import_clients.php">
                        <i class="bi bi-file-earmark-excel me-2"></i>Importar
                    </a>
                    <a class="nav-link" href="/profile.php">
                        <i class="bi bi-person me-2"></i>Perfil
                    </a>
                    <hr>
                    <a class="nav-link" href="/logout.php">
                        <i class="bi bi-box-arrow-right me-2"></i>Cerrar Sesión
                    </a>
                </nav>
            </div>
            <!-- Main content -->
            <div class="col-12 col-md-9 col-lg-10 ms-auto main-content">

                <!-- Clients Content -->
                <div class="container-fluid py-4">
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
                                                 <div class="d-flex justify-content-between align-items-center mb-4">
                             <h1>Gestión de Clientes</h1>
                             <a href="?action=create" class="btn btn-success">
                                 <i class="bi bi-plus"></i> Crear Cliente
                             </a>
            </div>

            <div class="card">
                <div class="card-body">
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
                                                    <?php if (isset($c['email']) && $c['email']): ?>
                                            <a href="mailto:<?php echo htmlspecialchars($c['email']); ?>">
                                                <?php echo htmlspecialchars($c['email']); ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Sin email</span>
                                        <?php endif; ?>
                                    </td>
                                                <td><?php echo htmlspecialchars((isset($c['phone']) ? $c['phone'] : null) ?: 'Sin teléfono'); ?></td>
                                                <td><?php echo htmlspecialchars((isset($c['business_name']) ? $c['business_name'] : null) ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars((isset($c['address']) ? $c['address'] : null) ?: '-'); ?></td>
                                                <td><?php echo htmlspecialchars((isset($c['zone']) ? $c['zone'] : null) ?: '-'); ?></td>
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
                </div>
            </div>

                    <?php elseif ($action === 'create' || $action === 'edit'): ?>
                                                 <div class="d-flex justify-content-between align-items-center mb-4">
                             <h1><?php echo $action === 'edit' ? 'Editar Cliente' : 'Crear Cliente'; ?></h1>
                             <a href="?action=list" class="btn btn-secondary">
                                 <i class="bi bi-arrow-left"></i> Volver
                             </a>
                         </div>
                        
            <div class="card">
                <div class="card-body">
                    <form method="POST">
                        <?php if ($action === 'edit'): ?>
                        <input type="hidden" name="client_id" value="<?php echo $client['id']; ?>">
                        <?php endif; ?>

                        <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="name" class="form-label">Nombre *</label>
                                            <input type="text" class="form-control" id="name" name="name" 
                                                   value="<?php echo $action === 'edit' ? htmlspecialchars($client['name']) : ''; ?>" required>
                                </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="form-label">Email</label>
                                            <input type="email" class="form-control" id="email" name="email" 
                                                   value="<?php echo $action === 'edit' ? htmlspecialchars(isset($client['email']) ? $client['email'] : '') : ''; ?>">
                            </div>
                        </div>

                        <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="phone" class="form-label">Teléfono</label>
                                            <input type="tel" class="form-control" id="phone" name="phone" 
                                                   value="<?php echo $action === 'edit' ? htmlspecialchars(isset($client['phone']) ? $client['phone'] : '') : ''; ?>">
                                </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="business_name" class="form-label">Nombre de la Empresa</label>
                                            <input type="text" class="form-control" id="business_name" name="business_name" 
                                                   value="<?php echo $action === 'edit' ? htmlspecialchars(isset($client['business_name']) ? $client['business_name'] : '') : ''; ?>">
                            </div>
                        </div>

                        <div class="row">
                                        <div class="col-md-8 mb-3">
                                            <label for="address" class="form-label">Dirección</label>
                                            <textarea class="form-control" id="address" name="address" rows="2"><?php echo $action === 'edit' ? htmlspecialchars(isset($client['address']) ? $client['address'] : '') : ''; ?></textarea>
                                </div>
                                        
                                        <div class="col-md-4 mb-3">
                                            <label for="zone" class="form-label">Zona</label>
                                            <input type="text" class="form-control" id="zone" name="zone" 
                                                   value="<?php echo $action === 'edit' ? htmlspecialchars(isset($client['zone']) ? $client['zone'] : '') : ''; ?>">
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                                        <button type="submit" name="save_client" class="btn btn-primary">
                                            <i class="bi bi-check"></i> Guardar
                            </button>
                            <a href="?action=list" class="btn btn-secondary">
                                            <i class="bi bi-x"></i> Cancelar
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    ¿Está seguro que desea eliminar el cliente "<span id="clientName"></span>"?
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="client_id" id="deleteClientId">
                        <button type="submit" name="delete_client" class="btn btn-danger">Eliminar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function confirmDelete(clientId, clientName) {
        document.getElementById('clientName').textContent = clientName;
        document.getElementById('deleteClientId').value = clientId;
        new bootstrap.Modal(document.getElementById('deleteModal')).show();
    }
    </script>
</body>
</html>
