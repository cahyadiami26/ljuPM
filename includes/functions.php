<?php
/**
 * Helper Functions
 */

require_once __DIR__ . '/../config/app.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// ============================================================
// USER FUNCTIONS (dari database existing)
// ============================================================

/**
 * Get user by ID dari database existing
 */
function getUserById($userId) {
    $conn = dbUser();
    $stmt = $conn->prepare("SELECT id, nik, name, email, position, division, department, username, role, avatar, photo, signature, is_active FROM users WHERE id = ? AND is_active = 1");
    $stmt->execute([$userId]);
    return $stmt->fetch();
}

/**
 * Get all active users
 */
function getAllUsers() {
    $conn = dbUser();
    $stmt = $conn->query("SELECT id, nik, name, email, position, division, department, username, role, avatar, photo FROM users WHERE is_active = 1 ORDER BY name ASC");
    return $stmt->fetchAll();
}

/**
 * Get users by division (bidang)
 */
function getUsersByDivision($division) {
    $conn = dbUser();
    $stmt = $conn->prepare("SELECT id, nik, name, email, position, division, department, username, role, avatar, photo FROM users WHERE division = ? AND is_active = 1 ORDER BY name ASC");
    $stmt->execute([$division]);
    return $stmt->fetchAll();
}

/**
 * Get users by department (divisi)
 */
function getUsersByDepartment($department) {
    $conn = dbUser();
    $stmt = $conn->prepare("SELECT id, nik, name, email, position, division, department, username, role, avatar, photo FROM users WHERE department = ? AND is_active = 1 ORDER BY name ASC");
    $stmt->execute([$department]);
    return $stmt->fetchAll();
}

/**
 * Get user's avatar URL
 */
function getUserAvatar($user) {
    if (!empty($user['photo'])) {
        return DMS_URL . '/assets/uploads/photos/' . $user['photo'];
    } elseif (!empty($user['avatar'])) {
        return DMS_URL . '/assets/uploads/avatars/' . $user['avatar'];
    }
    return BASE_URL . '/assets/img/default-avatar.png';
}

/**
 * Get user initials for avatar placeholder
 */
function getUserInitials($name) {
    $parts = explode(' ', trim($name));
    $initials = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $initials .= strtoupper(substr($part, 0, 1));
    }
    return $initials;
}

// ============================================================
// PROJECT FUNCTIONS
// ============================================================

/**
 * Create a new project
 */
