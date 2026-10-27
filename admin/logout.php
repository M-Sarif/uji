<?php
/**
 * Keluar admin (hanya lewat POST + token CSRF).   Letak: admin/logout.php
 */
require __DIR__ . '/../app/admin_auth.php';
admin_boot();

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && csrf_verify()) {
    $_SESSION = [];
    session_regenerate_id(true);
}
admin_redirect('login.php');