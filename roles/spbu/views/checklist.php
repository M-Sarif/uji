<?php
$step     = $_SESSION['checklist_step'];
$stepData = CHECKLIST_STEPS[$step];
$prevStep = $step - 1;
$nextStep = $step + 1;

// LO yang sedang dikerjakan pada wizard ini = LO yang dicentang pengguna
// di halaman Daftar LO. Kalau karena suatu hal belum ada yang dicentang,
// jatuhkan ke seluruh daftar supaya halaman tetap punya data untuk ditampilkan.
$activeLoIds = array_keys(array_filter($_SESSION['lo_checked']));
if (empty($activeLoIds)) {
    $activeLoIds = array_keys(LO_LIST);
}

$answer   = checklist_answer($step);
$stepDone = checklist_step_done($step, $activeLoIds);
$baseUrl  = 'index.php?screen=checklist&step=' . $step;

// Dialog verifikasi yang sedang dibuka pada langkah 6 (form_spp)
$modalProduk = null;
$modalSegel  = null;
if ($stepData['type'] === 'form_spp') {
    // Catatan: array_keys() mengembalikan Nomor LO sebagai integer (PHP
    // otomatis mengubah key numerik), jadi dibandingkan sebagai string.
    $activeLoStrings = array_map('strval', $activeLoIds);
    if (isset($_GET['buka_produk']) && in_array((string) $_GET['buka_produk'], $activeLoStrings, true)) {
        $modalProduk = (string) $_GET['buka_produk'];
    }
    if (isset($_GET['buka_segel']) && in_array((string) $_GET['buka_segel'], SEGEL_LIST, true)) {
        $modalSegel = (string) $_GET['buka_segel'];
    }
}

// Langkah 7 (form_ukur) - Nomor LO yang sudah punya hasil pengukuran DENGAN
// selisih (claim_loss > 0) tapi BELUM diajukan klaimnya (diajukan !== true).
// Dipakai untuk tombol "Ajukan Claim Losses" di bawah Daftar LO -- tombol
// ini hanya tampil selama masih ada LO berstatus begini, dan otomatis
// hilang lagi begitu semua LO yang bermasalah sudah diajukan klaimnya
// (jadi tidak nyangkut / muncul terus walau klaim-nya sudah diajukan).
$pendingClaimLoIds = [];
foreach ($activeLoIds as $loId) {
    $form = $_SESSION['lo_form'][$loId] ?? null;
    if ($form && (float) $form['claim_loss'] > 0 && empty($form['diajukan'])) {
        $pendingClaimLoIds[] = $loId;
    }
}

// Ikon panah dipakai berulang di beberapa kartu
$arrowRight   = '<svg class="arrow-icon" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h15M13 6l6 6-6 6"/></svg>';
$chevronRight = '<svg class="chevron-icon" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 18l6-6-6-6"/></svg>';
?>
<div class="progress-row">
    <?php for ($i = 1; $i <= CHECKLIST_PROGRESS_TOTAL; $i++): ?>
        <?php
        $segClass = 'progress-seg';
        if ($i < $step)      { $segClass .= ' done'; }
        elseif ($i === $step) { $segClass .= ' current'; }
        ?>
        <div class="<?php echo $segClass; ?>"></div>
    <?php endfor; ?>
    <span class="progress-count"><?php echo str_pad((string) $step, 2, '0', STR_PAD_LEFT); ?>/<?php echo CHECKLIST_PROGRESS_TOTAL; ?></span>
</div>

