<?php
/**
 * Session Configuration
 * Shared session dengan aplikasi lain di domain .lju.co.id
 */

// Set cookie params untuk shared session
session_name('LJU_APPS');
session_set_cookie_params([
    'lifetime' => 86400,
    'path' => '/',
    'domain' => '.lju.co.id',
    'secure' => (
    (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
    ($_SERVER['SERVER_PORT'] ?? 0) == 443 || 
    ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
),
    'httponly' => true,
    'samesite' => 'Lax'
]);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Mapping variabel session dari aplikasi lain
 * Menyesuaikan dengan session yang sudah ada
 */
function getCurrentUser() {
    return [
        'id'         => $_SESSION['user_id'] ?? '',
        'username'   => $_SESSION['username'] ?? '',
        'name'       => $_SESSION['user_name'] ?? '',
        'role'       => $_SESSION['role'] ?? '',
        'email'      => $_SESSION['user_email'] ?? '',
        'department' => $_SESSION['department'] ?? '',   // = Divisi dalam aplikasi
        'division'   => $_SESSION['division'] ?? '',     // = Bidang dalam aplikasi
        'position'   => $_SESSION['position'] ?? '',      // = Jabatan dalam aplikasi
        'avatar'     => $_SESSION['avatar'] ?? ''
    ];
}

/**
 * Cek apakah user sudah login
 */
function isLoggedIn() {
    return !empty($_SESSION['user_id']);
}
$redirect_after_login = trim($_GET['redirect'] ?? '');
/**
 * Redirect ke halaman login jika belum login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        // Dapatkan URL saat ini secara absolut
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? "https://" : "http://";
        $current_uri = $_SERVER['REQUEST_URI'] ?? '/';
        $full_current_url = $protocol . $_SERVER['HTTP_HOST'] . $current_uri;

        // Pastikan URL target system dikirim dengan benar
        $target_system = 'https://pm.lju.co.id/';
        $login_url = 'https://lju.co.id/dashboard/?target_system=' . urlencode($target_system) . '&redirect_after_login=' . urlencode($full_current_url);

        header('Location: ' . $login_url);
        exit;
    }
}

/**
 * Cek apakah user adalah admin
 */
function isAdmin() {
    $user = getCurrentUser();
    return $user['role'] === 'admin';
}

/**
 * Cek apakah user adalah Direksi
 */
function isDireksi() {
    $user = getCurrentUser();
    // department dalam database = divisi dalam aplikasi
    return $user['department'] === 'Direksi';
}

/**
 * Cek apakah user adalah Kepala Bidang
 */
function isKepalaBidang() {
    $user = getCurrentUser();
    return $user['position'] === 'Kepala Bidang';
}

/**
 * Cek apakah user adalah Kepala Divisi
 */
function isKepalaDivisi() {
    $user = getCurrentUser();
    return $user['position'] === 'Kepala Divisi';
}

/**
 * Cek apakah user adalah Manager
 */
function isManager() {
    $user = getCurrentUser();
    return $user['position'] === 'Manager';
}

/**
 * Cek apakah user punya akses managerial
 */
function hasManagerialAccess() {
    $user = getCurrentUser();
    $managerialPositions = ['Direktur Utama', 'Direktur Operasional', 'Kepala Bidang', 'Kepala Divisi', 'Manager'];
    return in_array($user['position'], $managerialPositions);
}