<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../config/session.php';
    require_once __DIR__ . '/../config/app.php';
    require_once __DIR__ . '/../includes/functions.php';
    requireLogin();
}

$pageTitle = '404 - Halaman Tidak Ditemukan';
?>

<div class="d-flex align-items-center justify-content-center" style="min-height: calc(100vh - 140px);">
    <div class="text-center" style="max-width: 560px;">
        <div class="mb-4">
            <div style="
                width: 120px;
                height: 120px;
                border-radius: 28px;
                background: var(--primary-soft);
                display: inline-flex;
                align-items: center;
                justify-content: center;
                color: var(--primary);
                font-size: 54px;
                box-shadow: var(--shadow-md);
            ">
                <i class="fas fa-compass"></i>
            </div>
        </div>

        <div class="display-3 fw-bold text-primary mb-2">404</div>
        <h3 class="fw-bold mb-2">Halaman Tidak Ditemukan</h3>
        <p class="text-muted mb-4">
            Halaman yang Anda cari mungkin telah dipindahkan, dihapus, atau alamat URL yang dimasukkan tidak sesuai.
        </p>

        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <a href="<?= BASE_URL ?>/?page=dashboard" class="btn btn-primary">
                <i class="fas fa-home"></i> Kembali ke Dashboard
            </a>
            <a href="<?= BASE_URL ?>/?page=projects&action=index" class="btn btn-outline-primary">
                <i class="fas fa-folder-open"></i> Lihat Projects
            </a>
        </div>

        <div class="mt-5">
            <div class="info-card">
                <div class="info-card-body text-start">
                    <div class="d-flex align-items-start gap-3">
                        <div class="bg-warning-soft text-warning rounded p-2">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div>
                            <div class="fw-semibold mb-1">Tips</div>
                            <div class="small text-muted">
                                Gunakan menu di sisi kiri untuk navigasi utama, atau gunakan fitur pencarian pada bagian atas halaman.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="small text-muted mt-4">
            <?= APP_NAME ?> • <?= COMPANY_NAME ?>
        </div>
    </div>
</div>