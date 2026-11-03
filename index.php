<?php
/**
 * Main Router
 * Project Management System - PT. Lampung Jasa Utama
 */
ob_start();
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';

// Require login for all pages
requireLogin();

// Get current user
$currentUser = getCurrentUser();

// Simple routing
$page = $_GET['page'] ?? 'dashboard';
$action = $_GET['action'] ?? 'index';
$id = $_GET['id'] ?? null;

// Whitelist pages
$allowedPages = [
    'dashboard' => 'pages/dashboard.php',
    'projects'  => 'pages/projects/',
    'tasks'     => 'pages/tasks/',
    'api'       => 'pages/api/',
    'profile'   => 'pages/profile.php',
];

// Handle routing
$pagePath = null;

if (!isset($allowedPages[$page])) {
    http_response_code(404);
    $pagePath = __DIR__ . '/pages/404.php';
} else {
    $target = $allowedPages[$page];
    
    // If it's a directory, look for action
    if (substr($target, -1) === '/') {
        $actionMap = [
            'index'      => 'index.php',
            'create'     => 'create.php',
            'view'       => 'view.php',
            'edit'       => 'edit.php',
            'delete'     => 'delete.php',
            'export_pdf' => 'export_pdf.php',
            'move'       => 'move.php',
            'update'       => 'update.php',
        ];
        
        $actionFile = $actionMap[$action] ?? 'index.php';
        $pagePath = __DIR__ . '/' . $target . $actionFile;
    } else {
        $pagePath = __DIR__ . '/' . $target;
    }
}

// API pages don't need layout
if ($page === 'api') {
    if (file_exists($pagePath)) {
        require $pagePath;
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'API endpoint not found']);
    }
    exit;
}

// Cek apakah ini request AJAX (misalnya untuk load modal task view)
$isAjax = !empty($_GET['ajax']);
if ($isAjax) {
    if ($pagePath && file_exists($pagePath)) {
        require $pagePath;
    } else {
        http_response_code(404);
        echo '<div class="modal-body text-center py-5 text-danger">Halaman tidak ditemukan</div>';
    }
    exit;
}

// Load page with layout
if ($pagePath && file_exists($pagePath)) {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/sidebar.php';
    ?>
    <div class="main-content">
        <?php require $pagePath; ?>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
} else {
    require __DIR__ . '/includes/header.php';
    require __DIR__ . '/includes/sidebar.php';
    ?>
    <div class="main-content">
        <div class="container-fluid py-5 text-center">
            <h1 class="display-1 text-primary">404</h1>
            <h3>Halaman Tidak Ditemukan</h3>
            <p class="text-muted">Halaman yang Anda cari tidak tersedia.</p>
            <a href="<?= BASE_URL ?>/?page=dashboard" class="btn btn-primary">
                <i class="fas fa-home me-2"></i>Kembali ke Dashboard
            </a>
        </div>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
}