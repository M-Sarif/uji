<?php
/* Layar Check-In AMT (User Guide bagian B).
 * Dua tab:
 *   - Verifikasi        : form (lokasi, aktivitas, foto selfie, Kirim) -> _work_form.php
 *   - Riwayat Check-In  : daftar Check-In yang sudah dilakukan (per cycle/ritase)
 * Hanya bisa dibuka saat timer Waktu Kerja berjalan (dijaga amt_guard_work()). */
work_styles();
?>
<link rel="stylesheet" href="<?= AMT_URL ?>/css/checkin.css?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/css/checkin.css') ?>">
<?php

$tab      = (($_GET['tab'] ?? '') === 'riwayat') ? 'riwayat' : 'verifikasi';
$checkins = work_checkins();
?>
<div class="ci-page">
<div class="ci-tabs" role="tablist">
  <button type="button" role="tab" id="ci-tab-verif" class="ci-tab<?= $tab === 'verifikasi' ? ' is-active' : '' ?>" data-ci-tab="verifikasi">Verifikasi</button>
  <button type="button" role="tab" id="ci-tab-riwayat" class="ci-tab<?= $tab === 'riwayat' ? ' is-active' : '' ?>" data-ci-tab="riwayat">Riwayat Check-In</button>
</div>

<!-- TAB: Verifikasi -->
<div class="ci-pane" id="ci-pane-verifikasi"<?= $tab === 'verifikasi' ? '' : ' hidden' ?>>
  <?php $mode = 'checkin'; require __DIR__ . '/_work_form.php'; ?>
</div>

<!-- TAB: Riwayat Check-In -->
<div class="ci-pane" id="ci-pane-riwayat"<?= $tab === 'riwayat' ? '' : ' hidden' ?>>
  <div class="ci-info">
    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/></svg>
    <span>Riwayat Check-In akan ter-reset setelah AMT melakukan Check-Out.</span>
  </div>

  <p class="ci-heading">Riwayat Check-In</p>

  <?php if (!$checkins): ?>
    <div class="ci-empty">Belum ada Check-In.</div>
  <?php else: ?>
    <?php foreach ($checkins as $i => $c): ?>
      <div class="ci-card">
        <div class="ci-col">
          <small>Check-In ke</small>
          <strong><?= $i + 1 ?></strong>
        </div>
        <div class="ci-col">
          <small>Waktu Check-In</small>
          <strong><?= h(date('d/m/y H:i', (int) $c['at'])) ?></strong>
        </div>
        <div class="ci-col">
          <small>Aktivitas</small>
          <strong><?= h(strtoupper((string) $c['activity'])) ?></strong>
        </div>
        <div class="ci-col">
          <small>Foto Verifikasi</small>
          <?php if (!empty($c['photo'])): ?>
            <button type="button" class="ci-photo-btn" data-ci-photo="<?= $i ?>">
              <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><path d="M21 15l-5-5L5 21"/></svg>
              Lihat Foto
            </button>
            <img class="ci-photo-src" id="ci-photo-<?= $i ?>" src="<?= h((string) $c['photo']) ?>" alt="" hidden>
          <?php else: ?>
            <strong>-</strong>
          <?php endif; ?>
        </div>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>

</div><!-- /.ci-page -->

<!-- Pop up Lihat Foto -->
<div class="ci-modal" id="ci-modal" hidden>
  <div class="ci-modal-box">
    <img id="ci-modal-img" alt="Foto Verifikasi Check-In">
    <button type="button" class="ci-modal-close" id="ci-modal-close">Tutup</button>
  </div>
</div>

<script src="<?= AMT_URL ?>/js/checkin.js?v=<?= (int) @filemtime(AMT_ASSET_DIR . '/js/checkin.js') ?>"></script>