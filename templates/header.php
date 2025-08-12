<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Gestión de Tickets para Ascensores</title>
    <!-- Preload Poppins WOFF2 (latin) for faster first paint -->
    <link rel="preload" as="font" type="font/woff2" href="<?php echo BASE_URL; ?>assets/fonts/s/poppins/v23/pxiEyp8kv8JHgFVrJJfecg.woff2" crossorigin="anonymous">
    <link rel="preload" as="font" type="font/woff2" href="<?php echo BASE_URL; ?>assets/fonts/s/poppins/v23/pxiByp8kv8JHgFVrLEj6Z1xlFQ.woff2" crossorigin="anonymous">
    <link rel="preload" as="font" type="font/woff2" href="<?php echo BASE_URL; ?>assets/fonts/s/poppins/v23/pxiByp8kv8JHgFVrLCz7Z1xlFQ.woff2" crossorigin="anonymous">
    <!-- Poppins self-hosted: removed Google Fonts -->
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.3/dist/leaflet.css" integrity="sha256-kLaT2GOSpHechhsozzB+flnD+zUyjE2LlfWPgU04xyI=" crossorigin="" />
    <?php
        $poppinsCssV = @filemtime(BASE_PATH . '/assets/css/poppins.css') ?: time();
        $styleCssV = @filemtime(BASE_PATH . '/assets/css/style.css') ?: time();
        $mapFixesCssV = @filemtime(BASE_PATH . '/assets/css/map-fixes.css') ?: time();
    ?>
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/poppins.css?v=<?php echo $poppinsCssV; ?>">
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/style.css?v=<?php echo $styleCssV; ?>">
    <!-- Map Fixes CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>assets/css/map-fixes.css?v=<?php echo $mapFixesCssV; ?>">
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?php echo BASE_URL; ?>assets/img/favicon.png">
<?php $publicLayout = isset($PUBLIC_LAYOUT) && $PUBLIC_LAYOUT; ?>
 </head>
 <body class="dark-mode <?php echo ($auth->isLoggedIn() && !$publicLayout) ? 'has-sidebar' : ''; ?>">
     <?php if ($auth->isLoggedIn() && !$publicLayout): ?>
     <!-- Top Navbar (shows hamburger on mobile) -->
     <nav class="navbar top-navbar d-flex align-items-center px-3 d-md-none">
         <div class="d-flex align-items-center gap-2">
             <!-- Hamburger only on mobile -->
             <button class="btn btn-outline-light d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar" aria-label="Abrir menú">
                 <i class="bi bi-list" style="font-size:1.25rem;"></i>
             </button>
             <a class="navbar-brand mb-0 h1 d-flex align-items-center" href="<?php echo BASE_URL; ?>dashboard.php">
                 <img src="<?php echo BASE_URL; ?>assets/img/logo.png" alt="Logo" style="height:28px;width:auto;"/>
             </a>
         </div>
     </nav>
     
     <!-- Offcanvas Sidebar for mobile -->
     <div class="offcanvas offcanvas-start mobile-sidebar" tabindex="-1" id="mobileSidebar" aria-labelledby="mobileSidebarLabel">
         <div class="offcanvas-header border-bottom">
             <h5 class="offcanvas-title" id="mobileSidebarLabel">Menú</h5>
             <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
         </div>
         <div class="offcanvas-body p-0">
             <div class="sidebar-content">
                 <?php include TEMPLATE_PATH . '/sidebar.php'; ?>
             </div>
         </div>
     </div>
     <?php endif; ?>
 
     <div class="container-fluid">
        <div class="row">
            <?php // $publicLayout ya definido antes del <body> ?>
            <?php if ($auth->isLoggedIn() && !$publicLayout): ?>
                <!-- Sidebar -->
                <div class="col-md-3 col-lg-2 px-0 sidebar d-none d-md-block">
                    <?php include TEMPLATE_PATH . '/sidebar.php'; ?>
                </div>
                <!-- Main content -->
                <div class="col-12 col-md-9 col-lg-10 ms-auto main-content">
            <?php else: ?>
                <!-- Full width for login/register pages -->
                <div class="col-12 main-content">
            <?php endif; ?>
                
                <!-- Flash messages -->
                <?php $flash = getFlash(); ?>
                <?php if ($flash): ?>
                <div class="alert alert-<?php echo $flash['type']; ?> alert-dismissible fade show mt-3" role="alert">
                    <?php echo $flash['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
