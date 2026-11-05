<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
}

$currentUser = getCurrentUser();

// Get parameters
$filters = [
    'status'   => $_GET['status'] ?? '',
    'priority' => $_GET['priority'] ?? '',
    'search'   => $_GET['search'] ?? '',
];
$viewMode = $_GET['view'] ?? 'grid';
$scope = $_GET['scope'] ?? ''; // 'department' (Divisi) or 'division' (Bidang)

// Set Page Title based on scope
if ($scope === 'department') {
    $pageTitle = 'Projects by Divisi';
    $pageDesc = 'Pengelompokkan seluruh project berdasarkan Divisi';
} elseif ($scope === 'division') {
    $pageTitle = 'Projects by Bidang';
    $pageDesc = 'Pengelompokkan seluruh project berdasarkan Bidang';
} else {
    $pageTitle = 'All Projects';
    $pageDesc = 'Kelola dan pantau seluruh project di satu tempat';
}

// Fetch projects
$rawProjects = getProjectsForUser($currentUser['id'], $filters);

// Enrich data with Creator's Division & Department, and handle grouping
$projects = [];
$groupedProjects = [];

foreach ($rawProjects as $p) {
    // Ambil data pembuat project untuk mengetahui Bidang/Divisi asalnya
    $creator = getUserById($p['created_by']);
    $p['creator_division'] = !empty($creator['division']) ? $creator['division'] : 'Lainnya (Uncategorized)';
    $p['creator_department'] = !empty($creator['department']) ? $creator['department'] : 'Lainnya (Uncategorized)';
    
    $projects[] = $p;

    // Grouping Logic
    if ($scope === 'department') {
        $groupKey = $p['creator_department'];
        $groupedProjects[$groupKey][] = $p;
    } elseif ($scope === 'division') {
        $groupKey = $p['creator_division'];
        $groupedProjects[$groupKey][] = $p;
    }
}

// Urutkan grup secara alfabetis
if ($scope) {
    ksort($groupedProjects);
}

// Count by status (for tabs)
$counts = ['all' => count($projects), 'active' => 0, 'planning' => 0, 'on_hold' => 0, 'completed' => 0, 'cancelled' => 0];
foreach ($projects as $p) {
    if (isset($counts[$p['status']])) {
        $counts[$p['status']]++;
    }
}

