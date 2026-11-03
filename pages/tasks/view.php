<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
}

$currentUser = getCurrentUser();
$taskId = (int)($_GET['id'] ?? 0);
$isAjax = !empty($_GET['ajax']);

if (!$taskId) {
    if ($isAjax) { echo '<div class="modal-body text-center py-5 text-danger">Task not found</div>'; exit; }
    setFlash('danger', 'Task tidak ditemukan.');
    redirect(BASE_URL . '/?page=dashboard');
}

$task = getTaskById($taskId);
if (!$task) {
    if ($isAjax) { echo '<div class="modal-body text-center py-5 text-danger">Task not found</div>'; exit; }
    setFlash('danger', 'Task tidak ditemukan.');
    redirect(BASE_URL . '/?page=dashboard');
}

if (!canViewProject($task['project_id'], $currentUser['id'])) {
    if ($isAjax) { echo '<div class="modal-body text-center py-5 text-danger">Access denied</div>'; exit; }
    setFlash('danger', 'Anda tidak memiliki akses.');
    redirect(BASE_URL . '/?page=projects&action=index');
}

$project = getProjectById($task['project_id']);
$assignee = $task['assigned_to'] ? getUserById($task['assigned_to']) : null;
$creator = getUserById($task['created_by']);
$attachments = getTaskAttachments($taskId);
$history = getTaskStatusHistory($taskId);

$isLeader = isProjectLeader($task['project_id'], $currentUser['id']);
$isCreator = ((int)$task['created_by'] === (int)$currentUser['id']);
$isAssignee = ((int)$task['assigned_to'] === (int)$currentUser['id']);

// Hak akses edit: Leader, Creator, Assignee, Admin
$canEdit = $isLeader || $isCreator || $isAssignee || isAdmin();
$canDeleteTask = $isCreator || isAdmin(); // hanya pembuat task atau admin
$daysLeft = daysRemaining($task['target_end_date']);
?>

<div class="modal-header" style="background: <?= htmlspecialchars($task['card_color']) ?>; border-bottom: 3px solid <?= PRIORITY_OPTIONS[$task['priority']]['color'] ?>;">
    <div class="flex-grow-1 pe-3">
        <div class="small text-muted mb-1">
            <a href="<?= BASE_URL ?>/?page=projects&action=view&id=<?= $task['project_id'] ?>" class="text-decoration-none">
                <i class="fas fa-folder me-1"></i><?= htmlspecialchars($project['title']) ?>
            </a>
            <span class="mx-1">/</span>
            <span class="badge bg-primary-soft text-primary"><?= htmlspecialchars($task['column_title']) ?></span>
        </div>
        <h5 class="modal-title mb-0 text-dark fw-bold"><?= htmlspecialchars($task['title']) ?></h5>
    </div>

    <div class="d-flex align-items-center gap-2">
        <?php if ($canEdit): ?>
            <!-- Tombol Edit yang mengarah ke halaman khusus Edit Task -->
            <a href="<?= BASE_URL ?>/?page=tasks&action=edit&id=<?= $taskId ?>" class="btn btn-sm btn-primary shadow-sm" title="Edit Task & Lampiran">
                <i class="fas fa-edit me-1"></i> Edit Task
            </a>
        <?php endif; ?>
        <?php if ($canDeleteTask): ?>
            <button type="button"
                    class="btn btn-sm btn-outline-danger shadow-sm"
                    title="Hapus Task"
                    onclick="deleteTask(<?= (int)$taskId ?>)">
                <i class="fas fa-trash me-1"></i> Delete
            </button>
        <?php endif; ?>
        <button type="button" class="btn-close ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
</div>

