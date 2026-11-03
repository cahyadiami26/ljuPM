<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
}

$currentUser = getCurrentUser();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect(BASE_URL . '/?page=dashboard');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    setFlash('danger', 'Token tidak valid.');
    redirect(BASE_URL . '/?page=dashboard');
}

$projectId = (int)($_POST['project_id'] ?? 0);
$columnId = (int)($_POST['column_id'] ?? 0);

if (!$projectId || !$columnId) {
    setFlash('danger', 'Data tidak lengkap.');
    redirect(BASE_URL . '/?page=dashboard');
}

// Permission check
if (!canViewProject($projectId, $currentUser['id'])) {
    setFlash('danger', 'Anda tidak memiliki akses ke project ini.');
    redirect(BASE_URL . '/?page=projects&action=index');
}

// Only project members can create tasks
if (!isProjectMember($projectId, $currentUser['id']) && !isAdmin() && !isDireksi()) {
    setFlash('danger', 'Anda bukan anggota project ini.');
    redirect(BASE_URL . '/?page=projects&action=view&id=' . $projectId);
}

// Validate column belongs to project
$conn = db();
$stmt = $conn->prepare("SELECT id FROM project_columns WHERE id = ? AND project_id = ?");
$stmt->execute([$columnId, $projectId]);
if (!$stmt->fetch()) {
    setFlash('danger', 'Kolom tidak valid.');
    redirect(BASE_URL . '/?page=projects&action=view&id=' . $projectId);
}

$title = cleanInput($_POST['title'] ?? '');
if (empty($title)) {
    setFlash('danger', 'Judul task wajib diisi.');
    redirect(BASE_URL . '/?page=projects&action=view&id=' . $projectId);
}

// Only project leader can assign tasks to other members
$isLeader = isProjectLeader($projectId, $currentUser['id']);
$isCreator = getProjectById($projectId)['created_by'] == $currentUser['id'];

$assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;

// If assigning to someone else, only leader can do this
if ($assignedTo && $assignedTo != $currentUser['id']) {
    if (!$isLeader && !$isCreator && !isAdmin()) {
        // Regular member can only assign to themselves
        $assignedTo = $currentUser['id'];
    } else {
        // Verify assignee is project member
        if (!isProjectMember($projectId, $assignedTo)) {
            setFlash('warning', 'User yang di-assign bukan anggota project. Task tetap dibuat tanpa assignment.');
            $assignedTo = null;
        }
    }
}

$data = [
    'project_id' => $projectId,
    'column_id' => $columnId,
    'title' => $title,
    'description' => cleanInput($_POST['description'] ?? ''),
    'notes' => cleanInput($_POST['notes'] ?? ''),
    'assigned_to' => $assignedTo,
    'created_by' => $currentUser['id'],
    'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
    'target_end_date' => !empty($_POST['target_end_date']) ? $_POST['target_end_date'] : null,
    'card_color' => $_POST['card_color'] ?? '#ffffff',
    'priority' => $_POST['priority'] ?? 'medium',
];

$taskId = createTask($data);

if (!$taskId) {
    setFlash('danger', 'Gagal membuat task.');
    redirect(BASE_URL . '/?page=projects&action=view&id=' . $projectId);
}

// Handle attachments
if (!empty($_FILES['attachments']['name'][0])) {
    $files = $_FILES['attachments'];
    $count = count($files['name']);
    $uploaded = 0;
    $failed = 0;
    
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_OK) {
            $file = [
                'name'     => $files['name'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'size'     => $files['size'][$i],
                'type'     => $files['type'][$i],
                'error'    => $files['error'][$i],
            ];
            $result = uploadAttachment($taskId, $file, $currentUser['id']);
            if ($result['success']) {
                $uploaded++;
            } else {
                $failed++;
            }
        }
    }
    
    if ($failed > 0) {
        setFlash('warning', "Task dibuat. $uploaded file berhasil diupload, $failed gagal.");
    } else {
        setFlash('success', "Task berhasil dibuat dengan $uploaded lampiran.");
    }
} else {
    setFlash('success', 'Task berhasil dibuat.');
}

// Update project progress
updateProjectProgress($projectId);

redirect(BASE_URL . '/?page=projects&action=view&id=' . $projectId);