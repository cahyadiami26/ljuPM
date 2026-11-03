<?php
/**
 * API Router
 * All API endpoints centralized
 */
ob_start();
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../config/app.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/functions.php';
}

// Set JSON header
header('Content-Type: application/json; charset=utf-8');

// Check login
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Not authenticated']);
    exit;
}

$currentUser = getCurrentUser();
$action = $_GET['action'] ?? '';

// Map actions to files
$apiActions = [
    'move_task'          => 'move_task.php',
    'update_task'        => 'update_task.php',
    'delete_task'        => 'delete_task.php',
    'upload_attachment'  => 'upload_attachment.php',
    'delete_attachment'  => 'delete_attachment.php',
    'add_column'         => 'add_column.php',
    'delete_column'      => 'delete_column.php',
    'get_users'          => 'get_users.php',
    'get_project_stats'  => 'get_project_stats.php',
    'delete_project'     => 'delete_project.php',
    'update_project_status' => 'update_project_status.php',
];

if (!isset($apiActions[$action])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action: ' . $action]);
    exit;
}

$targetFile = __DIR__ . '/' . $apiActions[$action];

if (!file_exists($targetFile)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'API endpoint not found']);
    exit;
}

// Verify CSRF for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!verifyCSRFToken($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }
}

// Include target file
require $targetFile;