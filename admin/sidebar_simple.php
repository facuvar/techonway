<div class="sidebar-header">
    <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="TecLocator Logo" class="img-fluid" style="max-height: 50px;">
</div>
<div class="sidebar-user">
    <div class="user-info">
        <i class="bi bi-person-circle" style="font-size:2.5rem;"></i>
        <div class="mt-2 fw-semibold"><?php echo $_SESSION['user_name'] ?? 'Admin'; ?></div>
        <small class="text-white">Administrador</small>
    </div>
</div>
<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/clients.php">
            <i class="bi bi-building"></i> Clientes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/technicians.php">
            <i class="bi bi-person-gear"></i> Técnicos
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/admins.php">
            <i class="bi bi-shield-lock"></i> Administradores
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/tickets.php">
            <i class="bi bi-ticket-perforated"></i> Tickets
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link active" href="<?php echo BASE_URL; ?>admin/calendar.php">
            <i class="bi bi-calendar-event"></i> Calendario de Citas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/service_requests.php">
            <i class="bi bi-journal-text"></i> Solicitudes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/visits.php">
            <i class="bi bi-clipboard-check"></i> Visitas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>admin/import_clients.php">
            <i class="bi bi-file-earmark-excel"></i> Importar Clientes
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>profile.php">
            <i class="bi bi-person"></i> Perfil
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php">
            <i class="bi bi-box-arrow-right"></i> Cerrar Sesión
        </a>
    </li>
</ul>
