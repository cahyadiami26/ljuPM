<?php
/**
 * API: Update Project Status
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

$projectId = (int)($_POST['project_id'] ?? 0);
$newStatus = $_POST['status'] ?? '';
$notes = cleanInput($_POST['notes'] ?? '');

$validStatuses = array_keys(PROJECT_STATUS);
if (!$projectId || !in_array($newStatus, $validStatuses)) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
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

$oldStatus = $project['status'];
if ($oldStatus === $newStatus) {
    echo json_encode(['success' => false, 'message' => 'Status sudah sama']);
    exit;
}

try {
    $conn = db();
    $conn->beginTransaction();
    
    // Update project
    $stmt = $conn->prepare("UPDATE projects SET status = ? WHERE id = ?");
    $stmt->execute([$newStatus, $projectId]);
    
    // If completed, set actual_end_date
    if ($newStatus === 'completed') {
        $stmt = $conn->prepare("UPDATE projects SET actual_end_date = CURDATE() WHERE id = ? AND actual_end_date IS NULL");
        $stmt->execute([$projectId]);
    }
    
    // Record history
    $stmt = $conn->prepare("
        INSERT INTO project_status_history (project_id, from_status, to_status, changed_by, notes)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$projectId, $oldStatus, $newStatus, $currentUser['id'], $notes]);
    
    logActivity($currentUser['id'], $projectId, null, 'project_status_changed', "Status: $oldStatus → $newStatus");
    
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Status project berhasil diperbarui']);
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Update project status error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}