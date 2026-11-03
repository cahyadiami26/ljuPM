<?php
/**
 * API: Add Column to Project
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
$slug = $_POST['slug'] ?? '';

if (!$projectId || empty($slug)) {
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

// Validate slug
if (!isset(KANBAN_COLUMN_OPTIONS[$slug])) {
    echo json_encode(['success' => false, 'message' => 'Kolom tidak valid']);
    exit;
}

$conn = db();

// Check if column already exists
$stmt = $conn->prepare("SELECT id FROM project_columns WHERE project_id = ? AND slug = ?");
$stmt->execute([$projectId, $slug]);
if ($stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Kolom sudah ada di project ini']);
    exit;
}

try {
    // Get next position
    $stmt = $conn->prepare("SELECT COALESCE(MAX(position), -1) + 1 as next_pos FROM project_columns WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $nextPos = $stmt->fetch()['next_pos'];
    
    $colData = KANBAN_COLUMN_OPTIONS[$slug];
    $isDone = ($slug === 'done') ? 1 : 0;
    
    $stmt = $conn->prepare("
        INSERT INTO project_columns (project_id, title, slug, position, color, is_done_column)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([$projectId, $colData['title'], $slug, $nextPos, $colData['color'], $isDone]);
    
    logActivity($currentUser['id'], $projectId, null, 'column_added', 'Column added: ' . $colData['title']);
    
    echo json_encode(['success' => true, 'message' => 'Kolom berhasil ditambahkan', 'column_id' => $conn->lastInsertId()]);
    
} catch (Exception $e) {
    error_log("Add column error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}