<div class="checklist-card">
    <h2><?php echo h($stepData['text']); ?> <span class="req">*</span></h2>

    <?php switch ($stepData['type']):
        case 'self_action_photo': ?>
            <div class="subtle-box">
                <p class="hint">Verifikasi mandiri tugas Anda.</p>
                <div class="row choice-group">
                    <a href="<?php echo $baseUrl; ?>&jawab=tidak" class="btn-choice red<?php echo $answer === 'tidak' ? ' is-selected' : ''; ?>">Tidak Dilakukan</a>
                    <a href="<?php echo $baseUrl; ?>&jawab=ya" class="btn-choice blue<?php echo $answer === 'ya' ? ' is-selected' : ''; ?>">Ya, Dilakukan</a>
                </div>
                <div class="subtle-divider"></div>
                <p class="hint">Foto sebagai bukti (opsional)</p>
                <label class="photo-drop">
                    <input type="file" accept="image/*" capture="environment" hidden>
                    <span class="photo-drop-title">Ambil Foto</span>
                    <span class="photo-drop-sub">Tap untuk membuka kamera</span>
                </label>
            </div>
        <?php break;

        case 'action': ?>
            <div class="subtle-box">
                <p class="hint">Tugas AMT dan diverifikasi oleh SPBU</p>
                <div class="row choice-group">
                    <a href="<?php echo $baseUrl; ?>&jawab=tidak" class="btn-choice red<?php echo $answer === 'tidak' ? ' is-selected' : ''; ?>">Tidak Dilakukan</a>
                    <a href="<?php echo $baseUrl; ?>&jawab=ya" class="btn-choice blue<?php echo $answer === 'ya' ? ' is-selected' : ''; ?>">Ya, dilakukan</a>
                </div>
            </div>
        <?php break;

        case 'photo': ?>
            <div class="subtle-box">
                <p class="hint">Verifikasi pelaksanaan tugas Anda.</p>
                <div class="row choice-group">
                    <a href="<?php echo $baseUrl; ?>&jawab=tidak" class="btn-choice red<?php echo $answer === 'tidak' ? ' is-selected' : ''; ?>">Tidak Dilakukan</a>
                    <a href="<?php echo $baseUrl; ?>&jawab=ya" class="btn-choice blue<?php echo $answer === 'ya' ? ' is-selected' : ''; ?>">Ya, Dilakukan</a>
                </div>
                <div class="subtle-divider"></div>
                <p class="hint">Foto pelaksanaan tugas Anda sebagai bukti (opsional)</p>
                <label class="photo-drop">
                    <input type="file" accept="image/*" capture="environment" hidden>
                    <span class="photo-drop-title">Ambil Foto</span>
                    <span class="photo-drop-sub">Tap untuk membuka kamera</span>
                </label>
            </div>
        <?php break;

        case 'form_spp': ?>
            <?php
            // Penanda untuk tutorial (lihat spbu_tour_checklist_steps() di roles/spbu/includes/tour_data.php):
            // data-all-done="1" bila SEMUA Produk sudah terisi -> tutorial lanjut ke bagian Segel.
            $produkAllDone = true;
            foreach ($activeLoIds as $produkCheckId) {
                if (empty($_SESSION['spp_produk'][$produkCheckId])) { $produkAllDone = false; break; }
            }
            ?>
            <div data-tour="spp-produk-group" data-all-done="<?php echo $produkAllDone ? '1' : '0'; ?>">
            <p class="section-heading">Produk</p>
            <p class="section-sub">Isi kesesuaian produk yang telah diterima pihak SPBU.</p>
            <?php foreach ($activeLoIds as $loId): $lo = LO_LIST[$loId]; $filled = !empty($_SESSION['spp_produk'][$loId]); ?>
            <a href="<?php echo $baseUrl; ?>&buka_produk=<?php echo urlencode($loId); ?>" class="nav-item-card<?php echo $filled ? ' done' : ''; ?>">
                <div class="nav-item-main">
                    <p class="nav-item-code"><?php echo h($loId); ?></p>
                    <p class="nav-item-title"><?php echo h($lo['order']); ?></p>
                    <span class="chip <?php echo $filled ? 'chip-done' : 'chip-pending'; ?>"><?php echo $filled ? 'Sudah Terisi' : 'Belum Terisi'; ?></span>
                </div>
                <?php echo $arrowRight; ?>
            </a>
            <?php endforeach; ?>
            </div>

            <?php
            // Dipakai oleh tutorial (lihat spbu_tour_checklist_steps() di
            // roles/spbu/includes/tour_data.php): data-all-done="1" bila SEMUA nomor
            // Segel sudah dibongkar -> tutorial lanjut ke tombol "Selanjutnya".
            $segelDoneCount = 0;
            foreach (SEGEL_LIST as $s) {
                if (!empty($_SESSION['spp_segel'][$s])) { $segelDoneCount++; }
            }
            ?>
            <div data-tour="spp-segel-group" data-tour-done-count="<?php echo $segelDoneCount; ?>" data-all-done="<?php echo $segelDoneCount === count(SEGEL_LIST) ? '1' : '0'; ?>">
            <p class="section-heading section-heading-gap">Segel</p>
            <p class="section-sub">Pilih nomor segel yang dibongkar di SPBU saat ini.</p>
            <?php foreach (SEGEL_LIST as $segel): $bongkar = !empty($_SESSION['spp_segel'][$segel]); ?>
            <a href="<?php echo $baseUrl; ?>&buka_segel=<?php echo urlencode($segel); ?>" class="nav-item-card<?php echo $bongkar ? ' done' : ''; ?>">
                <div class="nav-item-main">
                    <p class="nav-item-label">Nomor Segel</p>
                    <p class="nav-item-title"><?php echo h($segel); ?></p>
                    <span class="chip <?php echo $bongkar ? 'chip-done' : 'chip-pending'; ?>"><?php echo $bongkar ? 'Sudah Dibongkar' : 'Belum Dibongkar'; ?></span>
                </div>
                <?php echo $arrowRight; ?>
            </a>
            <?php endforeach; ?>
            </div>
        <?php break;

        case 'form_ukur': ?>
            <?php
            // Dipakai oleh tutorial Soal 7 (lihat spbu_tour_checklist_steps()
            // di roles/spbu/includes/tour_data.php): data-all-done="1" bila form
            // pengukuran SEMUA LO sudah terisi -> tutorial lanjut ke "Selanjutnya".
            $ukurDoneCount = 0;
            foreach ($activeLoIds as $doneCheckId) {
                if (!empty($_SESSION['lo_form'][$doneCheckId])) { $ukurDoneCount++; }
            }
            ?>
            <div data-tour="ukur-lo-group" data-tour-done-count="<?php echo $ukurDoneCount; ?>" data-all-done="<?php echo $ukurDoneCount === count($activeLoIds) ? '1' : '0'; ?>">
            <p class="section-heading">Daftar LO</p>
            <p class="section-sub">Isi satu persatu data LO terlebih dahulu untuk keperluan verifikasi order.</p>
            <?php foreach ($activeLoIds as $loId):
                $lo         = LO_LIST[$loId];
                $form       = $_SESSION['lo_form'][$loId] ?? null;
                $claimLoss  = $form ? (float) $form['claim_loss'] : 0.0;
                $metodeQ    = $form ? '&metode=' . urlencode($form['metode']) : ''; ?>
            <a href="index.php?screen=claim_loss&lo=<?php echo urlencode($loId); ?><?php echo $metodeQ; ?>" class="measure-lo-card<?php echo $form ? ' done' : ''; ?>">
                <div class="lo-detail">
                    <div class="lrow"><span class="label">Nomor LO</span><span class="val"><span class="colon">:</span><?php echo h($loId); ?></span></div>
                    <div class="lrow"><span class="label">Order</span><span class="val"><span class="colon">:</span><span class="order-input"><?php echo h($lo['order']); ?></span></span></div>
                    <div class="lrow"><span class="label">Claim Loss</span><span class="val claim-loss-val"><span class="colon">:</span><?php echo h(number_format($claimLoss, 0, ',', '.')); ?> L</span></div>
                    <div class="lrow"><span class="label">Form Bongkar</span><span class="val <?php echo $form ? 'done' : 'wait'; ?>"><span class="colon">:</span><?php echo $form ? 'Sudah Terisi' : 'Belum Terisi'; ?></span></div>
                </div>
                <?php echo $chevronRight; ?>
            </a>
            <?php endforeach; ?>
            </div>


            <?php if (!empty($pendingClaimLoIds)): ?>
            <button type="button" id="btnAjukanClaimList" class="btn-outline hasil-btn-full ajukan-claim-list-btn">
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 2L3 14h7l-1 8 10-12h-7l1-8z"/></svg>
                Ajukan Claim Losses
            </button>

            <!-- Pop up konfirmasi sebelum semua claim loss yang belum diajukan dikirim sekaligus -->
            <div class="modal-backdrop" id="ajukanClaimListModal" hidden>
                <div class="modal-sheet" role="dialog" aria-modal="true" aria-labelledby="ajukanClaimListJudul">
                    <h2 id="ajukanClaimListJudul">Ajukan Claim Losses</h2>
                    <p>
                        Ada <strong><?php echo count($pendingClaimLoIds); ?></strong> LO dengan selisih kurang yang
                        belum diajukan klaimnya:
                    </p>
                    <ul class="ajukan-claim-list-items">
                        <?php foreach ($pendingClaimLoIds as $pendingLoId): $pendingForm = $_SESSION['lo_form'][$pendingLoId]; ?>
                        <li>
                            <strong><?php echo h($pendingLoId); ?></strong>
                            &mdash; <?php echo h(number_format((float) $pendingForm['claim_loss'], 2, ',', '.')); ?> L
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <p style="color:#94a3b8;">Setelah diajukan, klaim untuk LO di atas tidak dapat dibatalkan lagi dari sini.</p>
                    <form method="post" action="index.php">
                        <input type="hidden" name="action" value="ajukan_claim_bulk">
                        <?php foreach ($pendingClaimLoIds as $pendingLoId): ?>
                        <input type="hidden" name="lo_ids[]" value="<?php echo h($pendingLoId); ?>">
                        <?php endforeach; ?>
                        <button type="submit" class="btn-primary" id="btnYaAjukanClaimList">Ya, Ajukan</button>
                    </form>
                    <button type="button" class="btn-outline modal-close" id="btnBatalAjukanClaimList">Batal</button>
                </div>
            </div>

            <script>
            (function () {
                var btnOpen  = document.getElementById('btnAjukanClaimList');
                var btnBatal = document.getElementById('btnBatalAjukanClaimList');
                var modal    = document.getElementById('ajukanClaimListModal');
                if (!btnOpen || !modal) { return; }

                function bukaModal() {
                    modal.hidden = false;
                    document.getElementById('btnYaAjukanClaimList').focus();
                }
                function tutupModal() {
                    modal.hidden = true;
                    btnOpen.focus();
                }

                btnOpen.addEventListener('click', bukaModal);
                btnBatal.addEventListener('click', tutupModal);

                modal.addEventListener('click', function (e) {
                    if (e.target === modal) { tutupModal(); }
                });
                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape' && !modal.hidden) { tutupModal(); }
                });
            })();
            </script>
            <?php endif; ?>
        <?php break;

        case 'dual_verif':
            $answerLawan = checklist_answer_lawan($step); ?>
            <div class="subtle-box">
                <p class="hint">Verifikasi pelaksanaan tugas Anda (Mandiri)</p>
                <div class="row choice-group">
                    <a href="<?php echo $baseUrl; ?>&jawab=tidak" class="btn-choice red<?php echo $answer === 'tidak' ? ' is-selected' : ''; ?>">Tidak Dilakukan</a>
                    <a href="<?php echo $baseUrl; ?>&jawab=ya" class="btn-choice blue<?php echo $answer === 'ya' ? ' is-selected' : ''; ?>">Ya, dilakukan</a>
                </div>
                <div class="subtle-divider"></div>
                <p class="hint">Foto sebagai bukti (opsional)</p>
                <label class="photo-drop">
                    <input type="file" accept="image/*" capture="environment" hidden>
                    <span class="photo-drop-title">Ambil Foto</span>
                    <span class="photo-drop-sub">Tap untuk membuka kamera</span>
                </label>
                <div class="subtle-divider"></div>
                <p class="hint">Verifikasi Tugas Role Lawan</p>
                <div class="row choice-group" data-tour="verif-lawan">
                    <a href="<?php echo $baseUrl; ?>&jawab_lawan=tidak" class="btn-choice red<?php echo $answerLawan === 'tidak' ? ' is-selected' : ''; ?>">Tidak Dilakukan</a>
                    <a href="<?php echo $baseUrl; ?>&jawab_lawan=ya" class="btn-choice blue<?php echo $answerLawan === 'ya' ? ' is-selected' : ''; ?>">Ya, dilakukan</a>
                </div>
            </div>
        <?php break;

        case 'test_report': ?>
            <div class="test-report-box">
                <div class="test-report-doc">Dokumen Test Report</div>
                <p class="test-report-status">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                    Dokumen telah dilihat
                </p>
            </div>
        <?php break;
    endswitch; ?>