// Helper function to render a single Card
function renderProjectCard($proj, $currentUser) {
    $leader = getProjectLeader($proj['id']);
    $members = getProjectMembers($proj['id']);
    $daysLeft = daysRemaining($proj['target_end_date']);
    
    $pmDb = db();
    // Hitung total task dalam project
    $stmt = $pmDb->prepare("SELECT COUNT(*) as total FROM tasks WHERE project_id = ?");
    $stmt->execute([$proj['id']]);
    $taskCount = $stmt->fetch()['total'];

    // Hitung total task yang di-assign ke currentUser
    $stmtUser = $pmDb->prepare("SELECT COUNT(*) as total FROM tasks WHERE project_id = ? AND assigned_to = ?");
    $stmtUser->execute([$proj['id'], $currentUser['id']]);
    $userTaskCount = $stmtUser->fetch()['total'];
    ?>
    <div class="col-md-6 col-xl-4">
        <div class="info-card h-100 project-card" style="cursor:pointer;" onclick="window.location.href='<?= BASE_URL ?>/?page=projects&action=view&id=<?= $proj['id'] ?>'">
            <div class="info-card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <span class="badge bg-<?= PROJECT_STATUS[$proj['status']]['badge'] ?>-soft text-<?= PROJECT_STATUS[$proj['status']]['badge'] ?>">
                        <?= PROJECT_STATUS[$proj['status']]['label'] ?>
                    </span>
                    <div class="d-flex gap-1 align-items-center">
                        <span class="badge bg-<?= PRIORITY_OPTIONS[$proj['priority']]['badge'] ?>-soft text-<?= PRIORITY_OPTIONS[$proj['priority']]['badge'] ?>">
                            <?= PRIORITY_OPTIONS[$proj['priority']]['label'] ?>
                        </span>
                        <span class="text-muted small" title="<?= PRIVACY_OPTIONS[$proj['privacy']]['label'] ?>">
                            <i class="<?= PRIVACY_OPTIONS[$proj['privacy']]['icon'] ?>"></i>
                        </span>
                    </div>
                </div>
                
                <!-- Title Project -->
                <h6 class="fw-bold mb-0 text-dark" style="min-height:22px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                    <?= htmlspecialchars($proj['title']) ?>
                </h6>

                <!-- Created At dibawah Title Project -->
                <div class="text-muted small mb-2" style="font-size: 11px;">
                    <i class="far fa-calendar-alt me-1 text-info"></i>Dibuat: <?= formatDate($proj['created_at'], 'datetime') ?>
                </div>
                
                <!-- Info Bidang & Divisi -->
                <div class="text-muted border-bottom pb-2 mb-2 d-flex flex-column gap-1">
                    <div style="font-size:11px; font-weight:600; color:var(--primary);">
                        <i class="fas fa-building me-1"></i> <?= htmlspecialchars($proj['creator_division']) ?>
                    </div>
                    <div style="font-size:10px;">
                        <?= htmlspecialchars($proj['creator_department']) ?>
                    </div>
                </div>
                
                <!-- Jumlah Task & Task Assigned ke User di bawah garis setelah nama bidang -->
                <div class="d-flex gap-2 mb-2" style="font-size: 11px;">
                    <span class="badge bg-light text-dark border"><i class="fas fa-tasks me-1"></i>Total: <?= $taskCount ?> task</span>
                    <span class="badge bg-primary text-primary"><i class="fas fa-user-check me-1"></i>Task Saya: <?= $userTaskCount ?></span>
                </div>

                <p class="small text-muted mb-3" style="min-height:36px; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;">
                    <?= htmlspecialchars($proj['description'] ?? 'Tidak ada deskripsi') ?>
                </p>
                
                <div class="d-flex align-items-center gap-2 mb-3">
                    <div class="kanban-card-assignee" style="width:28px;height:28px;font-size:10px;">
                        <?= getUserInitials($leader['name'] ?? 'NA') ?>
                    </div>
                    <div class="flex-grow-1 min-width-0">
                        <div class="small fw-semibold text-truncate"><?= htmlspecialchars($leader['name'] ?? 'N/A') ?></div>
                        <div class="text-muted" style="font-size:10px;">Project Leader</div>
                    </div>
                    <div class="d-flex" style="margin-left:4px;">
                        <?php 
                        $displayMembers = array_slice($members, 0, 3);
                        foreach ($displayMembers as $idx => $mem): 
                            if (!$mem['user']) continue;
                        ?>
                            <div class="kanban-card-assignee" style="width:24px;height:24px;font-size:9px;margin-left:<?= $idx > 0 ? '-8px' : '0' ?>;border:2px solid #fff;" title="<?= htmlspecialchars($mem['user']['name']) ?>">
                                <?= getUserInitials($mem['user']['name']) ?>
                            </div>
                        <?php endforeach; ?>
                        <?php if (count($members) > 3): ?>
                            <div class="kanban-card-assignee" style="width:24px;height:24px;font-size:9px;margin-left:-8px;border:2px solid #fff;background:#9ca3af;">
                                +<?= count($members) - 3 ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="mb-2">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="small text-muted">Progress</span>
                        <span class="small fw-bold text-primary"><?= number_format($proj['progress_percentage'], 0) ?>%</span>
                    </div>
                    <div class="progress">
                        <div class="progress-bar" style="width: <?= $proj['progress_percentage'] ?>%"></div>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                    <div class="small text-muted">
                        <i class="fas fa-tasks me-1"></i><?= $taskCount ?> tasks
                    </div>
                    <?php if ($daysLeft !== null): ?>
                    <div class="small text-<?= getDaysRemainingColor($daysLeft) ?>">
                        <i class="fas fa-clock me-1"></i>
                        <?= $daysLeft < 0 ? abs($daysLeft) . ' hari terlambat' : ($daysLeft === 0 ? 'Hari ini' : $daysLeft . ' hari lagi') ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Helper function to render a single Table Row
function renderProjectRow($proj, $currentUser) {
    $leader = getProjectLeader($proj['id']);
    $isCreator = ((int)$proj['created_by'] === (int)$currentUser['id']);
    $canDel = $isCreator || isAdmin();

    $pmDb = db();
    // Hitung total task dalam project
    $stmt = $pmDb->prepare("SELECT COUNT(*) as total FROM tasks WHERE project_id = ?");
    $stmt->execute([$proj['id']]);
    $taskCount = $stmt->fetch()['total'];

    // Hitung total task yang di-assign ke currentUser
    $stmtUser = $pmDb->prepare("SELECT COUNT(*) as total FROM tasks WHERE project_id = ? AND assigned_to = ?");
    $stmtUser->execute([$proj['id'], $currentUser['id']]);
    $userTaskCount = $stmtUser->fetch()['total'];
    ?>
    <tr>
        <td>
            <!-- Menampilkan Project Title dan Created At di bawahnya pada tabel list -->
            <a href="<?= BASE_URL ?>/?page=projects&action=view&id=<?= $proj['id'] ?>" class="fw-bold text-decoration-none" style="font-size: 14px;">
                <?= htmlspecialchars($proj['title']) ?>
            </a>
            <div class="text-muted mb-1" style="font-size: 11px;">
                <i class="far fa-calendar-alt me-1 text-info"></i>Dibuat: <?= formatDate($proj['created_at'], 'datetime') ?>
            </div>
            <!-- Jumlah Task & Task Assigned ke User di bawah tanggal pembuatan project pada list view -->
            <div class="d-flex gap-2 mt-1 mb-1" style="font-size: 11px;">
                <span class="badge bg-light text-dark border"><i class="fas fa-tasks me-1"></i>Total: <?= $taskCount ?> task</span>
                <span class="badge bg-primary text-primary"><i class="fas fa-user-check me-1"></i>Task Saya: <?= $userTaskCount ?></span>
            </div>
            <?php if (!empty($proj['description'])): ?>
                <div class="small text-muted mt-1"><?= htmlspecialchars(substr($proj['description'], 0, 50)) ?><?= strlen($proj['description']) > 50 ? '...' : '' ?></div>
            <?php endif; ?>
        </td>
        <td>
            <div class="fw-semibold" style="font-size: 11px;"><?= htmlspecialchars($proj['creator_division']) ?></div>
            <div class="text-muted" style="font-size: 10px;"><?= htmlspecialchars($proj['creator_department']) ?></div>
        </td>
        <td>
            <div class="d-flex align-items-center gap-2">
                <div class="kanban-card-assignee" style="width:26px;height:26px;font-size:9px;">
                    <?= getUserInitials($leader['name'] ?? 'NA') ?>
                </div>
                <span class="small"><?= htmlspecialchars($leader['name'] ?? 'N/A') ?></span>
            </div>
        </td>
        <td>
            <span class="badge bg-<?= PROJECT_STATUS[$proj['status']]['badge'] ?>-soft text-<?= PROJECT_STATUS[$proj['status']]['badge'] ?>">
                <?= PROJECT_STATUS[$proj['status']]['label'] ?>
            </span>
        </td>
        <td>
            <span class="badge bg-<?= PRIORITY_OPTIONS[$proj['priority']]['badge'] ?>-soft text-<?= PRIORITY_OPTIONS[$proj['priority']]['badge'] ?>">
                <?= PRIORITY_OPTIONS[$proj['priority']]['label'] ?>
            </span>
        </td>
        <td style="width:160px;">
            <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1">
                    <div class="progress-bar" style="width: <?= $proj['progress_percentage'] ?>%"></div>
                </div>
                <span class="small fw-bold"><?= number_format($proj['progress_percentage'], 0) ?>%</span>
            </div>
        </td>
        <td class="small"><?= formatDate($proj['target_end_date'], 'short') ?></td>
        <td class="text-end">
            <a href="<?= BASE_URL ?>/?page=projects&action=view&id=<?= $proj['id'] ?>" class="btn btn-sm btn-outline-primary" title="View">
                <i class="fas fa-eye"></i>
            </a>
            <?php if ($isCreator || isProjectLeader($proj['id'], $currentUser['id']) || isAdmin()): ?>
            <a href="<?= BASE_URL ?>/?page=projects&action=edit&id=<?= $proj['id'] ?>" class="btn btn-sm btn-outline-secondary" title="Edit">
                <i class="fas fa-edit"></i>
            </a>
            <?php endif; ?>
            <?php if ($canDel): ?>
            <button type="button" class="btn btn-sm btn-outline-danger" title="Delete" onclick="deleteProject(<?= (int)$proj['id'] ?>)">
                <i class="fas fa-trash"></i>
            </button>
            <?php endif; ?>
        </td>
    </tr>
    <?php
}
?>

<div class="page-header">
    <div class="page-header-title">
        <h2>
            <?php if ($scope): ?>
                <i class="fas fa-layer-group text-primary me-2"></i>
            <?php endif; ?>
            <?= htmlspecialchars($pageTitle) ?>
        </h2>
        <p><?= htmlspecialchars($pageDesc) ?></p>
    </div>
    <div class="d-flex gap-2">
        <div class="btn-group" role="group">
            <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'grid'])) ?>" class="btn <?= $viewMode === 'grid' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm" title="Grid View">
                <i class="fas fa-th"></i>
            </a>
            <a href="?<?= http_build_query(array_merge($_GET, ['view' => 'list'])) ?>" class="btn <?= $viewMode === 'list' ? 'btn-primary' : 'btn-outline-primary' ?> btn-sm" title="List View">
                <i class="fas fa-list"></i>
            </a>
        </div>
        <a href="<?= BASE_URL ?>/?page=projects&action=create" class="btn btn-primary">
            <i class="fas fa-plus me-1"></i> New Project
        </a>
    </div>
