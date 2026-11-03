<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../config/session.php';
    require_once __DIR__ . '/../includes/functions.php';
}

$currentUser = getCurrentUser();
$currentPage = $_GET['page'] ?? 'dashboard';
$pageTitle = $pageTitle ?? APP_NAME;
$avatar = $_SESSION['avatar'];
$currentUseravatar = "https://dms.lju.co.id/uploads/avatars/".$avatar;
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - <?= APP_SHORT_NAME ?></title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="<?= LOGO_URL ?>">
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Google Fonts - Inter -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <!-- SortableJS untuk Kanban -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    
    <!-- SweetAlert2 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    
    <!-- Select2 -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/select2-bootstrap-5-theme@1.3.0/dist/select2-bootstrap-5-theme.min.css" rel="stylesheet">
    
    <!-- Flatpickr Date Picker -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    
    <!-- Custom Styles -->
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
    
    <!-- Meta -->
    <meta name="csrf-token" content="<?= generateCSRFToken() ?>">
    <meta name="base-url" content="<?= BASE_URL ?>">
    <style>
        /* ========== AVATAR ========== */
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            object-fit: cover;
        }

        .avatar-sm { width: 32px; height: 32px; border-radius: 8px; }
        .avatar-lg { width: 56px; height: 56px; border-radius: 14px; }
        .avatar-xl { width: 80px; height: 80px; border-radius: 16px; }
        .avatar-circle { border-radius: 50%; }
    </style>
</head>
<body>

<!-- Top Navbar -->
<nav class="top-navbar">
    <div class="navbar-inner">
        <!-- Logo & Brand -->
        <div class="navbar-brand-section">
            <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                <i class="fas fa-bars"></i>
            </button>
            <a href="<?= BASE_URL ?>/?page=dashboard" class="navbar-brand">
                <img src="<?= LOGO_URL ?>" alt="LJU Logo" class="brand-logo">
                <div class="brand-text">
                    <div class="brand-title"><?= APP_SHORT_NAME ?></div>
                    <div class="brand-subtitle"><?= COMPANY_SHORT ?> • v<?= APP_VERSION ?></div>
                </div>
            </a>
        </div>
        
        <!-- Page Title Area -->
        <div class="navbar-page-title">
            <h1 class="page-title-text" id="pageTitle"><?= htmlspecialchars($pageTitle) ?></h1>
        </div>
        
        <!-- Right Section -->
        <div class="navbar-right">
            <!-- Search -->
            <div class="navbar-search d-none d-md-flex">
                <i class="fas fa-search search-icon"></i>
                <input type="text" placeholder="Search projects, tasks..." id="globalSearch">
            </div>
            
            <!-- Live Status -->
            <div class="live-status d-none d-lg-flex">
                <span class="live-dot"></span>
                <span class="live-text">LIVE</span>
            </div>
            
            <!-- Notifications -->
            <div class="dropdown">
                <button class="navbar-icon-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                    <span class="notif-badge">3</span>
                </button>
                <div class="dropdown-menu dropdown-menu-end notif-dropdown">
                    <div class="notif-header">
                        <h6 class="mb-0">Notifications</h6>
                        <a href="#" class="text-primary small">Mark all read</a>
                    </div>
                    <div class="notif-body">
                        <a href="#" class="notif-item">
                            <div class="notif-icon bg-primary-soft">
                                <i class="fas fa-tasks text-primary"></i>
                            </div>
                            <div class="notif-content">
                                <div class="notif-title">New task assigned</div>
                                <div class="notif-time">2 minutes ago</div>
                            </div>
                        </a>
                        <a href="#" class="notif-item">
                            <div class="notif-icon bg-warning-soft">
                                <i class="fas fa-clock text-warning"></i>
                            </div>
                            <div class="notif-content">
                                <div class="notif-title">Task deadline approaching</div>
                                <div class="notif-time">1 hour ago</div>
                            </div>
                        </a>
                        <a href="#" class="notif-item">
                            <div class="notif-icon bg-success-soft">
                                <i class="fas fa-check-circle text-success"></i>
                            </div>
                            <div class="notif-content">
                                <div class="notif-title">Project completed</div>
                                <div class="notif-time">Yesterday</div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- User Profile -->
            <div class="dropdown">
                <button class="user-profile-btn" data-bs-toggle="dropdown" aria-expanded="false">
                    <div class="user-avatar-sm">
                        <img src="<?= $currentUseravatar ?>" class="avatar"></img>
                    </div>
                    <div class="user-info d-none d-md-block">
                        <div class="user-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                        <div class="user-role"><?= htmlspecialchars($currentUser['position'] ?: 'User') ?></div>
                    </div>
                    <i class="fas fa-chevron-down d-none d-md-inline ms-2 small"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end user-dropdown">
                    <div class="user-dropdown-header">
                        <div class="user-avatar-lg">
                            <img src="<?= $currentUseravatar ?>" class="avatar"></img>
                        </div>
                        <div class="user-dropdown-info">
                            <div class="user-dropdown-name"><?= htmlspecialchars($currentUser['name']) ?></div>
                            <div class="user-dropdown-email"><?= htmlspecialchars($currentUser['email']) ?></div>
                            <span class="badge bg-primary-soft text-primary mt-1"><?= htmlspecialchars($currentUser['position']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Flash Message -->
<?php $flash = getFlash(); ?>
<?php if ($flash): ?>
<div class="flash-message-container">
    <div class="alert alert-<?= $flash['type'] ?> alert-dismissible fade show flash-alert" role="alert">
        <i class="fas fa-<?= $flash['type'] === 'success' ? 'check-circle' : ($flash['type'] === 'danger' ? 'exclamation-circle' : 'info-circle') ?> me-2"></i>
        <?= htmlspecialchars($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php endif; ?>

<div class="app-container">