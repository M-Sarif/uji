<?php
/**
 * Layar "Tiba di Lokasi".
 * Tampil setelah pengguna menekan langkah "Tiba di Lokasi" pada
 * halaman Detail Order (views/shipment.php).
 *
 * Isi layar:
 *  - Info waktu tiba & SPBU keberapa dari rute mobil tangki
 *  - Kartu verifikasi Mobil Tangki, AMT 1, dan AMT 2 (data: ARRIVAL_SUBJECTS)
 *  - Tombol "Simpan" yang baru aktif setelah SEMUA pertanyaan dijawab
 */

$arrivalError = $_SESSION['arrival_error'] ?? '';
unset($_SESSION['arrival_error']);
?>
<form method="post" action="index.php" class="arrival-wrap" id="arrivalForm" data-all="0">
    <input type="hidden" name="action" value="kirim_verifikasi">

    <div class="content-pad arrival-body">

        <!-- Ringkasan waktu tiba (tanpa kartu, teks langsung di atas latar halaman) -->
        <div class="arrival-info">
            <div class="arrival-info-col">
                <span class="label">Waktu Tiba</span>
                <span class="value"><?php echo h($_SESSION['arrival_time'] ?? date('d/m/Y H:i:s')); ?> WIB</span>
            </div>
            <div class="arrival-info-col">
                <span class="label">SPBU ke</span>
                <span class="value"><?php echo (int) ARRIVAL_SPBU_KE; ?> / <?php echo (int) ARRIVAL_SPBU_TOTAL; ?></span>
            </div>
        </div>

        <!-- Kartu verifikasi: mobil tangki, AMT 1, AMT 2 -->
        <!-- Dibungkus satu wadah (data-tour) supaya tutorial menyorot KETIGA
             kartu sekaligus dan tooltip tidak menutupi tombol "Ya, sesuai". -->
        <div data-tour="verif-subjects" style="display:flex;flex-direction:column;gap:0.875rem;">
        <?php foreach (ARRIVAL_SUBJECTS as $name => $subject): ?>
            <?php $answer = $_SESSION[$name] ?? null; ?>
            <div class="card subject-card">
                <div class="subject-row">
                    <div class="subject-thumb">
                        <img src="<?php echo h($subject['photo']); ?>"
                             alt="<?php echo h($subject['name']); ?>"
                             style="object-fit:<?php echo h($subject['fit']); ?>;">
                    </div>
                    <div class="subject-meta">
                        <p class="subject-name"><?php echo h($subject['name']); ?></p>
                        <p class="subject-sub"><?php echo h($subject['sub']); ?></p>
                    </div>
                </div>

                <div class="subject-divider"></div>

                <p class="question"><?php echo h($subject['question']); ?></p>
                <p class="question-hint"><?php echo h($subject['hint'] ?? ''); ?></p>

                <div class="row choice-group" data-group="<?php echo h($name); ?>">
                    <label class="btn-choice red<?php echo $answer === false ? ' is-selected' : ''; ?>">
                        <input type="radio" name="<?php echo h($name); ?>" value="tidak"
                               <?php echo $answer === false ? 'checked' : ''; ?>>
                        Tidak Sesuai
                    </label>
                    <label class="btn-choice blue<?php echo $answer === true ? ' is-selected' : ''; ?>">
                        <input type="radio" name="<?php echo h($name); ?>" value="ya"
                               <?php echo $answer === true ? 'checked' : ''; ?>>
                        Sesuai
                    </label>
                </div>
            </div>
        <?php endforeach; ?>
        </div>

        <?php if ($arrivalError !== ''): ?>
            <p class="error-text"><?php echo h($arrivalError); ?></p>
        <?php endif; ?>

    </div>

    <!-- Tombol simpan (nonaktif sampai semua pertanyaan dijawab) -->
    <div class="sticky-footer arrival-footer">
        <button type="button" class="btn-primary" id="btnSimpan" disabled>
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M5 3h11l3 3v15a0 0 0 010 0H5a0 0 0 010 0V3z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M8 3v6h8V3M8 21v-6h8v6"/>
            </svg>
            Simpan
        </button>
    </div>

    <!-- Pop up konfirmasi sebelum hasil verifikasi dikirim -->
    <div class="modal-backdrop" id="konfirmasiModal" hidden>
        <div class="modal-sheet" role="dialog" aria-modal="true" aria-labelledby="konfirmasiJudul">
            <h2 id="konfirmasiJudul">Konfirmasi Kirim Hasil Verifikasi</h2>
            <p>Apakah Anda yakin ingin mengirim hasil verifikasi MT dan AMT?</p>
            <button type="submit" class="btn-primary" id="btnKirim">Kirim Verifikasi MT dan AMT</button>
            <button type="button" class="btn-outline modal-close" id="btnTutup">Tutup</button>
        </div>
    </div>
</form>

<script>
(function () {
    var form   = document.getElementById('arrivalForm');
    var btn    = document.getElementById('btnSimpan');
    var groups = form.querySelectorAll('.choice-group');

    /* Tandai tombol terpilih + aktifkan "Simpan" bila semua sudah dijawab */
    function refresh() {
        var answered = 0;

        groups.forEach(function (group) {
            var isAnswered = false;

            group.querySelectorAll('.btn-choice').forEach(function (label) {
                var input = label.querySelector('input[type="radio"]');
                label.classList.toggle('is-selected', input.checked);
                if (input.checked) { isAnswered = true; }
            });

            if (isAnswered) { answered++; }

            // Penanda untuk tutorial: kartu yang sudah dijawab tidak disorot lagi.
            var card = group.closest('.subject-card');
            if (card) { card.classList.toggle('is-answered', isAnswered); }
        });

        btn.disabled = (answered !== groups.length);

        // Penanda untuk tutorial: langkah "jawab ketiga kartu" selesai bila semuanya sudah dijawab.
        form.setAttribute('data-all', btn.disabled ? '0' : '1');

        // Kalau tutorial sedang menunggu tombol "Simpan" ini aktif, langsung
        // coba tampilkan sekarang juga (tidak menunggu polling 400ms).
        if (window.OneFISTour) { window.OneFISTour.rescan(); }
    }

    form.addEventListener('change', function (e) {
        if (e.target.type === 'radio') { refresh(); }
    });

    /* ---------- Pop up konfirmasi ---------- */
    var modal = document.getElementById('konfirmasiModal');

    function bukaModal() {
        modal.hidden = false;
        document.getElementById('btnKirim').focus();
    }
    function tutupModal() {
        modal.hidden = true;
        btn.focus();
    }

    btn.addEventListener('click', function () {
        if (!btn.disabled) { bukaModal(); }
    });
    document.getElementById('btnTutup').addEventListener('click', tutupModal);

    /* klik area gelap di luar kartu = tutup */
    modal.addEventListener('click', function (e) {
        if (e.target === modal) { tutupModal(); }
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && !modal.hidden) { tutupModal(); }
    });

    refresh();
})();
</script>