<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
}

$currentUser = getCurrentUser();
$projectId = (int)($_GET['id'] ?? 0);

if (!$projectId) {
    setFlash('danger', 'Project tidak ditemukan.');
    redirect(BASE_URL . '/?page=projects&action=index');
}

$project = getProjectById($projectId);
if (!$project) {
    setFlash('danger', 'Project tidak ditemukan.');
    redirect(BASE_URL . '/?page=projects&action=index');
}

// Permission check
$isLeader = isProjectLeader($projectId, $currentUser['id']);
$isCreator = ((int)$project['created_by'] === (int)$currentUser['id']);

if (!$isLeader && !$isCreator && !isAdmin()) {
    setFlash('danger', 'Anda tidak memiliki akses untuk mengedit project ini.');
    redirect(BASE_URL . '/?page=projects&action=view&id=' . $projectId);
}

$pageTitle = 'Edit Project';
$members = getProjectMembers($projectId);
$currentMemberIds = array_column($members, 'user_id');

// Pembuat project atau Admin berhak menghapus
$canDeleteProject = $isCreator || isAdmin();

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Token tidak valid.');
        redirect(BASE_URL . '/?page=projects&action=edit&id=' . $projectId);
    }
    
    $title = cleanInput($_POST['title'] ?? '');
    $description = cleanInput($_POST['description'] ?? '');
    $leaderId = (int)($_POST['project_leader_id'] ?? 0);
    $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $targetEndDate = !empty($_POST['target_end_date']) ? $_POST['target_end_date'] : null;
    $privacy = $_POST['privacy'] ?? 'team';
    $priority = $_POST['priority'] ?? 'medium';
    $status = $_POST['status'] ?? 'active';
    
    if (empty($title)) {
        setFlash('danger', 'Judul project wajib diisi.');
    } else {
        try {
            $conn = db();
            $conn->beginTransaction();
            
            $oldStatus = $project['status'];
            
            $stmt = $conn->prepare("
                UPDATE projects 
                SET title = ?, description = ?, project_leader_id = ?, start_date = ?, target_end_date = ?, 
                    privacy = ?, priority = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $description, $leaderId, $startDate, $targetEndDate, $privacy, $priority, $status, $projectId]);
            
            if ($oldStatus !== $status) {
                $stmt = $conn->prepare("
                    INSERT INTO project_status_history (project_id, from_status, to_status, changed_by, notes)
                    VALUES (?, ?, ?, ?, ?)
                ");
                $stmt->execute([$projectId, $oldStatus, $status, $currentUser['id'], 'Status updated from edit form']);
                
                if ($status === 'completed') {
                    $conn->prepare("UPDATE projects SET actual_end_date = CURDATE() WHERE id = ?")->execute([$projectId]);
                }
            }
            
            $newMemberIds = [];
            if (!empty($_POST['members'])) {
                $newMemberIds = array_map('intval', $_POST['members']);
            }
            
            if (!empty($_POST['add_bidang'])) {
                foreach ($_POST['add_bidang'] as $bidang) {
                    $usersInBidang = getUsersByDivision($bidang);
                    foreach ($usersInBidang as $u) {
                        $newMemberIds[] = $u['id'];
                    }
                }
            }
            
            if (!empty($_POST['add_divisi'])) {
                foreach ($_POST['add_divisi'] as $divisi) {
                    $usersInDivisi = getUsersByDepartment($divisi);
                    foreach ($usersInDivisi as $u) {
                        $newMemberIds[] = $u['id'];
                    }
                }
            }
            
            $newMemberIds = array_unique(array_merge($newMemberIds, [$leaderId]));
            
            $stmt = $conn->prepare("DELETE FROM project_members WHERE project_id = ? AND role != 'leader'");
            $stmt->execute([$projectId]);
            
            $stmt = $conn->prepare("UPDATE project_members SET user_id = ? WHERE project_id = ? AND role = 'leader'");
            $stmt->execute([$leaderId, $projectId]);
            
            foreach ($newMemberIds as $memId) {
                if ($memId != $leaderId) {
                    $stmt = $conn->prepare("
                        INSERT IGNORE INTO project_members (project_id, user_id, role, added_by)
                        VALUES (?, ?, 'member', ?)
                    ");
                    $stmt->execute([$projectId, $memId, $currentUser['id']]);
                }
            }
            
            logActivity($currentUser['id'], $projectId, null, 'project_updated', 'Project updated: Privacy=' . $privacy);
            
            $conn->commit();
            setFlash('success', 'Project berhasil diperbarui.');
            redirect(BASE_URL . '/?page=projects&action=view&id=' . $projectId);
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Update project error: " . $e->getMessage());
            setFlash('danger', 'Gagal memperbarui project.');
        }
    }
}

$allUsers = getAllUsers();
$leader = getProjectLeader($projectId);
$leaderId = $leader['id'] ?? $currentUser['id'];

