<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
}

$currentUser = getCurrentUser();
$taskId = (int)($_GET['id'] ?? 0);

if (!$taskId) {
    setFlash('danger', 'Task tidak valid.');
    redirect(BASE_URL . '/?page=dashboard');
}

$task = getTaskById($taskId);
if (!$task) {
    setFlash('danger', 'Task tidak ditemukan.');
    redirect(BASE_URL . '/?page=dashboard');
}

// Check permission
$isLeader = isProjectLeader($task['project_id'], $currentUser['id']);
$isCreator = ((int)$task['created_by'] === (int)$currentUser['id']);
$isAssignee = ((int)$task['assigned_to'] === (int)$currentUser['id']);
$canEdit = $isLeader || $isCreator || $isAssignee || isAdmin();

if (!$canEdit) {
    setFlash('danger', 'Anda tidak memiliki hak akses untuk mengubah task ini.');
    redirect(BASE_URL . '/?page=projects&action=view&id=' . $task['project_id']);
}

// Handle Form Submission (Save Changes)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setFlash('danger', 'Token keamanan tidak valid.');
    } else {
        $title = cleanInput($_POST['title'] ?? '');
        $description = cleanInput($_POST['description'] ?? '');
        $notes = cleanInput($_POST['notes'] ?? '');
        $newColumnId = (int)($_POST['column_id'] ?? $task['column_id']);
        $assignedTo = !empty($_POST['assigned_to']) ? (int)$_POST['assigned_to'] : null;
        $startDate = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $targetEndDate = !empty($_POST['target_end_date']) ? $_POST['target_end_date'] : null;
        $priority = $_POST['priority'] ?? 'medium';
        $cardColor = $_POST['card_color'] ?? '#ffffff';

        // Validasi assignment
        if ($assignedTo && $assignedTo != $currentUser['id'] && $assignedTo != $task['assigned_to']) {
            if (!$isLeader && !$isCreator && !isAdmin()) {
                $assignedTo = $task['assigned_to']; // Revert
            } elseif (!isProjectMember($task['project_id'], $assignedTo)) {
                $assignedTo = null; // Prevent assigning to non-members
            }
        }

        if (empty($title)) {
            setFlash('danger', 'Judul task tidak boleh kosong.');
        } else {
            $conn = db();
            $stmt = $conn->prepare("SELECT project_id, is_done_column FROM project_columns WHERE id = ?");
            $stmt->execute([$newColumnId]);
            $col = $stmt->fetch();

            if ($col && $col['project_id'] == $task['project_id']) {
                try {
                    $conn->beginTransaction();
                    
                    $columnChanged = ($newColumnId != $task['column_id']);

                    $stmt = $conn->prepare("
                        UPDATE tasks 
                        SET title=?, description=?, notes=?, column_id=?, assigned_to=?, 
                            start_date=?, target_end_date=?, priority=?, card_color=?
                        WHERE id=?
                    ");
                    $stmt->execute([$title, $description, $notes, $newColumnId, $assignedTo, $startDate, $targetEndDate, $priority, $cardColor, $taskId]);

                    // Handle status change
                    if ($columnChanged) {
                        $stmt = $conn->prepare("INSERT INTO task_status_history (task_id, from_column_id, to_column_id, changed_by, notes) VALUES (?, ?, ?, ?, 'Status diperbarui via form Edit')");
                        $stmt->execute([$taskId, $task['column_id'], $newColumnId, $currentUser['id']]);

                        if ($col['is_done_column']) {
                            $stmt = $conn->prepare("UPDATE tasks SET actual_end_date = CURDATE() WHERE id = ? AND actual_end_date IS NULL");
                            $stmt->execute([$taskId]);
                        } else {
                            $stmt = $conn->prepare("UPDATE tasks SET actual_end_date = NULL WHERE id = ?");
                            $stmt->execute([$taskId]);
                        }
                    }

                    // Handle Attachments Upload
                    if (!empty($_FILES['attachments']['name'][0])) {
                        $files = $_FILES['attachments'];
                        $count = count($files['name']);
                        for ($i = 0; $i < $count; $i++) {
                            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                                $file = [
                                    'name'     => $files['name'][$i],
                                    'tmp_name' => $files['tmp_name'][$i],
                                    'size'     => $files['size'][$i],
                                    'type'     => $files['type'][$i],
                                    'error'    => $files['error'][$i],
                                ];
                                uploadAttachment($taskId, $file, $currentUser['id']);
                            }
                        }
                    }

                    updateProjectProgress($task['project_id']);
                    logActivity($currentUser['id'], $task['project_id'], $taskId, 'task_updated', 'Task diperbarui via halaman edit');

                    $conn->commit();
                    setFlash('success', 'Task berhasil diperbarui.');
                    redirect(BASE_URL . '/?page=projects&action=view&id=' . $task['project_id']);
                } catch (Exception $e) {
                    $conn->rollBack();
                    setFlash('danger', 'Gagal memperbarui task: ' . $e->getMessage());
                }
            }
        }
    }
}

