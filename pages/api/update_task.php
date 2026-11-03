<?php
/**
 * API: Update Task
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

$taskId = (int)($_POST['task_id'] ?? 0);
if (!$taskId) {
    echo json_encode(['success' => false, 'message' => 'Task ID required']);
    exit;
}

$task = getTaskById($taskId);
if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task tidak ditemukan']);
    exit;
}

// Permission check
$isLeader = isProjectLeader($task['project_id'], $currentUser['id']);
$isCreator = $task['created_by'] == $currentUser['id'];
$isAssignee = $task['assigned_to'] == $currentUser['id'];

if (!$isLeader && !$isCreator && !$isAssignee && !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk mengubah task ini']);
    exit;
}

$title = cleanInput($_POST['title'] ?? '');
if (empty($title)) {
    echo json_encode(['success' => false, 'message' => 'Judul task wajib diisi']);
    exit;
}

$description = cleanInput($_POST['description'] ?? '');
$notes = cleanInput($_POST['notes'] ?? '');
$newColumnId = (int)($_POST['column_id'] ?? $task['column_id']);
$assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
$startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
$targetEndDate = !empty($_POST['target_end_date']) ? $_POST['target_end_date'] : null;
$cardColor = $_POST['card_color'] ?? '#ffffff';
$priority = $_POST['priority'] ?? 'medium';

// Only leader can reassign to other users
if ($assignedTo && $assignedTo != $currentUser['id'] && $assignedTo != $task['assigned_to']) {
    if (!$isLeader && !$isCreator && !isAdmin()) {
        $assignedTo = $task['assigned_to']; // Keep original
    } else if (!isProjectMember($task['project_id'], $assignedTo)) {
        echo json_encode(['success' => false, 'message' => 'User bukan anggota project']);
        exit;
    }
}

// Validate column belongs to same project
$conn = db();
$stmt = $conn->prepare("SELECT project_id, is_done_column FROM project_columns WHERE id = ?");
$stmt->execute([$newColumnId]);
$col = $stmt->fetch();

if (!$col || $col['project_id'] != $task['project_id']) {
    echo json_encode(['success' => false, 'message' => 'Kolom tidak valid']);
    exit;
}

try {
    $conn->beginTransaction();
    
    // If column changed, record status history
    $columnChanged = ($newColumnId != $task['column_id']);
    
    // Update task
    $stmt = $conn->prepare("
        UPDATE tasks 
        SET title = ?, description = ?, notes = ?, column_id = ?, assigned_to = ?, 
            start_date = ?, target_end_date = ?, card_color = ?, priority = ?
        WHERE id = ?
    ");
    $stmt->execute([
        $title, $description, $notes, $newColumnId, $assignedTo,
        $startDate, $targetEndDate, $cardColor, $priority, $taskId
    ]);
    
    // Record status change
    if ($columnChanged) {
        $stmt = $conn->prepare("
            INSERT INTO task_status_history (task_id, from_column_id, to_column_id, changed_by, notes)
            VALUES (?, ?, ?, ?, 'Column changed via edit')
        ");
        $stmt->execute([$taskId, $task['column_id'], $newColumnId, $currentUser['id']]);
        
        // Set actual_end_date if moved to done column
        if ($col['is_done_column']) {
            $stmt = $conn->prepare("UPDATE tasks SET actual_end_date = CURDATE() WHERE id = ? AND actual_end_date IS NULL");
            $stmt->execute([$taskId]);
        } else {
            // If moved out of done column, clear actual_end_date
            $stmt = $conn->prepare("UPDATE tasks SET actual_end_date = NULL WHERE id = ?");
            $stmt->execute([$taskId]);
        }
    }
    
    // Log activity
    logActivity($currentUser['id'], $task['project_id'], $taskId, 'task_updated', 'Task updated: ' . $title);
    
    // Update project progress
    $progress = updateProjectProgress($task['project_id']);
    
    $conn->commit();
    
    echo json_encode([
        'success' => true, 
        'message' => 'Task berhasil diperbarui',
        'progress' => $progress
    ]);
    
} catch (Exception $e) {
    $conn->rollBack();
    error_log("Update task error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}