<?php
/**
 * API: Delete Task
 */

if (!isset($currentUser)) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    header('Content-Type: application/json');
    $currentUser = getCurrentUser();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$taskId = (int)($_POST['id'] ?? $_POST['task_id'] ?? 0);
if ($taskId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Task ID tidak valid']);
    exit;
}

$task = getTaskById($taskId);
if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task tidak ditemukan']);
    exit;
}

// Pembuat task atau Admin boleh menghapus
$isCreator = ((int)$task['created_by'] === (int)$currentUser['id']);
if (!$isCreator && !isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Hanya pembuat task atau Admin yang dapat menghapus task ini'
    ]);
    exit;
}

try {
    $conn = db();
    $conn->beginTransaction();

    // Hapus file fisik lampiran
    $attachments = getTaskAttachments($taskId);
    foreach ($attachments as $att) {
        $filePath = ATTACHMENT_DIR . $att['file_name'];
        if (!empty($att['file_name']) && file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    $stmt = $conn->prepare("DELETE FROM tasks WHERE id = ?");
    $stmt->execute([$taskId]);

    logActivity(
        $currentUser['id'],
        (int)$task['project_id'],
        null,
        'task_deleted',
        'Task dihapus: ' . $task['title']
    );

    updateProjectProgress((int)$task['project_id']);

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Task berhasil dihapus'
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('delete_task error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghapus task'
    ]);
}