<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
}

$currentUser = getCurrentUser();
$projectId = (int)($_GET['id'] ?? 0);

if (!$projectId || !canViewProject($projectId, $currentUser['id'])) {
    setFlash('danger', 'Project tidak ditemukan atau Anda tidak memiliki akses.');
    redirect(BASE_URL . '/?page=projects&action=index');
}

$project = getProjectById($projectId);
$pageTitle = $project['title'];

$columns = getProjectColumns($projectId);
$members = getProjectMembers($projectId);
$leader = getProjectLeader($projectId);
$isLeader = isProjectLeader($projectId, $currentUser['id']);
$isCreator = $project['created_by'] == $currentUser['id'];
$canEdit = $isLeader || $isCreator || isAdmin();
$canManageTasks = $canEdit || isProjectMember($projectId, $currentUser['id']);

// Load tasks per column
$tasksByColumn = [];
foreach ($columns as $col) {
    $tasksByColumn[$col['id']] = getTasksByColumn($col['id']);
}

// Task stats
$totalTasks = 0;
$doneTasks = 0;
foreach ($tasksByColumn as $colId => $tasks) {
    $totalTasks += count($tasks);
    $col = array_filter($columns, fn($c) => $c['id'] == $colId);
    $col = reset($col);
    if ($col && $col['is_done_column']) {
        $doneTasks += count($tasks);
    }
}
$daysLeft = daysRemaining($project['target_end_date']);
?>

<!-- Project Header -->
<div class="info-card mb-3">
    <div class="info-card-body">
        <div class="d-flex flex-wrap gap-3 align-items-start justify-content-between mb-3">
            <div class="flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                    <a href="<?= BASE_URL ?>/?page=projects&action=index" class="text-muted small text-decoration-none">
                        <i class="fas fa-arrow-left"></i> Projects
                    </a>
                    <span class="text-muted">/</span>
                    <span class="badge bg-<?= PROJECT_STATUS[$project['status']]['badge'] ?>-soft text-<?= PROJECT_STATUS[$project['status']]['badge'] ?>">
                        <?= PROJECT_STATUS[$project['status']]['label'] ?>
                    </span>
                    <span class="badge bg-<?= PRIORITY_OPTIONS[$project['priority']]['badge'] ?>-soft text-<?= PRIORITY_OPTIONS[$project['priority']]['badge'] ?>">
                        <?= PRIORITY_OPTIONS[$project['priority']]['label'] ?> Priority
                    </span>
                    <span class="badge bg-info-soft text-info" title="<?= PRIVACY_OPTIONS[$project['privacy']]['desc'] ?>">
                        <i class="<?= PRIVACY_OPTIONS[$project['privacy']]['icon'] ?>"></i> <?= PRIVACY_OPTIONS[$project['privacy']]['label'] ?>
                    </span>
                </div>
                <h3 class="fw-bold mb-1"><?= htmlspecialchars($project['title']) ?></h3>
                <p class="text-muted mb-0"><?= htmlspecialchars($project['description'] ?? 'No description') ?></p>
            </div>
            <?php
                $canDeleteProject = ($project['created_by'] == $currentUser['id']) || isAdmin();
                ?>
                
                <div class="d-flex gap-2 flex-wrap">
                    <a href="<?= BASE_URL ?>/?page=projects&action=export_pdf&id=<?= $projectId ?>"
                       target="_blank"
                       class="btn btn-outline-primary btn-sm">
                        <i class="fas fa-file-pdf"></i> Export PDF
                    </a>
                
                    <?php if ($canEdit): ?>
                        <a href="<?= BASE_URL ?>/?page=projects&action=edit&id=<?= $projectId ?>"
                           class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-edit"></i> Edit
                        </a>
                    <?php endif; ?>
                
                    <?php if ($canManageTasks): ?>
                        <button type="button"
                                class="btn btn-primary btn-sm"
                                data-bs-toggle="modal"
                                data-bs-target="#createTaskModal">
                            <i class="fas fa-plus"></i> New Task
                        </button>
                    <?php endif; ?>
                
                    <?php if ($canDeleteProject): ?>
                        <button type="button"
                                class="btn btn-outline-danger btn-sm"
                                onclick="deleteProject(<?= (int)$projectId ?>)">
                            <i class="fas fa-trash"></i> Delete
                        </button>
                    <?php endif; ?>
                </div>
        </div>
        
        <div class="row g-3 mt-2">
            <div class="col-md-3 col-6">
                <div class="small text-muted mb-1"><i class="fas fa-user-tie me-1"></i>Project Leader</div>
                <div class="d-flex align-items-center gap-2">
                    <div class="kanban-card-assignee" style="width:28px;height:28px;font-size:10px;">
                        <?= getUserInitials($leader['name'] ?? 'NA') ?>
                    </div>
                    <div class="fw-semibold small text-truncate"><?= htmlspecialchars($leader['name'] ?? 'N/A') ?></div>
                </div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small text-muted mb-1"><i class="fas fa-calendar me-1"></i>Start</div>
                <div class="fw-semibold small"><?= formatDate($project['start_date']) ?></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small text-muted mb-1"><i class="fas fa-flag-checkered me-1"></i>Target End</div>
                <div class="fw-semibold small"><?= formatDate($project['target_end_date']) ?></div>
            </div>
            <div class="col-md-2 col-6">
                <div class="small text-muted mb-1"><i class="fas fa-clock me-1"></i>Time Left</div>
                <?php if ($daysLeft !== null): ?>
                    <div class="fw-semibold small text-<?= getDaysRemainingColor($daysLeft) ?>">
                        <?= $daysLeft < 0 ? abs($daysLeft) . ' hari terlambat' : ($daysLeft === 0 ? 'Hari ini' : $daysLeft . ' hari lagi') ?>
                    </div>
                <?php else: ?>
                    <div class="small text-muted">-</div>
                <?php endif; ?>
            </div>
            <div class="col-md-3">
                <div class="small text-muted mb-1"><i class="fas fa-chart-line me-1"></i>Progress (<?= $doneTasks ?>/<?= $totalTasks ?> tasks)</div>
                <div class="d-flex align-items-center gap-2">
                    <div class="progress progress-thick flex-grow-1">
                        <div class="progress-bar" style="width: <?= $project['progress_percentage'] ?>%"></div>
                    </div>
                    <span class="fw-bold small text-primary"><?= number_format($project['progress_percentage'], 0) ?>%</span>
                </div>
            </div>
        </div>
        
        <!-- Team Avatars -->
        <div class="mt-3 pt-3 border-top">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small text-muted"><i class="fas fa-users me-1"></i>Team (<?= count($members) ?>):</span>
                <?php foreach ($members as $mem): if (!$mem['user']) continue; ?>
                    <div class="d-flex align-items-center gap-1 bg-light rounded-pill px-2 py-1" title="<?= htmlspecialchars($mem['user']['name']) ?> - <?= htmlspecialchars($mem['user']['position']) ?>">
                        <div class="kanban-card-assignee" style="width:22px;height:22px;font-size:9px;">
                            <?= getUserInitials($mem['user']['name']) ?>
                        </div>
                        <span class="small"><?= htmlspecialchars($mem['user']['name']) ?></span>
                        <?php if ($mem['role'] === 'leader'): ?>
                            <span class="badge bg-primary" style="font-size:8px;">LEADER</span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<!-- Kanban Board -->