</div>

<div class="wizard-nav">
    <?php if ($step > 1): ?>
        <a href="index.php?screen=checklist&step=<?php echo $prevStep; ?>" class="btn-wiz">
            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H5M11 6l-6 6 6 6"/></svg>
            Sebelumnya
        </a>
    <?php else: ?>
        <a href="index.php?screen=lo_list" class="btn-wiz">
            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M20 12H5M11 6l-6 6 6 6"/></svg>
            Sebelumnya
        </a>
    <?php endif; ?>

    <?php
    // Soal terakhir (14) -> layar "Konfirmasi LO" yang berdiri sendiri
    // (bukan soal; lihat views/konfirmasi_lo.php). Soal lain -> soal berikutnya.
    $nextHref  = $step < count(CHECKLIST_STEPS)
        ? 'index.php?screen=checklist&step=' . $nextStep
        : 'index.php?screen=konfirmasi_lo';
    $nextLabel = 'Selanjutnya';
    ?>
    <?php if ($stepDone): ?>
        <a href="<?php echo $nextHref; ?>" class="btn-wiz" data-tour="wizard-next">
            <?php echo h($nextLabel); ?>
            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h15M13 6l6 6-6 6"/></svg>
        </a>
    <?php else: ?>
        <span class="btn-wiz is-disabled">
            <?php echo h($nextLabel); ?>
            <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 12h15M13 6l6 6-6 6"/></svg>
        </span>
    <?php endif; ?>
