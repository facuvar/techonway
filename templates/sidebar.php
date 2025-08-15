<div class="sidebar-header">
    <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="TecLocator Logo" class="img-fluid" style="max-height: 50px;">
</div>
<div class="sidebar-user">
    <div class="user-info">
        <?php
            $currentUser = null;
            if (isset($_SESSION['user_id'])) {
                $dbSidebar = Database::getInstance();
                $currentUser = $dbSidebar->selectOne('SELECT avatar FROM users WHERE id = ?', [$_SESSION['user_id']]);
            }
            $avatar = $currentUser['avatar'] ?? null;
        ?>
        <?php if (!empty($avatar)): ?>
            <?php 
                $avatarSrc = $avatar;
                // Si no es data URI, agregar BASE_URL
                if (strpos($avatarSrc, 'data:') !== 0) {
                    $avatarSrc = BASE_URL . $avatarSrc;
                }
            ?>
            <img src="<?php echo escape($avatarSrc); ?>" alt="Avatar" style="width:64px;height:64px;border-radius:50%;object-fit:cover;border:2px solid #444;">
        <?php else: ?>
            <i class="bi bi-person-circle" style="font-size:2.5rem;"></i>
        <?php endif; ?>
        <?php 
            $roleText = $_SESSION['user_role'] === 'admin' ? 'Administrador' : 'Técnico';
            $displayName = trim($_SESSION['user_name'] ?? '');
            if ($displayName === '' || strcasecmp($displayName, $roleText) === 0) {
                $displayName = $_SESSION['user_email'] ?? $roleText;
            }
        ?>
        <div class="mt-2 fw-semibold"><?php echo escape($displayName); ?></div>
        <small class="text-white"><?php echo $_SESSION['user_role'] === 'admin' ? __('sidebar.role.admin') : __('sidebar.role.technician'); ?></small>
    </div>
</div>
<ul class="nav flex-column">
    <li class="nav-item">
        <?php if ($auth->isAdmin()): ?>
            <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo BASE_URL; ?>admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> <?php echo __('sidebar.dashboard', 'Dashboard'); ?>
            </a>
        <?php else: ?>
            <a class="nav-link <?php echo isActive('dashboard.php'); ?>" href="<?php echo BASE_URL; ?>technician/dashboard.php">
                <i class="bi bi-speedometer2"></i> <?php echo __('tech.dashboard.title', 'Dashboard de Técnico'); ?>
            </a>
        <?php endif; ?>
    </li>
    
    <?php if ($auth->isAdmin()): ?>
    <!-- Admin Menu Items -->
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('clients.php'); ?>" href="<?php echo BASE_URL; ?>admin/clients.php">
            <i class="bi bi-building"></i> <?php echo __('sidebar.clients'); ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('technicians.php'); ?>" href="<?php echo BASE_URL; ?>admin/technicians.php">
            <i class="bi bi-person-gear"></i> <?php echo __('sidebar.technicians'); ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('admins.php'); ?>" href="<?php echo BASE_URL; ?>admin/admins.php">
            <i class="bi bi-shield-lock"></i> <?php echo __('sidebar.admins', 'Administradores'); ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('tickets.php'); ?>" href="<?php echo BASE_URL; ?>admin/tickets.php">
            <i class="bi bi-ticket-perforated"></i> <?php echo __('sidebar.tickets'); ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('calendar.php'); ?>" href="<?php echo BASE_URL; ?>admin/calendar.php">
            <i class="bi bi-calendar-event"></i> Calendario de Citas
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('service_requests.php'); ?>" href="<?php echo BASE_URL; ?>admin/service_requests.php">
            <i class="bi bi-journal-text"></i> <?php echo __('sidebar.service_requests'); ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('visits.php'); ?>" href="<?php echo BASE_URL; ?>admin/visits.php">
            <i class="bi bi-clipboard-check"></i> <?php echo __('sidebar.visits'); ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('import_clients.php'); ?>" href="<?php echo BASE_URL; ?>admin/import_clients.php">
            <i class="bi bi-file-earmark-excel"></i> <?php echo __('sidebar.import_clients'); ?>
        </a>
    </li>
    <?php else: ?>
    <!-- Technician Menu Items -->
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('my-tickets.php'); ?>" href="<?php echo BASE_URL; ?>technician/my-tickets.php">
            <i class="bi bi-ticket-perforated"></i> <?php echo __('tech.menu.my_tickets', 'Mis Tickets'); ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('completed-visits.php'); ?>" href="<?php echo BASE_URL; ?>technician/completed-visits.php">
            <i class="bi bi-clipboard-check"></i> <?php echo __('tech.menu.my_visits', 'Mis Visitas'); ?>
        </a>
    </li>
    <?php endif; ?>
    
    <li class="nav-item">
        <a class="nav-link <?php echo isActive('profile.php'); ?>" href="<?php echo BASE_URL; ?>profile.php">
            <i class="bi bi-person"></i> <?php echo __('sidebar.profile'); ?>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link" href="<?php echo BASE_URL; ?>logout.php">
            <i class="bi bi-box-arrow-right"></i> <?php echo __('sidebar.logout'); ?>
        </a>
    </li>
</ul>

<hr class="my-3" />
<div class="px-3 pb-3">
    <div class="text-uppercase small mb-2"><?php echo __('sidebar.language'); ?></div>
    <div class="d-flex align-items-center gap-2" id="languageSwitchContainer">
        <span class="small"><?php echo __('language.es'); ?></span>
        <div class="form-check form-switch m-0">
            <input class="form-check-input" type="checkbox" id="langSwitch" <?php echo (($_SESSION['lang'] ?? 'es') === 'en') ? 'checked' : ''; ?>>
        </div>
        <span class="small"><?php echo __('language.en'); ?></span>
    </div>
    <script>
        // Evitar ejecución múltiple
        if (!window.langSwitchInitialized) {
            window.langSwitchInitialized = true;
            
            // Función simple de cambio de idioma
            window.switchLanguage = function(lang) {
                var url = '<?php echo BASE_URL; ?>set_language.php?lang=' + lang;
                window.location.href = url;
            };
            
            // Esperar a que DOM esté listo
            document.addEventListener('DOMContentLoaded', function() {
                // Método más simple - escuchar en document
                document.addEventListener('click', function(e) {
                    if (e.target.id === 'langSwitch') {
                        setTimeout(function() {
                            var lang = e.target.checked ? 'en' : 'es';
                            window.switchLanguage(lang);
                        }, 100);
                    }
                });
            });
        }
    </script>
    </div>
