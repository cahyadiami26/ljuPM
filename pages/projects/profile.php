<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/session.php';
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../includes/functions.php';
    requireLogin();
}

$currentUser = getCurrentUser();
$pageTitle = 'Profile';

$userDetail = getUserById($currentUser['id']);
if (!$userDetail) {
    $userDetail = [
        'id' => $currentUser['id'],
        'nik' => '',
        'name' => $currentUser['name'],
        'email' => $currentUser['email'],
        'username' => $currentUser['username'],
        'position' => $currentUser['position'],
        'division' => $currentUser['division'],
        'department' => $currentUser['department'],
        'role' => $currentUser['role'],
        'avatar' => null,
        'photo' => null,
        'signature' => null,
        'is_active' => 1,
    ];
}

$stats = getDashboardStats($currentUser['id']);
$projects = getProjectsForUser($currentUser['id'], ['limit' => 6]);

$conn = db();

// Task by priority
$taskByPriority = [
    'critical' => 0,
    'high' => 0,
    'medium' => 0,
    'low' => 0
];

try {
    $stmt = $conn->prepare("
        SELECT priority, COUNT(*) AS total
        FROM tasks
        WHERE assigned_to = ?
        GROUP BY priority
    ");
    $stmt->execute([$currentUser['id']]);
    while ($row = $stmt->fetch()) {
        $taskByPriority[$row['priority']] = (int)$row['total'];
    }
} catch (Exception $e) {
    error_log('Profile task priority error: ' . $e->getMessage());
}

// Recent activity
$recentActivities = [];
try {
    $stmt = $conn->prepare("
        SELECT *
        FROM activity_log
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 10
    ");
    $stmt->execute([$currentUser['id']]);
    $recentActivities = $stmt->fetchAll();
} catch (Exception $e) {
    error_log('Profile activity error: ' . $e->getMessage());
}
?>

<div class="page-header">
    <div class="page-header-title">
        <h2><i class="fas fa-user-circle me-2 text-primary"></i>Profile</h2>
        <p>Informasi pengguna dan ringkasan aktivitas Anda</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= DMS_URL ?>" class="btn btn-outline-primary">
            <i class="fas fa-th-large"></i> Buka DMS
        </a>
        <a href="<?= BASE_URL ?>/?page=dashboard" class="btn btn-primary">
            <i class="fas fa-home"></i> Dashboard
        </a>
    </div>
</div>

<div class="row g-3">
    <!-- Profile Card -->
    <div class="col-lg-4">
        <div class="info-card mb-3">
            <div class="info-card-body text-center">
                <?php
                $photoUrl = '';
                if (!empty($userDetail['photo'])) {
                    $photoUrl = DMS_URL . '/assets/uploads/photos/' . $userDetail['photo'];
                } elseif (!empty($userDetail['avatar'])) {
                    $photoUrl = DMS_URL . '/assets/uploads/avatars/' . $userDetail['avatar'];
                }
                ?>

                <?php if ($photoUrl): ?>
                    <img src="<?= htmlspecialchars($photoUrl) ?>" alt="Photo" style="
                        width: 110px;
                        height: 110px;
                        object-fit: cover;
                        border-radius: 50%;
                        border: 4px solid var(--primary-soft);
                        box-shadow: var(--shadow-md);
                    ">
                <?php else: ?>
                    <div class="mx-auto" style="
                        width: 110px;
                        height: 110px;
                        border-radius: 50%;
                        background: linear-gradient(135deg, var(--primary), var(--primary-light));
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        color: #fff;
                        font-size: 34px;
                        font-weight: 800;
                        letter-spacing: 1px;
                        box-shadow: var(--shadow-md);
                    ">
                        <?= getUserInitials($userDetail['name']) ?>
                    </div>
                <?php endif; ?>

                <h4 class="fw-bold mt-3 mb-1"><?= htmlspecialchars($userDetail['name']) ?></h4>
                <div class="text-muted small mb-2">@<?= htmlspecialchars($userDetail['username'] ?: '-') ?></div>

                <div class="d-flex justify-content-center gap-2 flex-wrap mb-3">
                    <span class="badge bg-primary-soft text-primary">
                        <?= htmlspecialchars($userDetail['position'] ?: 'User') ?>
                    </span>
                    <span class="badge bg-info-soft text-info">
                        <?= htmlspecialchars($userDetail['role'] ?: 'user') ?>
                    </span>
                    <?php if ((int)$userDetail['is_active'] === 1): ?>
                        <span class="badge bg-success-soft text-success">Active</span>
                    <?php else: ?>
                        <span class="badge bg-danger-soft text-danger">Inactive</span>
                    <?php endif; ?>
                </div>

                <div class="text-start mt-4">
                    <div class="profile-info-row">
                        <div class="profile-info-label"><i class="fas fa-id-card me-2"></i>NIK</div>
                        <div class="profile-info-value"><?= htmlspecialchars($userDetail['nik'] ?: '-') ?></div>
                    </div>
                    <div class="profile-info-row">
                        <div class="profile-info-label"><i class="fas fa-envelope me-2"></i>Email</div>
                        <div class="profile-info-value"><?= htmlspecialchars($userDetail['email'] ?: '-') ?></div>
                    </div>
                    <div class="profile-info-row">
                        <div class="profile-info-label"><i class="fas fa-building me-2"></i>Bidang</div>
                        <div class="profile-info-value"><?= htmlspecialchars($userDetail['division'] ?: '-') ?></div>
                    </div>
                    <div class="profile-info-row">
                        <div class="profile-info-label"><i class="fas fa-city me-2"></i>Divisi</div>
                        <div class="profile-info-value"><?= htmlspecialchars($userDetail['department'] ?: '-') ?></div>
                    </div>
                    <div class="profile-info-row">
                        <div class="profile-info-label"><i class="fas fa-briefcase me-2"></i>Jabatan</div>
                        <div class="profile-info-value"><?= htmlspecialchars($userDetail['position'] ?: '-') ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Signature -->
        <div class="info-card">
            <div class="info-card-header">
                <h5 class="info-card-title"><i class="fas fa-signature me-2 text-primary"></i>Signature</h5>
            </div>
            <div class="info-card-body text-center">
                <?php if (!empty($userDetail['signature'])): ?>
                    <img src="<?= DMS_URL ?>/assets/uploads/signatures/<?= htmlspecialchars($userDetail['signature']) ?>" alt="Signature" style="
                        max-width: 220px;
                        max-height: 100px;
                        object-fit: contain;
                    ">
                    <div class="small text-muted mt-2">Tanda tangan digunakan pada export PDF project.</div>
                <?php else: ?>
                    <div class="empty-state py-3">
                        <div class="empty-state-icon" style="font-size:34px;"><i class="fas fa-signature"></i></div>
                        <div class="empty-state-title">Signature belum tersedia</div>
                        <div class="empty-state-text">Kelola signature melalui aplikasi utama/DMS.</div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Stats and Activities -->
    <div class="col-lg-8">
        <div class="row g-3 mb-3">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-label">Projects</div>
                        <div class="stat-card-icon"><i class="fas fa-folder"></i></div>
                    </div>
                    <div class="stat-card-value"><?= number_format($stats['total_projects']) ?></div>
                    <div class="stat-card-footer">
                        <span class="stat-card-note">Total keterlibatan</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-label">Tasks</div>
                        <div class="stat-card-icon"><i class="fas fa-tasks"></i></div>
                    </div>
                    <div class="stat-card-value"><?= number_format($stats['total_tasks']) ?></div>
                    <div class="stat-card-footer">
                        <span class="stat-card-note">Assigned to me</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-card-header">
                        <div class="stat-card-label">Avg Time</div>
                        <div class="stat-card-icon"><i class="fas fa-clock"></i></div>
                    </div>
                    <div class="stat-card-value"><?= $stats['avg_completion_days'] ?><small class="fs-6 text-muted"> hari</small></div>
                    <div class="stat-card-footer">
                        <span class="stat-card-note">Rata-rata penyelesaian task</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <!-- Task Priority -->
            <div class="col-md-6">
                <div class="info-card h-100">
                    <div class="info-card-header">
                        <div>
                            <h5 class="info-card-title">Task Priority</h5>
                            <p class="info-card-subtitle">Distribusi task berdasarkan prioritas</p>
                        </div>
                    </div>
                    <div class="info-card-body">
                        <?php foreach (PRIORITY_OPTIONS as $key => $priority): ?>
                            <?php
                            $count = $taskByPriority[$key] ?? 0;
                            $total = max(1, array_sum($taskByPriority));
                            $percent = round(($count / $total) * 100, 1);
                            ?>
                            <div class="mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <div class="small fw-semibold">
                                        <span class="kanban-column-dot" style="background: <?= $priority['color'] ?>; display:inline-block;"></span>
                                        <?= $priority['label'] ?>
                                    </div>
                                    <div class="small text-muted"><?= $count ?> task</div>
                                </div>
                                <div class="progress">
                                    <div class="progress-bar" style="width: <?= $percent ?>%; background: <?= $priority['color'] ?>;"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="col-md-6">
                <div class="info-card h-100">
                    <div class="info-card-header">
                        <div>
                            <h5 class="info-card-title">Recent Activity</h5>
                            <p class="info-card-subtitle">Aktivitas terakhir Anda</p>
                        </div>
                    </div>
                    <div class="info-card-body">
                        <?php if (empty($recentActivities)): ?>
                            <div class="empty-state py-3">
                                <div class="empty-state-icon" style="font-size:34px;"><i class="fas fa-history"></i></div>
                                <div class="empty-state-title">Belum ada aktivitas</div>
                            </div>
                        <?php else: ?>
                            <div class="activity-list">
                                <?php foreach ($recentActivities as $act): ?>
                                    <div class="d-flex gap-2 mb-3">
                                        <div style="
                                            width: 30px;
                                            height: 30px;
                                            border-radius: 50%;
                                            background: var(--primary-soft);
                                            color: var(--primary);
                                            display: flex;
                                            align-items: center;
                                            justify-content: center;
                                            flex-shrink: 0;
                                        ">
                                            <i class="fas fa-bolt" style="font-size:11px;"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="small fw-semibold">
                                                <?= htmlspecialchars(str_replace('_', ' ', ucwords($act['action'], '_'))) ?>
                                            </div>
                                            <div class="text-muted" style="font-size:11px;">
                                                <?= htmlspecialchars($act['details'] ?: '-') ?>
                                            </div>
                                            <div class="text-muted" style="font-size:10px;">
                                                <?= formatDate($act['created_at'], 'datetime') ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Projects -->
        <div class="info-card">
            <div class="info-card-header">
                <div>
                    <h5 class="info-card-title">Recent Projects</h5>
                    <p class="info-card-subtitle">Project terbaru yang dapat Anda akses</p>
                </div>
                <a href="<?= BASE_URL ?>/?page=projects&action=index" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="info-card-body p-0">
                <?php if (empty($projects)): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon"><i class="fas fa-folder-open"></i></div>
                        <div class="empty-state-title">Belum ada project</div>
                    </div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($projects as $project): ?>
                            <a href="<?= BASE_URL ?>/?page=projects&action=view&id=<?= $project['id'] ?>" class="list-group-item list-group-item-action border-0 py-3">
                                <div class="d-flex justify-content-between align-items-start gap-3">
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold"><?= htmlspecialchars($project['title']) ?></div>
                                        <div class="small text-muted">
                                            <?= htmlspecialchars(substr($project['description'] ?? 'No description', 0, 90)) ?>
                                            <?= strlen($project['description'] ?? '') > 90 ? '...' : '' ?>
                                        </div>
                                        <div class="d-flex gap-2 mt-2 flex-wrap">
                                            <span class="badge bg-<?= PROJECT_STATUS[$project['status']]['badge'] ?>-soft text-<?= PROJECT_STATUS[$project['status']]['badge'] ?>">
                                                <?= PROJECT_STATUS[$project['status']]['label'] ?>
                                            </span>
                                            <span class="badge bg-<?= PRIORITY_OPTIONS[$project['priority']]['badge'] ?>-soft text-<?= PRIORITY_OPTIONS[$project['priority']]['badge'] ?>">
                                                <?= PRIORITY_OPTIONS[$project['priority']]['label'] ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div style="min-width:110px;">
                                        <div class="small text-end fw-bold text-primary mb-1">
                                            <?= number_format($project['progress_percentage'], 0) ?>%
                                        </div>
                                        <div class="progress">
                                            <div class="progress-bar" style="width: <?= $project['progress_percentage'] ?>%;"></div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<style>
.profile-info-row {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 10px 0;
    border-bottom: 1px solid var(--gray-100);
}

.profile-info-row:last-child {
    border-bottom: none;
}

.profile-info-label {
    color: var(--gray-500);
    font-size: 12px;
    font-weight: 600;
    min-width: 110px;
}

.profile-info-value {
    color: var(--gray-800);
    font-size: 12px;
    font-weight: 600;
    text-align: right;
    word-break: break-word;
}
</style>