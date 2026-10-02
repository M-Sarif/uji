<?php
/**
 * Layar: ?screen=amt_checklist_lo&id=...  (role AMT)  -- "Checklist Pra-Pembongkaran" (Daftar LO)
 *
 * Alur:
 *   Isi Checklist (amt_spbu) -> Daftar LO -> centang LO -> "Mulai Checklist" -> wizard 14 langkah (amt_checklist)
 *   Wizard selesai -> kembali ke sini, LO yang dikerjakan berstatus "Draft"
 *   "Kirim" -> pop up "Kirim Checklist" -> "Ya, Kirim" -> LO "Sudah Diisi"
 *   Semua LO terkirim -> kembali ke Aktifitas di SPBU.
 *
 * Tombol:
 *   Mulai Checklist : aktif bila ada LO yang dicentang
 *   Kirim           : aktif bila ada LO dicentang yang berstatus "Draft"
 * Logika tampilan (Pilih Semua, aktif/nonaktif tombol, pop up) ada di assets/amt/js/amt-checklist.js.
 * Penyimpanan & validasi di server: includes/amt/amt_pbk.php (aksi submit_pbk, phase=start|send).
 */
$s = amt_ship_current();
if ($s === null || !amt_pbk_ship_ready($s)) {
    echo '<section class="pbk"><p class="pbk-empty">Checklist tidak tersedia.</p></section>';
    return;
}

$id  = $s['id'];
$sel = array_flip(amt_pbk_selected($s));
$v   = function (string $file): int {
    return (int) @filemtime(AMT_ASSET_DIR . '/' . $file);
};

// Siapkan baris LO: nomor, nama order, status, dicentang?
$rows = [];
foreach ($s['products'] as $p) {
    $lo     = (string) $p['lo'];
    $status = amt_pbk_lo_status($id, $lo);
    $rows[] = [
        'lo'      => $lo,
        'order'   => (string) $p['name'],
        'status'  => $status,
        'checked' => isset($sel[$lo]),
        'label'   => ['belum' => 'Belum Diisi', 'draft' => 'Draft', 'done' => 'Sudah Diisi'][$status],
    ];
}

$selectable = array_filter($rows, function ($r) { return $r['status'] !== 'done'; });
$nChecked   = count(array_filter($selectable, function ($r) { return $r['checked']; }));
$allChecked = $selectable && $nChecked === count($selectable);
$anyDraft   = (bool) array_filter($selectable, function ($r) { return $r['checked'] && $r['status'] === 'draft'; });

$check = '<svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="3.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>';
?>
<link rel="stylesheet" href="<?= AMT_URL ?>/css/amt-checklist.css?v=<?= $v('css/amt-checklist.css') ?>">

<form method="post" action="<?= amt_e(amt_pbk_lo_url($s)) ?>" class="pbk pbl" id="pblForm" novalidate>
    <input type="hidden" name="action" value="submit_pbk">
    <input type="hidden" name="id" value="<?= amt_e($id) ?>">

    <div class="pbl-scroll">
        <h2 class="pbl-h">Daftar LO</h2>
        <p class="pbl-sub">Pilih LO yang akan dilakukan Checklist Pra-Pembongkaran.</p>

        <div data-tour="pbl-list">
            <label class="pbl-all" for="pblAll">
                <input type="checkbox" id="pblAll" class="pbl-input" <?= $allChecked ? 'checked' : '' ?> <?= $selectable ? '' : 'disabled' ?>>
                <span class="pbl-box" aria-hidden="true"><?= $check ?></span>
                <span class="pbl-all__t">Pilih Semua</span>
            </label>

            <?php foreach ($rows as $r): $isDone = $r['status'] === 'done'; ?>
                <label class="pbl-card<?= $r['checked'] ? ' is-selected' : '' ?><?= $isDone ? ' is-done' : '' ?>"
                       data-pbl-card data-status="<?= amt_e($r['status']) ?>">
                    <input type="checkbox" class="pbl-input" name="lo[]" value="<?= amt_e($r['lo']) ?>"
                           <?= $r['checked'] ? 'checked' : '' ?> <?= $isDone ? 'disabled' : '' ?>>
                    <span class="pbl-box" aria-hidden="true"><?= $check ?></span>
                    <span class="pbl-detail">
                        <span class="pbl-row"><span class="pbl-k">Nomor LO</span><span class="pbl-c">:</span><span class="pbl-v pbl-v--b"><?= amt_e($r['lo']) ?></span></span>
                        <span class="pbl-row"><span class="pbl-k">Order</span><span class="pbl-c">:</span><span class="pbl-v"><span class="pbl-order"><?= amt_e($r['order']) ?></span></span></span>
                        <span class="pbl-row"><span class="pbl-k">Status Checklist</span><span class="pbl-c">:</span><span class="pbl-v pbl-st pbl-st--<?= amt_e($r['status']) ?>"><?= amt_e($r['label']) ?></span></span>
                    </span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="pbk-dock">
        <div class="pbk-nav">
            <button type="submit" name="phase" value="start" id="pblStart" class="pbl-btn pbl-btn--solid"
                    <?= $nChecked > 0 ? '' : 'disabled' ?> data-tour="pbl-start">Mulai Checklist</button>
            <button type="button" id="pblSend" class="pbl-btn pbl-btn--ghost"
                    <?= $anyDraft ? '' : 'disabled' ?> aria-haspopup="dialog" data-tour="pbl-send">Kirim</button>
        </div>
    </div>
</form>

<!-- Pop up konfirmasi sebelum checklist dikirim (dipindahkan ke <body> oleh amt-checklist.js) -->
<div class="pbk-modal" id="pblModal" hidden>
    <div class="pbk-modal__card pbl-modal__card" role="dialog" aria-modal="true" aria-labelledby="pblModalTitle">
        <h2 class="pbl-modal__title" id="pblModalTitle">Kirim Checklist</h2>
        <p class="pbl-modal__text">Pastikan data yang diisi sudah benar. Apakah Anda yakin ingin mengirim checklist ini?</p>
        <p class="pbl-modal__note">Note : Data yang tersimpan tidak dapat diubah kembali setelah dikirim.</p>
        <button type="submit" form="pblForm" name="phase" value="send" class="pbl-btn pbl-btn--solid" id="pblYes">Ya, Kirim</button>
        <button type="button" class="pbl-btn pbl-btn--ghost" id="pblNo">Batal</button>
    </div>
</div>

<script src="<?= AMT_URL ?>/js/amt-checklist.js?v=<?= $v('js/amt-checklist.js') ?>"></script>