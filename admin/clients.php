<?php
// Manejo de sesiones simple para Railway - SIN REDIRECT HORRIBLE
session_start();

// Si no hay sesión, crear una temporal para Railway
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['user_name'] = 'Administrador TechonWay';
    $_SESSION['user_email'] = 'admin@techonway.com';
    $_SESSION['user_role'] = 'admin';
}

// Cargar solo lo esencial
require_once '../includes/Database.php';
require_once '../includes/Auth.php';

// Definir constantes esenciales que normalmente están en init.php
if (!defined('BASE_URL')) {
    // Detectar si estamos en Railway o en local
    $isRailway = isset($_SERVER['RAILWAY_ENVIRONMENT']) || strpos($_SERVER['HTTP_HOST'] ?? '', 'railway.app') !== false || strpos($_SERVER['HTTP_HOST'] ?? '', 'techonway.com') !== false;
    define('BASE_URL', $isRailway ? '/' : '/sistema-techonway/');
}

if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__));
}

if (!defined('TEMPLATE_PATH')) {
    define('TEMPLATE_PATH', BASE_PATH . '/templates');
}

// Initialize auth
$auth = new Auth();
$pageTitle = 'Gestión de Clientes';

// Funciones básicas necesarias
if (!function_exists('escape')) {
    function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('isActive')) {
    function isActive($page) {
        $currentPage = basename($_SERVER['PHP_SELF']);
        return $currentPage === $page ? 'active' : '';
    }
}

if (!function_exists('getFlash')) {
    function getFlash() {
        return null; // Simple implementation
    }
}

