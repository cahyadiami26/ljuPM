<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
}

$currentUser = getCurrentUser();
$taskId = (int)($_GET['id'] ?? $_GET['task_id'] ?? 0);

$task = getTaskById($taskId);
if (!$task) {
    setFlash('danger', 'Task tidak ditemukan.');
    redirect(BASE_URL . '/?page=projects&action=index');
}

// Cek Hak Akses Edit
$isLeader = isProjectLeader($task['project_id'], $currentUser['id']);
$isCreator = $task['created_by'] == $currentUser['id'];
$isAssignee = $task['assigned_to'] == $currentUser['id'];
$canEdit = $isLeader || $isCreator || $isAssignee || isAdmin();

if (!$canEdit) {
    setFlash('danger', 'Anda tidak memiliki akses untuk mengedit task ini.');
    redirect(BASE_URL . '/?page=projects&action=view&id=' . $task['project_id']);
}

$project = getProjectById($task['project_id']);
$columns = getProjectColumns($task['project_id']);
$members = getProjectMembers($task['project_id']);
$attachments = getTaskAttachments($taskId);

$pageTitle = "Edit Task: " . $task['title'];
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="mb-3">
                <a href="<?= BASE_URL ?>/?page=projects&action=view&id=<?= $task['project_id'] ?>" class="text-decoration-none text-muted small">
                    <i class="fas fa-arrow-left me-1"></i> Kembali ke Project
                </a>
            </div>

            <div class="card shadow-sm border-0 mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0 fw-bold"><i class="fas fa-edit text-primary me-2"></i>Edit Task & Kelola Lampiran</h5>
                </div>
                <div class="card-body">
                    <!-- Form Edit Task (Mengirim ke API Update Task) -->
                    <form id="standaloneEditTaskForm" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                        <input type="hidden" name="task_id" value="<?= $taskId ?>">

                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold small">Judul Task <span class="text-danger">*</span></label>
                            <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($task['title']) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold small">Deskripsi</label>
                            <textarea id="description" name="description" class="form-control" rows="3"><?= htmlspecialchars($task['description'] ?? '') ?></textarea>
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="column_id" class="form-label fw-bold small">Kolom / Status</label>
                                <select id="column_id" name="column_id" class="form-select">
                                    <?php foreach ($columns as $col): ?>
                                        <option value="<?= $col['id'] ?>" <?= $col['id'] == $task['column_id'] ? 'selected' : '' ?>><?= htmlspecialchars($col['title']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="assigned_to" class="form-label fw-bold small">Assign To</label>
                                <select id="assigned_to" name="assigned_to" class="form-select">
                                    <option value="">-- Belum Di-assign --</option>
                                    <?php foreach ($members as $mem): if (!$mem['user']) continue; ?>
                                        <option value="<?= $mem['user']['id'] ?>" <?= $mem['user']['id'] == $task['assigned_to'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($mem['user']['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="start_date" class="form-label fw-bold small">Tanggal Mulai</label>
                                <input type="date" id="start_date" name="start_date" class="form-control" value="<?= $task['start_date'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="target_end_date" class="form-label fw-bold small">Target Selesai</label>
                                <input type="date" id="target_end_date" name="target_end_date" class="form-control" value="<?= $task['target_end_date'] ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="priority" class="form-label fw-bold small">Prioritas</label>
                                <select id="priority" name="priority" class="form-select">
                                    <?php foreach (PRIORITY_OPTIONS as $key => $val): ?>
                                        <option value="<?= $key ?>" <?= $key === $task['priority'] ? 'selected' : '' ?>><?= $val['label'] ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold small">Warna Kartu</label>
                                <div class="d-flex gap-2 flex-wrap pt-1">
                                    <?php foreach (CARD_COLORS as $color => $name): ?>
                                    <label class="color-picker cursor-pointer" title="<?= $name ?>">
                                        <input type="radio" name="card_color" value="<?= $color ?>" <?= $color === $task['card_color'] ? 'checked' : '' ?> class="d-none">
                                        <span class="color-swatch shadow-xs d-block" style="background:<?= $color ?>; border:2px solid #fff; width:28px; height:28px; border-radius:50%; box-shadow: 0 0 0 1px #dee2e6;"></span>
                                    </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="notes" class="form-label fw-bold small">Catatan Tambahan</label>
                            <textarea id="notes" name="notes" class="form-control" rows="2"><?= htmlspecialchars($task['notes'] ?? '') ?></textarea>
                        </div>

                        <!-- Bagian Upload Lampiran Tambahan -->
                        <div class="mb-4 p-3 border rounded bg-light">
                            <label for="attachments" class="form-label fw-bold small text-dark">
                                <i class="fas fa-paperclip text-primary me-1"></i> Tambah Lampiran File Baru
                            </label>
                            <input type="file" id="attachments" name="attachments[]" class="form-control" multiple>
                            <div class="form-text">Maksimal 10MB per file. Format diizinkan: <?= implode(', ', ALLOWED_EXTENSIONS) ?></div>
                            
                            <!-- Daftar Lampiran yang Sudah Ada -->
                            <?php if (!empty($attachments)): ?>
                                <div class="mt-3">
                                    <span class="small fw-bold text-muted">Lampiran Saat Ini:</span>
                                    <ul class="list-group mt-2">
                                        <?php foreach ($attachments as $att): ?>
                                            <li class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">
                                                <span class="small text-truncate" style="max-width: 80%;">
                                                    <i class="fas fa-file me-1 text-secondary"></i> <?= htmlspecialchars($att['original_name']) ?>
                                                </span>
                                                <a href="<?= BASE_URL ?>/?page=api&action=delete_attachment&id=<?= $att['id'] ?>" class="text-danger small text-decoration-none" onclick="return confirm('Hapus lampiran ini?')">
                                                    <i class="fas fa-trash"></i> Hapus
                                                </a>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="<?= BASE_URL ?>/?page=projects&action=view&id=<?= $task['project_id'] ?>" class="btn btn-outline-secondary">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('standaloneEditTaskForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const formData = new FormData(this);
    
    PM.loading('Menyimpan perubahan task...');

    // 1. Kirim data teks task ke API update_task yang sudah ada
    fetch(APP_CONFIG.BASE_URL + '/?page=api&action=update_task', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (!res.success) {
            PM.closeAlert();
            Swal.fire('Gagal', res.message || 'Gagal memperbarui task', 'error');
            return;
        }

        // 2. Jika ada file lampiran yang dipilih, upload file secara berurutan
        const fileInput = document.getElementById('attachments');
        if (fileInput.files.length > 0) {
            PM.loading('Mengunggah lampiran...');
            let uploadPromises = [];

            for (let i = 0; i < fileInput.files.length; i++) {
                let singleFormData = new FormData();
                singleFormData.append('task_id', <?= $taskId ?>);
                singleFormData.append('csrf_token', '<?= generateCSRFToken() ?>');
                singleFormData.append('attachment', fileInput.files[i]);

                uploadPromises.push(
                    fetch(APP_CONFIG.BASE_URL + '/?page=api&action=upload_attachment', {
                        method: 'POST',
                        body: singleFormData
                    }).then(r => r.json())
                );
            }

            Promise.all(uploadPromises).then(() => {
                PM.closeAlert();
                PM.toast('success', 'Task dan lampiran berhasil diperbarui');
                setTimeout(() => {
                    window.location.href = APP_CONFIG.BASE_URL + '/?page=projects&action=view&id=<?= $task['project_id'] ?>';
                }, 800);
            }).catch(() => {
                PM.closeAlert();
                PM.toast('warning', 'Task diperbarui, namun ada kendala saat upload lampiran');
                setTimeout(() => {
                    window.location.href = APP_CONFIG.BASE_URL + '/?page=projects&action=view&id=<?= $task['project_id'] ?>';
                }, 1200);
            });
        } else {
            PM.closeAlert();
            PM.toast('success', 'Task berhasil diperbarui');
            setTimeout(() => {
                window.location.href = APP_CONFIG.BASE_URL + '/?page=projects&action=view&id=<?= $task['project_id'] ?>';
            }, 800);
        }
    })
    .catch(() => {
        PM.closeAlert();
        Swal.fire('Error', 'Terjadi kesalahan jaringan', 'error');
    });
});
</script>

<style>
.color-picker input:checked + .color-swatch {
    box-shadow: 0 0 0 3px var(--primary) !important;
    transform: scale(1.1);
    transition: all 0.2s ease;
}
.color-picker {
    cursor: pointer;
}
.shadow-xs {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.04) !important;
}
</style>