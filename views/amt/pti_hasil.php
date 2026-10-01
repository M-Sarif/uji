<?php
/**
 * Layar: ?screen=amt_pti_hasil  (role AMT)  -- "Hasil Inspeksi"
 *
 * Tampilan BACA SAJA dari inspeksi yang terakhir dikirim: hasil GO / NO GO,
 * jawaban tiap langkah (Layak / Tidak layak), dan catatan AMT.
 * Dibuka lewat tombol "Lihat Hasil Inspeksi" di halaman PTI (pti.php).
 * Guard akses ada di amt_pti_bootstrap().
 */
require_once __DIR__ . '/../../includes/amt/amt_pti_functions.php';

$sub     = amt_pti_submitted();
$answers = $sub ? $sub['answers'] : [];
$result  = $sub ? $sub['result'] : 'GO';
$failed  = amt_pti_failed_from($answers);
$note    = $sub ? $sub['note'] : '';
$ship    = amt_pti_shipment();
$when    = ($sub && !empty($sub['done_at'])) ? date('d M Y, H:i', strtotime($sub['done_at'])) : '';
$steps   = amt_pti_steps();
$isGo    = $result === 'GO';
?>

<section class="pti-screen pti-hasil">
    <div class="pti-result pti-result--<?= $isGo ? 'go' : 'nogo' ?>" role="status" data-tour="hasil-banner">
        <svg viewBox="0 0 24 24" width="20" height="20" aria-hidden="true" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <circle cx="12" cy="12" r="9"/>
            <?php if ($isGo): ?><path d="M8 12.5l2.7 2.7L16 9.8"/><?php else: ?><path d="M9 9l6 6M15 9l-6 6"/><?php endif; ?>
        </svg>
        <div>
            <strong>Hasil Inspeksi adalah <?= amt_e($result) ?></strong>
            <span><?= $isGo
                ? 'Mobil tangki dalam keadaan layak beroperasi.'
                : 'Mobil tangki belum layak beroperasi. Laporkan ke pengawas sebelum berangkat.' ?></span>
        </div>
    </div>

    <?php if ($ship): ?>
        <div class="pti-meta">
            <div><span class="pti-label">Nomor Polisi MT</span><b><?= amt_e($ship['nomor_polisi']) ?></b></div>
            <?php if ($when !== ''): ?>
                <div><span class="pti-label">Dikirim</span><b><?= amt_e($when) ?></b></div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <?php if ($failed): ?>
        <ul class="pti-failed" aria-label="Item yang tidak layak">
            <?php foreach ($failed as $f): ?>
                <li><b><?= amt_e($f['label']) ?></b> <span>(<?= amt_e($f['title']) ?>)</span></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <div class="pti-review" data-tour="hasil-list">
        <?php foreach ($steps as $no => $data):
            if (empty($data['items'])) { continue; } ?>
            <div class="pti-review__group">
                <h3 class="pti-review__title"><?= $no ?>. <?= amt_e($data['title']) ?></h3>
                <?php foreach ($data['items'] as $item):
                    $val = isset($answers[$no][$item['key']]) ? $answers[$no][$item['key']] : ''; ?>
                    <div class="pti-review__row">
                        <span><?= amt_e($item['label']) ?></span>
                        <span class="pti-chip pti-chip--<?= $val === 'tidak' ? 'bad' : 'ok' ?>">
                            <?= $val === 'tidak' ? 'Tidak layak' : 'Layak' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>

        <div class="pti-review__group">
            <h3 class="pti-review__title">Catatan</h3>
            <p class="pti-review__note"><?= $note !== '' ? nl2br(amt_e($note)) : '-' ?></p>
        </div>
    </div>
</section>