<?php
$currentPage = $_GET['page'] ?? 'dashboard';
$currentAction = $_GET['action'] ?? 'index';
$currentUser = $currentUser ?? getCurrentUser();

// Get counts for badges
try {
    $ongoingCount = 0;
    $pmDb = db();
    $userProjects = getProjectsForUser($currentUser['id'], ['status' => 'active']);
    $ongoingCount = count($userProjects);
    
    $stmt = $pmDb->prepare("
        SELECT COUNT(*) as total FROM tasks t 
        INNER JOIN project_columns pc ON t.column_id = pc.id 
        WHERE t.assigned_to = ? AND pc.is_done_column = 0
    ");
    $stmt->execute([$currentUser['id']]);
    $myTasksCount = $stmt->fetch()['total'];
} catch (Exception $e) {
    $ongoingCount = 0;
    $myTasksCount = 0;
}
?>

<!-- Sidebar -->
<aside class="sidebar" id="sidebar">
    <div class="sidebar-inner">
        
        <!-- Main Menu -->
        <div class="sidebar-section">
            <div class="sidebar-heading">MAIN CONSOLE</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/?page=dashboard" class="sidebar-link">
                        <span class="sidebar-icon"><i class="fas fa-th-large"></i></span>
                        <span class="sidebar-text">Overview Console</span>
                    </a>
                </li>
                <li class="sidebar-item <?= ($currentPage === 'projects' && $currentAction === 'index') ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/?page=projects&action=index" class="sidebar-link">
                        <span class="sidebar-icon"><i class="fas fa-folder-open"></i></span>
                        <span class="sidebar-text">All Projects</span>
                        <?php if ($ongoingCount > 0): ?>
                            <span class="sidebar-badge"><?= $ongoingCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <li class="sidebar-item <?= ($currentPage === 'projects' && $currentAction === 'create') ? 'active' : '' ?>">
                    <a href="<?= BASE_URL ?>/?page=projects&action=create" class="sidebar-link">
                        <span class="sidebar-icon"><i class="fas fa-plus-circle"></i></span>
                        <span class="sidebar-text">New Project</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?= BASE_URL ?>/?page=projects&action=index&filter=my_tasks" class="sidebar-link">
                        <span class="sidebar-icon"><i class="fas fa-tasks"></i></span>
                        <span class="sidebar-text">My Tasks</span>
                        <?php if ($myTasksCount > 0): ?>
                            <span class="sidebar-badge sidebar-badge-warning"><?= $myTasksCount ?></span>
                        <?php endif; ?>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Analytics -->
        <div class="sidebar-section">
            <div class="sidebar-heading">ANALYTICS & REPORTS</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="<?= BASE_URL ?>/?page=projects&action=index&status=completed" class="sidebar-link">
                        <span class="sidebar-icon"><i class="fas fa-check-double"></i></span>
                        <span class="sidebar-text">Completed</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?= BASE_URL ?>/?page=projects&action=index&status=on_hold" class="sidebar-link">
                        <span class="sidebar-icon"><i class="fas fa-pause-circle"></i></span>
                        <span class="sidebar-text">On Hold</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?= BASE_URL ?>/?page=projects&action=index&priority=critical" class="sidebar-link">
                        <span class="sidebar-icon"><i class="fas fa-fire"></i></span>
                        <span class="sidebar-text">Critical Priority</span>
                    </a>
                </li>
            </ul>
        </div>
        
        <!-- Info Section -->
        <?php if (hasManagerialAccess() || isDireksi()): ?>
        <div class="sidebar-section">
            <div class="sidebar-heading">MANAGEMENT</div>
            <ul class="sidebar-menu">
                <li class="sidebar-item">
                    <a href="<?= BASE_URL ?>/?page=projects&action=index&scope=division" class="sidebar-link">
                        <span class="sidebar-icon"><i class="fas fa-building"></i></span>
                        <span class="sidebar-text">By Bidang</span>
                    </a>
                </li>
                <li class="sidebar-item">
                    <a href="<?= BASE_URL ?>/?page=projects&action=index&scope=department" class="sidebar-link">
                        <span class="sidebar-icon"><i class="fas fa-city"></i></span>
                        <span class="sidebar-text">By Divisi</span>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
        <div class="sidebar-section">
            <li class="sidebar-item">
                    <a href="https://lju.co.id/dashboard" class="sidebar-link">
                        <span class="sidebar-icon"><i class="fas fa-sign-out-alt me-2"></i></span>
                        <span class="sidebar-text">back to Dashboard</span>
                    </a>
            </li>
        
        
        <!-- System Status Footer -->
        <div class="sidebar-footer">
            <div class="system-status">
                <div class="status-indicator">
                    <span class="status-dot"></span>
                    <span class="status-text">SYSTEM OPERATIONAL</span>
                </div>
                <div class="status-time" id="systemTime">
                    <?= date('H:i:s') ?> WIB
                </div>
                <div class="status-date">
                    <?= strtoupper(formatDate(date('Y-m-d'), 'long')) ?>
                </div>
                <div class="status-user">
                    <?= htmlspecialchars($currentUser['division'] ?: 'General') ?> • Node <?= substr(md5($currentUser['id']), 0, 3) ?>
                </div>
            </div>
        </div>
        
    </div>
</aside>

<!-- Sidebar Backdrop for Mobile -->
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>