$bidangList = [];
$divisiList = [];
foreach ($allUsers as $u) {
    if ($u['division']) $bidangList[$u['division']] = $u['division'];
    if ($u['department']) $divisiList[$u['department']] = $u['department'];
}
sort($bidangList);
sort($divisiList);

$columns = getProjectColumns($projectId);
?>

<div class="page-header">
    <div class="page-header-title">
        <h2><i class="fas fa-edit me-2 text-primary"></i>Edit Project</h2>
        <p><?= htmlspecialchars($project['title']) ?></p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= BASE_URL ?>/?page=projects&action=view&id=<?= $projectId ?>" class="btn btn-outline-secondary">
            <i class="fas fa-arrow-left me-1"></i> Back to Project
        </a>
        <?php if ($canDeleteProject): ?>
        <button type="button" class="btn btn-outline-danger" onclick="deleteProject(<?= $projectId ?>)">
            <i class="fas fa-trash me-1"></i> Delete Project
        </button>
        <?php endif; ?>
    </div>
</div>

<form method="POST" action="" id="editProjectForm">
    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
    
    <div class="row g-3">
        <div class="col-lg-8">
            <div class="info-card mb-3">
                <div class="info-card-header">
                    <h5 class="info-card-title"><i class="fas fa-info-circle me-2 text-primary"></i>Basic Information</h5>
                </div>
                <div class="info-card-body">
                    <div class="mb-3">
                        <label class="form-label">Project Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($project['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date</label>
                            <input type="text" name="start_date" class="form-control datepicker" value="<?= $project['start_date'] ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Target End Date</label>
                            <input type="text" name="target_end_date" class="form-control datepicker" value="<?= $project['target_end_date'] ?>">
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="info-card mb-3">
                <div class="info-card-header">
                    <h5 class="info-card-title"><i class="fas fa-users me-2 text-primary"></i>Team Members</h5>
                </div>
                <div class="info-card-body">
                    <div class="mb-3">
                        <label class="form-label">Project Leader <span class="text-danger">*</span></label>
                        <select name="project_leader_id" class="form-select select2" required>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= $u['id'] == $leaderId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name']) ?> - <?= htmlspecialchars($u['position'] ?: 'Staff') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Team Members</label>
                        <select name="members[]" class="form-select select2" multiple>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= $u['id'] ?>" <?= in_array($u['id'], $currentMemberIds) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name']) ?> - <?= htmlspecialchars($u['position'] ?: 'Staff') ?> (<?= htmlspecialchars($u['division'] ?: '-') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Add All from Bidang</label>
                            <select name="add_bidang[]" class="form-select select2" multiple>
                                <?php foreach ($bidangList as $b): ?>
                                    <option value="<?= htmlspecialchars($b) ?>"><?= htmlspecialchars($b) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Add All from Divisi</label>
                            <select name="add_divisi[]" class="form-select select2" multiple>
                                <?php foreach ($divisiList as $d): ?>
                                    <option value="<?= htmlspecialchars($d) ?>"><?= htmlspecialchars($d) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="info-card mb-3">
                <div class="info-card-header">
                    <div>
                        <h5 class="info-card-title"><i class="fas fa-columns me-2 text-primary"></i>Kanban Columns</h5>
                        <p class="info-card-subtitle mb-0">Manage columns for this project</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addColumnModal">
                        <i class="fas fa-plus me-1"></i> Add Column
                    </button>
                </div>
                <div class="info-card-body">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>COLUMN</th>
                                    <th>COLOR</th>
                                    <th>DONE COLUMN</th>
                                    <th>ACTION</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($columns as $col): 
                                    $taskCount = count(getTasksByColumn($col['id']));
                                ?>
                                <tr>
                                    <td><?= $col['position'] + 1 ?></td>
                                    <td>
                                        <div class="fw-semibold"><?= htmlspecialchars($col['title']) ?></div>
                                        <div class="small text-muted"><?= $taskCount ?> tasks</div>
                                    </td>
                                    <td><span class="kanban-column-dot" style="background:<?= $col['color'] ?>;display:inline-block;"></span></td>
                                    <td>
                                        <?php if ($col['is_done_column']): ?>
                                            <span class="badge bg-success-soft text-success">Done</span>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($taskCount == 0): ?>
                                        <a href="#" onclick="deleteColumn(<?= $col['id'] ?>); return false;" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <span class="text-muted small" title="Cannot delete - has tasks">
                                            <i class="fas fa-lock"></i>
                                        </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="info-card mb-3">
                <div class="info-card-header">
                    <h5 class="info-card-title"><i class="fas fa-cog me-2 text-primary"></i>Project Settings</h5>
                </div>
                <div class="info-card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="status" class="form-select">
                            <?php foreach (PROJECT_STATUS as $key => $val): ?>
                                <option value="<?= $key ?>" <?= $project['status'] === $key ? 'selected' : '' ?>><?= $val['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <?php foreach (PRIORITY_OPTIONS as $key => $val): ?>
                                <option value="<?= $key ?>" <?= $project['priority'] === $key ? 'selected' : '' ?>><?= $val['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-0">
                        <label class="form-label mb-2">Privacy Level</label>
                        <div class="privacy-options-list">
                            <?php foreach (PRIVACY_OPTIONS as $key => $val): 
                                $isChecked = ($project['privacy'] === $key);
                            ?>
                            <div class="mb-2 position-relative">
                                <input type="radio" name="privacy" id="edit_privacy_<?= $key ?>" value="<?= $key ?>" <?= $isChecked ? 'checked' : '' ?> class="privacy-radio-input">
                                <label for="edit_privacy_<?= $key ?>" class="privacy-card-label d-flex align-items-center gap-2 p-2 border rounded w-100">
                                    <i class="<?= $val['icon'] ?> text-primary" style="width:24px; text-align:center;"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small text-dark"><?= $val['label'] ?></div>
                                        <div class="text-muted" style="font-size:10px;"><?= $val['desc'] ?></div>
                                    </div>
                                    <div class="privacy-check-icon text-primary">
                                        <i class="fas fa-check-circle"></i>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="info-card">
                <div class="info-card-body">
                    <button type="submit" class="btn btn-primary w-100 mb-2">
                        <i class="fas fa-save me-1"></i> Save Changes
                    </button>
                    <a href="<?= BASE_URL ?>/?page=projects&action=view&id=<?= $projectId ?>" class="btn btn-outline-secondary w-100">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Add Column Modal -->
<div class="modal fade" id="addColumnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus text-primary me-2"></i>Add New Column</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small">Select a column to add to the kanban board:</p>
                <div class="row g-2">
                    <?php 
                    $existingSlugs = array_column($columns, 'slug');
                    foreach (KANBAN_COLUMN_OPTIONS as $slug => $col):
                        if (in_array($slug, $existingSlugs)) continue;
                    ?>
                    <div class="col-12">
                        <button type="button" class="btn btn-outline-secondary w-100 text-start" onclick="addColumn('<?= $slug ?>')">
                            <span class="kanban-column-dot" style="background:<?= $col['color'] ?>;display:inline-block;margin-right:8px;"></span>
                            <i class="<?= $col['icon'] ?> me-2"></i><?= $col['title'] ?>
                        </button>
                    </div>
                    <?php endforeach; ?>
                    <?php if (count(array_diff_key(KANBAN_COLUMN_OPTIONS, array_flip($existingSlugs))) === 0): ?>
                        <div class="col-12 text-center text-muted py-3">
                            All available columns have been added.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Style Khusus Privacy Radio Button Cards */
.privacy-radio-input {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.privacy-card-label {
    cursor: pointer;
    transition: all 0.2s ease;
    background: #ffffff;
}
.privacy-card-label:hover {
    border-color: var(--primary) !important;
    background: var(--gray-50);
}
.privacy-card-label .privacy-check-icon {
    opacity: 0;
    visibility: hidden;
    transition: all 0.2s ease;
}
.privacy-radio-input:checked + .privacy-card-label {
    border-color: var(--primary) !important;
    background: var(--primary-soft) !important;
    box-shadow: 0 0 0 1px var(--primary);
}
.privacy-radio-input:checked + .privacy-card-label .privacy-check-icon {
    opacity: 1;
    visibility: visible;
}
</style>

<script>
const PROJECT_ID = <?= $projectId ?>;

function addColumn(slug) {
    PM.loading('Adding column...');
    PM.post(APP_CONFIG.BASE_URL + '/?page=api&action=add_column', {
        project_id: PROJECT_ID,
        slug: slug
    }).then(res => {
        PM.closeAlert();
        if (res.success) {
            PM.toast('success', 'Column added');
            setTimeout(() => window.location.reload(), 800);
        } else {
            Swal.fire('Error', res.message || 'Failed to add column', 'error');
        }
    });
}

function deleteColumn(columnId) {
    PM.confirm({
        title: 'Delete Column?',
        text: 'This column will be removed from the board.',
        icon: 'warning'
    }).then(result => {
        if (result.isConfirmed) {
            PM.loading('Deleting column...');
            PM.post(APP_CONFIG.BASE_URL + '/?page=api&action=delete_column', {
                column_id: columnId,
                project_id: PROJECT_ID
            }).then(res => {
                PM.closeAlert();
                if (res.success) {
                    PM.toast('success', 'Column deleted');
                    setTimeout(() => window.location.reload(), 800);
                } else {
                    Swal.fire('Error', res.message || 'Failed to delete', 'error');
                }
            });
        }
    });
}
</script>