<?php
/**
 * Riwayat 100 percobaan login terakhir (pengguna lewat token + admin).   Letak: admin/logs.php
 */
require __DIR__ . '/../app/admin_auth.php';
admin_boot();
$admin = admin_require();

$rows = db()->query(
    'SELECT l.attempted_at, l.scope, l.ip, l.success, l.role, l.username, t.label AS token_label
       FROM login_attempts l
       LEFT JOIN access_tokens t ON t.id = l.token_id
      ORDER BY l.id DESC
      LIMIT 100'
)->fetchAll();

admin_header('Riwayat Login', $admin, 'logs');
?>
<h1>Riwayat Login</h1>
<p class="muted">100 percobaan terakhir. Token yang salah tidak disimpan (hanya alamat IP dan waktunya).</p>
<div class="card table-scroll">
<table class="tbl">
    <thead><tr><th>Waktu</th><th>Jenis</th><th>Hasil</th><th>Peran</th><th>Keterangan</th><th>IP</th></tr></thead>
    <tbody>
    <?php if (!$rows): ?>
        <tr><td colspan="6" class="empty">Belum ada catatan.</td></tr>
    <?php endif; ?>
    <?php foreach ($rows as $r): ?>
        <tr>
            <td><?php echo e($r['attempted_at']); ?></td>
            <td><?php echo $r['scope'] === 'admin' ? 'Admin' : 'Token'; ?></td>
            <td><span class="pill <?php echo (int) $r['success'] === 1 ? 'pill-ok' : 'pill-bad'; ?>"><?php echo (int) $r['success'] === 1 ? 'Berhasil' : 'Gagal'; ?></span></td>
            <td><?php echo $r['role'] ? e(strtoupper($r['role'])) : '-'; ?></td>
            <td><?php echo e($r['scope'] === 'admin' ? ($r['username'] ?? '-') : ($r['token_label'] ?? '-')); ?></td>
            <td><?php echo e($r['ip']); ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
<?php admin_footer();