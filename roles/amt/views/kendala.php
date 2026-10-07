<?php
/**
 * Layar: ?screen=amt_kendala  (role AMT)  -- "Laporkan Kendala"
 * Letak: roles/amt/views/kendala.php
 *
 * Form: Jenis Kendala, Estimasi Kendala (Ya, Bisa Diprediksi / Tidak Bisa Diprediksi), Estimasi Waktu
 * Kendala Selesai (HANYA bila "Ya"), Foto Bukti, Justifikasi AMT. Tombol "Kirim Laporan Kendala" aktif
 * bila semua isian wajib sudah lengkap -> POST submit_kendala (amt_kendala_handle_post()).
 * Logika layar: assets/js/amt-kendala.js. Tutorial: amt_tour_kendala_steps() (includes/tutorial.php).
 */
$s = amt_kendala_ship();
if ($s === null) {
    echo '<section class="kd"><p class="kd-empty">Tidak ada pengiriman yang sedang berjalan.</p></section>';
    return;
}
$v = function (string $f): int { return (int) @filemtime(AMT_ASSET_DIR . '/' . $f); };
?>
<form class="kd" id="kdForm" method="post" action="<?= amt_e(amt_kendala_url($s)) ?>" autocomplete="off"
      data-jenis="0" data-bisa="ya" data-est="0" data-photo="0" data-note="0" data-ready="0"
      data-maxnote="<?= (int) AMT_KENDALA_NOTE_MAX ?>">
    <input type="hidden" name="action" value="submit_kendala">
    <input type="hidden" name="id" value="<?= amt_e($s['id']) ?>">
    <input type="hidden" name="est_h" id="kdEstH" value="">
    <input type="hidden" name="est_m" id="kdEstM" value="">
    <input type="hidden" name="photo_data" id="kdPhotoData" value="">

    <div class="kd-body">
        <!-- Jenis Kendala -->
        <label class="kd-label" for="kdJenis">Jenis Kendala<b class="kd-req">*</b></label>
        <div class="kd-select" id="kdJenisWrap">
            <select id="kdJenis" name="jenis">
                <option value="">Pilih jenis kendala</option>
                <?php foreach (AMT_KENDALA_TYPES as $key => $label): ?>
                    <option value="<?= amt_e($key) ?>"><?= amt_e($label) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="kd-clear" id="kdJenisClear" aria-label="Hapus pilihan jenis kendala" hidden>&times;</button>
        </div>

        <!-- Estimasi Kendala -->
        <div class="kd-label" id="kdBisaLabel">Estimasi Kendala<b class="kd-req">*</b></div>
        <div class="kd-radios" id="kdBisa" role="radiogroup" aria-labelledby="kdBisaLabel">
            <label class="kd-radio"><input type="radio" name="bisa" value="ya" checked><span class="kd-dot"></span><span>Ya, Bisa Diprediksi</span></label>
            <label class="kd-radio"><input type="radio" name="bisa" value="tidak"><span class="kd-dot"></span><span>Tidak Bisa Diprediksi</span></label>
        </div>

        <!-- Estimasi Waktu Kendala Selesai (hanya bila "Ya, Bisa Diprediksi") -->
        <div id="kdTimeBlock">
            <label class="kd-label" for="kdTime">Estimasi Waktu Kendala Selesai<b class="kd-req">*</b></label>
            <button type="button" class="kd-field kd-time" id="kdTime" aria-haspopup="dialog">
                <span class="kd-time__text is-ph" id="kdTimeText">Pilih perkiraan durasi selesai</span>
                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
            </button>
        </div>

        <!-- Foto Bukti -->
        <div class="kd-label" id="kdPhotoLabel">Foto Bukti<b class="kd-req">*</b></div>
        <div class="kd-photo" id="kdPhoto">
            <button type="button" class="kd-photo__idle" id="kdPhotoBtn" aria-labelledby="kdPhotoLabel">
                <span class="kd-photo__title">Foto Bukti Kendala</span>
                <span class="kd-photo__sub">Arahkan kamera ke bukti kendala</span>
                <span class="kd-photo__act">
                    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    Ambil Foto
                </span>
            </button>
            <div class="kd-photo__done" id="kdPhotoDone" hidden>
                <img id="kdPhotoImg" alt="Foto bukti kendala">
                <button type="button" class="kd-photo__del" id="kdPhotoDel">
                    <svg viewBox="0 0 24 24" width="13" height="13" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2M6 6l1 14h10l1-14M10 11v6M14 11v6"/></svg>
                    Hapus Foto
                </button>
            </div>
        </div>

        <!-- Justifikasi AMT -->
        <label class="kd-label" for="kdNote">Justifikasi AMT<b class="kd-req">*</b></label>
        <div class="kd-ta" id="kdNoteWrap">
            <textarea id="kdNote" name="note" rows="4" maxlength="<?= (int) AMT_KENDALA_NOTE_MAX ?>"
                      placeholder="Masukkan keterangan atau kronologi kejadian"></textarea>
            <button type="button" class="kd-clear kd-clear--ta" id="kdNoteClear" aria-label="Hapus keterangan" hidden>&times;</button>
        </div>
    </div>

    <div class="kd-footer">
        <button type="submit" class="kd-send" id="kdSend" disabled>
            <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z"/></svg>
            <span>Kirim Laporan Kendala</span>
        </button>
    </div>

    <!-- Pop up: Estimasi Waktu Kendala Selesai (jam : menit) -->
    <div class="kd-modal" id="kdModal" hidden>
        <div class="kd-sheet" role="dialog" aria-modal="true" aria-labelledby="kdModalTitle">
            <h3 class="kd-sheet__title" id="kdModalTitle">Estimasi Waktu Kendala Selesai</h3>
            <div class="kd-wheels" id="kdWheels">
                <div class="kd-wheel" id="kdWheelH" data-max="<?= (int) AMT_KENDALA_MAX_HOURS ?>" tabindex="0" role="spinbutton" aria-label="Jam"></div>
                <div class="kd-wheels__sep">:</div>
                <div class="kd-wheel" id="kdWheelM" data-max="59" tabindex="0" role="spinbutton" aria-label="Menit"></div>
            </div>
            <p class="kd-sheet__hint">Geser angka ke atas / bawah, atau ketuk angka di atas / bawahnya.</p>
            <button type="button" class="kd-apply" id="kdApply" disabled>Terapkan</button>
            <button type="button" class="kd-cancel" id="kdCancel">Batal</button>
        </div>
    </div>
</form>

<script src="<?= AMT_URL ?>/js/camera.js?v=<?= $v('js/camera.js') ?>"></script>
<script src="<?= AMT_URL ?>/js/amt-kendala.js?v=<?= $v('js/amt-kendala.js') ?>"></script>