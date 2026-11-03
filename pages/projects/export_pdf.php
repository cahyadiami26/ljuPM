<?php
if (!defined('BASE_URL')) {
    require_once __DIR__ . '/../../config/session.php';
    require_once __DIR__ . '/../../includes/functions.php';
    requireLogin();
}

// Matikan display error sementara khusus halaman PDF agar tidak merusak binary stream PDF
ini_set('display_errors', 0);
error_reporting(0);

$currentUser = getCurrentUser();
$projectId = (int)($_GET['id'] ?? 0);

if (!$projectId || !canViewProject($projectId, $currentUser['id'])) {
    setFlash('danger', 'Project tidak ditemukan atau Anda tidak memiliki akses.');
    redirect(BASE_URL . '/?page=projects&action=index');
}

$project = getProjectById($projectId);
$columns = getProjectColumns($projectId);
$members = getProjectMembers($projectId);
$leader = getProjectLeader($projectId);
$creator = getUserById($project['created_by']);

// Path Autoload Dompdf
$dompdfAutoload = __DIR__ . '/../../libs/dompdf/autoload.inc.php';
if (!file_exists($dompdfAutoload)) {
    die("Library Dompdf tidak ditemukan di: libs/dompdf/autoload.inc.php. Silakan unduh dan ekstrak dompdf ke folder libs/dompdf.");
}

require_once $dompdfAutoload;

use Dompdf\Dompdf;
use Dompdf\Options;

// Helper untuk mengubah Gambar/URL/Path lokal menjadi Base64 Data URI (Mencegah Dompdf Crash saat fetch SSL)
function imageToBase64($urlOrPath) {
    if (empty($urlOrPath)) return '';
    
    // Jika path lokal di server
    if (file_exists($urlOrPath)) {
        $ext = pathinfo($urlOrPath, PATHINFO_EXTENSION);
        $data = @file_get_contents($urlOrPath);
        if ($data !== false) {
            return 'data:image/' . $ext . ';base64,' . base64_encode($data);
        }
    }
    
    // Jika via URL HTTPS/HTTP
    try {
        $arrContextOptions = [
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ],
        ];
        $data = @file_get_contents($urlOrPath, false, stream_context_create($arrContextOptions));
        if ($data !== false) {
            $ext = pathinfo(parse_url($urlOrPath, PHP_URL_PATH), PATHINFO_EXTENSION) ?: 'png';
            return 'data:image/' . $ext . ';base64,' . base64_encode($data);
        }
    } catch (Exception $e) {}
    
    return '';
}

// Prepare Logo Base64
$logoBase64 = imageToBase64(LOGO_URL);

// Prepare Signature Base64
$signatureBase64 = '';
if ($leader && !empty($leader['signature'])) {
    // Cek file fisik di server jika ada di direktori DMS
    $localSignPath = $_SERVER['DOCUMENT_ROOT'] . '/assets/uploads/signatures/' . $leader['signature'];
    $remoteSignUrl = DMS_URL . '/assets/uploads/signatures/' . $leader['signature'];
    
    if (file_exists($localSignPath)) {
        $signatureBase64 = imageToBase64($localSignPath);
    } else {
        $signatureBase64 = imageToBase64($remoteSignUrl);
    }
}

// Calculate Task Stats
$tasksByColumn = [];
$totalTasks = 0;
$doneTasks = 0;
foreach ($columns as $col) {
    $tasks = getTasksByColumn($col['id']);
    $tasksByColumn[$col['id']] = ['column' => $col, 'tasks' => $tasks];
    $totalTasks += count($tasks);
    if ($col['is_done_column']) {
        $doneTasks += count($tasks);
    }
}

