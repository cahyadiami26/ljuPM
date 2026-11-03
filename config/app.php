<?php
/**
 * Application Configuration
 */

// Base URL
define('BASE_URL', 'https://pm.lju.co.id');
define('DMS_URL', 'https://dms.lju.co.id');

// Application Info
define('APP_NAME', 'Project Management System');
define('APP_SHORT_NAME', 'Project Management');
define('APP_VERSION', '1.0.0');
define('COMPANY_NAME', 'PT. Lampung Jasa Utama');
define('COMPANY_SHORT', 'PT. LJU');
define('LOGO_URL', 'https://dms.lju.co.id/assets/img/logo.png');

// Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/../assets/uploads/');
define('ATTACHMENT_DIR', __DIR__ . '/../assets/uploads/attachments/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar']);

// Kanban Column Options
define('KANBAN_COLUMN_OPTIONS', [
    'backlog' => ['title' => 'Backlog', 'color' => '#6c757d', 'icon' => 'fas fa-inbox'],
    'new_request' => ['title' => 'New Request', 'color' => '#0dcaf0', 'icon' => 'fas fa-plus-circle'],
    'todo' => ['title' => 'To Do', 'color' => '#ffc107', 'icon' => 'fas fa-clipboard-list'],
    'trouble_blocking' => ['title' => 'Trouble/Blocking', 'color' => '#dc3545', 'icon' => 'fas fa-exclamation-triangle'],
    'in_progress' => ['title' => 'In Progress', 'color' => '#0d6efd', 'icon' => 'fas fa-spinner'],
    'testing' => ['title' => 'Testing', 'color' => '#6f42c1', 'icon' => 'fas fa-vial'],
    'done' => ['title' => 'Done', 'color' => '#198754', 'icon' => 'fas fa-check-circle']
]);

// Default Kanban Columns
define('DEFAULT_COLUMNS', ['todo', 'in_progress', 'done']);

// Privacy Options
define('PRIVACY_OPTIONS', [
    'personal'   => ['label' => 'Personal', 'icon' => 'fas fa-user-lock', 'desc' => 'Hanya Anda yang bisa melihat'],
    'team'       => ['label' => 'Anggota Tim', 'icon' => 'fas fa-users', 'desc' => 'Hanya anggota tim project'],
    'division'   => ['label' => 'Anggota Bidang', 'icon' => 'fas fa-building', 'desc' => 'Semua anggota bidang terkait'],
    'department' => ['label' => 'Anggota Divisi', 'icon' => 'fas fa-city', 'desc' => 'Semua anggota divisi terkait'],
    'all'        => ['label' => 'Semua User', 'icon' => 'fas fa-globe', 'desc' => 'Semua user dapat melihat']
]);

// Project Status Options
define('PROJECT_STATUS', [
    'planning'   => ['label' => 'Planning', 'color' => '#6c757d', 'badge' => 'secondary'],
    'active'     => ['label' => 'Active', 'color' => '#0d6efd', 'badge' => 'primary'],
    'on_hold'    => ['label' => 'On Hold', 'color' => '#ffc107', 'badge' => 'warning'],
    'completed'  => ['label' => 'Completed', 'color' => '#198754', 'badge' => 'success'],
    'cancelled'  => ['label' => 'Cancelled', 'color' => '#dc3545', 'badge' => 'danger']
]);

// Priority Options
define('PRIORITY_OPTIONS', [
    'low'      => ['label' => 'Low', 'color' => '#198754', 'badge' => 'success'],
    'medium'   => ['label' => 'Medium', 'color' => '#ffc107', 'badge' => 'warning'],
    'high'     => ['label' => 'High', 'color' => '#fd7e14', 'badge' => 'orange'],
    'critical' => ['label' => 'Critical', 'color' => '#dc3545', 'badge' => 'danger']
]);

// Task Card Colors
define('CARD_COLORS', [
    '#ffffff' => 'White',
    '#fff3cd' => 'Yellow',
    '#cff4fc' => 'Cyan',
    '#d1e7dd' => 'Green',
    '#f8d7da' => 'Red',
    '#e2d9f3' => 'Purple',
    '#ffe5d0' => 'Orange',
    '#dee2e6' => 'Gray'
]);

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Error Reporting (set to 0 in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/error.log');

// Create necessary directories
$dirs = [UPLOAD_DIR, ATTACHMENT_DIR, __DIR__ . '/../logs'];
foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
}