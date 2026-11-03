<?php
/**
 * API: Delete Project
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

$projectId = (int)($_POST['id'] ?? $_POST['project_id'] ?? 0);
if ($projectId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Project ID tidak valid']);
    exit;
}

$project = getProjectById($projectId);
if (!$project) {
    echo json_encode(['success' => false, 'message' => 'Project tidak ditemukan']);
    exit;
}

// Pembuat project atau Admin boleh menghapus
$isCreator = ((int)$project['created_by'] === (int)$currentUser['id']);
if (!$isCreator && !isAdmin()) {
    echo json_encode([
        'success' => false,
        'message' => 'Hanya pembuat project atau Admin yang dapat menghapus project ini'
    ]);
    exit;
}

try {
    $conn = db();
    $conn->beginTransaction();

    // Hapus file fisik lampiran
    $stmt = $conn->prepare("
        SELECT ta.file_name
        FROM task_attachments ta
        INNER JOIN tasks t ON ta.task_id = t.id
        WHERE t.project_id = ?
    ");
    $stmt->execute([$projectId]);
    $attachments = $stmt->fetchAll();

    foreach ($attachments as $att) {
        $filePath = ATTACHMENT_DIR . $att['file_name'];
        if (!empty($att['file_name']) && file_exists($filePath)) {
            @unlink($filePath);
        }
    }

    $stmt = $conn->prepare("DELETE FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);

    logActivity(
        $currentUser['id'],
        null,
        null,
        'project_deleted',
        'Project dihapus: ' . $project['title']
    );

    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Project berhasil dihapus'
    ]);
} catch (Throwable $e) {
    if (isset($conn) && $conn->inTransaction()) {
        $conn->rollBack();
    }
    error_log('delete_project error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menghapus project'
    ]);
}