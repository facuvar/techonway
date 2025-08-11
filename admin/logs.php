<?php
/**
 * Visor de Logs del Sistema
 */
require_once '../includes/init.php';
require_once '../includes/Logger.php';

// Verificar autenticación admin
$auth->requireAdmin();

// Procesar acciones
if ($_POST) {
    if (isset($_POST['clear_logs'])) {
        Logger::clearLogs();
        Logger::info('Logs limpiados por admin', ['admin_id' => $_SESSION['user_id']]);
        $message = 'Logs limpiados exitosamente';
    }
}

// Obtener parámetros
$lines = isset($_GET['lines']) ? (int)$_GET['lines'] : 100;
$level_filter = isset($_GET['level']) ? $_GET['level'] : '';

// Obtener logs y estadísticas
$logs = Logger::getRecentLogs($lines);
$stats = Logger::getStats();

// Filtrar por nivel si se especifica
if ($level_filter) {
    $logs = array_filter($logs, function($log) use ($level_filter) {
        return $log['level'] === $level_filter;
    });
}

// Incluir header (ya incluye sidebar automáticamente)
include '../templates/header.php';
?>

<div class="main-content">
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="bi bi-file-text"></i> Sistema de Logs
                        </h4>
                        <div class="d-flex gap-2">
                            <button class="btn btn-sm btn-danger" data-bs-toggle="modal" data-bs-target="#clearLogsModal">
                                <i class="bi bi-trash"></i> Limpiar Logs
                            </button>
                            <button class="btn btn-sm btn-secondary" onclick="location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Actualizar
                            </button>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Estadísticas -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="stat-card bg-primary text-white p-3 rounded">
                                    <h5>Total Logs</h5>
                                    <h3><?php echo number_format($stats['total']); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="stat-card bg-success text-white p-3 rounded">
                                    <h5>Tamaño Archivo</h5>
                                    <h3><?php echo formatBytes($stats['size']); ?></h3>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="stat-card bg-info text-white p-3 rounded">
                                    <h5>Por Nivel</h5>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($stats['by_level'] as $level => $count): ?>
                                            <span class="badge bg-light text-dark">
                                                <?php echo $level; ?>: <?php echo $count; ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Número de líneas:</label>
                                <select class="form-select" onchange="updateFilter()">
                                    <option value="50" <?php echo $lines == 50 ? 'selected' : ''; ?>>50</option>
                                    <option value="100" <?php echo $lines == 100 ? 'selected' : ''; ?>>100</option>
                                    <option value="200" <?php echo $lines == 200 ? 'selected' : ''; ?>>200</option>
                                    <option value="500" <?php echo $lines == 500 ? 'selected' : ''; ?>>500</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Filtrar por nivel:</label>
                                <select class="form-select" id="levelFilter" onchange="updateFilter()">
                                    <option value="">Todos</option>
                                    <option value="ERROR" <?php echo $level_filter == 'ERROR' ? 'selected' : ''; ?>>ERROR</option>
                                    <option value="WARNING" <?php echo $level_filter == 'WARNING' ? 'selected' : ''; ?>>WARNING</option>
                                    <option value="INFO" <?php echo $level_filter == 'INFO' ? 'selected' : ''; ?>>INFO</option>
                                    <option value="DEBUG" <?php echo $level_filter == 'DEBUG' ? 'selected' : ''; ?>>DEBUG</option>
                                    <option value="DATABASE" <?php echo $level_filter == 'DATABASE' ? 'selected' : ''; ?>>DATABASE</option>
                                    <option value="AUTH" <?php echo $level_filter == 'AUTH' ? 'selected' : ''; ?>>AUTH</option>
                                    <option value="PHP_ERROR" <?php echo $level_filter == 'PHP_ERROR' ? 'selected' : ''; ?>>PHP_ERROR</option>
                                    <option value="PHP_WARNING" <?php echo $level_filter == 'PHP_WARNING' ? 'selected' : ''; ?>>PHP_WARNING</option>
                                </select>
                            </div>
                        </div>

                        <!-- Logs -->
                        <div class="table-responsive" style="max-height: 600px; overflow-y: auto;">
                            <table class="table table-sm">
                                <thead class="table-dark sticky-top">
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Nivel</th>
                                        <th>Mensaje</th>
                                        <th>IP</th>
                                        <th>URI</th>
                                        <th>Memoria</th>
                                        <th>Contexto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted">
                                                No hay logs disponibles
                                            </td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($logs as $log): ?>
                                            <tr class="log-entry log-<?php echo strtolower($log['level']); ?>">
                                                <td class="text-nowrap small">
                                                    <?php echo date('H:i:s', strtotime($log['timestamp'])); ?>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php echo getLevelColor($log['level']); ?>">
                                                        <?php echo $log['level']; ?>
                                                    </span>
                                                </td>
                                                <td class="log-message">
                                                    <?php echo htmlspecialchars($log['message']); ?>
                                                </td>
                                                <td class="small"><?php echo $log['ip']; ?></td>
                                                <td class="small" title="<?php echo htmlspecialchars($log['request_uri']); ?>">
                                                    <?php echo truncateString($log['request_uri'], 30); ?>
                                                </td>
                                                <td class="small">
                                                    <?php echo formatBytes($log['memory_usage']); ?>
                                                </td>
                                                <td>
                                                    <?php if (!empty($log['context'])): ?>
                                                        <button class="btn btn-sm btn-outline-info" 
                                                                onclick="showContext(<?php echo htmlspecialchars(json_encode($log['context'])); ?>)">
                                                            <i class="bi bi-info-circle"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para limpiar logs -->