</div>

<?php if ($modalProduk !== null):
    $lo       = LO_LIST[$modalProduk];
    $tersimpan = $_SESSION['spp_produk'][$modalProduk] ?? null;
    $kesesuaian = is_array($tersimpan) ? ($tersimpan['kesesuaian'] ?? null) : null; ?>
<div class="spp-modal-backdrop">
    <a href="<?php echo $baseUrl; ?>" class="spp-modal-dismiss" aria-label="Tutup"></a>
    <form method="post" action="index.php" class="spp-modal">
        <input type="hidden" name="action" value="save_spp_produk">
        <input type="hidden" name="lo" value="<?php echo h($modalProduk); ?>">

        <span class="sheet-grabber" aria-hidden="true"></span>
        <h3 class="spp-modal-title">Produk</h3>

        <div class="spp-modal-rows">
            <div class="spp-modal-row"><span class="label">Nomor LO</span><span class="value"><?php echo h($modalProduk); ?></span></div>
            <div class="spp-modal-row"><span class="label">Produk / Material</span><span class="value"><?php echo h($lo['produk']); ?></span></div>
            <div class="spp-modal-row"><span class="label">Quantity</span><span class="value"><?php echo h($lo['qty']); ?></span></div>
        </div>

        <p class="spp-modal-question">Bagaimana LO, Produk, dan Qty yang didapat?</p>
        <div class="spp-opt-row" data-tour="spp-produk-kesesuaian">
            <label class="spp-opt danger">
                <input type="radio" name="kesesuaian" value="tidak" required<?php echo $kesesuaian === 'tidak' ? ' checked' : ''; ?>>
                <span>Tidak Sesuai</span>
            </label>
            <label class="spp-opt">
                <input type="radio" name="kesesuaian" value="sesuai" required<?php echo $kesesuaian === 'sesuai' ? ' checked' : ''; ?>>
                <span>Sesuai</span>
            </label>
        </div>

        <button type="submit" class="spp-modal-save">Simpan</button>
    </form>
