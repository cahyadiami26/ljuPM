<?php
/**
 * API: Move Task
 * Handles kanban drag & drop task movement
 */

if (!isset($currentUser)) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../config/app.php';
    require_once __DIR__ . '/../../config/database.php';
    require_once __DIR__ . '/../../includes/functions.php';
    header('Content-Type: application/json; charset=utf-8');
    $currentUser = getCurrentUser();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$taskId       = (int)($_POST['task_id'] ?? 0);
$fromColumnId = (int)($_POST['from_column_id'] ?? 0);
$toColumnId   = (int)($_POST['to_column_id'] ?? 0);
$newPosition  = (int)($_POST['new_position'] ?? 0);
$positionsJson = $_POST['positions'] ?? '[]';

if (!$taskId || !$toColumnId) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']);
    exit;
}

$task = getTaskById($taskId);
if (!$task) {
    echo json_encode(['success' => false, 'message' => 'Task tidak ditemukan']);
    exit;
}

if (!canViewProject($task['project_id'], $currentUser['id'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

if (!isProjectMember($task['project_id'], $currentUser['id']) && !isAdmin() && !isDireksi()) {
    echo json_encode(['success' => false, 'message' => 'Anda bukan anggota project ini']);
    exit;
}

$conn = db();

$stmt = $conn->prepare("
    SELECT id, project_id, title, is_done_column 
    FROM project_columns 
    WHERE id = ?
");
$stmt->execute([$toColumnId]);
$targetColumn = $stmt->fetch();

if (!$targetColumn || (int)$targetColumn['project_id'] !== (int)$task['project_id']) {
    echo json_encode(['success' => false, 'message' => 'Kolom target tidak valid']);
    exit;
}

try {
    $conn->beginTransaction();

    $oldColumnId = (int)$task['column_id'];

    /*
     * Update task ke kolom baru
     */
    $stmt = $conn->prepare("
        UPDATE tasks 
        SET column_id = ?, position = ?, updated_at = NOW()
        WHERE id = ?
    ");
    $stmt->execute([$toColumnId, $newPosition, $taskId]);

    /*
     * Jika masuk ke kolom done, set actual_end_date.
     * Jika keluar dari kolom done, clear actual_end_date.
     */
    if ((int)$targetColumn['is_done_column'] === 1) {
        $stmt = $conn->prepare("
            UPDATE tasks 
            SET actual_end_date = CURDATE() 
            WHERE id = ? AND actual_end_date IS NULL
        ");
        $stmt->execute([$taskId]);
    } else {
        $stmt = $conn->prepare("
            UPDATE tasks 
            SET actual_end_date = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$taskId]);
    }

    /*
     * Catat history hanya jika pindah kolom
     */
    if ($oldColumnId !== $toColumnId) {
        $stmt = $conn->prepare("
            INSERT INTO task_status_history 
                (task_id, from_column_id, to_column_id, changed_by, notes)
            VALUES 
                (?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $taskId,
            $oldColumnId,
            $toColumnId,
            $currentUser['id'],
            'Task moved via Kanban board'
        ]);
    }

    /*
     * Update posisi task di kolom target
     */
    $positions = json_decode($positionsJson, true);

    if (is_array($positions)) {
        foreach ($positions as $pos) {
            if (!isset($pos['id'], $pos['position'])) {
                continue;
            }

            $stmt = $conn->prepare("
                UPDATE tasks 
                SET position = ? 
                WHERE id = ? AND column_id = ?
            ");
            $stmt->execute([
                (int)$pos['position'],
                (int)$pos['id'],
                $toColumnId
            ]);
        }
    }

    /*
     * Rapikan ulang posisi di kolom asal jika berbeda kolom
     */
    if ($oldColumnId !== $toColumnId) {
        $stmt = $conn->prepare("
            SELECT id 
            FROM tasks 
            WHERE column_id = ? 
            ORDER BY position ASC, id ASC
        ");
        $stmt->execute([$oldColumnId]);
        $oldColumnTasks = $stmt->fetchAll();

        $i = 0;
        foreach ($oldColumnTasks as $oldTask) {
            $stmtUpdate = $conn->prepare("
                UPDATE tasks 
                SET position = ? 
                WHERE id = ?
            ");
            $stmtUpdate->execute([$i, $oldTask['id']]);
            $i++;
        }
    }

    /*
     * Update progress project
     */
    $progress = updateProjectProgress($task['project_id']);

    /*
     * Log activity
     */
    logActivity(
        $currentUser['id'],
        $task['project_id'],
        $taskId,
        'task_moved',
        'Task moved to ' . $targetColumn['title']
    );

    $conn->commit();

    echo json_encode([
        'success'  => true,
        'message'  => 'Task berhasil dipindahkan',
        'progress' => $progress
    ]);
    exit;

} catch (Exception $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }

    error_log('Move task API error: ' . $e->getMessage());

    echo json_encode([
        'success' => false,
        'message' => 'Gagal memindahkan task: ' . $e->getMessage()
    ]);
    exit;
}