$pageTitle = 'Edit Task';
$project = getProjectById($task['project_id']);
$columns = getProjectColumns($task['project_id']);
$members = getProjectMembers($task['project_id']);
$attachments = getTaskAttachments($taskId);
?>

<div class="page-header">
    <div class="page-header-title">
        <h2><i class="fas fa-edit me-2 text-primary"></i>Edit Task</h2>
        <p><?= htmlspecialchars($project['title']) ?> / <span class="badge bg-secondary"><?= htmlspecialchars($task['title']) ?></span></p>
    </div>
    <a href="<?= BASE_URL ?>/?page=projects&action=view&id=<?= $task['project_id'] ?>" class="btn btn-outline-secondary shadow-sm">
        <i class="fas fa-arrow-left me-1"></i> Kembali ke Project
    </a>
</div>

<div class="row g-4">
    <!-- Form Kiri -->
    <div class="col-lg-8">
        <form method="POST" action="" enctype="multipart/form-data" id="mainEditForm">
            <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
            
            <div class="info-card mb-4">
                <div class="info-card-header bg-white">
                    <h5 class="info-card-title"><i class="fas fa-align-left me-2 text-primary"></i>Informasi Utama</h5>
                </div>
                <div class="info-card-body">
                    <div class="mb-3">
                        <label class="form-label">Judul Task <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" value="<?= htmlspecialchars($task['title']) ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Deskripsi</label>
                        <textarea name="description" class="form-control" rows="5"><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan Tambahan (Notes)</label>
                        <textarea name="notes" class="form-control" rows="3" style="background-color: #fff8dc;"><?= htmlspecialchars($task['notes'] ?? '') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Upload Area Baru disatukan dalam form utama -->
            <div class="info-card mb-4">
                <div class="info-card-header bg-white">
                    <h5 class="info-card-title"><i class="fas fa-cloud-upload-alt me-2 text-primary"></i>Upload Lampiran Baru</h5>
                </div>
                <div class="info-card-body">
                    <input type="file" name="attachments[]" class="form-control" multiple>
                    <div class="form-text mt-2 text-muted">Anda dapat memilih lebih dari satu file. Maks 10MB per file. (<?= implode(', ', ALLOWED_EXTENSIONS) ?>)</div>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary px-4 py-2 w-100 shadow-sm fw-bold">
                <i class="fas fa-save me-2"></i> Simpan Semua Perubahan
            </button>
        </form>
    </div>

    <!-- Kolom Kanan (Setting & Att List) -->
    <div class="col-lg-4">
        
        <!-- Setting Card diletakkan di luar tag form kiri, TAPI kita sambungkan menggunakan attribute HTML5 form="mainEditForm" -->
        <div class="info-card mb-4">
            <div class="info-card-header bg-white">
                <h5 class="info-card-title"><i class="fas fa-cog me-2 text-primary"></i>Pengaturan Task</h5>
            </div>
            <div class="info-card-body">
                <div class="mb-3">
                    <label class="form-label">Kolom / Status</label>
                    <select name="column_id" form="mainEditForm" class="form-select fw-bold text-primary">
                        <?php foreach ($columns as $col): ?>
                            <option value="<?= $col['id'] ?>" <?= $col['id'] == $task['column_id'] ? 'selected' : '' ?>><?= htmlspecialchars($col['title']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Ditugaskan Kepada</label>
                    <select name="assigned_to" form="mainEditForm" class="form-select select2">
                        <option value="">-- Belum Di-assign --</option>
                        <?php foreach ($members as $mem): if (!$mem['user']) continue; ?>
                            <option value="<?= $mem['user']['id'] ?>" <?= $mem['user']['id'] == $task['assigned_to'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($mem['user']['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Prioritas</label>
                    <select name="priority" form="mainEditForm" class="form-select">
                        <?php foreach (PRIORITY_OPTIONS as $key => $val): ?>
                            <option value="<?= $key ?>" <?= $key === $task['priority'] ? 'selected' : '' ?>><?= $val['label'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3 row g-2">
                    <div class="col-6">
                        <label class="form-label">Mulai</label>
                        <input type="text" form="mainEditForm" name="start_date" class="form-control datepicker" value="<?= $task['start_date'] ?>">
                    </div>
                    <div class="col-6">
                        <label class="form-label">Selesai</label>
                        <input type="text" form="mainEditForm" name="target_end_date" class="form-control datepicker" value="<?= $task['target_end_date'] ?>">
                    </div>
                </div>
                <div class="mb-0">
                    <label class="form-label mb-2">Warna Kartu</label>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach (CARD_COLORS as $color => $name): ?>
                        <label class="color-picker" title="<?= $name ?>">
                            <input type="radio" form="mainEditForm" name="card_color" value="<?= $color ?>" <?= $color === $task['card_color'] ? 'checked' : '' ?> class="d-none">
                            <span class="color-swatch" style="background:<?= $color ?>; border:1px solid #ddd; width:26px; height:26px; border-radius:50%; display:block;"></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Daftar Lampiran -->
        <div class="info-card">
            <div class="info-card-header bg-white">
                <h5 class="info-card-title"><i class="fas fa-paperclip me-2 text-primary"></i>Lampiran Tersimpan (<?= count($attachments) ?>)</h5>
            </div>
            <div class="info-card-body p-0">
                <?php if (empty($attachments)): ?>
                    <div class="p-4 text-center text-muted small">Belum ada file lampiran.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($attachments as $att): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div class="text-truncate me-2">
                                    <div class="small fw-semibold text-dark text-truncate" title="<?= htmlspecialchars($att['original_name']) ?>">
                                        <?= htmlspecialchars($att['original_name']) ?>
                                    </div>
                                    <div style="font-size:10px;" class="text-muted"><?= formatFileSize($att['file_size']) ?></div>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0">
                                    <a href="<?= BASE_URL ?>/assets/uploads/attachments/<?= urlencode($att['file_name']) ?>" target="_blank" class="btn btn-sm btn-light text-primary">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <!-- Tombol Hapus memanggil API via AJAX agar tidak perlu mematikan form utama -->
                                    <button type="button" class="btn btn-sm btn-light text-danger" onclick="deleteAttachment(<?= $att['id'] ?>)">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
    </div>
</div>

<style>
.color-picker { cursor: pointer; }
.color-picker input:checked + .color-swatch {
    box-shadow: 0 0 0 3px var(--primary);
    transform: scale(1.1);
}
</style>

<script>
function deleteAttachment(id) {
    PM.confirm({ title: 'Hapus Lampiran?', text: 'File akan dihapus secara permanen.', icon: 'warning' })
    .then(result => {
        if (result.isConfirmed) {
            PM.post(APP_CONFIG.BASE_URL + '/?page=api&action=delete_attachment', { id: id })
            .then(res => {
                if (res.success) {
                    PM.toast('success', 'Lampiran berhasil dihapus');
                    setTimeout(() => window.location.reload(), 700);
                } else {
                    Swal.fire('Error', res.message || 'Gagal menghapus file', 'error');
                }
            });
        }
    });
}
</script>