</div>

<!-- Filter Bar -->
<div class="info-card mb-3">
    <div class="info-card-body">
        <form method="GET" action="" class="row g-2 align-items-end">
            <input type="hidden" name="page" value="projects">
            <input type="hidden" name="action" value="index">
            <input type="hidden" name="view" value="<?= htmlspecialchars($viewMode) ?>">
            <?php if($scope): ?>
                <input type="hidden" name="scope" value="<?= htmlspecialchars($scope) ?>">
            <?php endif; ?>
            
            <div class="col-md-4">
                <label class="form-label">Search</label>
                <div class="position-relative">
                    <i class="fas fa-search position-absolute" style="left:12px;top:50%;transform:translateY(-50%);color:#9ca3af;font-size:12px;"></i>
                    <input type="text" name="search" class="form-control ps-4" placeholder="Cari judul project..." value="<?= htmlspecialchars($filters['search']) ?>">
                </div>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Status</label>
                <select name="status" class="form-select">
                    <option value="">Semua Status</option>
                    <?php foreach (PROJECT_STATUS as $key => $val): ?>
                        <option value="<?= $key ?>" <?= $filters['status'] === $key ? 'selected' : '' ?>><?= $val['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-3">
                <label class="form-label">Priority</label>
                <select name="priority" class="form-select">
                    <option value="">Semua Prioritas</option>
                    <?php foreach (PRIORITY_OPTIONS as $key => $val): ?>
                        <option value="<?= $key ?>" <?= $filters['priority'] === $key ? 'selected' : '' ?>><?= $val['label'] ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="col-md-2 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-grow-1">
                    <i class="fas fa-filter"></i> Filter
                </button>
                <a href="?page=projects&action=index<?= $scope ? '&scope='.$scope : '' ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-redo"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Status Tabs -->
<div class="d-flex gap-2 mb-4 flex-wrap">
    <a href="?page=projects&action=index<?= $scope ? '&scope='.$scope : '' ?>" class="btn btn-sm <?= empty($filters['status']) ? 'btn-primary' : 'btn-outline-primary' ?>">
        All <span class="badge bg-red text-primary ms-1"><?= count($projects) ?></span>
    </a>
    <?php foreach (PROJECT_STATUS as $key => $val): ?>
        <a href="?page=projects&action=index&status=<?= $key ?><?= $scope ? '&scope='.$scope : '' ?>" class="btn btn-sm <?= $filters['status'] === $key ? 'btn-primary' : 'btn-outline-primary' ?>">
            <?= $val['label'] ?> <span class="badge bg-red text-primary ms-1"><?= $counts[$key] ?></span>
        </a>
    <?php endforeach; ?>
</div>

<!-- Render Logic -->
<?php if (empty($projects)): ?>
    <div class="info-card">
        <div class="empty-state">
            <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
            <div class="empty-state-title">Tidak Ada Project</div>
            <div class="empty-state-text mb-3">
                <?= !empty($filters['search']) || !empty($filters['status']) ? 'Sesuaikan filter pencarian Anda' : 'Mulai buat project pertama Anda' ?>
            </div>
            <a href="<?= BASE_URL ?>/?page=projects&action=create" class="btn btn-primary">
                <i class="fas fa-plus"></i> Buat Project
            </a>
        </div>
    </div>

<?php elseif ($viewMode === 'grid'): ?>
    
    <?php if ($scope): ?>
        <!-- GRID VIEW: WITH GROUPING -->
        <?php foreach ($groupedProjects as $groupName => $groupProjs): ?>
            <div class="d-flex align-items-center mb-3 mt-4">
                <h5 class="fw-bold mb-0 text-primary">
                    <i class="<?= $scope === 'department' ? 'fas fa-city' : 'fas fa-building' ?> me-2"></i><?= htmlspecialchars($groupName) ?>
                </h5>
                <div class="ms-3 border-top flex-grow-1" style="border-color: #d1d5db !important;"></div>
                <span class="badge bg-white text-dark border ms-3 shadow-sm px-3 py-2"><?= count($groupProjs) ?> Project</span>
            </div>
            <div class="row g-3 mb-4">
                <?php foreach ($groupProjs as $proj) { renderProjectCard($proj, $currentUser); } ?>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <!-- GRID VIEW: NO GROUPING -->
        <div class="row g-3">
            <?php foreach ($projects as $proj) { renderProjectCard($proj, $currentUser); } ?>
        </div>
    <?php endif; ?>

<?php else: ?>
    <!-- LIST VIEW (TABLE) -->
    <div class="table-container">
        <table class="table mb-0">
            <thead>
                <tr>
                    <th>PROJECT</th>
                    <th>BIDANG / DIVISI</th>
                    <th>LEADER</th>
                    <th>STATUS</th>
                    <th>PRIORITY</th>
                    <th>PROGRESS</th>
                    <th>TARGET END</th>
                    <th class="text-end">ACTION</th>
                </tr>
            </thead>
            
            <?php if ($scope): ?>
                <!-- LIST VIEW: WITH GROUPING -->
                <?php foreach ($groupedProjects as $groupName => $groupProjs): ?>
                    <tbody>
                        <tr>
                            <td colspan="8" class="bg-light border-bottom">
                                <div class="d-flex align-items-center py-1">
                                    <i class="<?= $scope === 'department' ? 'fas fa-city' : 'fas fa-building' ?> text-primary me-2"></i>
                                    <span class="fw-bold text-primary" style="font-size:14px;"><?= htmlspecialchars($groupName) ?></span>
                                    <span class="badge bg-primary-soft text-primary ms-3"><?= count($groupProjs) ?> Project</span>
                                </div>
                            </td>
                        </tr>
                        <?php foreach ($groupProjs as $proj) { renderProjectRow($proj, $currentUser); } ?>
                    </tbody>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- LIST VIEW: NO GROUPING -->
                <tbody>
                    <?php foreach ($projects as $proj) { renderProjectRow($proj, $currentUser); } ?>
                </tbody>
            <?php endif; ?>
        </table>
    </div>
<?php endif; ?>

<style>
.project-card { transition: all 0.2s ease; }
.project-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
</style>
