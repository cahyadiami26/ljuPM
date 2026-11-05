<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../config/session.php';
    require_once __DIR__ . '/../includes/functions.php';
    //require_once __DIR__ . '/../config/database.php';
}

$currentUser = getCurrentUser();
$currentPage = $_GET['page'] ?? 'dashboard';
$pageTitle = $pageTitle ?? APP_NAME;
$avatar = $_SESSION['avatar'];
$currentUseravatar = "https://dms.lju.co.id/uploads/avatars/".$avatar;
$currId = $_SESSION['user_id'];
$conn = db();
$stmt = $conn->prepare("SELECT
    l.*,
    p.title AS project_title,
    t.title AS task_title,
    t.assigned_to
FROM activity_log l
JOIN projects p ON l.project_id = p.id
JOIN tasks t ON l.task_id = t.id
WHERE l.action IN ('task_created', 'task_updated', 'task_moved')
  AND t.assigned_to = ?
  AND l.created_at >= DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY)
  AND l.created_at < DATE_ADD(
      DATE_SUB(CURDATE(), INTERVAL WEEKDAY(CURDATE()) DAY),
      INTERVAL 7 DAY
  )
ORDER BY l.created_at DESC");
$stmt->execute([$currId]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$notifCount = count($notifications);
    
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
                    <?php if ($notifCount > 0): ?>
                        <span class="notif-badge"><?= $notifCount ?></span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notif-dropdown" style="max-height: 350px; overflow-y: auto;">
                    <div class="notif-header d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                        <h6 class="mb-0">Notifications</h6>
                        <span class="badge bg-primary rounded-pill"><?= $notifCount ?> baru</span>
                    </div>
                    <div class="notif-body">
                        <?php if ($notifCount > 0): ?>
                            <?php foreach ($notifications as $notif): ?>
                                <?php 
                                    // Tentukan ikon & warna berdasarkan aksi
                                    $iconClass = 'fa-tasks text-primary';
                                    $bgClass = 'bg-primary-soft';
                                    $actionText = 'Task diperbarui';
                                    
                                    if ($notif['action'] === 'task_created') {
                                        $iconClass = 'fa-plus-circle text-success';
                                        $bgClass = 'bg-success-soft';
                                        $actionText = 'Task baru dibuat';
                                    } elseif ($notif['action'] === 'task_moved') {
                                        $iconClass = 'fa-exchange-alt text-warning';
                                        $bgClass = 'bg-warning-soft';
                                        $actionText = 'Status task dipindahkan';
                                    }
                                ?>
                                <a href="<?= BASE_URL ?>/?page=projects&action=view&id=<?= $notif['project_id'] ?>" class="notif-item d-flex align-items-start px-3 py-2 border-bottom text-decoration-none">
                                    <div class="notif-icon <?= $bgClass ?> p-2 rounded me-3 text-center" style="min-width: 35px;">
                                        <i class="fas <?= $iconClass ?>"></i>
                                    </div>
                                    <div class="notif-content overflow-hidden">
                                        <div class="notif-title fw-bold text-dark text-truncate" style="font-size: 0.85rem;"><?= htmlspecialchars($notif['task_title']) ?></div>
                                        <div class="small text-muted text-truncate"><?= $actionText ?> di <?= htmlspecialchars($notif['project_title']) ?></div>
                                        <div class="notif-time text-muted" style="font-size: 0.75rem;"><?= date('d M Y, H:i', strtotime($notif['created_at'])) ?></div>
                                    </div>
                                </a>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted small">
                                <i class="fas fa-bell-slash fa-2x mb-2"></i>
                                <p class="mb-0">Tidak ada notifikasi minggu ini.</p>
                            </div>
                        <?php endif; ?>
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
