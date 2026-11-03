<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
}

$currentUser = getCurrentUser();
$pageTitle = 'Create New Project';

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Token tidak valid. Silakan coba lagi.');
        redirect(BASE_URL . '/?page=projects&action=create');
    }
    
    $data = [
        'title' => cleanInput($_POST['title'] ?? ''),
        'description' => cleanInput($_POST['description'] ?? ''),
        'created_by' => $currentUser['id'],
        'project_leader_id' => (int)($_POST['project_leader_id'] ?? $currentUser['id']),
        'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
        'target_end_date' => !empty($_POST['target_end_date']) ? $_POST['target_end_date'] : null,
        'privacy' => $_POST['privacy'] ?? 'team',
        'priority' => $_POST['priority'] ?? 'medium',
        'columns' => $_POST['columns'] ?? DEFAULT_COLUMNS,
        'members' => []
    ];
    
    if (!empty($_POST['members'])) {
        $data['members'] = array_map('intval', $_POST['members']);
    }
    
    if (!empty($_POST['add_bidang'])) {
        foreach ($_POST['add_bidang'] as $bidang) {
            $usersInBidang = getUsersByDivision($bidang);
            foreach ($usersInBidang as $u) {
                $data['members'][] = $u['id'];
            }
        }
    }
    
    if (!empty($_POST['add_divisi'])) {
        foreach ($_POST['add_divisi'] as $divisi) {
            $usersInDivisi = getUsersByDepartment($divisi);
            foreach ($usersInDivisi as $u) {
                $data['members'][] = $u['id'];
            }
        }
    }
    
    $data['members'] = array_unique($data['members']);
    
    if (empty($data['title'])) {
        setFlash('danger', 'Judul project wajib diisi.');
    } elseif (empty($data['columns'])) {
        setFlash('danger', 'Pilih minimal satu kolom kerja.');
    } else {
        $projectId = createProject($data);
        if ($projectId) {
            setFlash('success', 'Project berhasil dibuat!');
            redirect(BASE_URL . '/?page=projects&action=view&id=' . $projectId);
        } else {
            setFlash('danger', 'Gagal membuat project. Silakan coba lagi.');
        }
    }
}

$allUsers = getAllUsers();

$bidangList = [];
$divisiList = [];
foreach ($allUsers as $u) {
    if ($u['division']) $bidangList[$u['division']] = $u['division'];
    if ($u['department']) $divisiList[$u['department']] = $u['department'];
}
sort($bidangList);
sort($divisiList);
?>

<div class="page-header">
    <div class="page-header-title">
        <h2><i class="fas fa-plus-circle me-2 text-primary"></i>Create New Project</h2>
        <p>Fill in project details, team members, and configure working columns</p>
    </div>
    <a href="<?= BASE_URL ?>/?page=projects&action=index" class="btn btn-outline-secondary">
        <i class="fas fa-arrow-left me-1"></i> Back
    </a>
</div>

<form method="POST" action="" id="createProjectForm">
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
                        <input type="text" name="title" class="form-control" placeholder="e.g., Website Redesign 2026" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="4" placeholder="Describe the project scope, objectives, and deliverables..."></textarea>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Start Date <span class="text-danger">*</span></label>
                            <input type="text" name="start_date" class="form-control datepicker" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Target End Date <span class="text-danger">*</span></label>
                            <input type="text" name="target_end_date" class="form-control datepicker" required>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="info-card mb-3">
                <div class="info-card-header">
                    <div>
                        <h5 class="info-card-title"><i class="fas fa-columns me-2 text-primary"></i>Kolom Project Board</h5>
                        <p class="info-card-subtitle mb-0">Pilih kolom yang akan anda masukkan dalam project board</p>
                    </div>
                </div>
                <div class="info-card-body">
                    <div class="row g-2">
                        <?php foreach (KANBAN_COLUMN_OPTIONS as $slug => $col): 
                            $checked = in_array($slug, DEFAULT_COLUMNS);
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <label class="column-option d-block" style="cursor:pointer;">
                                <input type="checkbox" name="columns[]" value="<?= $slug ?>" <?= $checked ? 'checked' : '' ?> class="column-checkbox d-none">
                                <div class="column-card border rounded p-3 d-flex align-items-center gap-2 <?= $checked ? 'active' : '' ?>">
                                    <div class="column-color" style="width:12px;height:32px;background:<?= $col['color'] ?>;border-radius:3px;"></div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold small"><i class="<?= $col['icon'] ?> me-1"></i><?= $col['title'] ?></div>
                                    </div>
                                    <i class="fas fa-check-circle text-primary check-icon" style="display:<?= $checked ? 'block' : 'none' ?>;"></i>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
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
                                <option value="<?= $u['id'] ?>" <?= $u['id'] == $currentUser['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($u['name']) ?> - <?= htmlspecialchars($u['position'] ?: 'Staff') ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Select Individual Members</label>
                        <select name="members[]" class="form-select select2" multiple>
                            <?php foreach ($allUsers as $u): ?>
                                <option value="<?= $u['id'] ?>">
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
        </div>
        
        <div class="col-lg-4">
            <div class="info-card mb-3">
                <div class="info-card-header">
                    <h5 class="info-card-title"><i class="fas fa-cog me-2 text-primary"></i>Project Settings</h5>
                </div>
                <div class="info-card-body">
                    <div class="mb-3">
                        <label class="form-label">Priority</label>
                        <select name="priority" class="form-select">
                            <?php foreach (PRIORITY_OPTIONS as $key => $val): ?>
                                <option value="<?= $key ?>" <?= $key === 'medium' ? 'selected' : '' ?>><?= $val['label'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mb-0">
                        <label class="form-label mb-2">Privacy Level</label>
                        <div class="privacy-options-list">
                            <?php foreach (PRIVACY_OPTIONS as $key => $val): 
                                $isChecked = ($key === 'team');
                            ?>
                            <div class="mb-2 position-relative">
                                <input type="radio" name="privacy" id="create_privacy_<?= $key ?>" value="<?= $key ?>" <?= $isChecked ? 'checked' : '' ?> class="privacy-radio-input">
                                <label for="create_privacy_<?= $key ?>" class="privacy-card-label d-flex align-items-center gap-2 p-2 border rounded w-100">
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
                        <i class="fas fa-check me-1"></i> Create Project
                    </button>
                    <a href="<?= BASE_URL ?>/?page=projects&action=index" class="btn btn-outline-secondary w-100">
                        Cancel
                    </a>
                </div>
            </div>
        </div>
    </div>
</form>

<style>
.column-card {
    transition: all 0.2s ease;
    border-color: #e5e7eb !important;
}
.column-card:hover {
    border-color: var(--primary) !important;
    background: var(--gray-50);
}
.column-card.active {
    border-color: var(--primary) !important;
    background: var(--primary-soft);
}

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
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.column-checkbox').forEach(cb => {
        cb.addEventListener('change', function() {
            const card = this.parentElement.querySelector('.column-card');
            const check = card.querySelector('.check-icon');
            if (this.checked) {
                card.classList.add('active');
                check.style.display = 'block';
            } else {
                card.classList.remove('active');
                check.style.display = 'none';
            }
        });
    });
});
</script>