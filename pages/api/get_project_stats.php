<?php
/**
 * API: Get Project Statistics
 */

if (!isset($currentUser)) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    header('Content-Type: application/json');
    $currentUser = getCurrentUser();
}

$projectId = (int)($_GET['project_id'] ?? 0);

if (!$projectId) {
    // Return user dashboard stats
    $stats = getDashboardStats($currentUser['id']);
    echo json_encode(['success' => true, 'stats' => $stats]);
    exit;
}

if (!canViewProject($projectId, $currentUser['id'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak']);
    exit;
}

$project = getProjectById($projectId);
$columns = getProjectColumns($projectId);
$members = getProjectMembers($projectId);

$conn = db();
$stats = [
    'project' => $project,
    'total_columns' => count($columns),
    'total_members' => count($members),
    'total_tasks' => 0,
    'tasks_by_column' => [],
    'tasks_by_priority' => ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0],
    'tasks_by_status' => ['completed' => 0, 'in_progress' => 0, 'not_started' => 0],
    'overdue_tasks' => 0
];

// Tasks per column
foreach ($columns as $col) {
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM tasks WHERE column_id = ?");
    $stmt->execute([$col['id']]);
    $count = $stmt->fetch()['total'];
    $stats['tasks_by_column'][] = [
        'column_id' => $col['id'],
        'title' => $col['title'],
        'color' => $col['color'],
        'count' => $count
    ];
    $stats['total_tasks'] += $count;
    
    if ($col['is_done_column']) {
        $stats['tasks_by_status']['completed'] += $count;
    }
}

// Tasks by priority
$stmt = $conn->prepare("
    SELECT priority, COUNT(*) as total 
    FROM tasks WHERE project_id = ? 
    GROUP BY priority
");
$stmt->execute([$projectId]);
while ($row = $stmt->fetch()) {
    $stats['tasks_by_priority'][$row['priority']] = (int)$row['total'];
}

// Overdue tasks (target end date passed, not in done column)
$stmt = $conn->prepare("
    SELECT COUNT(*) as total FROM tasks t
    INNER JOIN project_columns pc ON t.column_id = pc.id
    WHERE t.project_id = ? 
    AND t.target_end_date < CURDATE() 
    AND pc.is_done_column = 0
");
$stmt->execute([$projectId]);
$stats['overdue_tasks'] = (int)$stmt->fetch()['total'];

// In progress
$stats['tasks_by_status']['in_progress'] = $stats['total_tasks'] - $stats['tasks_by_status']['completed'];

echo json_encode(['success' => true, 'stats' => $stats]);