<div class="modal-body">
    <ul class="nav nav-tabs mb-3" id="taskDetailTabs" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tab-btn-details" data-bs-toggle="tab" data-bs-target="#tab-details" type="button" role="tab">
                <i class="fas fa-info-circle me-1"></i> Details
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-btn-attachments" data-bs-toggle="tab" data-bs-target="#tab-attachments" type="button" role="tab">
                <i class="fas fa-paperclip me-1"></i> Attachments <span class="badge bg-secondary ms-1"><?= count($attachments) ?></span>
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tab-btn-history" data-bs-toggle="tab" data-bs-target="#tab-history" type="button" role="tab">
                <i class="fas fa-history me-1"></i> History
            </button>
        </li>
    </ul>
    
    <div class="tab-content">
        <!-- Details Tab -->
        <div class="tab-pane fade show active" id="tab-details" role="tabpanel">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="small text-muted mb-1"><i class="fas fa-user me-1"></i>Assigned To</div>
                    <?php if ($assignee): ?>
                        <div class="d-flex align-items-center gap-2">
                            <div class="kanban-card-assignee" style="width:28px;height:28px;font-size:10px;">
                                <?= getUserInitials($assignee['name']) ?>
                            </div>
                            <div>
                                <div class="fw-semibold small"><?= htmlspecialchars($assignee['name']) ?></div>
                                <div class="text-muted" style="font-size:10px;"><?= htmlspecialchars($assignee['position'] ?: 'Staff') ?></div>
                            </div>
                        </div>
                    <?php else: ?>
                        <span class="text-muted small">Belum di-assign</span>
                    <?php endif; ?>
                </div>
                <div class="col-md-6">
                    <div class="small text-muted mb-1"><i class="fas fa-user-edit me-1"></i>Created By</div>
                    <div class="fw-semibold small"><?= htmlspecialchars($creator['name'] ?? 'N/A') ?></div>
                    <div class="text-muted" style="font-size:10px;"><?= formatDate($task['created_at'], 'datetime') ?></div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1"><i class="fas fa-flag me-1"></i>Priority</div>
                    <span class="badge bg-<?= PRIORITY_OPTIONS[$task['priority']]['badge'] ?>-soft text-<?= PRIORITY_OPTIONS[$task['priority']]['badge'] ?>">
                        <?= PRIORITY_OPTIONS[$task['priority']]['label'] ?>
                    </span>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1"><i class="fas fa-calendar me-1"></i>Start Date</div>
                    <div class="fw-semibold small"><?= $task['start_date'] ? formatDate($task['start_date']) : '-' ?></div>
                </div>
                <div class="col-md-4">
                    <div class="small text-muted mb-1"><i class="fas fa-flag-checkered me-1"></i>Target End</div>
                    <div class="fw-semibold small">
                        <?= $task['target_end_date'] ? formatDate($task['target_end_date']) : '-' ?>
                        <?php if ($daysLeft !== null): ?>
                            <span class="badge bg-<?= getDaysRemainingColor($daysLeft) ?>-soft text-<?= getDaysRemainingColor($daysLeft) ?> ms-1" style="font-size:9px;">
                                <?= $daysLeft < 0 ? abs($daysLeft) . ' hari terlambat' : ($daysLeft === 0 ? 'Hari ini' : $daysLeft . ' hari lagi') ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <?php if ($task['actual_end_date']): ?>
                <div class="col-12">
                    <div class="small text-muted mb-1"><i class="fas fa-check-circle me-1"></i>Completed At</div>
                    <div class="fw-semibold small text-success"><?= formatDate($task['actual_end_date']) ?></div>
                </div>
                <?php endif; ?>
                
                <div class="col-12">
                    <div class="small text-muted mb-1"><i class="fas fa-align-left me-1"></i>Description</div>
                    <div class="p-3 bg-light rounded small" style="white-space: pre-line;">
                        <?= !empty($task['description']) ? htmlspecialchars($task['description']) : '<em class="text-muted">Tidak ada deskripsi</em>' ?>
                    </div>
                </div>
                
                <?php if (!empty($task['notes'])): ?>
                <div class="col-12">
                    <div class="small text-muted mb-1"><i class="fas fa-sticky-note me-1"></i>Notes</div>
                    <div class="p-3 rounded small" style="background:#fff8dc;border-left:3px solid #ffc107; white-space: pre-line;">
                        <?= htmlspecialchars($task['notes']) ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Attachments Tab -->
        <div class="tab-pane fade" id="tab-attachments" role="tabpanel">
            <div id="attachmentsList">
                <?php if (empty($attachments)): ?>
                    <div class="empty-state py-4">
                        <div class="empty-state-icon"><i class="fas fa-paperclip"></i></div>
                        <div class="empty-state-title">Belum Ada Lampiran</div>
                        <div class="empty-state-text">Tidak ada file yang dilampirkan pada task ini.</div>
                    </div>
                <?php else: ?>
                    <div class="list-group">
                        <?php foreach ($attachments as $att): 
                            $uploader = getUserById($att['uploaded_by']);
                            $ext = strtolower(pathinfo($att['original_name'], PATHINFO_EXTENSION));
                            $iconClass = 'fa-file';
                            if (in_array($ext, ['jpg','jpeg','png','gif'])) $iconClass = 'fa-file-image';
                            elseif ($ext === 'pdf') $iconClass = 'fa-file-pdf';
                            elseif (in_array($ext, ['doc','docx'])) $iconClass = 'fa-file-word';
                            elseif (in_array($ext, ['xls','xlsx'])) $iconClass = 'fa-file-excel';
                        ?>
                        <div class="list-group-item d-flex align-items-center gap-3">
                            <div style="width:40px;height:40px;background:var(--primary-soft);border-radius:8px;display:flex;align-items:center;justify-content:center;color:var(--primary);">
                                <i class="fas <?= $iconClass ?>"></i>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-semibold small text-truncate"><?= htmlspecialchars($att['original_name']) ?></div>
                                <div class="text-muted" style="font-size:10px;">
                                    <?= formatFileSize($att['file_size']) ?> • 
                                    Uploaded by <?= htmlspecialchars($uploader['name'] ?? 'Unknown') ?> • 
                                    <?= formatDate($att['uploaded_at'], 'datetime') ?>
                                </div>
                            </div>
                            <div class="d-flex gap-1">
                                <a href="<?= BASE_URL ?>/assets/uploads/attachments/<?= urlencode($att['file_name']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" title="View">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="<?= BASE_URL ?>/assets/uploads/attachments/<?= urlencode($att['file_name']) ?>" download="<?= htmlspecialchars($att['original_name']) ?>" class="btn btn-sm btn-outline-secondary" title="Download">
                                    <i class="fas fa-download"></i>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- History Tab -->
        <div class="tab-pane fade" id="tab-history" role="tabpanel">
            <?php if (empty($history)): ?>
                <div class="empty-state py-4">
                    <div class="empty-state-icon"><i class="fas fa-history"></i></div>
                    <div class="empty-state-title">Belum Ada Riwayat</div>
                </div>
            <?php else: ?>
                <div class="timeline position-relative ps-2">
                    <?php foreach ($history as $h): 
                        $changer = getUserById($h['changed_by']);
                    ?>
                    <div class="timeline-item d-flex gap-3 mb-3">
                        <div style="width:32px;height:32px;background:var(--primary);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;font-size:12px;">
                            <i class="fas fa-arrow-right"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-semibold small">
                                <?php if ($h['from_column_title']): ?>
                                    Dipindahkan dari <span class="badge bg-secondary"><?= htmlspecialchars($h['from_column_title']) ?></span>
                                    ke <span class="badge bg-primary"><?= htmlspecialchars($h['to_column_title']) ?></span>
                                <?php else: ?>
                                    Dibuat di kolom <span class="badge bg-primary"><?= htmlspecialchars($h['to_column_title']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="text-muted small">
                                oleh <strong><?= htmlspecialchars($changer['name'] ?? 'Unknown') ?></strong>
                                • <?= formatDate($h['changed_at'], 'datetime') ?>
                            </div>
                            <?php if (!empty($h['notes'])): ?>
                                <div class="small text-muted mt-1"><em><?= htmlspecialchars($h['notes']) ?></em></div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>