// Mulai tangkap HTML Template PDF
ob_start();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    @page { margin: 100px 40px 60px 40px; }
    
    body {
        font-family: 'Helvetica', Arial, sans-serif;
        font-size: 11px;
        color: #1a1f2e;
        line-height: 1.6;
    }
    
    /* Header */
    #header {
        position: fixed;
        top: -80px;
        left: 0;
        right: 0;
        height: 70px;
        border-bottom: 2px solid #1e3a5f;
        padding-bottom: 10px;
    }
    
    #header table { width: 100%; border-collapse: collapse; }
    
    .logo-cell { width: 60px; vertical-align: middle; }
    .logo-cell img { width: 55px; height: 55px; object-fit: contain; }
    
    .company-cell {
        vertical-align: middle;
        padding-left: 12px;
        color: #1e3a5f;
    }
    
    .company-name { font-size: 15px; font-weight: bold; letter-spacing: 0.5px; }
    .company-address { font-size: 9px; color: #4b5563; margin-top: 2px; }
    .document-type { font-size: 8px; color: #6b7280; text-transform: uppercase; letter-spacing: 1.5px; margin-top: 4px; }
    
    .doc-info-cell {
        vertical-align: middle;
        text-align: right;
        font-size: 9px;
        color: #6b7280;
    }
    
    /* Footer */
    #footer {
        position: fixed;
        bottom: -40px;
        left: 0;
        right: 0;
        height: 30px;
        border-top: 1px solid #d1d5db;
        padding-top: 8px;
        font-size: 9px;
        color: #6b7280;
    }
    
    #footer table { width: 100%; }
    .footer-right { text-align: right; }
    
    /* Document Title */
    .doc-title {
        text-align: center;
        margin: 20px 0 25px 0;
    }
    
    .doc-title h1 {
        font-size: 18px;
        color: #1e3a5f;
        margin: 0 0 4px 0;
        letter-spacing: 0.5px;
        font-weight: bold;
    }
    
    .doc-title .subtitle {
        font-size: 10px;
        color: #6b7280;
        letter-spacing: 2px;
        text-transform: uppercase;
    }
    
    .doc-title .divider {
        width: 60px;
        height: 3px;
        background: #1e3a5f;
        margin: 8px auto;
    }
    
    /* Section */
    .section {
        margin-bottom: 20px;
    }
    
    .section-title {
        font-size: 11px;
        font-weight: bold;
        color: #1e3a5f;
        border-left: 4px solid #1e3a5f;
        padding: 4px 0 4px 10px;
        margin-bottom: 10px;
        background: #f3f4f6;
        text-transform: uppercase;
        letter-spacing: 0.8px;
    }
    
    /* Info Table */
    .info-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 8px;
    }
    
    .info-table td {
        padding: 6px 8px;
        vertical-align: top;
        border-bottom: 1px solid #f3f4f6;
    }
    
    .info-table .label {
        width: 25%;
        font-weight: bold;
        color: #4b5563;
        font-size: 10px;
    }
    
    .info-table .value {
        color: #1a1f2e;
        font-size: 10px;
    }
    
    /* Description Box */
    .description-box {
        background: #f9fafb;
        border-left: 3px solid #1e3a5f;
        padding: 10px 12px;
        border-radius: 3px;
        font-size: 10px;
        line-height: 1.6;
        color: #374151;
    }
    
    /* Members Table */
    .members-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 6px;
    }
    
    .members-table th {
        background: #1e3a5f;
        color: #fff;
        padding: 8px;
        font-size: 9px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        text-align: left;
    }
    
    .members-table td {
        padding: 7px 8px;
        border-bottom: 1px solid #e5e7eb;
        font-size: 10px;
    }
    
    .members-table tr:nth-child(even) td {
        background: #f9fafb;
    }
    
    .role-badge {
        display: inline-block;
        padding: 2px 8px;
        border-radius: 10px;
        font-size: 8px;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .role-leader { background: #1e3a5f; color: #fff; }
    .role-member { background: #dbeafe; color: #1e40af; }
    
    /* Status Badge */
    .status-badge {
        display: inline-block;
        padding: 3px 10px;
        border-radius: 12px;
        font-size: 9px;
        font-weight: bold;
        text-transform: uppercase;
    }
    
    .status-active { background: #dbeafe; color: #1e40af; }
    .status-completed { background: #d1fae5; color: #065f46; }
    .status-planning { background: #e5e7eb; color: #374151; }
    .status-on_hold { background: #fef3c7; color: #92400e; }
    .status-cancelled { background: #fee2e2; color: #991b1b; }
    
    /* Priority */
    .priority-critical { color: #dc2626; font-weight: bold; }
    .priority-high { color: #ea580c; font-weight: bold; }
    .priority-medium { color: #ca8a04; font-weight: bold; }
    .priority-low { color: #16a34a; font-weight: bold; }
    
    /* Progress Bar */
    .progress-wrapper {
        width: 100%;
        background: #e5e7eb;
        border-radius: 10px;
        overflow: hidden;
        height: 12px;
        margin-top: 4px;
    }
    
    .progress-fill {
        height: 100%;
        background: #1e3a5f;
        text-align: center;
        color: #fff;
        font-size: 8px;
        line-height: 12px;
        font-weight: bold;
    }
    
    /* Tasks Table */
    .tasks-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 8px;
        font-size: 9px;
    }
    
    .tasks-table th {
        background: #1e3a5f;
        color: #fff;
        padding: 7px;
        text-align: left;
        font-size: 9px;
        text-transform: uppercase;
    }
    
    .tasks-table td {
        padding: 6px 7px;
        border-bottom: 1px solid #e5e7eb;
        vertical-align: top;
    }
    
    .tasks-table tr:nth-child(even) td {
        background: #f9fafb;
    }
    
    .column-header {
        background: #f3f4f6;
        color: #1e3a5f;
        font-weight: bold;
        font-size: 10px;
        padding: 6px 10px;
        margin-top: 10px;
        border-left: 3px solid #1e3a5f;
    }
    
    /* Signature */
    .signature-section {
        margin-top: 30px;
        page-break-inside: avoid;
    }
    
    .signature-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .signature-box {
        width: 45%;
        text-align: center;
        padding: 10px;
    }
    
    .signature-title {
        font-size: 10px;
        color: #4b5563;
        margin-bottom: 10px;
    }
    
    .signature-image {
        max-width: 140px;
        max-height: 60px;
        margin: 5px auto;
        display: block;
    }
    
    .signature-name {
        font-weight: bold;
        font-size: 11px;
        color: #1e3a5f;
        border-top: 1px solid #1a1f2e;
        padding-top: 4px;
        margin-top: 10px;
    }
    
    .signature-position {
        font-size: 9px;
        color: #6b7280;
    }
    
    /* Stats */
    .stats-row {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
    }
    
    .stat-cell {
        width: 25%;
        text-align: center;
        padding: 10px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
    }
    
    .stat-value {
        font-size: 16px;
        font-weight: bold;
        color: #1e3a5f;
    }
    
    .stat-label {
        font-size: 8px;
        color: #6b7280;
        text-transform: uppercase;
        margin-top: 2px;
    }
</style>
</head>
<body>

<!-- Header -->
<div id="header">
    <table>
        <tr>
            <td class="logo-cell">
                <?php if ($logoBase64): ?>
                    <img src="<?= $logoBase64 ?>" alt="LJU">
                <?php endif; ?>
            </td>
            <td class="company-cell">
                <div class="company-name"><?= COMPANY_NAME ?></div>
                <div class="company-address">Lampung, Indonesia</div>
                <div class="document-type">PROJECT DOCUMENTATION</div>
            </td>
            <td class="doc-info-cell">
                Document No: PRJ-<?= str_pad($project['id'], 5, '0', STR_PAD_LEFT) ?><br>
                Generated: <?= date('d M Y, H:i') ?><br>
                By: <?= htmlspecialchars($currentUser['name']) ?>
            </td>
        </tr>
    </table>
</div>

<!-- Footer -->
<div id="footer">
    <table>
        <tr>
            <td>© <?= date('Y') ?> <?= COMPANY_NAME ?> - Confidential Document</td>
            <td class="footer-right">Project Management System</td>
        </tr>
    </table>
</div>

<!-- Document Title -->
<div class="doc-title">
    <div class="subtitle">Project Documentation Report</div>
    <div class="divider"></div>
    <h1><?= htmlspecialchars(strtoupper($project['title'])) ?></h1>
</div>

<!-- Project Info -->
<div class="section">
    <div class="section-title">Project Information</div>
    <table class="info-table">
        <tr>
            <td class="label">Project ID</td>
            <td class="value">PRJ-<?= str_pad($project['id'], 5, '0', STR_PAD_LEFT) ?></td>
            <td class="label">Status</td>
            <td class="value">
                <span class="status-badge status-<?= $project['status'] ?>"><?= PROJECT_STATUS[$project['status']]['label'] ?></span>
            </td>
        </tr>
        <tr>
            <td class="label">Priority</td>
            <td class="value priority-<?= $project['priority'] ?>"><?= PRIORITY_OPTIONS[$project['priority']]['label'] ?></td>
            <td class="label">Privacy Level</td>
            <td class="value"><?= PRIVACY_OPTIONS[$project['privacy']]['label'] ?></td>
        </tr>
        <tr>
            <td class="label">Start Date</td>
            <td class="value"><?= formatDate($project['start_date'], 'long') ?></td>
            <td class="label">Target End Date</td>
            <td class="value"><?= formatDate($project['target_end_date'], 'long') ?></td>
        </tr>
        <tr>
            <td class="label">Created By</td>
            <td class="value"><?= htmlspecialchars($creator['name'] ?? 'N/A') ?></td>
            <td class="label">Created At</td>
            <td class="value"><?= formatDate($project['created_at'], 'datetime') ?></td>
        </tr>
        <?php if (!empty($project['actual_end_date'])): ?>
        <tr>
            <td class="label">Actual End Date</td>
            <td class="value" colspan="3"><?= formatDate($project['actual_end_date'], 'long') ?></td>
        </tr>
        <?php endif; ?>
    </table>
</div>

<!-- Progress -->
<div class="section">
    <div class="section-title">Progress Overview</div>
    <table class="stats-row">
        <tr>
            <td class="stat-cell">
                <div class="stat-value"><?= $totalTasks ?></div>
                <div class="stat-label">Total Tasks</div>
            </td>
            <td class="stat-cell">
                <div class="stat-value"><?= $doneTasks ?></div>
                <div class="stat-label">Completed</div>
            </td>
            <td class="stat-cell">
                <div class="stat-value"><?= $totalTasks - $doneTasks ?></div>
                <div class="stat-label">Remaining</div>
            </td>
            <td class="stat-cell">
                <div class="stat-value"><?= number_format($project['progress_percentage'], 0) ?>%</div>
                <div class="stat-label">Progress</div>
            </td>
        </tr>
    </table>
    <div class="progress-wrapper">
        <div class="progress-fill" style="width: <?= max(3, $project['progress_percentage']) ?>%">
            <?= number_format($project['progress_percentage'], 0) ?>%
        </div>
    </div>
</div>

<!-- Description -->
<div class="section">
    <div class="section-title">Project Description</div>
    <div class="description-box">
        <?= nl2br(htmlspecialchars($project['description'] ?: 'No description provided.')) ?>
    </div>
</div>

<!-- Team Members -->
<div class="section">
    <div class="section-title">Team Members (<?= count($members) ?> Personnel)</div>
    <table class="members-table">
        <thead>
            <tr>
                <th style="width:5%;">#</th>
                <th style="width:25%;">Name</th>
                <th style="width:15%;">NIK</th>
                <th style="width:20%;">Position</th>
                <th style="width:20%;">Bidang / Divisi</th>
                <th style="width:15%;">Role</th>
            </tr>
        </thead>
        <tbody>
            <?php $no = 1; foreach ($members as $mem): 
                if (!$mem['user']) continue;
                $u = $mem['user'];
            ?>
            <tr>
                <td><?= $no++ ?></td>
                <td><strong><?= htmlspecialchars($u['name']) ?></strong></td>
                <td><?= htmlspecialchars($u['nik'] ?: '-') ?></td>
                <td><?= htmlspecialchars($u['position'] ?: '-') ?></td>
                <td>
                    <?= htmlspecialchars($u['division'] ?: '-') ?><br>
                    <span style="font-size:8px;color:#6b7280;"><?= htmlspecialchars($u['department'] ?: '-') ?></span>
                </td>
                <td>
                    <span class="role-badge role-<?= $mem['role'] ?>"><?= strtoupper($mem['role']) ?></span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Task Breakdown by Column -->
<div class="section">
    <div class="section-title">Tasks Breakdown</div>
    
    <?php foreach ($tasksByColumn as $colId => $data): 
        $col = $data['column'];
        $tasks = $data['tasks'];
        if (empty($tasks)) continue;
    ?>
    <div class="column-header">
        <?= htmlspecialchars($col['title']) ?> (<?= count($tasks) ?> task<?= count($tasks) > 1 ? 's' : '' ?>)
    </div>
    <table class="tasks-table">
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:28%;">Task Title</th>
                <th style="width:20%;">Assigned To</th>
                <th style="width:10%;">Priority</th>
                <th style="width:12%;">Start Date</th>
                <th style="width:12%;">Target End</th>
                <th style="width:14%;">Actual End</th>
            </tr>
        </thead>
        <tbody>
            <?php $tno = 1; foreach ($tasks as $task): 
                $assignee = $task['assigned_to'] ? getUserById($task['assigned_to']) : null;
            ?>
            <tr>
                <td><?= $tno++ ?></td>
                <td>
                    <strong><?= htmlspecialchars($task['title']) ?></strong>
                    <?php if (!empty($task['description'])): ?>
                    <div style="font-size:8px;color:#6b7280;margin-top:2px;">
                        <?= htmlspecialchars(substr($task['description'], 0, 100)) ?><?= strlen($task['description']) > 100 ? '...' : '' ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($assignee['name'] ?? '-') ?></td>
                <td class="priority-<?= $task['priority'] ?>"><?= PRIORITY_OPTIONS[$task['priority']]['label'] ?></td>
                <td><?= $task['start_date'] ? formatDate($task['start_date'], 'short') : '-' ?></td>
                <td><?= $task['target_end_date'] ? formatDate($task['target_end_date'], 'short') : '-' ?></td>
                <td><?= $task['actual_end_date'] ? formatDate($task['actual_end_date'], 'short') : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <br>
    <?php endforeach; ?>
    
    <?php if ($totalTasks === 0): ?>
        <div style="text-align:center;padding:20px;color:#6b7280;font-style:italic;">
            No tasks created yet.
        </div>
    <?php endif; ?>
</div>

<!-- Signature Section -->
<div class="signature-section">
    <table class="signature-table">
        <tr>
            <td style="width:55%;"></td>
            <td class="signature-box">
                <div class="signature-title">Project Leader</div>
                <?php if ($signatureBase64): ?>
                    <img src="<?= $signatureBase64 ?>" class="signature-image" alt="Signature">
                <?php else: ?>
                    <div style="height:50px;"></div>
                <?php endif; ?>
                <div class="signature-name">
                    <?= htmlspecialchars($leader['name'] ?? 'N/A') ?>
                </div>
                <div class="signature-position">
                    <?= htmlspecialchars($leader['position'] ?? '') ?><br>
                    <?php if (!empty($leader['nik'])): ?>NIK: <?= htmlspecialchars($leader['nik']) ?><?php endif; ?>
                </div>
            </td>
        </tr>
    </table>
    
    <div style="margin-top:20px;text-align:center;font-size:8px;color:#6b7280;font-style:italic;">
        Dokumen ini dihasilkan secara otomatis oleh sistem <?= APP_NAME ?> pada <?= date('d F Y, H:i:s') ?> WIB
    </div>
</div>

</body>
</html>
<?php
$html = ob_get_clean();

// BERSIHKAN SEMUA OUTPUT BUFFER TERTINGGAL (Termasuk dari index.php)
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Konfigurasi Dompdf
$options = new Options();
$options->set('isHtml5ParserEnabled', true);
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'Helvetica');

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Nama file download
$filename = 'Project_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $project['title']) . '_' . date('YmdHis') . '.pdf';

// Stream PDF langsung ke browser tanpa karakter sampah
header("Content-Type: application/pdf");
header("Content-Disposition: inline; filename=\"" . $filename . "\"");
header("Cache-Control: private, max-age=0, must-revalidate");
header("Pragma: public");

echo $dompdf->output();
exit;