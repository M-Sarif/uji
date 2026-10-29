<?php
require __DIR__ . '/../app/admin_auth.php';
admin_boot();
$admin = admin_require();
$pdo   = db();

const PER_PAGE = 20;

function token_filters(array $src): array
{
    $role   = isset($src['role']) && is_string($src['role']) && in_array($src['role'], AUTH_ROLES, true) ? $src['role'] : '';
    $status = isset($src['status']) && is_string($src['status']) && in_array($src['status'], ['aktif', 'nonaktif', 'kadaluarsa', 'habis'], true) ? $src['status'] : '';
    $q      = isset($src['q']) && is_string($src['q']) ? trim(str_cut($src['q'], 60)) : '';
    $page   = isset($src['page']) && is_string($src['page']) && ctype_digit($src['page']) ? max(1, min(100000, (int) $src['page'])) : 1;
    return ['role' => $role, 'status' => $status, 'q' => $q, 'page' => $page];
}

function token_status(array $t, string $now): array
{
    if ((int) $t['is_active'] !== 1) {
        return ['nonaktif', 'Nonaktif', 'pill-off'];
    }
    if ($t['expires_at'] !== null && $t['expires_at'] <= $now) {
        return ['kadaluarsa', 'Kedaluwarsa', 'pill-bad'];
    }
    if ((int) $t['max_uses'] > 0 && (int) $t['used_count'] >= (int) $t['max_uses']) {
        return ['habis', 'Kuota habis', 'pill-warn'];
    }
    return ['aktif', 'Aktif', 'pill-ok'];
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $back = token_filters($_POST);
    $back = array_filter($back, static fn($v) => $v !== '' && $v !== 1);

    if (!csrf_verify()) {
        admin_flash_set('err', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
        admin_redirect('index.php', $back);
    }
    $action = post_str('action', 20);

    if ($action === 'create') {
        $role  = post_str('role', 10);
        $label = post_str('label', 100);
        $count = ctype_digit(post_str('count', 3)) ? (int) post_str('count', 3) : 0;
        $maxU  = post_str('max_uses', 7);
        $expIn = post_str('expires_at', 16);

        $errors = [];
        if (!in_array($role, AUTH_ROLES, true)) {
            $errors[] = 'Peran harus AMT atau SPBU.';
        }
        if ($label === '') {
            $errors[] = 'Nama / keterangan pemilik token wajib diisi.';
        }
        if ($count < 1 || $count > 50) {
            $errors[] = 'Jumlah token harus 1-50.';
        }
        if ($maxU !== '' && !ctype_digit($maxU)) {
            $errors[] = 'Batas pemakaian harus berupa angka (kosong / 0 = tanpa batas).';
        }
        $maxUses = $maxU === '' ? 0 : min((int) $maxU, 1000000);

        $expiresAt = null;
        if ($expIn !== '') {
            $dt = DateTime::createFromFormat('!Y-m-d\TH:i', $expIn);
            $ok = $dt instanceof DateTime && $dt->format('Y-m-d\TH:i') === $expIn;
            if (!$ok) {
                $errors[] = 'Format tanggal kedaluwarsa tidak valid.';
            } elseif ($dt->getTimestamp() <= time()) {
                $errors[] = 'Tanggal kedaluwarsa harus di masa depan.';
            } else {
                $expiresAt = $dt->format('Y-m-d H:i:s');
            }
        }

        if ($errors) {
            admin_flash_set('err', implode(' ', $errors));
            admin_redirect('index.php', $back);
        }

        $ins = $pdo->prepare(
            'INSERT INTO access_tokens (token_hash, token_hint, role, label, is_active, max_uses, used_count, expires_at, created_by, created_at)
             VALUES (:hash, :hint, :role, :label, 1, :max, 0, :exp, :by, :at)'
        );
        $made = [];
        $pdo->beginTransaction();
        try {
            for ($i = 1; $i <= $count; $i++) {
                $plain = token_generate();
                $ins->execute([
                    ':hash'  => token_hash($plain),
                    ':hint'  => substr($plain, -4),
                    ':role'  => $role,
                    ':label' => $count > 1 ? str_cut($label, 94) . ' #' . $i : $label,
                    ':max'   => $maxUses,
                    ':exp'   => $expiresAt,
                    ':by'    => $admin['id'],
                    ':at'    => date('Y-m-d H:i:s'),
                ]);
                $made[] = ['token' => token_format($plain), 'label' => $count > 1 ? str_cut($label, 94) . ' #' . $i : $label, 'role' => $role];
            }
            $pdo->commit();
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
        $_SESSION['new_tokens'] = $made;
        admin_flash_set('ok', count($made) . ' token ' . strtoupper($role) . ' berhasil dibuat. Salin sekarang - token tidak akan ditampilkan lagi.');
        admin_redirect('index.php', $back);
    }

    $id = ctype_digit(post_str('id', 10)) ? (int) post_str('id', 10) : 0;
    if ($id > 0 && $action === 'toggle') {
        $to = post_str('to', 1) === '1' ? 1 : 0;
        $pdo->prepare('UPDATE access_tokens SET is_active = :a WHERE id = :id')->execute([':a' => $to, ':id' => $id]);
        admin_flash_set('ok', $to === 1 ? 'Token diaktifkan.' : 'Token dinonaktifkan. Pengguna yang sedang memakainya akan keluar otomatis.');
    } elseif ($id > 0 && $action === 'delete') {
        $pdo->prepare('DELETE FROM access_tokens WHERE id = :id')->execute([':id' => $id]);
        admin_flash_set('ok', 'Token dihapus.');
    }
    admin_redirect('index.php', $back);
}

$f   = token_filters($_GET);
$now = date('Y-m-d H:i:s');

$where  = [];
$params = [];
if ($f['role'] !== '') {
    $where[] = 'role = :role';
    $params[':role'] = $f['role'];
}
if ($f['q'] !== '') {
    $where[] = 'label LIKE :q';
    $params[':q'] = '%' . addcslashes($f['q'], '%_\\') . '%';
}
switch ($f['status']) {
    case 'aktif':
        $where[] = 'is_active = 1 AND (expires_at IS NULL OR expires_at > :now) AND (max_uses = 0 OR used_count < max_uses)';
        $params[':now'] = $now;
        break;
    case 'nonaktif':
        $where[] = 'is_active = 0';
        break;
    case 'kadaluarsa':
        $where[] = 'expires_at IS NOT NULL AND expires_at <= :now';
        $params[':now'] = $now;
        break;
    case 'habis':
        $where[] = 'max_uses > 0 AND used_count >= max_uses';
        break;
}
$whereSql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

$cnt = $pdo->prepare('SELECT COUNT(*) FROM access_tokens' . $whereSql);
$cnt->execute($params);
$total  = (int) $cnt->fetchColumn();
$pages  = max(1, (int) ceil($total / PER_PAGE));
$page   = min($f['page'], $pages);
$offset = ($page - 1) * PER_PAGE;

$st = $pdo->prepare(
    'SELECT id, token_hint, role, label, is_active, max_uses, used_count, expires_at, last_used_at, created_at
       FROM access_tokens' . $whereSql . ' ORDER BY id DESC LIMIT :lim OFFSET :off'
);
foreach ($params as $k => $v) {
    $st->bindValue($k, $v);
}
$st->bindValue(':lim', PER_PAGE, PDO::PARAM_INT);
$st->bindValue(':off', $offset, PDO::PARAM_INT);
$st->execute();
$tokens = $st->fetchAll();

$stat = $pdo->prepare(
    "SELECT COUNT(*) AS total,
            COALESCE(SUM(role = 'amt'), 0) AS amt,
            COALESCE(SUM(role = 'spbu'), 0) AS spbu,
            COALESCE(SUM(is_active = 1 AND (expires_at IS NULL OR expires_at > :now) AND (max_uses = 0 OR used_count < max_uses)), 0) AS aktif
       FROM access_tokens"
);
$stat->execute([':now' => $now]);
$stats = $stat->fetch();

$fail = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE success = 0 AND attempted_at >= :since");
$fail->execute([':since' => date('Y-m-d H:i:s', time() - 86400)]);
$failed24 = (int) $fail->fetchColumn();

$newTokens = $_SESSION['new_tokens'] ?? [];
unset($_SESSION['new_tokens']);

function list_url(array $f, int $page): string
{
    $q = array_filter(array_merge($f, ['page' => $page]), static fn($v) => $v !== '' && $v !== 1);
    return admin_url() . ($q ? '?' . http_build_query($q) : '');
}

admin_header('Kelola Token', $admin, 'tokens');
?>
<h1>Kelola Token</h1>

<section class="stats">
    <div class="stat"><b><?php echo (int) $stats['total']; ?></b><span>Total token</span></div>
    <div class="stat"><b><?php echo (int) $stats['aktif']; ?></b><span>Aktif sekarang</span></div>
    <div class="stat"><b><?php echo (int) $stats['amt']; ?> / <?php echo (int) $stats['spbu']; ?></b><span>AMT / SPBU</span></div>
    <div class="stat"><b><?php echo $failed24; ?></b><span>Login gagal 24 jam</span></div>
</section>

<?php if ($newTokens): ?>
<section class="card card-new">
    <h2>Token baru (tampil sekali)</h2>
    <p class="muted">Berikan ke pemilik yang sesuai. Setelah halaman ini ditutup, token tidak bisa dilihat lagi - hanya bisa dibuat ulang.</p>
    <ul class="newlist">
    <?php foreach ($newTokens as $n): ?>
        <li>
            <span class="pill pill-role"><?php echo e(strtoupper($n['role'])); ?></span>
            <span class="nl-label"><?php echo e($n['label']); ?></span>
            <code class="nl-token"><?php echo e($n['token']); ?></code>
            <button type="button" class="btn btn-small" data-copy="<?php echo e($n['token']); ?>">Salin</button>
        </li>
    <?php endforeach; ?>
    </ul>
    <?php if (count($newTokens) > 1): ?>
    <button type="button" class="btn btn-small" data-copy-all>Salin semua</button>
    <?php endif; ?>
</section>
<?php endif; ?>

<section class="card">
    <h2>Buat token baru</h2>
    <form method="post" action="<?php echo e(admin_url()); ?>" class="grid-form" autocomplete="off">
        <?php echo csrf_field(); ?>
        <input type="hidden" name="action" value="create">
        <fieldset class="field field-role">
            <legend>Peran</legend>
            <label class="radio"><input type="radio" name="role" value="amt" required> AMT</label>
            <label class="radio"><input type="radio" name="role" value="spbu" required> SPBU</label>
        </fieldset>
        <label class="field"><span>Nama / keterangan pemilik</span>
            <input type="text" name="label" maxlength="100" required placeholder="mis. AMT Budi - MT KT 1234 AB">
        </label>
        <label class="field"><span>Jumlah token</span>
            <input type="number" name="count" value="1" min="1" max="50" required>
        </label>
        <label class="field"><span>Batas pemakaian <small>(kosong / 0 = tanpa batas)</small></span>
            <input type="number" name="max_uses" min="0" max="1000000" placeholder="tanpa batas">
        </label>
        <label class="field"><span>Kedaluwarsa <small>(opsional)</small></span>
            <input type="datetime-local" name="expires_at">
        </label>
        <div class="field field-submit"><button type="submit" class="btn btn-primary">Buat Token</button></div>
    </form>
    <p class="muted small">Satu "pemakaian" = satu kali berhasil masuk. Sesi yang sudah masuk tetap berjalan sampai token dinonaktifkan, dihapus, atau kedaluwarsa.</p>
</section>

<section class="card">
    <h2>Daftar token</h2>
    <form method="get" action="<?php echo e(admin_url()); ?>" class="filters">
        <select name="role" aria-label="Peran">
            <option value="">Semua peran</option>
            <option value="amt"<?php echo $f['role'] === 'amt' ? ' selected' : ''; ?>>AMT</option>
            <option value="spbu"<?php echo $f['role'] === 'spbu' ? ' selected' : ''; ?>>SPBU</option>
        </select>
        <select name="status" aria-label="Status">
            <option value="">Semua status</option>
            <?php foreach (['aktif' => 'Aktif', 'nonaktif' => 'Nonaktif', 'kadaluarsa' => 'Kedaluwarsa', 'habis' => 'Kuota habis'] as $k => $v): ?>
                <option value="<?php echo e($k); ?>"<?php echo $f['status'] === $k ? ' selected' : ''; ?>><?php echo e($v); ?></option>
            <?php endforeach; ?>
        </select>
        <input type="search" name="q" value="<?php echo e($f['q']); ?>" maxlength="60" placeholder="Cari nama / keterangan">
        <button type="submit" class="btn">Terapkan</button>
        <?php if ($f['role'] !== '' || $f['status'] !== '' || $f['q'] !== ''): ?><a class="btn btn-ghost" href="<?php echo e(admin_url()); ?>">Reset</a><?php endif; ?>
    </form>

    <div class="table-scroll">
    <table class="tbl">
        <thead><tr><th>#</th><th>Peran</th><th>Pemilik</th><th>Token</th><th>Status</th><th>Dipakai</th><th>Kedaluwarsa</th><th>Terakhir dipakai</th><th>Aksi</th></tr></thead>
        <tbody>
        <?php if (!$tokens): ?>
            <tr><td colspan="9" class="empty">Belum ada token.</td></tr>
        <?php endif; ?>
        <?php foreach ($tokens as $t): [$sCode, $sText, $sClass] = token_status($t, $now); ?>
            <tr>
                <td><?php echo (int) $t['id']; ?></td>
                <td><span class="pill pill-role"><?php echo e(strtoupper($t['role'])); ?></span></td>
                <td><?php echo e($t['label']); ?></td>
                <td><code>••••-••••-••••-<?php echo e($t['token_hint']); ?></code></td>
                <td><span class="pill <?php echo e($sClass); ?>"><?php echo e($sText); ?></span></td>
                <td><?php echo (int) $t['used_count']; ?> / <?php echo (int) $t['max_uses'] === 0 ? '&infin;' : (int) $t['max_uses']; ?></td>
                <td><?php echo $t['expires_at'] !== null ? e($t['expires_at']) : '-'; ?></td>
                <td><?php echo $t['last_used_at'] !== null ? e($t['last_used_at']) : '-'; ?></td>
                <td class="actions">
                    <form method="post" action="<?php echo e(admin_url()); ?>">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                        <input type="hidden" name="to" value="<?php echo (int) $t['is_active'] === 1 ? '0' : '1'; ?>">
                        <?php foreach (['role', 'status', 'q'] as $k): if ($f[$k] !== ''): ?><input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($f[$k]); ?>"><?php endif; endforeach; ?>
                        <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
                        <button type="submit" class="btn btn-small"><?php echo (int) $t['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan'; ?></button>
                    </form>
                    <form method="post" action="<?php echo e(admin_url()); ?>" data-confirm="Hapus token ini secara permanen? Pengguna yang memakainya akan keluar.">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?php echo (int) $t['id']; ?>">
                        <?php foreach (['role', 'status', 'q'] as $k): if ($f[$k] !== ''): ?><input type="hidden" name="<?php echo e($k); ?>" value="<?php echo e($f[$k]); ?>"><?php endif; endforeach; ?>
                        <input type="hidden" name="page" value="<?php echo (int) $page; ?>">
                        <button type="submit" class="btn btn-small btn-danger">Hapus</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    </div>

    <?php if ($pages > 1): ?>
    <nav class="pager" aria-label="Halaman">
        <?php if ($page > 1): ?><a class="btn btn-small" href="<?php echo e(list_url($f, $page - 1)); ?>">&laquo; Sebelumnya</a><?php endif; ?>
        <span class="muted">Halaman <?php echo $page; ?> dari <?php echo $pages; ?> (<?php echo $total; ?> token)</span>
        <?php if ($page < $pages): ?><a class="btn btn-small" href="<?php echo e(list_url($f, $page + 1)); ?>">Berikutnya &raquo;</a><?php endif; ?>
    </nav>
    <?php endif; ?>
</section>
<?php admin_footer();