<div class="kanban-board" id="kanbanBoard" data-project-id="<?= $projectId ?>">
    <?php foreach ($columns as $column): ?>
    <div class="kanban-column" data-column-id="<?= $column['id'] ?>">
        <div class="kanban-column-header" style="border-top: 3px solid <?= $column['color'] ?>;">
            <div class="kanban-column-header-left">
                <span class="kanban-column-dot" style="background: <?= $column['color'] ?>"></span>
                <span class="kanban-column-title"><?= htmlspecialchars($column['title']) ?></span>
                <span class="kanban-column-count"><?= count($tasksByColumn[$column['id']]) ?></span>
            </div>
            <?php if ($canManageTasks): ?>
            <button type="button" class="btn btn-sm btn-link p-0 text-muted" onclick="openCreateTask(<?= $column['id'] ?>)" title="Add task">
                <i class="fas fa-plus"></i>
            </button>
            <?php endif; ?>
        </div>
        <div class="kanban-column-body" data-column-id="<?= $column['id'] ?>">
            <?php foreach ($tasksByColumn[$column['id']] as $task): 
                $assignee = $task['assigned_to'] ? getUserById($task['assigned_to']) : null;
                $attachments = getTaskAttachments($task['id']);
                $taskDaysLeft = daysRemaining($task['target_end_date']);
                $priorityData = PRIORITY_OPTIONS[$task['priority']] ?? null;
            ?>
            <div class="kanban-card priority-<?= $task['priority'] ?>" 
                 data-task-id="<?= $task['id'] ?>" 
                 style="background: <?= $task['card_color'] ?>;"
                 onclick="openTaskDetail(<?= $task['id'] ?>)">
                
                <!-- Priority Badge -->
                <?php if ($priorityData): ?>
                <div class="mb-2">
                    <span class="badge bg-<?= $priorityData['badge'] ?>-soft text-<?= $priorityData['badge'] ?>" style="font-size: 10px;">
                        <i class="fas fa-exclamation-circle me-1"></i><?= htmlspecialchars($priorityData['label']) ?>
                    </span>
                </div>
                <?php endif; ?>

                <div class="kanban-card-title"><?= htmlspecialchars($task['title']) ?></div>
                <?php if (!empty($task['description'])): ?>
                <div class="kanban-card-desc"><?= htmlspecialchars($task['description']) ?></div>
                <?php endif; ?>
                
                <div class="kanban-card-footer mt-2 pt-2 border-top">
                    <div class="kanban-card-meta d-flex align-items-center gap-2">
                        <?php if ($task['target_end_date']): ?>
                            <span class="badge bg-light text-<?= getDaysRemainingColor($taskDaysLeft) ?> border" title="Target End: <?= formatDate($task['target_end_date']) ?>" style="font-size: 10px;">
                                <i class="fas fa-calendar-alt me-1"></i><?= date('d M Y', strtotime($task['target_end_date'])) ?>
                                <?php if ($taskDaysLeft !== null): ?>
                                    (<?= $taskDaysLeft < 0 ? abs($taskDaysLeft) . 'h terlambat' : ($taskDaysLeft === 0 ? 'Hari ini' : $taskDaysLeft . 'h lagi') ?>)
                                <?php endif; ?>
                            </span>
                        <?php else: ?>
                            <span class="text-muted small" style="font-size: 10px;"><i class="far fa-calendar me-1"></i>No Target</span>
                        <?php endif; ?>

                        <?php if (!empty($attachments)): ?>
                            <span class="text-muted small" style="font-size: 10px;"><i class="fas fa-paperclip me-1"></i><?= count($attachments) ?></span>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Assigned To Avatar & Name -->
                    <?php if ($assignee): ?>
                        <div class="d-flex align-items-center gap-1" title="Assigned to: <?= htmlspecialchars($assignee['name']) ?>">
                            <div class="kanban-card-assignee" style="width:24px;height:24px;font-size:9px;">
                                <?= getUserInitials($assignee['name']) ?>
                            </div>
                            <span class="small text-truncate" style="font-size: 10px; max-width: 80px;"><?= htmlspecialchars($assignee['name']) ?></span>
                        </div>
                    <?php else: ?>
                        <span class="text-muted fst-italic" style="font-size: 10px;">Unassigned</span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<!-- Create Task Modal -->