</div>
<?php endif; ?>

<script>
/*
 * Pertahankan posisi scroll di halaman checklist ini.
 *
 * Yang men-scroll di aplikasi ini adalah wadah ".content" (tinggi wadah
 * dikunci setinggi layar), BUKAN window -- makanya window.scrollTo / scrollY
 * tidak berpengaruh dan halaman selalu kembali ke atas setelah pop up
 * Produk/Segel dibuka lalu disimpan (halaman dimuat ulang).
 *
 * Solusi: tepat sebelum pengguna membuka pop up (klik kartu Produk/Segel),
 * menutupnya (klik area gelap), atau menyimpannya (submit), posisi
 * scrollTop ".content" disimpan ke sessionStorage + penanda "pending".
 * Begitu halaman yang sama dimuat lagi, posisi itu dikembalikan SEKALI,
 * lalu penanda dihapus -- jadi masuk lagi ke soal ini dari tempat lain
 * tetap mulai dari atas, persis seperti sistem asli.
 */
(function () {
    var step      = <?php echo (int) $step; ?>;
    var posKey    = 'checklistScrollPos' + step;
    var flagKey   = 'checklistScrollPending';
    var scroller  = document.querySelector('.content');
    if (!scroller) { return; }

    if ('scrollRestoration' in history) { history.scrollRestoration = 'manual'; }

    function mark() {
        try {
            sessionStorage.setItem(posKey, String(scroller.scrollTop));
            sessionStorage.setItem(flagKey, String(step));
        } catch (e) {}
    }

    // Pulihkan posisi (hanya kalau penandanya milik soal ini)
    var y = null;
    try {
        if (sessionStorage.getItem(flagKey) === String(step)) {
            var saved = parseInt(sessionStorage.getItem(posKey), 10);
            if (!isNaN(saved)) { y = saved; }
        }
        sessionStorage.removeItem(flagKey);
    } catch (e) {}

    if (y !== null) {
        var apply = function () { scroller.scrollTop = y; };
        apply();                                       // segera (sebelum paint pertama)
        document.addEventListener('DOMContentLoaded', apply);
        window.addEventListener('load', apply);
        requestAnimationFrame(function () { apply(); requestAnimationFrame(apply); });
        setTimeout(apply, 120);                        // jaga-jaga: font/gambar selesai dimuat
    }

    document.addEventListener('click', function (e) {
        var t = e.target.closest ? e.target.closest('a.nav-item-card, a.spp-modal-dismiss') : null;
        if (t) { mark(); }
    }, true);
    document.addEventListener('submit', function (e) {
        if (e.target.closest && e.target.closest('.spp-modal')) { mark(); }
    }, true);
})();
</script>

