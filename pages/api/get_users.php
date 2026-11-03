<?php
/**
 * API: Get Users (for select2 ajax, etc)
 */

if (!isset($currentUser)) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    header('Content-Type: application/json');
    $currentUser = getCurrentUser();
}

$search = $_GET['q'] ?? '';
$bidang = $_GET['bidang'] ?? '';
$divisi = $_GET['divisi'] ?? '';
$limit = min((int)($_GET['limit'] ?? 50), 200);

$conn = dbUser();

$sql = "SELECT id, nik, name, email, position, division, department 
        FROM users WHERE is_active = 1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (name LIKE ? OR nik LIKE ? OR email LIKE ? OR position LIKE ?)";
    $searchTerm = '%' . $search . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($bidang)) {
    $sql .= " AND division = ?";
    $params[] = $bidang;
}

if (!empty($divisi)) {
    $sql .= " AND department = ?";
    $params[] = $divisi;
}

$sql .= " ORDER BY name ASC LIMIT " . $limit;

try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
    
    // Format for select2
    $results = [];
    foreach ($users as $u) {
        $results[] = [
            'id' => $u['id'],
            'text' => $u['name'] . ' - ' . ($u['position'] ?: 'Staff'),
            'name' => $u['name'],
            'nik' => $u['nik'],
            'position' => $u['position'],
            'division' => $u['division'],
            'department' => $u['department'],
            'initials' => getUserInitials($u['name'])
        ];
    }
    
    echo json_encode(['success' => true, 'results' => $results, 'total' => count($results)]);
    
} catch (Exception $e) {
    error_log("Get users error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Failed to load users']);
}