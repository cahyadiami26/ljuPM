<?php
/**
 * API: Delete Column from Project
 */

if (!isset($currentUser)) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    header('Content-Type: application/json');
    $currentUser = getCurrentUser();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$columnId = (int)($_POST['column_id'] ?? 0);
$projectId = (int)($_POST['project_id'] ?? 0);

if (!$columnId || !$projectId) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$project = getProjectById($projectId);
if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project tidak ditemukan']);
    exit;
}

// Permission
$isLeader = isProjectLeader($projectId, $currentUser['id']);
$isCreator = $project['created_by'] == $currentUser['id'];
if (!$isLeader && !$isCreator && !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

$conn = db();

// Verify column belongs to project
$stmt = $conn->prepare("SELECT id, title FROM project_columns WHERE id = ? AND project_id = ?");
$stmt->execute([$columnId, $projectId]);
$col = $stmt->fetch();

if (!$col) {
    echo json_encode(['success' => false, 'message' => 'Kolom tidak ditemukan']);
    exit;
}

// Check if column has tasks
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE column_id = ?");
$stmt->execute([$columnId]);
if ($stmt->fetch()['total'] > 0) {
    echo json_encode(['success' => false, 'message' => 'Kolom masih memiliki task. Pindahkan atau hapus task terlebih dahulu']);
    exit;
}

// Check if this is the last column
$stmt = $conn->prepare("SELECT COUNT(*) as total FROM project_columns WHERE project_id = ?");
$stmt->execute([$projectId]);
if ($stmt->fetch()['total'] <= 1) {
    echo json_encode(['success' => false, 'message' => 'Tidak bisa menghapus kolom terakhir']);
    exit;
}

try {
    $stmt = $conn->prepare("DELETE FROM project_columns WHERE id = ?");
    $stmt->execute([$columnId]);
    
    logActivity($currentUser['id'], $projectId, null, 'column_deleted', 'Column deleted: ' . $col['title']);
    
    echo json_encode(['success' => true, 'message' => 'Kolom berhasil dihapus']);
    
} catch (Exception $e) {
    error_log("Delete column error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}