<?php if ($modalSegel !== null):
    $tersimpan  = $_SESSION['spp_segel'][$modalSegel] ?? null;
    $kesesuaian = is_array($tersimpan) ? ($tersimpan['kesesuaian'] ?? null) : null;
    $kondisi    = is_array($tersimpan) ? ($tersimpan['kondisi'] ?? null) : null; ?>
<div class="spp-modal-backdrop">
    <a href="<?php echo $baseUrl; ?>" class="spp-modal-dismiss" aria-label="Tutup"></a>
    <form method="post" action="index.php" class="spp-modal">
        <input type="hidden" name="action" value="save_spp_segel">
        <input type="hidden" name="segel" value="<?php echo h($modalSegel); ?>">

        <span class="sheet-grabber" aria-hidden="true"></span>
        <h3 class="spp-modal-title">Segel <?php echo h($modalSegel); ?></h3>

        <p class="spp-modal-question">Bagaimana nomor segel yang didapat?</p>
        <div class="spp-opt-row" data-tour="spp-segel-kesesuaian">
            <label class="spp-opt danger">
                <input type="radio" name="kesesuaian" value="tidak" required<?php echo $kesesuaian === 'tidak' ? ' checked' : ''; ?>>
                <span>Tidak Sesuai</span>
            </label>
            <label class="spp-opt">
                <input type="radio" name="kesesuaian" value="sesuai" required<?php echo $kesesuaian === 'sesuai' ? ' checked' : ''; ?>>
                <span>Sesuai</span>
            </label>
        </div>

        <p class="spp-modal-question">Bagaimana kondisi segel yang didapat?</p>
        <div class="spp-opt-row" data-tour="spp-segel-kondisi">
            <label class="spp-opt danger">
                <input type="radio" name="kondisi" value="rusak" required<?php echo $kondisi === 'rusak' ? ' checked' : ''; ?>>
                <span>Tidak Sesuai</span>
            </label>
            <label class="spp-opt">
                <input type="radio" name="kondisi" value="baik" required<?php echo $kondisi === 'baik' ? ' checked' : ''; ?>>
                <span>Sesuai</span>
            </label>
        </div>

        <button type="submit" class="spp-modal-save">Simpan</button>
    </form>
</div>
<?php endif; ?>