<div class="modal fade" id="clearLogsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Limpieza</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                ¿Estás seguro que quieres limpiar todos los logs? Esta acción no se puede deshacer.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form method="POST" style="display: inline;">
                    <button type="submit" name="clear_logs" class="btn btn-danger">Limpiar Logs</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal para contexto -->
<div class="modal fade" id="contextModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Contexto del Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <pre id="contextContent"></pre>
            </div>
        </div>
    </div>
</div>

<?php if (isset($message)): ?>
<div class="toast-container position-fixed bottom-0 end-0 p-3">
    <div class="toast show" role="alert">
        <div class="toast-header">
            <strong class="me-auto">Sistema</strong>
            <button type="button" class="btn-close" data-bs-dismiss="toast"></button>
        </div>
        <div class="toast-body">
            <?php echo $message; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<style>
.stat-card {
    text-align: center;
    margin-bottom: 1rem;
}
.log-error {
    border-left: 4px solid #dc3545;
    background-color: rgba(220, 53, 69, 0.05);
}
.log-warning {
    border-left: 4px solid #ffc107;
    background-color: rgba(255, 193, 7, 0.05);
}
.log-info {
    border-left: 4px solid #0dcaf0;
    background-color: rgba(13, 202, 240, 0.05);
}
.log-debug {
    border-left: 4px solid #6c757d;
    background-color: rgba(108, 117, 125, 0.05);
}
.log-database {
    border-left: 4px solid #198754;
    background-color: rgba(25, 135, 84, 0.05);
}
.log-auth {
    border-left: 4px solid #6f42c1;
    background-color: rgba(111, 66, 193, 0.05);
}
.log-php_error {
    border-left: 4px solid #dc3545;
    background-color: rgba(220, 53, 69, 0.08);
}
.log-php_warning {
    border-left: 4px solid #ffc107;
    background-color: rgba(255, 193, 7, 0.08);
}
.log-message {
    max-width: 300px;
    word-wrap: break-word;
}
/* Mejorar legibilidad del texto en dark mode */
.table-dark .log-entry {
    color: #fff !important;
}
.table-dark .log-entry td {
    color: #fff !important;
}
/* Asegurar que los badges se vean bien */
.badge {
    font-weight: 500;
}
</style>

<script>
function updateFilter() {
    const lines = document.querySelector('select').value;
    const level = document.getElementById('levelFilter').value;
    const url = new URL(window.location);
    url.searchParams.set('lines', lines);
    if (level) {
        url.searchParams.set('level', level);
    } else {
        url.searchParams.delete('level');
    }
    window.location = url;
}

function showContext(context) {
    document.getElementById('contextContent').textContent = JSON.stringify(context, null, 2);
    new bootstrap.Modal(document.getElementById('contextModal')).show();
}

// Auto refresh cada 30 segundos
setInterval(() => {
    if (!document.querySelector('.modal.show')) {
        location.reload();
    }
}, 30000);
</script>

<?php
include '../templates/footer.php';

// Funciones helper
function getLevelColor($level) {
    switch ($level) {
        case 'ERROR': return 'danger';
        case 'WARNING': return 'warning';
        case 'INFO': return 'info';
        case 'DEBUG': return 'secondary';
        case 'DATABASE': return 'success';
        case 'AUTH': return 'purple';
        case 'PHP_ERROR': return 'danger';
        case 'PHP_WARNING': return 'warning';
        case 'PHP_INFO': return 'info';
        default: return 'primary';
    }
}

function formatBytes($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

function truncateString($string, $length) {
    return strlen($string) > $length ? substr($string, 0, $length) . '...' : $string;
}
?>
