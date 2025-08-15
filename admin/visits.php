<?php
/**
 * Visits management page for administrators
 */
require_once '../includes/init.php';

// Require admin authentication
$auth->requireAdmin();

// Get database connection
$db = Database::getInstance();

// Get action from query string
$action = $_GET['action'] ?? 'list';
$visitId = $_GET['id'] ?? null;

// Get all visits for list view
$visits = [];
$activeVisits = [];
if ($action === 'list') {
    $visits = $db->select("
        SELECT v.*, t.id as ticket_id, t.description, t.status as ticket_status,
               c.name as client_name, c.business_name, u.name as technician_name
        FROM visits v
        JOIN tickets t ON v.ticket_id = t.id
        JOIN clients c ON t.client_id = c.id
        JOIN users u ON t.technician_id = u.id
        ORDER BY v.start_time DESC
    ");
    
    // Get active visits (started but not finished) with coordinates for map
    $activeVisits = $db->select("
        SELECT v.*, t.id as ticket_id, t.description, t.status as ticket_status,
               c.name as client_name, c.business_name, c.address, c.latitude, c.longitude,
               u.name as technician_name
        FROM visits v
        JOIN tickets t ON v.ticket_id = t.id
        JOIN clients c ON t.client_id = c.id
        JOIN users u ON t.technician_id = u.id
        WHERE v.start_time IS NOT NULL 
        AND v.end_time IS NULL
        AND c.latitude IS NOT NULL 
        AND c.longitude IS NOT NULL
        ORDER BY v.start_time DESC
    ");
}

// Get visit details for view
$visit = null;
if ($action === 'view' && $visitId) {
    $visit = $db->selectOne("
        SELECT v.*, t.id as ticket_id, t.description, t.status as ticket_status,
               c.name as client_name, c.business_name, c.address, c.latitude, c.longitude,
               u.name as technician_name, u.email as technician_email, u.phone as technician_phone
        FROM visits v
        JOIN tickets t ON v.ticket_id = t.id
        JOIN clients c ON t.client_id = c.id
        JOIN users u ON t.technician_id = u.id
        WHERE v.id = ?
    ", [$visitId]);
    
    if (!$visit) {
        flash('Visita no encontrada.', 'danger');
        redirect('visits.php');
    }
}

// Page title
$pageTitle = $action === 'view' ? __('visits.title.view', 'Detalles de Visita') : __('visits.title.index', 'Gestión de Visitas');

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1><?php echo $pageTitle; ?></h1>
        
        <?php if ($action === 'view'): ?>
            <a href="visits.php" class="btn btn-secondary">
                <i class="bi bi-arrow-left"></i> <?php echo __('common.back_to_list', 'Volver a la Lista'); ?>
            </a>
        <?php endif; ?>
    </div>
    
    <?php if ($action === 'list'): ?>
        <!-- Visits List -->
        <div class="card">
            <div class="card-body">
                <?php if (count($visits) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th><?php echo __('common.id', 'ID'); ?></th>
                                    <th><?php echo __('common.ticket', 'Ticket'); ?></th>
                                    <th><?php echo __('common.client', 'Cliente'); ?></th>
                                    <th><?php echo __('common.technician', 'Técnico'); ?></th>
                                    <th><?php echo __('common.start', 'Inicio'); ?></th>
                                    <th><?php echo __('common.end', 'Fin'); ?></th>
                                    <th><?php echo __('common.status', 'Estado'); ?></th>
                                    <th><?php echo __('common.actions', 'Acciones'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($visits as $visit): ?>
                                    <tr>
                                        <td><?php echo $visit['id']; ?></td>
                                        <td><?php echo $visit['ticket_id']; ?></td>
                                        <td><?php echo escape($visit['client_name']); ?></td>
                                        <td><?php echo escape($visit['technician_name']); ?></td>
                                        <td><?php echo $visit['start_time'] ? formatDateTime($visit['start_time']) : '-'; ?></td>
                                        <td><?php echo $visit['end_time'] ? formatDateTime($visit['end_time']) : '-'; ?></td>
                                        <td>
                                            <?php if (!$visit['end_time']): ?>
                                                <span class="badge bg-info"><?php echo __('visits.status.in_progress', 'En Progreso'); ?></span>
                                            <?php elseif ($visit['completion_status'] === 'success'): ?>
                                                <span class="badge bg-success"><?php echo __('visits.status.success', 'Finalizada con Éxito'); ?></span>
                                            <?php elseif ($visit['completion_status'] === 'failure'): ?>
                                                <span class="badge bg-danger"><?php echo __('visits.status.failure', 'Finalizada sin Éxito'); ?></span>
                                            <?php else: ?>
                                                <span class="badge bg-warning"><?php echo __('visits.status.unknown', 'Estado Desconocido'); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm" role="group">
                                                <a href="?action=view&id=<?php echo $visit['id']; ?>" class="btn btn-info">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <p class="text-center">No hay visitas registradas</p>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Mapa de Visitas Activas -->
        <?php if (count($activeVisits) > 0): ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-geo-alt"></i> Mapa de Visitas Activas 
                        <span class="badge bg-info ms-2"><?php echo count($activeVisits); ?></span>
                    </h5>
                    <small class="text-muted">Visitas iniciadas que aún no han finalizado</small>
                </div>
                <div class="card-body">
                    <div id="activeVisitsMap" style="height: 500px; border-radius: 8px;"></div>
                </div>
            </div>
        <?php else: ?>
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-geo-alt"></i> Mapa de Visitas Activas
                    </h5>
                </div>
                <div class="card-body text-center">
                    <i class="bi bi-geo-alt-fill" style="font-size: 3rem; color: #6c757d;"></i>
                    <p class="mt-3 text-muted">No hay visitas activas en este momento</p>
                </div>
            </div>
        <?php endif; ?>
        
    <?php elseif ($action === 'view' && $visit): ?>
        <!-- View Visit Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo __('visits.view.info_title', 'Información de la Visita'); ?> #<?php echo $visit['id']; ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-4">
                            <h6><?php echo __('common.status', 'Estado'); ?>:</h6>
                            <?php if (!$visit['start_time']): ?>
                                <span class="badge bg-secondary fs-6"><?php echo __('visits.status.not_started', 'No iniciada'); ?></span>
                            <?php elseif (!$visit['end_time']): ?>
                                <span class="badge bg-info fs-6"><?php echo __('visits.status.in_progress', 'En Progreso'); ?></span>
                            <?php elseif ($visit['completion_status'] === 'success'): ?>
                                <span class="badge bg-success fs-6"><?php echo __('visits.status.success', 'Finalizada con Éxito'); ?></span>
                            <?php elseif ($visit['completion_status'] === 'failure'): ?>
                                <span class="badge bg-danger fs-6"><?php echo __('visits.status.failure', 'Finalizada sin Éxito'); ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning fs-6"><?php echo __('visits.status.unknown', 'Estado Desconocido'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="mb-4">
                            <h6><?php echo __('visits.view.ticket', 'Ticket'); ?>:</h6>
                            <p>
                                <strong>#<?php echo $visit['ticket_id']; ?></strong><br>
                                <?php echo escape(substr($visit['description'], 0, 100)) . (strlen($visit['description']) > 100 ? '...' : ''); ?>
                                <br>
                                <a href="../admin/tickets.php?action=view&id=<?php echo $visit['ticket_id']; ?>" class="btn btn-sm mt-2" style="background-color:#2D3142; border-color:#2D3142; color:#fff;">
                                    <i class="bi bi-ticket-perforated"></i> <?php echo __('visits.view.view_full_ticket', 'Ver Ticket Completo'); ?>
                                </a>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6><?php echo __('visits.view.client', 'Cliente'); ?>:</h6>
                            <p>
                                <strong><?php echo escape($visit['client_name']); ?></strong><br>
                                <?php echo escape($visit['business_name']); ?><br>
                                <?php echo escape($visit['address']); ?>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6><?php echo __('visits.view.technician', 'Técnico'); ?>:</h6>
                            <p>
                                <strong><?php echo escape($visit['technician_name']); ?></strong><br>
                                Email: <?php echo escape($visit['technician_email']); ?><br>
                                Teléfono: <?php echo escape($visit['technician_phone']); ?>
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <h6><?php echo __('visits.view.dates', 'Fechas'); ?>:</h6>
                            <p>
                                <strong><?php echo __('common.start', 'Inicio'); ?>:</strong> <?php echo $visit['start_time'] ? formatDateTime($visit['start_time']) : __('visits.status.not_started', 'No iniciada'); ?><br>
                                <strong><?php echo __('visits.view.end_time', 'Fin'); ?>:</strong> <?php echo $visit['end_time'] ? formatDateTime($visit['end_time']) : __('visits.status.in_progress', 'En Progreso'); ?>
                            </p>
                        </div>
                        
                        <!-- Estado de la visita -->
                        <div class="mb-4">
                            <h6><?php echo __('common.status', 'Estado'); ?>:</h6>
                            <?php if (!$visit['start_time']): ?>
                                <span class="badge bg-secondary fs-6"><?php echo __('visits.status.not_started', 'No iniciada'); ?></span>
                            <?php elseif (!$visit['end_time']): ?>
                                <span class="badge bg-info fs-6"><?php echo __('visits.status.in_progress', 'En Progreso'); ?></span>
                            <?php elseif ($visit['completion_status'] === 'success'): ?>
                                <span class="badge bg-success fs-6"><?php echo __('visits.status.success', 'Finalizada con Éxito'); ?></span>
                            <?php elseif ($visit['completion_status'] === 'failure'): ?>
                                <span class="badge bg-danger fs-6"><?php echo __('visits.status.failure', 'Finalizada sin Éxito'); ?></span>
                            <?php else: ?>
                                <span class="badge bg-warning fs-6"><?php echo __('visits.status.unknown', 'Estado Desconocido'); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Comentarios iniciales (si existen) -->
                        <?php if (!empty($visit['start_notes'])): ?>
                        <div class="mb-4">
                            <h6><?php echo __('visits.view.initial_comments', 'Comentarios Iniciales'); ?>:</h6>
                            <div class="p-3 border rounded bg-info text-white">
                                <?php echo nl2br(escape($visit['start_notes'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Comentarios de finalización (si existen) -->
                        <?php if (!empty($visit['comments'])): ?>
                        <div class="mb-4">
                            <h6><?php echo __('visits.view.final_comments', 'Comentarios de Finalización'); ?>:</h6>
                            <div class="p-3 border rounded bg-info text-white">
                                <?php echo nl2br(escape($visit['comments'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Motivo de no finalización (si aplica) -->
                        <?php if ($visit['end_time'] && $visit['completion_status'] !== 'success' && !empty($visit['failure_reason'])): ?>
                        <div class="mb-4">
                            <h6><?php echo __('visits.view.not_completed_reason', 'Motivo de No Finalización'); ?>:</h6>
                            <div class="p-3 border rounded bg-danger bg-opacity-10">
                                <?php echo nl2br(escape($visit['failure_reason'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-4">
                <!-- Map -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo __('visits.view.location', 'Ubicación'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div id="map" class="map-container"></div>
                        
                        <!-- Contador de tiempo -->
                        <div class="mt-3 p-3 border rounded">
                            <h6>
                                <i class="bi bi-clock"></i> 
                                <?php if (!$visit['start_time']): ?>
                                    <?php echo __('visits.view.timer.not_started', 'Visita no iniciada'); ?>
                                <?php elseif (!$visit['end_time']): ?>
                                    <?php echo __('visits.view.timer.elapsed', 'Tiempo transcurrido:'); ?>
                                <?php else: ?>
                                    <?php echo __('visits.view.timer.duration', 'Duración total:'); ?>
                                <?php endif; ?>
                            </h6>
                            
                            <?php if (!$visit['start_time']): ?>
                                <div class="text-center">
                                    <span class="badge bg-secondary">00:00:00</span>
                                </div>
                            <?php elseif (!$visit['end_time']): ?>
                                        <div class="text-center">
                                            <span id="visit-duration" class="badge bg-info fs-5"><?php echo __('visits.view.timer.calculating', 'Calculando...'); ?></span>
                                        </div>
                                <?php
                                // Calcular duración inicial en PHP
                                $startTime = new DateTime($visit['start_time']);
                                $now = new DateTime();
                                $initialDuration = $now->getTimestamp() - $startTime->getTimestamp();
                                $initialHours = floor($initialDuration / 3600);
                                $initialMinutes = floor(($initialDuration % 3600) / 60);
                                $initialSeconds = $initialDuration % 60;
                                
                                // Formatear para mostrar
                                $initialHoursFormatted = str_pad($initialHours, 2, '0', STR_PAD_LEFT);
                                $initialMinutesFormatted = str_pad($initialMinutes, 2, '0', STR_PAD_LEFT);
                                $initialSecondsFormatted = str_pad($initialSeconds, 2, '0', STR_PAD_LEFT);
                                $initialTimeFormatted = "$initialHoursFormatted:$initialMinutesFormatted:$initialSecondsFormatted";
                                ?>
                                <script>
                                    document.addEventListener('DOMContentLoaded', function() {
                                        // Inicializar con los valores calculados en PHP
                                        let seconds = <?php echo $initialSeconds; ?>;
                                        let minutes = <?php echo $initialMinutes; ?>;
                                        let hours = <?php echo $initialHours; ?>;
                                        
                                        // Establecer el valor inicial
                                        document.getElementById('visit-duration').textContent = 
                                            '<?php echo $initialTimeFormatted; ?>';
                                        
                                        // Función para incrementar el contador cada segundo
                                        function incrementTimer() {
                                            seconds++;
                                            
                                            if (seconds >= 60) {
                                                seconds = 0;
                                                minutes++;
                                                
                                                if (minutes >= 60) {
                                                    minutes = 0;
                                                    hours++;
                                                }
                                            }
                                            
                                            // Formatear con ceros a la izquierda
                                            const formattedHours = String(hours).padStart(2, '0');
                                            const formattedMinutes = String(minutes).padStart(2, '0');
                                            const formattedSeconds = String(seconds).padStart(2, '0');
                                            
                                            // Actualizar el elemento en la página
                                            document.getElementById('visit-duration').textContent = 
                                                `${formattedHours}:${formattedMinutes}:${formattedSeconds}`;
                                        }
                                        
                                        // Incrementar el contador cada segundo
                                        setInterval(incrementTimer, 1000);
                                    });
                                </script>
                            <?php else: ?>
                                <?php
                                    // Calcular duración
                                    $startTime = new DateTime($visit['start_time']);
                                    $endTime = new DateTime($visit['end_time']);
                                    $duration = $startTime->diff($endTime);
                                    
                                    // Formatear duración
                                    if ($duration->days > 0) {
                                        $durationText = $duration->format('%d días, %h horas, %i minutos');
                                    } else {
                                        $durationText = $duration->format('%h horas, %i minutos');
                                    }
                                ?>
                                <div class="text-center">
                                    <span class="badge bg-success fs-5"><?php echo $durationText; ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- JavaScript for map display -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const clientLat = <?php echo $visit['latitude']; ?>;
                const clientLng = <?php echo $visit['longitude']; ?>;
                
                const map = L.map('map').setView([clientLat, clientLng], 15);
                
                L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                
                // Add marker at client location
                L.marker([clientLat, clientLng])
                    .addTo(map)
                    .bindPopup("<?php echo escape($visit['client_name']); ?><br><?php echo escape($visit['address']); ?>");
                
                // Make map refresh when it becomes visible
                map.invalidateSize();
            });
        </script>
    <?php endif; ?>
</div>

<!-- JavaScript para el mapa de visitas activas -->
<?php if ($action === 'list' && count($activeVisits) > 0): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos de visitas activas
    const activeVisits = <?php echo json_encode($activeVisits); ?>;
    
    // Verificar que hay visitas con coordenadas válidas
    const validVisits = activeVisits.filter(visit => 
        visit.latitude && visit.longitude && 
        !isNaN(parseFloat(visit.latitude)) && !isNaN(parseFloat(visit.longitude))
    );
    
    if (validVisits.length === 0) {
        console.log('No hay visitas con coordenadas válidas');
        return;
    }
    
    // Calcular centro del mapa basado en las visitas
    let centerLat = 0, centerLng = 0;
    validVisits.forEach(visit => {
        centerLat += parseFloat(visit.latitude);
        centerLng += parseFloat(visit.longitude);
    });
    centerLat /= validVisits.length;
    centerLng /= validVisits.length;
    
    // Inicializar mapa
    const map = L.map('activeVisitsMap').setView([centerLat, centerLng], 12);
    
    // Agregar capa de tiles
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);
    
    // Crear iconos personalizados para visitas activas
    const activeIcon = L.divIcon({
        className: 'custom-div-icon',
        html: '<div style="background-color: #17a2b8; color: white; border-radius: 50%; width: 30px; height: 30px; display: flex; align-items: center; justify-content: center; border: 3px solid white; box-shadow: 0 2px 8px rgba(0,0,0,0.3); font-weight: bold; font-size: 12px;">🔧</div>',
        iconSize: [30, 30],
        iconAnchor: [15, 15]
    });
    
    // Agregar marcadores para cada visita activa
    validVisits.forEach(visit => {
        const lat = parseFloat(visit.latitude);
        const lng = parseFloat(visit.longitude);
        
        // Formatear hora de inicio
        const startTime = new Date(visit.start_time);
        const startTimeFormatted = startTime.toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
        
        // Calcular duración actual
        const now = new Date();
        const duration = Math.floor((now - startTime) / 1000 / 60); // minutos
        const hours = Math.floor(duration / 60);
        const minutes = duration % 60;
        const durationText = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
        
        // Crear popup con información
        const popupContent = `
            <div style="min-width: 250px;">
                <h6 style="margin-bottom: 10px; color: #17a2b8;">
                    <i class="bi bi-tools"></i> Visita en Progreso
                </h6>
                <p style="margin: 5px 0;"><strong>Técnico:</strong> ${visit.technician_name}</p>
                <p style="margin: 5px 0;"><strong>Cliente:</strong> ${visit.client_name}</p>
                <p style="margin: 5px 0;"><strong>Dirección:</strong> ${visit.address}</p>
                <p style="margin: 5px 0;"><strong>Inicio:</strong> ${startTimeFormatted}</p>
                <p style="margin: 5px 0;"><strong>Duración:</strong> <span style="color: #17a2b8; font-weight: bold;">${durationText}</span></p>
                <hr style="margin: 10px 0;">
                <p style="margin: 5px 0; font-size: 12px; color: #666;">
                    <strong>Ticket #${visit.ticket_id}:</strong><br>
                    ${visit.description.substring(0, 80)}${visit.description.length > 80 ? '...' : ''}
                </p>
                <div style="text-align: center; margin-top: 10px;">
                    <a href="?action=view&id=${visit.id}" class="btn btn-sm btn-info">
                        <i class="bi bi-eye"></i> Ver Detalles
                    </a>
                </div>
            </div>
        `;
        
        // Agregar marcador con popup
        const marker = L.marker([lat, lng], { icon: activeIcon })
            .addTo(map)
            .bindPopup(popupContent);
        
        // Agregar tooltip que se muestra al hacer hover
        const tooltipContent = `
            <strong>${visit.technician_name}</strong><br>
            Inicio: ${startTimeFormatted}<br>
            Duración: ${durationText}
        `;
        marker.bindTooltip(tooltipContent, {
            permanent: false,
            direction: 'top',
            offset: [0, -20]
        });
    });
    
    // Ajustar el zoom para que se vean todos los marcadores
    if (validVisits.length > 1) {
        const group = new L.featureGroup(map._layers);
        map.fitBounds(group.getBounds().pad(0.1));
    }
    
    // Actualizar duraciones cada minuto
    setInterval(() => {
        // Recalcular y actualizar tooltips y popups
        map.eachLayer(layer => {
            if (layer instanceof L.Marker && layer.getTooltip()) {
                // Actualizar tooltip y popup con nueva duración
                const visit = validVisits.find(v => 
                    Math.abs(parseFloat(v.latitude) - layer.getLatLng().lat) < 0.0001 &&
                    Math.abs(parseFloat(v.longitude) - layer.getLatLng().lng) < 0.0001
                );
                
                if (visit) {
                    const startTime = new Date(visit.start_time);
                    const now = new Date();
                    const duration = Math.floor((now - startTime) / 1000 / 60);
                    const hours = Math.floor(duration / 60);
                    const minutes = duration % 60;
                    const durationText = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;
                    
                    const startTimeFormatted = startTime.toLocaleString('es-ES', {
                        day: '2-digit',
                        month: '2-digit',
                        year: 'numeric',
                        hour: '2-digit',
                        minute: '2-digit'
                    });
                    
                    const tooltipContent = `
                        <strong>${visit.technician_name}</strong><br>
                        Inicio: ${startTimeFormatted}<br>
                        Duración: ${durationText}
                    `;
                    layer.setTooltipContent(tooltipContent);
                }
            }
        });
    }, 60000); // Actualizar cada minuto
});
</script>
<?php endif; ?>

<?php include_once '../templates/footer.php'; ?>
