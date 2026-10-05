<?php
/**
 * Layar: ?screen=amt_surat_jalan&id=...  (role AMT)  -- "Surat Jalan"
 * Letak: roles/amt/views/surat_jalan.php
 *
 * Satu kartu per Nomor LO: "Ambil Foto" -> kamera BELAKANG layar penuh -> Foto -> (Ambil Ulang | Simpan Foto)
 * -> kartu menampilkan hasil foto + "Lihat Foto". Tombol "Simpan Foto" di bawah aktif bila SEMUA LO sudah
 * punya foto, lalu mengirim form (POST submit_sj) ke amt_sj_handle_post() di includes/amt_sj.php.
 */
$s = amt_ship_current();
if ($s === null) {
    echo '<section class="sj"><p class="sj-empty">Shipment tidak ditemukan.</p></section>';
    return;
}
$v = function (string $f): int { return (int) @filemtime(AMT_ASSET_DIR . '/' . $f); };
$illustration = AMT_URL . '/img/surat-jalan/surat-jalan-illustration.png';
?>
<link rel="stylesheet" href="<?= AMT_URL ?>/css/camera.css?v=<?= $v('css/camera.css') ?>">

<form class="sj" id="sjForm" method="post" action="<?= amt_e(amt_sj_url($s)) ?>" autocomplete="off">
    <input type="hidden" name="action" value="submit_sj">
    <input type="hidden" name="id" value="<?= amt_e($s['id']) ?>">

    <div class="sj-list">
        <?php foreach ($s['products'] as $p): $lo = (string) $p['lo']; ?>
            <section class="sj-item" data-lo="<?= amt_e($lo) ?>">
                <div class="sj-label">
                    <span>Nomor LO : <?= amt_e($lo) ?><b class="sj-req">*</b></span>
                    <button type="button" class="sj-info" aria-label="Info order LO <?= amt_e($lo) ?>" aria-expanded="false">
                        <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><circle cx="12" cy="12" r="9.5"/><path d="M12 11v5.5M12 7.6v.01"/></svg>
                    </button>
                </div>
                <p class="sj-tip" hidden>Order: <?= amt_e($p['name']) ?></p>

                <div class="sj-card">
                    <!-- Belum ada foto -->
                    <div class="sj-empty-state">
                        <img class="sj-illus" src="<?= amt_e($illustration) ?>" alt="">
                        <h3 class="sj-card-title">Foto Surat Jalan</h3>
                        <p class="sj-card-sub">Pastikan hasil foto surat jalan jelas dan tidak blur</p>
                        <button type="button" class="sj-link" data-act="take">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                            Ambil Foto
                        </button>
                    </div>
                    <!-- Sudah ada foto -->
                    <div class="sj-photo-state" hidden>
                        <img class="sj-thumb" alt="Foto surat jalan LO <?= amt_e($lo) ?>">
                        <button type="button" class="sj-link" data-act="view">
                            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
                            Lihat Foto
                        </button>
                    </div>
                </div>
                <input type="hidden" name="photo[<?= amt_e($lo) ?>]" value="">
            </section>
        <?php endforeach; ?>
    </div>

    <div class="sj-footer">
        <button type="submit" class="sj-save" id="sjSave" disabled>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
            <span>Simpan Foto</span>
        </button>
    </div>
</form>

<!-- Pop up "Lihat Foto" -->
<div class="sj-modal" id="sjModal" hidden>
    <div class="sj-modal-box" role="dialog" aria-modal="true" aria-label="Foto Surat Jalan">
        <img id="sjModalImg" alt="Foto Surat Jalan">
        <div class="sj-modal-actions">
            <button type="button" class="sj-modal-btn" id="sjModalRetake">Ambil Ulang</button>
            <button type="button" class="sj-modal-btn is-primary" id="sjModalClose">Tutup</button>
        </div>
    </div>
</div>

<script src="<?= AMT_URL ?>/js/camera.js?v=<?= $v('js/camera.js') ?>"></script>
<script src="<?= AMT_URL ?>/js/surat-jalan.js?v=<?= $v('js/surat-jalan.js') ?>"></script>