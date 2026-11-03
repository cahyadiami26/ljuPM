<?php
/**
 * API: Upload Attachment
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
if (!canViewProject($task['project_id'], $currentUser['id'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

if (!isProjectMember($task['project_id'], $currentUser['id']) && !isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Bukan anggota project']);
    exit;
}

if (!isset($_FILES['attachment']) || $_FILES['attachment']['error'] !== UPLOAD_ERR_OK) {
    $errorMsg = 'File tidak dapat diupload';
    if (isset($_FILES['attachment'])) {
        switch ($_FILES['attachment']['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $errorMsg = 'File terlalu besar';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMsg = 'File tidak lengkap terupload';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMsg = 'Tidak ada file dipilih';
                break;
        }
    }
    echo json_encode(['success' => false, 'message' => $errorMsg]);
    exit;
}

$result = uploadAttachment($taskId, $_FILES['attachment'], $currentUser['id']);

if ($result['success']) {
    logActivity($currentUser['id'], $task['project_id'], $taskId, 'attachment_uploaded', 'File: ' . $_FILES['attachment']['name']);
    echo json_encode([
        'success' => true,
        'message' => 'File berhasil diupload',
        'attachment_id' => $result['id']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => $result['message']]);
}