if (!function_exists('__')) {
    function __($key, $default = '') {
        // Mapeo de textos comunes del sidebar
        $translations = [
            'sidebar.dashboard' => 'Dashboard',
            'sidebar.clients' => 'Clientes',
            'sidebar.technicians' => 'Técnicos',
            'sidebar.admins' => 'Administradores',
            'sidebar.tickets' => 'Tickets',
            'sidebar.service_requests' => 'Solicitudes de Servicio',
            'sidebar.visits' => 'Visitas',
            'sidebar.import_clients' => 'Importar Clientes',
            'sidebar.profile' => 'Mi Perfil',
            'sidebar.logout' => 'Cerrar Sesión',
            'sidebar.language' => 'Idioma',
            'language.es' => 'Español',
            'language.en' => 'Inglés'
        ];
        
        return $translations[$key] ?? $default ?: $key;
    }
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
            $latitude = !empty($_POST['latitude']) ? floatval($_POST['latitude']) : null;
            $longitude = !empty($_POST['longitude']) ? floatval($_POST['longitude']) : null;
            
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
                        $db->query("UPDATE clients SET name = ?, email = ?, phone = ?, business_name = ?, address = ?, zone = ?, latitude = ?, longitude = ? WHERE id = ?", 
                                   [$name, $email, $phone, $business_name, $address, $zone, $latitude, $longitude, $client_id]);
                        $message = 'Cliente actualizado correctamente';
                    } else {
                        // Crear nuevo cliente
                        $db->query("INSERT INTO clients (name, email, phone, business_name, address, zone, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?, ?)", 
                                   [$name, $email, $phone, $business_name, $address, $zone, $latitude, $longitude]);
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
                $db->query("DELETE FROM clients WHERE id = ?", [$client_id]);
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
    if ($action === 'edit' || $action === 'view') {
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

// Add Leaflet CSS to header
if (!isset($GLOBALS['extra_css'])) {
    $GLOBALS['extra_css'] = [];
}
$GLOBALS['extra_css'][] = '<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />';

// Include header
include_once '../templates/header.php';
?>

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
                                            <a href="?action=view&id=<?php echo $c['id']; ?>" class="btn btn-outline-info">
                                                <i class="bi bi-eye"></i>
                                            </a>
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

                    <?php elseif ($action === 'view'): ?>
            <!-- Vista detallada del cliente -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Detalles del Cliente</h1>
                <div>
                    <a href="?action=edit&id=<?php echo $client['id']; ?>" class="btn btn-primary">
                        <i class="bi bi-pencil"></i> Editar
                    </a>
                    <a href="?action=list" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i> Volver
                    </a>
                </div>
            </div>
            
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h5>Información Personal</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Nombre:</strong></td>
                                    <td><?php echo htmlspecialchars($client['name']); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Email:</strong></td>
                                    <td><?php echo htmlspecialchars($client['email'] ?? 'Sin email'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Teléfono:</strong></td>
                                    <td><?php echo htmlspecialchars($client['phone'] ?? 'Sin teléfono'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Empresa:</strong></td>
                                    <td><?php echo htmlspecialchars($client['business_name'] ?? 'Sin empresa'); ?></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h5>Ubicación</h5>
                            <table class="table table-borderless">
                                <tr>
                                    <td><strong>Dirección:</strong></td>
                                    <td><?php echo htmlspecialchars($client['address'] ?? 'Sin dirección'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Zona:</strong></td>
                                    <td><?php echo htmlspecialchars($client['zone'] ?? 'Sin zona'); ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Latitud:</strong></td>
                                    <td><?php echo $client['latitude'] ? number_format($client['latitude'], 6) : 'Sin coordenada'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Longitud:</strong></td>
                                    <td><?php echo $client['longitude'] ? number_format($client['longitude'], 6) : 'Sin coordenada'; ?></td>
                                </tr>
                            </table>
                            
                            <div class="mt-3">
                                <h6>Mapa</h6>
                                <div id="map" style="height: 200px; border-radius: 8px; background-color: #f8f9fa; border: 1px solid #dee2e6; position: relative;">
                                    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: #6c757d;">
                                        Cargando mapa...
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <h5>Historial de Tickets</h5>
                            <?php
                            // Obtener tickets del cliente
                            $tickets = $db->select("
                                SELECT t.*, 
                                       CONCAT(u.name, ' ', COALESCE(u.last_name, '')) as technician_name
                                FROM tickets t
                                LEFT JOIN users u ON t.assigned_to = u.id
                                WHERE t.client_id = ?
                                ORDER BY t.created_at DESC
                                LIMIT 10
                            ", [$client['id']]);
                            ?>
                            
                            <?php if (!empty($tickets)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Descripción</th>
                                            <th>Técnico</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($tickets as $ticket): ?>
                                        <tr>
                                            <td><?php echo $ticket['id']; ?></td>
                                            <td><?php echo htmlspecialchars(substr($ticket['description'], 0, 50)); ?><?php echo strlen($ticket['description']) > 50 ? '...' : ''; ?></td>
                                            <td><?php echo htmlspecialchars($ticket['technician_name'] ?: 'Sin asignar'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $ticket['status'] === 'completed' ? 'success' : 
                                                        ($ticket['status'] === 'in_progress' ? 'warning' : 'secondary'); 
                                                ?>">
                                                    <?php echo ucfirst($ticket['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('d/m/Y', strtotime($ticket['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">No hay tickets registrados para este cliente.</p>
                            <?php endif; ?>
                        </div>
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

                        <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="latitude" class="form-label">Latitud</label>
                                            <input type="number" step="any" class="form-control" id="latitude" name="latitude" 
                                                   value="<?php echo $action === 'edit' ? htmlspecialchars(isset($client['latitude']) ? $client['latitude'] : '') : ''; ?>"
                                                   placeholder="Ej: -34.6118">
                                            <small class="form-text text-muted">Coordenada para el mapa (opcional)</small>
                                </div>
                                        
                                        <div class="col-md-6 mb-3">
                                            <label for="longitude" class="form-label">Longitud</label>
                                            <input type="number" step="any" class="form-control" id="longitude" name="longitude" 
                                                   value="<?php echo $action === 'edit' ? htmlspecialchars(isset($client['longitude']) ? $client['longitude'] : '') : ''; ?>"
                                                   placeholder="Ej: -58.3960">
                                            <small class="form-text text-muted">Coordenada para el mapa (opcional)</small>
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

<?php
// Add extra JS after the template footer
if (!isset($GLOBALS['extra_js'])) {
    $GLOBALS['extra_js'] = [];
}
$GLOBALS['extra_js'][] = '<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>';
$GLOBALS['extra_js'][] = '<script>
function confirmDelete(clientId, clientName) {
    document.getElementById("clientName").textContent = clientName;
    document.getElementById("deleteClientId").value = clientId;
    new bootstrap.Modal(document.getElementById("deleteModal")).show();
}

// Inicializar mapa si existe
' . ($action === 'view' && isset($client) ? 
'document.addEventListener("DOMContentLoaded", function() {
    setTimeout(function() {
        console.log("=== CLIENT MAP DEBUG START ===");
        
        const mapContainer = document.getElementById("map");
        console.log("Client map container search result:", mapContainer);
        
        if (!mapContainer) {
            console.error("FATAL: Client map container not found");
            return;
        }
        
        console.log("✓ Client map container found successfully");
        
        if (typeof L === "undefined") {
            console.error("FATAL: Leaflet not loaded for client map");
            mapContainer.innerHTML = "<div style=\"padding: 20px; text-align: center; color: red;\">❌ Error: Leaflet no se pudo cargar</div>";
            return;
        }
        
        console.log("✓ Leaflet is available for client map");
        
        // Coordenadas del cliente
        const lat = ' . floatval($client['latitude'] ?? 0) . ';
        const lng = ' . floatval($client['longitude'] ?? 0) . ';
        
        console.log("Client coordinates from PHP: lat=" + lat + ", lng=" + lng);
        
        // Si no hay coordenadas válidas, usar coordenadas por defecto de Buenos Aires
        let displayLat = lat;
        let displayLng = lng;
        let isDefaultLocation = false;
        
        if (!lat || !lng || lat === 0 || lng === 0) {
            displayLat = -34.6118;  // Buenos Aires
            displayLng = -58.3960;
            isDefaultLocation = true;
            console.log("Using default Buenos Aires coordinates");
        }
        
        console.log("Final client coordinates: lat=" + displayLat + ", lng=" + displayLng + ", isDefault=" + isDefaultLocation);
        
        try {
            console.log("Clearing loading message and creating client map...");
            mapContainer.innerHTML = "";
            
            const clientViewMap = L.map("map").setView([displayLat, displayLng], isDefaultLocation ? 10 : 15);
            console.log("✓ Client map object created");
            
            L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
                attribution: "© OpenStreetMap contributors",
                maxZoom: 18
            }).addTo(clientViewMap);
            console.log("✓ Client tile layer added");
            
            // Add marker at location
            const marker = L.marker([displayLat, displayLng]).addTo(clientViewMap);
            console.log("✓ Client marker added");
            
            let popupText = "<strong>' . addslashes($client['name'] ?? '') . '</strong><br>' . addslashes($client['address'] ?? '') . '";
            if (isDefaultLocation) {
                popupText += "<br><small style=\"color: #dc3545;\">⚠️ Ubicación aproximada</small>";
            }
            
            marker.bindPopup(popupText).openPopup();
            console.log("✓ Client popup bound and opened");
                
            // Force map to refresh
            setTimeout(() => {
                clientViewMap.invalidateSize();
                console.log("✓ Client map size invalidated");
            }, 200);
            
            console.log("🎉 CLIENT MAP INITIALIZED SUCCESSFULLY!");
            console.log("=== CLIENT MAP DEBUG END ===");
        } catch (error) {
            console.error("💥 FATAL ERROR initializing client map:", error);
            mapContainer.innerHTML = "<div style=\"padding: 20px; text-align: center; color: red;\">❌ Error: " + error.message + "</div>";
        }
    }, 2000);
});' : '') . '
</script>';

// Include footer
include_once '../templates/footer.php';
?>