<?php if ($canManageTasks): ?>
<div class="modal fade" id="createTaskModal" tabindex="-1" aria-labelledby="createTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="<?= BASE_URL ?>/?page=tasks&action=create" enctype="multipart/form-data">
                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                <input type="hidden" name="project_id" value="<?= $projectId ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="createTaskModalLabel"><i class="fas fa-plus-circle text-primary me-2"></i>Create New Task</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="create_task_title" class="form-label">Task Title <span class="text-danger">*</span></label>
                        <input type="text" id="create_task_title" name="title" class="form-control" required placeholder="Masukkan judul task">
                    </div>
                    <div class="mb-3">
                        <label for="create_task_description" class="form-label">Description</label>
                        <textarea id="create_task_description" name="description" class="form-control" rows="3" placeholder="Deskripsikan task..."></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="createTaskColumn" class="form-label">Column / Status <span class="text-danger">*</span></label>
                            <select name="column_id" id="createTaskColumn" class="form-select" required>
                                <?php foreach ($columns as $col): ?>
                                    <option value="<?= $col['id'] ?>"><?= htmlspecialchars($col['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="create_task_assigned_to" class="form-label">Assign To</label>
                            <select id="create_task_assigned_to" name="assigned_to" class="form-select select2-modal">
                                <option value="">-- Belum Di-assign --</option>
                                <?php foreach ($members as $mem): if (!$mem['user']) continue; ?>
                                    <option value="<?= $mem['user']['id'] ?>"><?= htmlspecialchars($mem['user']['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="create_task_start_date" class="form-label">Start Date</label>
                            <input type="text" id="create_task_start_date" name="start_date" class="form-control datepicker-modal" value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label for="create_task_target_end_date" class="form-label">Target End Date</label>
                            <input type="text" id="create_task_target_end_date" name="target_end_date" class="form-control datepicker-modal">
                        </div>
                        <div class="col-md-6">
                            <label for="create_task_priority" class="form-label">Priority</label>
                            <select id="create_task_priority" name="priority" class="form-select">
                                <?php foreach (PRIORITY_OPTIONS as $key => $val): ?>
                                    <option value="<?= $key ?>" <?= $key === 'medium' ? 'selected' : '' ?>><?= $val['label'] ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Card Color</label>
                            <div class="d-flex gap-2 flex-wrap">
                                <?php foreach (CARD_COLORS as $color => $name): ?>
                                <label class="color-picker" title="<?= $name ?>">
                                    <input type="radio" name="card_color" value="<?= $color ?>" <?= $color === '#ffffff' ? 'checked' : '' ?> class="d-none">
                                    <span class="color-swatch" style="background:<?= $color ?>; border:1px solid #ddd;"></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-12">
                            <label for="create_task_notes" class="form-label">Notes</label>
                            <textarea id="create_task_notes" name="notes" class="form-control" rows="2" placeholder="Catatan tambahan..."></textarea>
                        </div>
                        <div class="col-12">
                            <label for="create_task_attachments" class="form-label">Attachments</label>
                            <input type="file" id="create_task_attachments" name="attachments[]" class="form-control" multiple>
                            <div class="form-text">Maksimal 10MB per file. Ekstensi diizinkan: <?= implode(', ', ALLOWED_EXTENSIONS) ?></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check me-1"></i> Create Task</button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Task Detail Modal -->
<div class="modal fade" id="taskDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" id="taskDetailContent">
            <div class="modal-body text-center py-5">
                <div class="spinner-border text-primary" role="status"></div>
                <div class="mt-2 text-muted">Loading task details...</div>
            </div>
        </div>
    </div>
</div>

<style>
.color-picker { cursor: pointer; }
.color-swatch {
    width: 28px; height: 28px; border-radius: 50%; display: block;
    transition: all 0.2s ease;
}
.color-picker input:checked + .color-swatch {
    box-shadow: 0 0 0 3px var(--primary);
    transform: scale(1.1);
}
</style>

<script>
window.PROJECT_ID = <?= $projectId ?>;
window.CAN_MANAGE_TASKS = <?= $canManageTasks ? 'true' : 'false' ?>;

function openCreateTask(columnId) {
    document.getElementById('createTaskColumn').value = columnId;
    new bootstrap.Modal(document.getElementById('createTaskModal')).show();
}

function openTaskDetail(taskId) {
    const modal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
    modal.show();
    
    fetch(APP_CONFIG.BASE_URL + '/?page=tasks&action=view&id=' + taskId + '&ajax=1')
        .then(r => r.text())
        .then(html => {
            document.getElementById('taskDetailContent').innerHTML = html;
        })
        .catch(() => {
            document.getElementById('taskDetailContent').innerHTML = '<div class="modal-body text-center py-5 text-danger">Failed to load task details</div>';
        });
}

function deleteProject(projectId) {
    PM.confirm({
        title: 'Hapus Project?',
        text: 'Semua task, lampiran, dan riwayat project ini akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.',
        icon: 'warning',
        confirmText: 'Ya, Hapus Project'
    }).then(result => {
        if (!result.isConfirmed) return;

        PM.loading('Menghapus project...');
        PM.post(APP_CONFIG.BASE_URL + '/?page=api&action=delete_project', {
            id: projectId
        }).then(res => {
            PM.closeAlert();
            if (res.success) {
                PM.toast('success', 'Project berhasil dihapus');
                setTimeout(() => {
                    window.location.href = APP_CONFIG.BASE_URL + '/?page=projects&action=index';
                }, 800);
            } else {
                Swal.fire('Gagal', res.message || 'Tidak bisa menghapus project', 'error');
            }
        }).catch(() => {
            PM.closeAlert();
            Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
        });
    });
}

// Initialize modal specific plugins when shown
document.getElementById('createTaskModal')?.addEventListener('shown.bs.modal', function() {
    $('.select2-modal').select2({ theme: 'bootstrap-5', dropdownParent: $('#createTaskModal') });
    flatpickr('.datepicker-modal', { dateFormat: 'Y-m-d', locale: 'id' });
});
</script>

<script src="<?= BASE_URL ?>/assets/js/kanban.js"></script>
