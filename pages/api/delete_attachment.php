<?php
/**
 * API: Delete Attachment
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

$attachmentId = (int)($_POST['id'] ?? 0);
if (!$attachmentId) {
    echo json_encode(['success' => false, 'message' => 'Attachment ID required']);
    exit;
}

$conn = db();
$stmt = $conn->prepare("
    SELECT ta.*, t.project_id, t.created_by as task_creator 
    FROM task_attachments ta 
    INNER JOIN tasks t ON ta.task_id = t.id 
    WHERE ta.id = ?
");
$stmt->execute([$attachmentId]);
$attachment = $stmt->fetch();

if (!$attachment) {
    echo json_encode(['success' => false, 'message' => 'Attachment tidak ditemukan']);
    exit;
}

// Permission: uploader, task creator, project leader, or admin
$canDelete = ($attachment['uploaded_by'] == $currentUser['id']) ||
             ($attachment['task_creator'] == $currentUser['id']) ||
             isProjectLeader($attachment['project_id'], $currentUser['id']) ||
             isAdmin();

if (!$canDelete) {
    echo json_encode(['success' => false, 'message' => 'Anda tidak memiliki akses untuk menghapus lampiran ini']);
    exit;
}

if (deleteAttachment($attachmentId, $currentUser['id'])) {
    logActivity($currentUser['id'], $attachment['project_id'], $attachment['task_id'], 'attachment_deleted', 'File: ' . $attachment['original_name']);
    echo json_encode(['success' => true, 'message' => 'Lampiran berhasil dihapus']);
} else {
    echo json_encode(['success' => false, 'message' => 'Gagal menghapus lampiran']);
}