<?php
/**
 * Scan QR Code Page (Standalone version without main.js dependencies)
 */
require_once '../includes/init.php';

// Require technician authentication
$auth->requireLogin();
$auth->requireTechnician();

// Get user info
$userId = $_SESSION['user_id'];

// Page title
$pageTitle = __('tech.scan_qr.title', 'Escanear QR');

// Custom styles
$customStyles = '
<style>
    .map-container {
        height: 300px;
        width: 100%;
        border-radius: 5px;
    }
    #reader {
        width: 100%;
        height: 300px;
        border-radius: 5px;
        overflow: hidden;
    }
    #scan-result {
        margin-top: 20px;
    }
    #processing {
        z-index: 9999;
    }
</style>
';

// Custom head content
$customHead = '
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />
<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
';

// Include header
include_once '../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <h1 class="mb-4"><i class="bi bi-qr-code-scan"></i> <?php echo __('tech.scan_qr.heading', 'Escanear Código QR'); ?></h1>
            
            <!-- Scanner Container -->
            <div id="scanner-container">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo __('tech.scan_qr.scanner.title', 'Escáner de Código QR'); ?></h5>
                    </div>
                    <div class="card-body">
                        <div id="reader"></div>
                        <div class="mt-3">
                            <button id="start-scan" class="btn btn-primary">
                                <i class="bi bi-camera"></i> <?php echo __('tech.scan_qr.start_scanner', 'Iniciar Escáner'); ?>
                            </button>
                            <button id="stop-scan" class="btn btn-danger d-none">
                                <i class="bi bi-stop-circle"></i> <?php echo __('tech.scan_qr.stop_scanner', 'Detener Escáner'); ?>
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Scan Error -->
                <div id="scan-error" class="alert alert-danger mt-4 d-none">
                    <i class="bi bi-exclamation-triangle"></i> <span id="error-message"><?php echo __('tech.scan_qr.scan_error', 'Error al escanear el código QR.'); ?></span>
                </div>
                
                <!-- Instructions -->
                <div class="card mt-4">
                    <div class="card-header">
                        <h5 class="card-title"><?php echo __('tech.scan_qr.instructions.title', 'Instrucciones'); ?></h5>
                    </div>
                    <div class="card-body">
                        <ol class="mb-3">
                            <li>1. <?php echo __('tech.scan_qr.instructions.step1', 'Haga clic en "Iniciar Escáner"'); ?></li>
                            <li>2. <?php echo __('tech.scan_qr.instructions.step2', 'Permita el acceso a la cámara cuando se le solicite'); ?></li>
                            <li>3. <?php echo __('tech.scan_qr.instructions.step3', 'Apunte la cámara al código QR'); ?></li>
                            <li>4. <?php echo __('tech.scan_qr.instructions.step4', 'Una vez escaneado, se verificará su ubicación'); ?></li>
                            <li>5. <?php echo __('tech.scan_qr.instructions.step5', 'Si está dentro del rango permitido (100 metros), podrá iniciar o finalizar la visita'); ?></li>
                            <li>6. <?php echo __('tech.scan_qr.instructions.step6', 'Al finalizar, indique si el problema quedó solucionado o no'); ?></li>
                        </ol>
                        <p class="mb-2">
                            <?php echo __('tech.scan_qr.instructions.must_be_within_100m', 'Debe estar a menos de 100 metros de la ubicación registrada del cliente para iniciar o finalizar una visita.'); ?>
                        </p>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle"></i> <?php echo __('tech.scan_qr.instructions.no_qr_use_start_button', 'Si no hay código QR puede iniciar su visita con el botón INICIAR VISITA que se encuentra abajo.'); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Auto-processing message -->
            <div id="auto-process" class="card d-none">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border text-primary me-3" role="status"></div>
                        <div>
                            <h5 class="mb-1"><?php echo __('tech.scan_qr.auto.obtaining_location', 'Obteniendo su ubicación...'); ?></h5>
                            <p class="mb-0"><?php echo __('tech.scan_qr.auto.allow_location', 'Por favor, permita el acceso a su ubicación cuando se le solicite.'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Scan Result -->
            <div id="scan-result" class="d-none">
                <div id="ticket-info" class="card mb-4">
                    <!-- Ticket info will be inserted here -->
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title"><?php echo __('tech.scan_qr.location_status.title', 'Estado de Ubicación'); ?></h5>
                </div>
                <div class="card-body">
                    <div id="location-status" class="alert alert-warning">
                        <i class="bi bi-geo-alt"></i> <?php echo __('tech.scan_qr.location_status.waiting', 'Esperando a obtener su ubicación...'); ?>
                    </div>
                    <div id="distance-info" class="d-none">
                        <p><?php echo __('tech.scan_qr.distance.label', 'Distancia al cliente:'); ?> <span id="distance-value">--</span> <?php echo __('tech.scan_qr.distance.suffix', 'metros'); ?></p>
                    </div>
                    <div id="scan-map" class="map-container mt-3"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Visit Start Form -->
    <div id="start-visit-form" class="card mt-4 d-none">
        <div class="card-header">
            <h5 class="card-title"><?php echo __('visits.actions.start_visit', 'Iniciar Visita'); ?></h5>
        </div>
        <div class="card-body">
            <form id="form-start-visit">
                <input type="hidden" id="ticket-id" name="ticket_id">
                <div class="mb-3">
                    <label for="start-notes" class="form-label"><?php echo __('visits.form.start_notes_optional', 'Notas Iniciales (Opcional)'); ?></label>
                    <textarea class="form-control" id="start-notes" name="start_notes" rows="3"></textarea>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-play-circle"></i> <?php echo __('visits.actions.start_visit', 'Iniciar Visita'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Visit End Form -->
    <div id="end-visit-form" class="card mt-4 d-none">
        <div class="card-header">
            <h5 class="card-title"><?php echo __('visits.actions.finalize_visit', 'Finalizar Visita'); ?></h5>
        </div>
        <div class="card-body">
            <form id="form-end-visit">
                <input type="hidden" id="visit-id" name="visit_id">
                
                <div class="mb-3">
                    <label class="form-label"><?php echo __('visits.form.completed_question', '¿Se completó la reparación?'); ?></label>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="completion_status" id="status-success" value="success" checked>
                        <label class="form-check-label" for="status-success">
                            <?php echo __('visits.form.completed_yes', 'Sí, la reparación fue exitosa'); ?>
                        </label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="completion_status" id="status-failure" value="failure">
                        <label class="form-check-label" for="status-failure">
                            <?php echo __('visits.form.completed_no', 'No, no se pudo completar la reparación'); ?>
                        </label>
                    </div>
                </div>
                
                <div id="success-fields">
                    <div class="mb-3">
                        <label for="comments" class="form-label"><?php echo __('visits.form.comments', 'Comentarios sobre la reparación'); ?></label>
                        <textarea class="form-control" id="comments" name="comments" rows="3" required></textarea>
                    </div>
                </div>
                
                <div id="failure-fields" class="d-none">
                    <div class="mb-3">
                        <label for="failure-reason" class="form-label"><?php echo __('visits.form.failure_reason', 'Motivo de no finalización'); ?></label>
                        <textarea class="form-control" id="failure-reason" name="failure_reason" rows="3"></textarea>
                    </div>
                </div>
                
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle"></i> <?php echo __('visits.actions.finalize_visit', 'Finalizar Visita'); ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Processing Indicator -->
    <div id="processing" class="position-fixed top-0 start-0 w-100 h-100 d-none" style="background-color: rgba(0,0,0,0.5); z-index: 9999;">
        <div class="d-flex justify-content-center align-items-center h-100">
            <div class="card p-4">
                <div class="text-center">
                    <div class="spinner-border text-primary mb-3" role="status"></div>
                    <h5 id="processing-message"><?php echo __('common.processing', 'Procesando...'); ?></h5>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- HTML5 QR Code Scanner Script -->
<script src="https://unpkg.com/html5-qrcode@2.2.1/html5-qrcode.min.js"></script>

<?php include_once 'scan_qr_footer.php'; ?>
<script>
// Exponer textos traducidos para scan_qr.js
window.I18N = {
  scanQr: {
    getting: <?php echo json_encode(__('tech.scan_qr.location_status.getting', 'Obteniendo su ubicación...')); ?>,
    error: <?php echo json_encode(__('tech.scan_qr.location_status.error', 'Error al obtener su ubicación. Por favor, permita el acceso a su ubicación.')); ?>,
    yourLocation: <?php echo json_encode(__('tech.scan_qr.map.your_location', 'Su ubicación actual')); ?>,
    clientLocation: <?php echo json_encode(__('tech.scan_qr.map.client_location', 'Ubicación del cliente')); ?>,
    distanceLabel: <?php echo json_encode(__('tech.scan_qr.distance.label', 'Distancia al cliente:')); ?>,
    distanceSuffix: <?php echo json_encode(__('tech.scan_qr.distance.suffix', 'metros')); ?>,
    debugTitle: <?php echo json_encode(__('tech.scan_qr.debug.title', 'Información de depuración:')); ?>,
    yourPosition: <?php echo json_encode(__('tech.scan_qr.debug.your_position', 'Tu posición')); ?>,
    clientPosition: <?php echo json_encode(__('tech.scan_qr.debug.client_position', 'Posición del cliente')); ?>,
    withinTest: <?php echo json_encode(__('tech.scan_qr.range.within_test', 'Usted se encuentra dentro del rango permitido (modo prueba: 2 km)')); ?>,
    outsideTest: <?php echo json_encode(__('tech.scan_qr.range.outside_test', 'Usted se encuentra fuera del rango permitido. Debe estar a menos de 2 km del cliente (modo prueba).')); ?>
  },
  common: {
    geoUnsupported: <?php echo json_encode(__('geolocation.unsupported', 'Su navegador no soporta geolocalización.')); ?>
  }
};
</script>