function createProject($data) {
    $conn = db();
    
    try {
        $conn->beginTransaction();
        
        // Insert project
        $stmt = $conn->prepare("
            INSERT INTO projects (title, description, created_by, project_leader_id, start_date, target_end_date, privacy, status, priority)
            VALUES (?, ?, ?, ?, ?, ?, ?, 'active', ?)
        ");
        $stmt->execute([
            $data['title'],
            $data['description'] ?? null,
            $data['created_by'],
            $data['project_leader_id'],
            $data['start_date'] ?? null,
            $data['target_end_date'] ?? null,
            $data['privacy'] ?? 'team',
            $data['priority'] ?? 'medium'
        ]);
        $projectId = $conn->lastInsertId();
        
        // Insert columns
        $columns = $data['columns'] ?? DEFAULT_COLUMNS;
        $allColumnOptions = KANBAN_COLUMN_OPTIONS;
        
        foreach ($columns as $pos => $colSlug) {
            if (isset($allColumnOptions[$colSlug])) {
                $colData = $allColumnOptions[$colSlug];
                $isDone = ($colSlug === 'done') ? 1 : 0;
                $stmt = $conn->prepare("
                    INSERT INTO project_columns (project_id, title, slug, position, color, is_done_column)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$projectId, $colData['title'], $colSlug, $pos, $colData['color'], $isDone]);
            }
        }
        
        // Insert project leader as member
        $stmt = $conn->prepare("
            INSERT INTO project_members (project_id, user_id, role, added_by)
            VALUES (?, ?, 'leader', ?)
        ");
        $stmt->execute([$projectId, $data['project_leader_id'], $data['created_by']]);
        
        // Insert additional team members
        if (!empty($data['members'])) {
            foreach ($data['members'] as $memberId) {
                if ($memberId != $data['project_leader_id']) {
                    $stmt = $conn->prepare("
                        INSERT IGNORE INTO project_members (project_id, user_id, role, added_by)
                        VALUES (?, ?, 'member', ?)
                    ");
                    $stmt->execute([$projectId, $memberId, $data['created_by']]);
                }
            }
        }
        
        // Record initial status
        $stmt = $conn->prepare("
            INSERT INTO project_status_history (project_id, to_status, changed_by, notes)
            VALUES (?, 'active', ?, 'Project created')
        ");
        $stmt->execute([$projectId, $data['created_by']]);
        
        // Log activity
        logActivity($data['created_by'], $projectId, null, 'project_created', 'Project "' . $data['title'] . '" created');
        
        $conn->commit();
        return $projectId;
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error creating project: " . $e->getMessage());
        return false;
    }
}

/**
 * Get project by ID
 */
function getProjectById($projectId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM projects WHERE id = ?");
    $stmt->execute([$projectId]);
    return $stmt->fetch();
}

/**
 * Get project columns
 */
function getProjectColumns($projectId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM project_columns WHERE project_id = ? ORDER BY position ASC");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

/**
 * Get project members
 */
function getProjectMembers($projectId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM project_members WHERE project_id = ? ORDER BY role ASC");
    $stmt->execute([$projectId]);
    $members = $stmt->fetchAll();
    
    // Enrich with user data
    foreach ($members as &$member) {
        $member['user'] = getUserById($member['user_id']);
    }
    
    return $members;
}

/**
 * Get project leader
 */
function getProjectLeader($projectId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT user_id FROM project_members WHERE project_id = ? AND role = 'leader' LIMIT 1");
    $stmt->execute([$projectId]);
    $result = $stmt->fetch();
    if ($result) {
        return getUserById($result['user_id']);
    }
    return null;
}

/**
 * Check if user can view project
 */
function canViewProject($projectId, $userId) {
    $project = getProjectById((int)$projectId);
    $user = getUserById((int)$userId);

    if (!$project || !$user) {
        return false;
    }

    $canSeeAll = (
        ($user['role'] ?? '') === 'admin' ||
        ($user['position'] ?? '') === 'System Administrator' ||
        ($user['department'] ?? '') === 'Direksi'
    );

    if ($canSeeAll) {
        return true;
    }

    // Pembuat project selalu dapat melihat project miliknya.
    if ((int)$project['created_by'] === (int)$userId) {
        return true;
    }

    // Project personal hanya dapat dilihat pembuatnya.
    if ($project['privacy'] === 'personal') {
        return false;
    }

    // Anggota project dapat melihat project non-personal.
    if (isProjectMember($projectId, $userId)) {
        return true;
    }

    if ($project['privacy'] === 'all') {
        return true;
    }

    $creator = getUserById($project['created_by']);
    if (!$creator) {
        return false;
    }

    // division database = Bidang aplikasi
    $sameBidang =
        !empty($user['division']) &&
        $user['division'] === $creator['division'];

    // department database = Divisi aplikasi
    $sameDivisi =
        !empty($user['department']) &&
        $user['department'] === $creator['department'];

    if ($project['privacy'] === 'division' && $sameBidang) {
        return true;
    }

    if ($project['privacy'] === 'department' && $sameDivisi) {
        return true;
    }

    // Kepala Bidang/Divisi dapat melihat project non-personal sesuai cakupannya.
    if (($user['position'] ?? '') === 'Kepala Bidang' && $sameBidang) {
        return true;
    }

    if (($user['position'] ?? '') === 'Kepala Divisi' && $sameDivisi) {
        return true;
    }

    return false;
}

/**
 * Check if user is project member
 */
function isProjectMember($projectId, $userId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$projectId, $userId]);
    return (bool) $stmt->fetch();
}

/**
 * Check if user is project leader
 */
function isProjectLeader($projectId, $userId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT id FROM project_members WHERE project_id = ? AND user_id = ? AND role = 'leader'");
    $stmt->execute([$projectId, $userId]);
    return (bool) $stmt->fetch();
}

/**
 * Get projects for user (based on access)
 */
function getProjectsForUser($userId, $filters = []) {
    $userId = (int)$userId;

    if ($userId <= 0) {
        return [];
    }

    try {
        $user = getUserById($userId);

        if (!$user) {
            error_log("getProjectsForUser: user ID {$userId} tidak ditemukan atau tidak aktif.");
            return [];
        }

        $conn = db();
        $conditions = [];
        $params = [];

        // Administrator, System Administrator, dan pengguna Divisi Direksi
        // dapat melihat seluruh project, termasuk project personal.
        $canSeeAll = (
            ($user['role'] ?? '') === 'admin' ||
            ($user['position'] ?? '') === 'System Administrator' ||
            ($user['department'] ?? '') === 'Direksi'
        );

        if (!$canSeeAll) {
            // division pada database user = Bidang dalam aplikasi
            $bidangUserIds = [];
            if (!empty($user['division'])) {
                $bidangUsers = getUsersByDivision($user['division']);
                $bidangUserIds = array_values(array_unique(array_map(
                    'intval',
                    array_column($bidangUsers, 'id')
                )));
            }

            // department pada database user = Divisi dalam aplikasi
            $divisiUserIds = [];
            if (!empty($user['department'])) {
                $divisiUsers = getUsersByDepartment($user['department']);
                $divisiUserIds = array_values(array_unique(array_map(
                    'intval',
                    array_column($divisiUsers, 'id')
                )));
            }

            $accessParts = [
                "p.privacy = 'all'",
                "pm.user_id = ?"
            ];
            $accessParams = [$userId];

            // Privacy anggota Bidang
            if (!empty($bidangUserIds)) {
                $marks = implode(',', array_fill(0, count($bidangUserIds), '?'));
                $accessParts[] =
                    "(p.privacy = 'division' AND p.created_by IN ($marks))";
                $accessParams = array_merge($accessParams, $bidangUserIds);
            }

            // Privacy anggota Divisi
            if (!empty($divisiUserIds)) {
                $marks = implode(',', array_fill(0, count($divisiUserIds), '?'));
                $accessParts[] =
                    "(p.privacy = 'department' AND p.created_by IN ($marks))";
                $accessParams = array_merge($accessParams, $divisiUserIds);
            }

            // Kepala Bidang dapat melihat project non-personal di Bidangnya.
            if (
                ($user['position'] ?? '') === 'Kepala Bidang' &&
                !empty($bidangUserIds)
            ) {
                $marks = implode(',', array_fill(0, count($bidangUserIds), '?'));
                $accessParts[] = "p.created_by IN ($marks)";
                $accessParams = array_merge($accessParams, $bidangUserIds);
            }

            // Kepala Divisi dapat melihat project non-personal di Divisinya.
            if (
                ($user['position'] ?? '') === 'Kepala Divisi' &&
                !empty($divisiUserIds)
            ) {
                $marks = implode(',', array_fill(0, count($divisiUserIds), '?'));
                $accessParts[] = "p.created_by IN ($marks)";
                $accessParams = array_merge($accessParams, $divisiUserIds);
            }

            // Project personal tetap hanya terlihat oleh pembuatnya.
            $conditions[] = "
                (
                    p.created_by = ?
                    OR (
                        p.privacy <> 'personal'
                        AND (" . implode(' OR ', $accessParts) . ")
                    )
                )
            ";

            $params[] = $userId;
            $params = array_merge($params, $accessParams);
        }

        if (!empty($filters['status']) && isset(PROJECT_STATUS[$filters['status']])) {
            $conditions[] = 'p.status = ?';
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority']) && isset(PRIORITY_OPTIONS[$filters['priority']])) {
            $conditions[] = 'p.priority = ?';
            $params[] = $filters['priority'];
        }

        if (!empty($filters['search'])) {
            $conditions[] = '(p.title LIKE ? OR p.description LIKE ?)';
            $search = '%' . $filters['search'] . '%';
            $params[] = $search;
            $params[] = $search;
        }

        $sql = "
            SELECT DISTINCT p.*
            FROM projects p
            LEFT JOIN project_members pm ON pm.project_id = p.id
        ";

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $sql .= ' ORDER BY p.updated_at DESC';

        if (isset($filters['limit'])) {
            $limit = max(1, min(200, (int)$filters['limit']));
            $offset = max(0, (int)($filters['offset'] ?? 0));
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();

    } catch (Throwable $e) {
        error_log('getProjectsForUser error: ' . $e->getMessage());
        return [];
    }
}

// ============================================================
// TASK FUNCTIONS
// ============================================================

/**
 * Create a task
 */
/**
 * Create a task
 */
function createTask($data) {
    $conn = db();
    
    try {
        $conn->beginTransaction();
        
        // Get max position in column
        $stmt = $conn->prepare("SELECT COALESCE(MAX(position), -1) + 1 as next_pos FROM tasks WHERE column_id = ?");
        $stmt->execute([$data['column_id']]);
        $nextPos = $stmt->fetch()['next_pos'];
        
        $stmt = $conn->prepare("
            INSERT INTO tasks (project_id, column_id, title, description, notes, assigned_to, created_by, start_date, target_end_date, card_color, priority, position)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $data['project_id'],
            $data['column_id'],
            $data['title'],
            $data['description'] ?? null,
            $data['notes'] ?? null,
            $data['assigned_to'] ?? null,
            $data['created_by'],
            $data['start_date'] ?? null,
            $data['target_end_date'] ?? null,
            $data['card_color'] ?? '#ffffff',
            $data['priority'] ?? 'medium',
            $nextPos
        ]);
        $taskId = $conn->lastInsertId();
        
        // Record initial status
        $stmt = $conn->prepare("
            INSERT INTO task_status_history (task_id, to_column_id, changed_by, notes)
            VALUES (?, ?, ?, 'Task created')
        ");
        $stmt->execute([$taskId, $data['column_id'], $data['created_by']]);
        
        // Log activity (Fixed: removed the accidental nested function declaration)
        logActivity($data['created_by'], $data['project_id'], $taskId, 'task_created', 'Task "' . $data['title'] . '" created');
        
        $conn->commit();
        return $taskId;
        
    } catch (Exception $e) {
        $conn->rollBack();
        error_log("Error creating task: " . $e->getMessage());
        return false;
    }
}

/**
 * Get task by ID
 */
function getTaskById($taskId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT t.*, pc.title as column_title, pc.slug as column_slug FROM tasks t LEFT JOIN project_columns pc ON t.column_id = pc.id WHERE t.id = ?");
    $stmt->execute([$taskId]);
    return $stmt->fetch();
}

/**
 * Get tasks for a column
 */
function getTasksByColumn($columnId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM tasks WHERE column_id = ? ORDER BY position ASC");
    $stmt->execute([$columnId]);
    return $stmt->fetchAll();
}

/**
 * Get tasks for a project
 */
function getTasksByProject($projectId) {
    $conn = db();
    $stmt = $conn->prepare("
        SELECT t.*, pc.title as column_title, pc.slug as column_slug 
        FROM tasks t 
        LEFT JOIN project_columns pc ON t.column_id = pc.id 
        WHERE t.project_id = ? 
        ORDER BY pc.position ASC, t.position ASC
    ");
    $stmt->execute([$projectId]);
    return $stmt->fetchAll();
}

/**
 * Move task to different column (status change)
 */
/**
 * Move task to another Kanban column.
 */
function moveTask($taskId, $toColumnId, $userId, $newPosition = null) {
    $conn = db();

    // Jika pemanggil sudah membuka transaksi, fungsi ini tidak mengelolanya.
    $ownsTransaction = !$conn->inTransaction();

    try {
        if ($ownsTransaction) {
            $conn->beginTransaction();
        }

        $task = getTaskById($taskId);
        if (!$task) {
            throw new RuntimeException('Task tidak ditemukan.');
        }

        $fromColumnId = (int)$task['column_id'];
        $projectId = (int)$task['project_id'];
        $toColumnId = (int)$toColumnId;

        // Pastikan kolom tujuan milik project yang sama.
        $stmt = $conn->prepare("
            SELECT id, is_done_column
            FROM project_columns
            WHERE id = ? AND project_id = ?
        ");
        $stmt->execute([$toColumnId, $projectId]);
        $column = $stmt->fetch();

        if (!$column) {
            throw new RuntimeException('Kolom tujuan tidak valid.');
        }

        $sql = "
            UPDATE tasks
            SET column_id = ?, updated_at = NOW()
        ";
        $params = [$toColumnId];

        if ($newPosition !== null) {
            $sql .= ", position = ?";
            $params[] = (int)$newPosition;
        }

        $sql .= " WHERE id = ?";
        $params[] = (int)$taskId;

        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        // Catat history hanya jika status/kolom benar-benar berubah.
        if ($fromColumnId !== $toColumnId) {
            $stmt = $conn->prepare("
                INSERT INTO task_status_history
                    (task_id, from_column_id, to_column_id, changed_by)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                (int)$taskId,
                $fromColumnId,
                $toColumnId,
                (int)$userId
            ]);
        }

        // Perbarui tanggal selesai berdasarkan kolom tujuan.
        if ((int)$column['is_done_column'] === 1) {
            $stmt = $conn->prepare("
                UPDATE tasks
                SET actual_end_date = COALESCE(actual_end_date, CURDATE())
                WHERE id = ?
            ");
            $stmt->execute([(int)$taskId]);
        } else {
            $stmt = $conn->prepare("
                UPDATE tasks
                SET actual_end_date = NULL
                WHERE id = ?
            ");
            $stmt->execute([(int)$taskId]);
        }

        updateProjectProgress($projectId);

        logActivity(
            (int)$userId,
            $projectId,
            (int)$taskId,
            'task_moved',
            'Task moved to another Kanban column'
        );

        if ($ownsTransaction) {
            $conn->commit();
        }

        return true;

    } catch (Throwable $e) {
        if ($ownsTransaction && $conn->inTransaction()) {
            $conn->rollBack();
        }

        error_log('moveTask error: ' . $e->getMessage());
        return false;
    }
}

/**
 * Update project progress percentage
 */
function updateProjectProgress($projectId) {
    $conn = db();
    
    // Get total tasks
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE project_id = ?");
    $stmt->execute([$projectId]);
    $total = $stmt->fetch()['total'];
    
    if ($total == 0) {
        $progress = 0;
    } else {
        // Get tasks in done columns
        $stmt = $conn->prepare("
            SELECT COUNT(*) as done FROM tasks t 
            INNER JOIN project_columns pc ON t.column_id = pc.id 
            WHERE t.project_id = ? AND pc.is_done_column = 1
        ");
        $stmt->execute([$projectId]);
        $done = $stmt->fetch()['done'];
        
        $progress = round(($done / $total) * 100, 2);
    }
    
    $stmt = $conn->prepare("UPDATE projects SET progress_percentage = ? WHERE id = ?");
    $stmt->execute([$progress, $projectId]);
    
    // Auto complete project if 100%
    if ($progress >= 100) {
        $stmt = $conn->prepare("UPDATE projects SET status = 'completed', actual_end_date = CURDATE() WHERE id = ? AND status != 'completed'");
        $stmt->execute([$projectId]);
    }
    
    return $progress;
}

// ============================================================
// ATTACHMENT FUNCTIONS
// ============================================================

/**
 * Upload attachment for task
 */
function uploadAttachment($taskId, $file, $userId) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Tipe file tidak diizinkan'];
    }
    
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'Ukuran file melebihi batas (10MB)'];
    }
    
    $fileName = uniqid('att_') . '_' . time() . '.' . $ext;
    $uploadPath = ATTACHMENT_DIR . $fileName;
    
    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        $conn = db();
        $stmt = $conn->prepare("
            INSERT INTO task_attachments (task_id, file_name, original_name, file_size, file_type, uploaded_by)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$taskId, $fileName, $file['name'], $file['size'], $file['type'], $userId]);
        
        return ['success' => true, 'id' => $conn->lastInsertId(), 'file_name' => $fileName];
    }
    
    return ['success' => false, 'message' => 'Gagal mengupload file'];
}

/**
 * Get attachments for task
 */
function getTaskAttachments($taskId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM task_attachments WHERE task_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$taskId]);
    return $stmt->fetchAll();
}

/**
 * Delete attachment
 */
function deleteAttachment($attachmentId, $userId) {
    $conn = db();
    $stmt = $conn->prepare("SELECT * FROM task_attachments WHERE id = ?");
    $stmt->execute([$attachmentId]);
    $attachment = $stmt->fetch();
    
    if ($attachment) {
        $filePath = ATTACHMENT_DIR . $attachment['file_name'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }
        $stmt = $conn->prepare("DELETE FROM task_attachments WHERE id = ?");
        $stmt->execute([$attachmentId]);
        return true;
    }
    return false;
}

// ============================================================
// DASHBOARD FUNCTIONS
// ============================================================

/**
 * Get dashboard stats for user
 */
function getDashboardStats($userId) {
    $conn = db();
    $user = getUserById($userId);
    
    $stats = [];
    
    // Total projects user terlibat
    $projects = getProjectsForUser($userId);
    $stats['total_projects'] = count($projects);
    
    // Ongoing projects
    $stats['ongoing_projects'] = 0;
    $stats['completed_projects'] = 0;
    foreach ($projects as $p) {
        if (in_array($p['status'], ['active', 'planning', 'on_hold'])) {
            $stats['ongoing_projects']++;
        }
        if ($p['status'] === 'completed') {
            $stats['completed_projects']++;
        }
    }
    
    // Tasks assigned to user
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE assigned_to = ?");
    $stmt->execute([$userId]);
    $stats['total_tasks'] = $stmt->fetch()['total'];
    
    // Completed tasks
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total FROM tasks t 
        INNER JOIN project_columns pc ON t.column_id = pc.id 
        WHERE t.assigned_to = ? AND pc.is_done_column = 1
    ");
    $stmt->execute([$userId]);
    $stats['completed_tasks'] = $stmt->fetch()['total'];
    
    // Average task completion time (in days)
    $stmt = $conn->prepare("
        SELECT AVG(DATEDIFF(t.actual_end_date, t.start_date)) as avg_days 
        FROM tasks t 
        INNER JOIN project_columns pc ON t.column_id = pc.id 
        WHERE t.assigned_to = ? AND pc.is_done_column = 1 AND t.actual_end_date IS NOT NULL AND t.start_date IS NOT NULL
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    $stats['avg_completion_days'] = $result['avg_days'] ? round($result['avg_days'], 1) : 0;
    
    return $stats;
}

/**
 * Get ongoing projects for user
 */
function getOngoingProjects($userId, $limit = 10) {
    return getProjectsForUser($userId, ['status' => 'active', 'limit' => $limit]);
}

/**
 * Get ongoing tasks for user
 */
function getOngoingTasks($userId, $limit = 10) {
    $userId = (int)$userId;
    $limit = max(1, min(100, (int)$limit));

    $projects = getProjectsForUser($userId);
    $projectIds = array_values(array_unique(array_map(
        'intval',
        array_column($projects, 'id')
    )));

    if (empty($projectIds)) {
        return [];
    }

    try {
        $conn = db();
        $marks = implode(',', array_fill(0, count($projectIds), '?'));

        $sql = "
            SELECT DISTINCT
                t.*,
                p.title AS project_title,
                pc.title AS column_title,
                pc.slug AS column_slug
            FROM tasks t
            INNER JOIN projects p ON p.id = t.project_id
            INNER JOIN project_columns pc ON pc.id = t.column_id
            WHERE t.project_id IN ($marks)
              AND (t.assigned_to = ? OR t.created_by = ?)
              AND pc.is_done_column = 0
            ORDER BY
                CASE t.priority
                    WHEN 'critical' THEN 1
                    WHEN 'high' THEN 2
                    WHEN 'medium' THEN 3
                    WHEN 'low' THEN 4
                END,
                t.target_end_date ASC
            LIMIT {$limit}
        ";

        $params = array_merge($projectIds, [$userId, $userId]);
        $stmt = $conn->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();

    } catch (Throwable $e) {
        error_log('getOngoingTasks error: ' . $e->getMessage());
        return [];
    }
}

/**
 * Get upcoming projects (starting soon)
 */
function getUpcomingProjects($userId, $limit = 5) {
    $conn = db();
    $projects = getProjectsForUser($userId, ['status' => 'planning']);
    return array_slice($projects, 0, $limit);
}

// ============================================================
// UTILITY FUNCTIONS
// ============================================================

/**
 * Log activity
 */
function logActivity($userId, $projectId = null, $taskId = null, $action = '', $details = null) {
    try {
        $conn = db();
        $stmt = $conn->prepare("
            INSERT INTO activity_log (user_id, project_id, task_id, action, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$userId, $projectId, $taskId, $action, $details]);
    } catch (Exception $e) {
        error_log("Log activity error: " . $e->getMessage());
    }
}

/**
 * Format date to Indonesian format
 */
function formatDate($date, $format = 'long') {
    if (empty($date)) return '-';
    
    $timestamp = strtotime($date);
    $months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
    $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
    
    switch ($format) {
        case 'long':
            return date('d', $timestamp) . ' ' . $months[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
        case 'short':
            return date('d/m/Y', $timestamp);
        case 'full':
            return $days[date('w', $timestamp)] . ', ' . date('d', $timestamp) . ' ' . $months[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp);
        case 'datetime':
            return date('d', $timestamp) . ' ' . $months[date('n', $timestamp) - 1] . ' ' . date('Y', $timestamp) . ' ' . date('H:i', $timestamp);
        default:
            return date($format, $timestamp);
    }
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return round($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return round($bytes / 1024, 2) . ' KB';
    return $bytes . ' bytes';
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Clean input
 */
function cleanInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Set flash message
 */
function setFlash($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Get and clear flash message
 */
function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

/**
 * Get task status history
 */
function getTaskStatusHistory($taskId) {
    $conn = db();
    $stmt = $conn->prepare("
        SELECT tsh.*, 
               fc.title as from_column_title, 
               tc.title as to_column_title
        FROM task_status_history tsh
        LEFT JOIN project_columns fc ON tsh.from_column_id = fc.id
        LEFT JOIN project_columns tc ON tsh.to_column_id = tc.id
        WHERE tsh.task_id = ?
        ORDER BY tsh.changed_at DESC
    ");
    $stmt->execute([$taskId]);
    return $stmt->fetchAll();
}

/**
 * Calculate days remaining
 */
function daysRemaining($targetDate) {
    if (empty($targetDate)) return null;
    $now = new DateTime();
    $target = new DateTime($targetDate);
    $diff = $now->diff($target);
    return $diff->invert ? -$diff->days : $diff->days;
}

/**
 * Get color for days remaining
 */
function getDaysRemainingColor($days) {
    if ($days === null) return 'secondary';
    if ($days < 0) return 'danger';
    if ($days <= 3) return 'warning';
    return 'success';
}

/**
 * Redirect with message
 */
function redirect($url, $type = null, $message = null) {
    if ($type && $message) {
        setFlash($type, $message);
    }
    
    // Jika header belum terkirim, gunakan PHP header redirect
    if (!headers_sent()) {
        header("Location: $url");
        exit;
    } else {
        // Jika header sudah terlanjur terkirim, gunakan JavaScript redirect
        echo '<script type="text/javascript">';
        echo 'window.location.href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '";';
        echo '</script>';
        echo '<noscript>';
        echo '<meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" />';
        echo '</noscript>';
        exit;
    }
}