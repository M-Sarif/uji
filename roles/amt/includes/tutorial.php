<?php
/* ============================================================
 * OneFIS - AMT - Data tutorial terpandu (guided tour)
 *
 * Mesin tutorial: roles/amt/assets/js/tutorial-amt.js  (khusus AMT)
 * Tampilan       : roles/amt/assets/css/tutorial-amt.css (khusus AMT)
 * Tutorial peran SPBU TIDAK tersentuh oleh file-file ini.
 *
 * CARA KERJA (dirancang untuk orang awam)
 * ---------------------------------------
 * Tutorial BERBASIS KONDISI, bukan berbasis ketukan. Mesin selalu
 * menampilkan langkah PERTAMA yang belum selesai, jadi tutorial tidak
 * pernah "hilang" walau pengguna melompat-lompat, membatalkan kamera,
 * atau mengisi form tidak berurutan.
 *
 * Tiap langkah:
 *   'no'        => nomor langkah pada alur (null = tidak diberi nomor)
 *   'target'    => selector elemen yang harus diketuk (null = kartu di tengah)
 *   'highlight' => (opsional) elemen yang disorot, jika lebih luas dari target
 *   'title'     => judul singkat (kata kerja di depan)
 *   'text'      => penjelasan 1-2 kalimat
 *   'hint'      => kalimat aksi di kotak biru
 *   'done'      => selector; langkah SELESAI bila elemen ini ada di halaman
 *                  (null = langkah aksi: selesai saat target diketuk / tombol ditekan)
 *   'needTap'   => (opsional) true = langkah selesai HANYA bila target sudah
 *                  diketuk DAN selector 'done' terpenuhi (mis. Perbarui Lokasi)
 *   'wait'      => (opsional) teks kotak biru saat target sudah diketuk tetapi
 *                  'done' belum terpenuhi ("menunggu...")
 *   'ok'        => pesan singkat saat langkah baru saja selesai
 *   'settle'    => (opsional) ms; 'done' baru dianggap terpenuhi bila pengguna sudah
 *                  berhenti mengetik selama itu (untuk kolom teks)
 *   'label'     => (opsional) teks kecil di atas kartu; menimpa nama alur
 *   'when'      => (opsional) selector; kartu BARU muncul bila elemen ini ada
 *                  (mis. menu PTI yang baru menyala). Sebelumnya kartu disembunyikan.
 *   'delay'     => (opsional) jeda (ms) setelah 'when' terpenuhi sebelum kartu muncul
 *   'announce'  => (opsional) true = saat langkah ini selesai halaman langsung diberi
 *                  tahu (event amt:tour-done), tanpa menunggu seluruh alur selesai
 *   'list'      => (opsional) array teks; tampil sebagai daftar bernomor di bawah 'text'
 *   'autoHide'  => (opsional) ms; kartu tertutup sendiri bila tidak diketuk (dianggap
 *                  sama dengan mengetuk tombol kartu, mis. "Mengerti")
 *   'remember'  => (opsional) true = langkah yang sudah dibaca dicatat, jadi tidak
 *                  diulang bila pengguna pindah halaman lalu kembali sebelum alur selesai
 *   'okText'    => (opsional) kalimat di bawah pesan 'ok'
 *   'hold'      => (opsional) lama pesan 'ok' tampil sebelum pindah langkah
 *                  (milidetik; bawaan 1000)
 *   'button'    => (opsional) label tombol di kartu (mis. "Mulai")
 *
 * Halaman TIDAK digulir otomatis oleh tutorial. Kartu yang menyesuaikan
 * posisinya (atas / bawah) supaya tidak menutupi bagian yang disorot.
 *
 * Status form dibaca dari atribut data-* pada #wk-form yang diperbarui
 * oleh roles/amt/views/_work_form.php:
 *   data-loc="1"   lokasi sudah sesuai titik kerja
 *   data-pin="1"   lokasi sesuai DAN peta (pin) sudah selesai dimuat
 *   data-act="1"   aktivitas sudah dipilih (End Work: selalu 1)
 *   data-photo="1" foto verifikasi sudah tersimpan
 *
 * Dipanggil dari core/layout_bottom.php lewat amt_tour_config($screen).
 * ============================================================ */

// Nama layar untuk label (dipakai bila langkah tidak punya alur bernomor)
const AMT_TOUR_SCREEN_ORDER = ['amt_home', 'start_end', 'start_work', 'checkin', 'checkout', 'amt_pti', 'amt_pti_form', 'amt_pti_hasil', 'amt_shipments', 'amt_shipment_detail', 'amt_spbu', 'amt_checklist_lo', 'amt_checklist', 'amt_verifikasi', 'amt_verifikasi_qr', 'amt_verifikasi_kode', 'amt_verifikasi_sukses', 'end_work'];

const AMT_TOUR_SCREEN_LABELS = [
    'amt_home'   => 'Beranda AMT',
    'start_end'  => 'Start / End Work',
    'start_work' => 'Start Work',
    'checkin'    => 'Check-In',
    'checkout'   => 'Check-Out',
    'amt_pti'      => 'Pre-Trip Inspection',
    'amt_pti_form' => 'Form Inspeksi',
    'amt_pti_hasil' => 'Hasil Inspeksi',
    'amt_shipments' => 'Shipments',
    'amt_shipment_detail' => 'Detail Order',
    'amt_spbu' => 'Aktifitas di SPBU',
    'amt_checklist_lo' => 'Daftar LO',
    'amt_checklist' => 'Checklist Pra-Pembongkaran',
    'amt_verifikasi' => 'Verifikasi Order',
    'amt_verifikasi_qr' => 'Pindai Kode QR',
    'amt_verifikasi_kode' => 'Kode Konfirmasi',
    'amt_verifikasi_sukses' => 'Order Terverifikasi',
    'end_work'   => 'End Work',
];

// Alur tutorial; 'total' = jumlah langkah bernomor pada alur itu
const AMT_TOUR_JOURNEYS = [
    'masuk'   => ['label' => 'Absen Masuk',  'total' => 6],
    'checkin' => ['label' => 'Check-In',     'total' => 5],
    'pulang'  => ['label' => 'Absen Pulang', 'total' => 4],
    'dcu'     => ['label' => 'Check-In Berhasil', 'total' => 1],   // pemberitahuan DCU (tanpa nomor langkah)
    // Inspeksi PTI: 1 buka menu, 2-3 ringkasan, 4-5 jawab item, 6 catatan, 7 kirim, 8 konfirmasi
    'pti'     => ['label' => 'Inspeksi PTI', 'total' => 8],
    // Pengiriman: 1 buka Detail Order, 2 buka kartu SPBU, 3 ketuk Tiba di Lokasi,
    // 4 cek lokasi, 5 konfirmasi tiba (menu Shipments di Beranda = kartu tersendiri)
    'kirim'   => ['label' => 'Pengiriman', 'total' => 5],
    // Checklist Pra-Pembongkaran: 1 pilih LO, 2 Mulai Checklist, 3 jawab soal, 4 buat QR Code (soal 7),
    // 5 ketuk Kirim, 6 konfirmasi "Ya, Kirim"
    'checklist' => ['label' => 'Checklist Pra-Pembongkaran', 'total' => 6],
    // Verifikasi Order: 1 pilih LO, 2 pilih metode, 3 konfirmasi ke Petugas SPBU + pindai QR / ketik kode, 4 hasil
    'verifikasi' => ['label' => 'Verifikasi Order', 'total' => 4],
    // Check-Out: 1 buka menu Check-Out (setelah scan segel di AVM), 2 lokasi, 3 aktivitas, 4 foto, 5 kirim
    'checkout' => ['label' => 'Check-Out', 'total' => 5],
];

// Jeda (ms) sebelum tutorial DCU muncul di beranda setelah Check-In berhasil
// (nilainya diatur di roles/amt/includes/amt_pti_data.php)
const AMT_TOUR_DCU_DELAY_MS = AMT_DCU_SHOW_DELAY;

/**
 * Langkah-langkah form (lokasi, aktivitas, foto, kirim).
 * Dipakai bersama oleh Start Work, Check-In, dan End Work supaya
 * bahasanya konsisten.
 *
 * @param string $mode 'start' | 'checkin' | 'end'
 */
function amt_tour_form_steps(string $mode): array
{
    $isCheckin = ($mode === 'checkin');
    $isEnd     = ($mode === 'end');

    $what = $isCheckin ? 'Check-In' : ($isEnd ? 'absen pulang' : 'absen masuk');
    $n    = $isCheckin ? 2 : ($isEnd ? 2 : 3);   // nomor langkah pertama di form
    $steps = [];

    // Check-In punya dua tab: pastikan pengguna ada di tab "Verifikasi"
    if ($isCheckin) {
        $steps[] = [
            'no'     => null,
            'target' => '#ci-tab-verif',
            'title'  => 'Buka tab “Verifikasi”',
            'text'   => 'Check-In dilakukan di tab “Verifikasi”. Tab “Riwayat Check-In” hanya untuk melihat daftar Check-In sebelumnya.',
            'hint'   => '👆 Ketuk tab “Verifikasi”',
            'done'   => '#ci-pane-verifikasi:not([hidden])',
        ];
    }

    // 1) Lokasi
    $steps[] = [
        'no'        => $n,
        'target'    => '#wk-refresh',
        'highlight' => '#wk-loc',
        'title'     => 'Cek lokasi Anda',
        'text'      => 'Pin di peta harus ada di area kerja. Ketuk “Perbarui Lokasi”, lalu tunggu sampai pin muncul dan kotak di bawah peta berwarna hijau.',
        'hint'      => '👆 Ketuk “Perbarui Lokasi”',
        'wait'      => '⏳ Menunggu lokasi sesuai… perhatikan pin di peta',
        'done'      => '#wk-form[data-pin="1"]',
        'needTap'   => true,
        'ok'        => '✅ Lokasi sudah sesuai',
        'okText'    => 'Lihat pin di peta: sudah tepat di area kerja.',
        'hold'      => 2000,
    ];

    // 2) Aktivitas (tidak ada di End Work: mengikuti pilihan saat Start Work)
    if (!$isEnd) {
        $n++;
        $steps[] = [
            'no'        => $n,
            'target'    => '#wk-akt',
            'highlight' => '#wk-act-box',
            'title'     => 'Pilih aktivitas',
            'text'      => $isCheckin
                ? 'Ketuk kolom ini, lalu pilih “Tugas Rutin” (tugas harian biasa) atau “Tugas Lembur” (di luar jam biasa).'
                : 'Ketuk kolom ini, lalu pilih “Hadir” bila Anda masuk kerja hari ini.',
            'hint'      => '👆 Ketuk kolom “Pilih aktivitas”, lalu pilih salah satu',
            'done'      => '#wk-form[data-act="1"]',
            'ok'        => '✅ Aktivitas sudah dipilih',
        ];
    }

    // 3) Foto selfie
    $n++;
    $steps[] = [
        'no'        => $n,
        'target'    => '#wk-take',
        'highlight' => '#wk-photo-box',
        'title'     => 'Ambil foto selfie',
        'text'      => 'Ketuk “Ambil Foto”, hadapkan wajah ke kamera depan, ketuk tombol potret, lalu ketuk “Simpan Foto”.',
        'hint'      => '👆 Ketuk “Ambil Foto”',
        'done'      => '#wk-form[data-photo="1"]',
        'ok'        => '✅ Foto sudah tersimpan',
    ];

    // 4) Kirim
    $n++;
    $steps[] = [
        'no'     => $n,
        'target' => '#wk-submit',
        'title'  => 'Kirim ' . $what,
        'text'   => $isCheckin
            ? 'Semua sudah lengkap. Ketuk “Kirim”. Check-In Anda akan tercatat di tab “Riwayat Check-In”.'
            : ($isEnd
                ? 'Semua sudah lengkap. Ketuk “Kirim” untuk mengakhiri waktu kerja hari ini.'
                : 'Semua sudah lengkap. Ketuk “Kirim” untuk mulai bekerja. Waktu Kerja langsung berjalan.'),
        'hint'   => '👆 Ketuk tombol biru “Kirim”',
        'done'   => null,
    ];

    return $steps;
}

/**
 * Langkah tutorial PTI (Pre-Trip Inspection).
 * Alur: beranda (buka menu PTI) -> ringkasan (Isi Inspeksi) -> form 11 langkah.
 *
 * @param string $screen 'amt_pti' | 'amt_pti_form'
 * @return array{steps:array, journey:string, subKey:?string}
 */
function amt_tour_pti_steps(string $screen): array
{
    // ---- Ringkasan PTI ----
    if ($screen === 'amt_pti') {
        if (amt_pti_shipment() === null) {
            return ['journey' => 'pti', 'steps' => [], 'subKey' => null];   // belum ada shipment
        }
        if (amt_pti_is_done()) {
            // Halaman awal PTI setelah inspeksi terkirim. Hanya 2 kartu:
            //  1) menyorot KOTAK KARTU PTI (status "Sudah Inspeksi"); tertutup sendiri setelah 5 detik
            //     (atau ketuk "Mengerti"),
            //  2) menyorot panah kembali (←) supaya AMT kembali ke Beranda; di sana tutorial segel menyusul.
            // 'remember' = kartu 1 tidak diulang bila halaman dimuat ulang sebelum AMT menekan ←.
            return ['journey' => 'pti', 'subKey' => 'amt_pti_done', 'steps' => [
                [
                    'no'       => null,
                    'label'    => 'Inspeksi Terkirim',
                    'target'   => '[data-tour="pti-card"]',
                    'title'    => 'Inspeksi selesai ✅',
                    'text'     => 'Hasil inspeksi sudah terkirim dan status PTI sekarang “Sudah Inspeksi”. Anda bisa melihat hasilnya lewat “Lihat Hasil Inspeksi”, atau mengisi ulang lewat “Isi Inspeksi Lagi”.',
                    'button'   => 'Mengerti',
                    'autoHide' => AMT_PTI_DONE_PAGE_VISIBLE_FOR,   // hilang sendiri setelah 5 detik
                    'remember' => true,
                    'done'     => null,
                ],
                [
                    'no'     => null,
                    'label'  => 'Kembali ke Beranda',
                    'target' => 'a.back-btn',
                    'title'  => 'Kembali ke Beranda',
                    'text'   => 'Ketuk panah kembali (←) di kiri atas untuk kembali ke Beranda. Tahapan berikutnya (ambil segel, scan segel, dan seterusnya) dijelaskan di sana.',
                    'hint'   => '👆 Ketuk panah ← di kiri atas',
                    'done'   => null,
                ],
            ]];
        }
        return ['journey' => 'pti', 'subKey' => null, 'steps' => [
            [
                'no'     => 2,
                'target' => '[data-tour="pti-card"]',
                'title'  => 'Cek data mobil tangki',
                'text'   => 'Pastikan Nomor Polisi MT dan Kapasitas Tangki sesuai dengan mobil tangki yang Anda bawa.',
                'hint'   => '👆 Ketuk “Mengerti” bila data sudah sesuai',
                'button' => 'Mengerti',
                'done'   => null,
            ],
            [
                'no'     => 3,
                'target' => '[data-tour="pti-start"]',
                'title'  => 'Ketuk “Isi Inspeksi”',
                'text'   => 'Inspeksi terdiri dari 11 langkah pendek. Anda akan memeriksa kondisi mobil tangki dari beberapa sisi.',
                'hint'   => '👆 Ketuk tombol biru “Isi Inspeksi”',
                'done'   => null,
            ],
        ]];
    }

    // ---- Form inspeksi (11 langkah) ----
    $step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
    $step = max(1, min(AMT_PTI_TOTAL, $step));
    $next = '#ptiForm .pti-next:not(.is-locked)';   // tombol lanjut aktif = semua item / catatan sudah diisi

    if ($step === 1) {
        return ['journey' => 'pti', 'subKey' => 'amt_pti_form_1', 'steps' => [
            [
                'no'        => 4,
                'target'    => '[data-tour="pti-items"]',
                'title'     => 'Periksa lalu jawab tiap item',
                'text'      => 'Lihat foto acuan, cek kondisi mobil tangki, lalu pilih “Layak” bila baik atau “Tidak layak” bila ada masalah. Semua item bertanda * wajib dijawab.',
                'hint'      => '👆 Pilih Layak atau Tidak layak di setiap item',
                'done'      => $next,
                'ok'        => '✅ Semua item sudah dijawab',
            ],
            [
                'no'     => 5,
                'target' => '#ptiForm .pti-next',
                'title'  => 'Ketuk “Selanjutnya”',
                'text'   => 'Ulangi hal yang sama untuk sisi mobil berikutnya, sampai langkah 11. Tombol “Sebelumnya” dipakai bila ingin memperbaiki jawaban.',
                'hint'   => '👆 Ketuk “Selanjutnya”',
                'done'   => null,
            ],
        ]];
    }

    if ($step < AMT_PTI_TOTAL) {
        // Langkah 2-10: pengingat singkat, cukup tampil sekali
        return ['journey' => 'pti', 'subKey' => 'amt_pti_form_mid', 'steps' => [[
            'no'     => null,
            'label'  => 'Form Inspeksi · Bagian ' . $step . ' dari ' . AMT_PTI_TOTAL,
            'target' => '[data-tour="pti-items"]',
            'title'  => 'Sama seperti tadi',
            'text'   => 'Jawab semua item di sisi ini, lalu ketuk “Selanjutnya”. Ulangi sampai langkah ' . AMT_PTI_TOTAL . '.',
            'button' => 'Mengerti',
            'done'   => null,
        ]]];
    }

    // Langkah 11: sorot CATATAN dulu (pengguna harus menulis), baru tombol Kirim.
    // 'settle' = tunggu pengguna berhenti mengetik sebentar, supaya kartu tidak
    // berpindah ke "Kirim" di tengah-tengah pengetikan.
    return ['journey' => 'pti', 'subKey' => 'amt_pti_form_11', 'steps' => [
        [
            'no'        => 6,
            'target'    => '#ptiNote',
            'highlight' => '[data-tour="pti-note"]',
            'title'     => 'Tulis catatan dulu',
            'text'      => 'Kolom Catatan wajib diisi. Tuliskan kondisi mobil tangki, misalnya “MT dalam kondisi baik.” Tombol Kirim baru menyala setelah catatan terisi.',
            'hint'      => '👆 Ketuk kolom Catatan, lalu ketik',
            'done'      => $next,
            'settle'    => 1500,
            'ok'        => '✅ Catatan sudah terisi',
        ],
        [
            'no'     => 7,
            'target' => '#ptiForm .pti-btn--send',
            'title'  => 'Ketuk “Kirim”',
            'text'   => 'Semua sudah lengkap. Ketuk “Kirim”, lalu konfirmasi pada pertanyaan yang muncul.',
            'hint'   => '👆 Ketuk tombol biru “Kirim”',
            'done'   => null,
        ],
        [
            // Popup konfirmasi (gambar: "Kirim Hasil Inspeksi"). Kartu ini baru muncul saat popup
            // terbuka; bila AMT mengetuk "Batal", kartu hilang dan muncul lagi saat popup dibuka ulang.
            'no'        => 8,
            'target'    => '#ptiConfirmYes',
            'highlight' => '[data-tour="pti-confirm"]',
            'when'      => '.pti-confirm.is-open',
            'title'     => 'Konfirmasi pengiriman',
            'text'      => 'Pastikan semua jawaban sudah benar, karena data yang sudah dikirim tidak bisa diubah lagi. Ketuk “Ya, kirim hasil inspeksi” untuk mengirim, atau “Batal” untuk memeriksa lagi.',
            'hint'      => '👆 Ketuk “Ya, kirim hasil inspeksi”',
            'done'      => null,
        ],
    ]];
}

/**
 * Tutorial Shipments (AMT).
 *  - amt_shipments        : arahkan ke "Lihat Detail Order" pada pengiriman berstatus Sedang Dikirim.
 *  - amt_shipment_detail  : arahkan ke kartu abu-abu SPBU (data SPBU + produk yang dibawa).
 * Pengiriman yang sudah selesai tidak diberi tutorial (hanya untuk dilihat).
 *
 * @return array{steps:array, journey:string}
 */
function amt_tour_ship_steps(string $screen): array
{
    if ($screen === 'amt_shipments') {
        return ['journey' => 'kirim', 'steps' => [[
            'no'     => 1,
            'label'  => 'Shipments',
            'target' => '[data-tour="ship-detail-active"]',
            'title'  => 'Buka detail order',
            'text'   => 'Pengiriman yang sedang berjalan berstatus “Sedang Dikirim”. Ketuk “Lihat Detail Order” pada pengiriman itu untuk melihat rutenya.',
            'hint'   => '👆 Ketuk “Lihat Detail Order” pada pengiriman Sedang Dikirim',
            'done'   => null,
        ]]];
    }

    // amt_shipment_detail: tutorial hanya untuk pengiriman yang sedang berjalan
    $s = amt_ship_current();
    if ($s === null || $s['status'] !== 'sedang') {
        return ['journey' => 'kirim', 'steps' => []];
    }
    return ['journey' => 'kirim', 'steps' => [[
        'no'     => 2,
        'label'  => 'Detail Order',
        'target' => '[data-tour="ship-spbu-card"]',
        'title'  => 'Buka kartu SPBU',
        'text'   => 'Kartu abu-abu ini berisi SPBU tujuan dan produk yang Anda bawa. Ketuk kartunya untuk membuka aktifitas di SPBU.',
        'hint'   => '👆 Ketuk kartu abu-abu “SPBU”',
        'done'   => null,
    ]]];
}

/**
 * Tutorial layar "Aktifitas di SPBU" (amt_spbu) untuk pengiriman yang sedang berjalan.
 *
 * Sebelum tiba (berbasis keadaan, jadi tidak "hilang" bila popup ditutup lalu dibuka lagi):
 *   3) ketuk kartu "Tiba di Lokasi"            -> selesai saat popup terbuka (#arrSheet.is-open)
 *   4) "Perbarui Lokasi", tunggu pin sesuai    -> selesai saat data-pin="1", lalu ditahan 2 detik
 *   5) ketuk "Ya, pengiriman telah tiba"       -> halaman memproses lalu kembali ke layar ini
 * Sesudah tiba: satu kartu pemberitahuan di tengah (langkah berikutnya: Isi Checklist).
 * Pengiriman yang sudah selesai tidak diberi tutorial.
 *
 * @return array{steps:array, journey:string, subKey:?string}
 */
function amt_tour_spbu_steps(): array
{
    $s = amt_ship_current();
    if ($s === null || $s['status'] !== 'sedang') {
        return ['journey' => 'kirim', 'subKey' => null, 'steps' => []];
    }

    if (amt_spbu_arrived($s)) {
        // Checklist sudah terkirim -> tutorial berpindah ke Verifikasi Order
        if (amt_pbk_done($s['id'])) {
            return amt_tour_verif_spbu_pack($s);
        }
        // Langsung arahkan ke kartu aktif "Isi Checklist" (membuka Daftar LO)
        $steps = [[
            'no'     => null,
            'label'  => 'Isi Checklist',
            'target' => '[data-tour="spbu-step-active"]',
            'title'  => 'Ketuk “Isi Checklist”',
            'text'   => 'Sebelum BBM dibongkar, Anda mengisi Checklist Pra-Pembongkaran. Ketuk kartu biru “Isi Checklist” untuk membuka Daftar LO.',
            'hint'   => '👆 Ketuk kartu biru “Isi Checklist”',
            'done'   => null,
        ]];
        return ['journey' => 'kirim', 'subKey' => 'amt_spbu_arrived', 'steps' => $steps];
    }

    return ['journey' => 'kirim', 'subKey' => null, 'steps' => [
        [
            'no'     => 3,
            'label'  => 'Aktifitas di SPBU',
            'target' => '[data-tour="spbu-step-active"]',
            'title'  => 'Ketuk “Tiba di Lokasi”',
            'text'   => 'Bila mobil tangki sudah sampai di SPBU tujuan, ketuk kartu biru “Tiba di Lokasi” untuk memberi tahu bahwa Anda sudah tiba.',
            'hint'   => '👆 Ketuk kartu biru “Tiba di Lokasi”',
            'done'   => '#arrSheet.is-open',
        ],
        [
            'no'        => 4,
            'label'     => 'Tiba di Lokasi',
            'target'    => '#arr-refresh',
            'highlight' => '#arr-loc',
            'title'     => 'Cek lokasi Anda',
            'text'      => 'Pin di peta harus ada di SPBU tujuan. Ketuk “Perbarui Lokasi”, lalu tunggu sampai pin muncul di peta dan tombol biru di bawahnya aktif.',
            'hint'      => '👆 Ketuk “Perbarui Lokasi”',
            'wait'      => '⏳ Menunggu lokasi sesuai… perhatikan pin di peta',
            'when'      => '#arrSheet.is-open',
            'done'      => '#arrSheet[data-pin="1"]',
            'needTap'   => true,
            'ok'        => '✅ Lokasi sudah sesuai',
            'okText'    => 'Lihat pin di peta: sudah tepat di SPBU tujuan.',
            'hold'      => 2000,   // tahan 2 detik setelah lokasi sesuai, baru lanjut
        ],
        [
            'no'     => 5,
            'label'  => 'Tiba di Lokasi',
            'target' => '#arr-yes',
            'when'   => '#arrSheet.is-open',
            'title'  => 'Ketuk “Ya, pengiriman telah tiba”',
            'text'   => 'Lokasi sudah sesuai. Ketuk tombol biru ini untuk mencatat bahwa mobil tangki sudah tiba di SPBU.',
            'hint'   => '👆 Ketuk tombol biru “Ya, pengiriman telah tiba”',
            'done'   => null,
        ],
    ]];
}

/**
 * Tutorial layar "Hasil Inspeksi" (baca saja): jelaskan GO / NO GO, lalu arahkan kembali
 * ke halaman PTI (←). 'remember' pada langkah pertama supaya tidak diulang.
 */
function amt_tour_pti_hasil_steps(): array
{
    $nogo = (amt_pti_submitted()['result'] ?? 'GO') === 'NO GO';
    return ['journey' => 'pti', 'subKey' => 'amt_pti_hasil', 'steps' => [
        [
            'no'       => null,
            'label'    => 'Hasil Inspeksi',
            'target'   => '[data-tour="hasil-banner"]',
            'title'    => $nogo ? 'Hasil: NO GO' : 'Hasil: GO',
            'text'     => $nogo
                ? 'Ada item yang Tidak layak, jadi mobil tangki belum boleh berangkat. Laporkan ke pengawas, lalu periksa ulang setelah diperbaiki.'
                : 'Semua item Layak, jadi mobil tangki siap beroperasi. Jika ada item Tidak layak, hasilnya NO GO dan Anda wajib lapor ke pengawas.',
            'button'   => 'Mengerti',
            'remember' => true,
            'done'     => null,
        ],
        [
            'no'       => null,
            'label'    => 'Hasil Inspeksi',
            'target'   => '[data-tour="hasil-list"]',
            'title'    => 'Jawaban Anda',
            'text'     => 'Daftar ini menampilkan jawaban Layak / Tidak layak untuk setiap sisi mobil tangki, beserta catatan Anda. Halaman ini hanya untuk dilihat.',
            'button'   => 'Mengerti',
            'remember' => true,
            'done'     => null,
        ],
        [
            'no'     => null,
            'label'  => 'Hasil Inspeksi',
            'target' => 'a.back-btn',
            'title'  => 'Kembali ke halaman PTI',
            'text'   => 'Ketuk panah kembali (←) di kiri atas untuk kembali ke halaman PTI, lalu kembali lagi ke Beranda.',
            'hint'   => '👆 Ketuk panah ← di kiri atas',
            'done'   => null,
        ],
    ]];
}

/**
 * Tutorial di BERANDA setelah hasil inspeksi PTI dikirim (popup "Ya, Kirim").
 *
 *  1) Kartu tahapan berikutnya; tertutup sendiri setelah AMT_PTI_DONE_VISIBLE_FOR (20 detik)
 *     bila tidak diketuk "Mengerti".
 *  2) AMT_PTI_DONE_SHIPMENT_DELAY (3 detik) setelah kartu 1 tertutup: sorotan ke ikon Shipments.
 *
 * Hasil NO GO: mobil tangki belum layak jalan, jadi AMT tidak diarahkan ke segel / SPBU;
 * hanya pemberitahuan untuk melapor ke pengawas.
 *
 * @return array{steps:array, journey:string, subKey:string}
 */
function amt_tour_pti_done_pack(int $checkins): array
{
    $subKey = 'amt_home_ptidone_' . $checkins;
    $result = amt_pti_state()['result'] ?? null;

    if ($result === 'NO GO') {
        return ['journey' => 'pti', 'subKey' => $subKey, 'steps' => [[
            'no'     => null,
            'label'  => 'PTI Selesai',
            'target' => null,
            'title'  => 'Hasil inspeksi: NO GO',
            'text'   => 'Mobil tangki belum layak beroperasi. Laporkan ke pengawas sebelum berangkat.',
            'button' => 'Mengerti',
            'done'   => null,
        ]]];
    }

    return ['journey' => 'pti', 'subKey' => $subKey, 'steps' => [
        [
            'no'       => null,
            'label'    => 'PTI Selesai',
            'target'   => null,
            'title'    => 'PTI selesai ✅',
            'text'     => 'PTI sudah selesai. Lanjutkan dengan tahapan berikut:',
            'list'     => [
                'Ambil segel.',
                'Scan segel.',
                'Lakukan Get In.',
                'Masuk ke Filling Shed.',
                'Lakukan Get Out.',
                'Ambil surat jalan.',
                'Menuju SPBU tujuan.',
            ],
            'button'   => 'Mengerti',
            'autoHide' => AMT_PTI_DONE_VISIBLE_FOR,      // hilang sendiri setelah 20 detik
            'remember' => true,                           // tidak diulang bila pengguna pindah halaman lalu kembali
            'done'     => null,
        ],
        [
            'no'     => null,
            'label'  => 'Langkah berikutnya',
            'target' => '[data-tour="amt-menu-shipments"]',
            'when'   => '[data-tour="amt-menu-shipments"]',
            'delay'  => AMT_PTI_DONE_SHIPMENT_DELAY,     // muncul 3 detik setelah kartu 1 tertutup
            'title'  => 'Buka menu “Shipments”',
            'text'   => 'Ketuk menu Shipments untuk membuka daftar pengiriman Anda.',
            'hint'   => '👆 Ketuk menu “Shipments” di kotak yang menyala',
            'done'   => null,
        ],
    ]];
}

/**
 * Tutorial "Daftar LO" (Checklist Pra-Pembongkaran), layar amt_checklist_lo.
 * Berbasis keadaan: ada LO berstatus "Draft" -> tutorial mengirim checklist; bila tidak -> tutorial
 * memilih LO lalu "Mulai Checklist". Elemen disorot lewat data-tour yang sudah ada di
 * roles/amt/views/pra_bongkar_lo.php (pbl-list, pbl-start, pbl-send).
 *
 * @return array{steps:array, journey:string, subKey:?string}
 */
function amt_tour_pbk_lo_steps(): array
{
    $s = amt_ship_current();
    if ($s === null || !amt_pbk_ship_ready($s)) {
        return ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    }

    $hasDraft = false;
    foreach (amt_pbk_lo_ids($s) as $lo) {
        if (amt_pbk_lo_status($s['id'], $lo) === 'draft') {
            $hasDraft = true;
            break;
        }
    }

    // ---- Ada LO "Draft": kirim checklist ----
    if ($hasDraft) {
        return ['journey' => 'checklist', 'subKey' => 'amt_pbl_draft', 'steps' => [
            [
                'no'     => null,
                'target' => '[data-tour="pbl-list"]',
                'title'  => 'Centang LO yang akan dikirim',
                'text'   => 'LO berstatus “Draft” sudah terisi tetapi BELUM dikirim ke SPBU. Centang LO Draft yang ingin dikirim. Tombol “Kirim” baru menyala setelah ada LO Draft yang dicentang.',
                'hint'   => '👆 Centang LO berstatus Draft',
                'done'   => '#pblSend:not(:disabled)',
                'ok'     => '✅ LO sudah dipilih',
            ],
            [
                'no'     => 5,
                'target' => '[data-tour="pbl-send"]',
                'title'  => 'Ketuk “Kirim”',
                'text'   => 'Ketuk “Kirim”, lalu akan muncul pertanyaan konfirmasi sebelum checklist benar-benar dikirim.',
                'hint'   => '👆 Ketuk tombol “Kirim”',
                'done'   => '#pblModal:not([hidden])',
            ],
            [
                // Pop up "Kirim Checklist" disorot penuh. Bila AMT mengetuk "Batal", kartu ini hilang dan
                // kartu "Ketuk Kirim" muncul lagi; kartu ini muncul lagi saat pop up dibuka ulang.
                'no'        => 6,
                'target'    => '#pblYes',
                'highlight' => '#pblModal .pbl-modal__card',
                'when'      => '#pblModal:not([hidden])',
                'title'     => 'Konfirmasi pengiriman',
                'text'      => 'Pastikan semua jawaban sudah benar, karena data yang sudah dikirim tidak bisa diubah lagi. Ketuk “Ya, Kirim” untuk mengirim, atau “Batal” untuk memeriksa lagi.',
                'hint'      => '👆 Ketuk “Ya, Kirim”',
                'done'      => null,
            ],
        ]];
    }

    // ---- Belum ada Draft: pilih LO lalu mulai checklist ----
    return ['journey' => 'checklist', 'subKey' => 'amt_pbl_belum', 'steps' => [
        [
            'no'     => 1,
            'label'  => 'Daftar LO',
            'target' => '[data-tour="pbl-list"]',
            'title'  => 'Pilih LO yang akan diisi',
            'text'   => 'Setiap kartu adalah satu LO yang dibawa mobil tangki. Centang LO yang akan dibongkar di SPBU ini, atau ketuk “Pilih Semua”. Status “Belum Diisi” berarti checklist LO itu belum dikerjakan.',
            'hint'   => '👆 Centang minimal satu LO',
            'done'   => '.pbl-card.is-selected',
            'ok'     => '✅ LO sudah dipilih',
        ],
        [
            'no'     => 2,
            'label'  => 'Daftar LO',
            'target' => '[data-tour="pbl-start"]',
            'title'  => 'Ketuk “Mulai Checklist”',
            'text'   => 'Anda akan mengisi 14 soal pemeriksaan sebelum pembongkaran BBM. Isi dengan jujur dan bertanggung jawab.',
            'hint'   => '👆 Ketuk tombol biru “Mulai Checklist”',
            'done'   => null,
        ],
    ]];
}

/**
 * Tutorial Checklist Pra-Pembongkaran (14 soal, satu soal per halaman), layar amt_checklist.
 *
 * Mengikuti panduan OneFIS AMT: ada 2 tipe soal yang wajib dijawab.
 *   - Verifikasi Tugas SPBU : tugas Petugas SPBU, AMT memeriksa lalu memilih "Tidak Dilakukan" / "Ya, Dilakukan".
 *   - Tugas AMT             : verifikasi pekerjaan sendiri + foto bukti (opsional).
 * Soal 7 membuat QR Code (Claim Losses; tombol "QR Code" muncul di kanan bawah sesudahnya).
 * Soal 11 dan 14 punya dua bagian (mandiri + "Verifikasi Tugas Role Lawan").
 *
 * Tiap tipe soal dijelaskan SEKALI (kunci tutorial per tipe), jadi 14 soal tidak diulang-ulang.
 * Tombol "?" menampilkan lagi penjelasan tipe soal yang sedang dibuka.
 * Elemen disorot memakai selector yang sudah ada di roles/amt/views/pra_bongkar.php.
 *
 * @return array{steps:array, journey:string, subKey:?string}
 */
function amt_tour_pbk_steps(): array
{
    $s = amt_ship_current();
    if ($s === null || !amt_pbk_ship_ready($s)) {
        return ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    }

    $defs  = amt_pbk_steps();
    $total = count($defs);
    $step  = isset($_GET['step']) ? (int) $_GET['step'] : 1;
    $step  = max(1, min($total, $step));
    $type  = $defs[$step]['type'];
    $next  = '#pbkNext:not(.is-locked)';   // tombol lanjut aktif = soal ini sudah lengkap dijawab

    // Kartu penutup tiap soal: ketuk "Selanjutnya" (tombol "Selesai" di soal terakhir)
    $nextCard = [
        'no'     => null,
        'label'  => 'Checklist Pra-Pembongkaran',
        'target' => '#pbkNext',
        'title'  => 'Ketuk “Selanjutnya”',
        'text'   => 'Ketuk “Selanjutnya” untuk ke soal berikutnya. Tombol “Sebelumnya” dipakai bila ingin memperbaiki jawaban.',
        'hint'   => '👆 Ketuk “Selanjutnya”',
        'done'   => null,
    ];

    // ---- Soal terakhir (14): dua bagian, lalu "Selesai" ----
    if ($step === $total) {
        return ['journey' => 'checklist', 'subKey' => 'amt_pbk_last', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Terakhir',
                'target' => '[data-tour="pbk-card"]',
                'title'  => 'Soal terakhir',
                'text'   => 'Jawab kedua bagian soal ini: verifikasi pekerjaan Anda sendiri, dan “Verifikasi Tugas Role Lawan” untuk tugas Petugas SPBU.',
                'hint'   => '👆 Jawab kedua bagian soal',
                'done'   => $next,
                'ok'     => '✅ Semua jawaban sudah dipilih',
            ],
            [
                'no'     => null,
                'label'  => 'Soal Terakhir',
                'target' => '#pbkNext',
                'title'  => 'Ketuk “Selesai”',
                'text'   => 'Jawaban tersimpan dan Anda kembali ke Daftar LO. LO yang baru diisi berstatus “Draft” dan baru terkirim setelah Anda mengetuk “Kirim”.',
                'hint'   => '👆 Ketuk tombol biru “Selesai”',
                'done'   => null,
            ],
        ]];
    }

    // ---- Soal 7: Generate QR Code ----
    if ($type === 'qr') {
        return ['journey' => 'checklist', 'subKey' => 'amt_pbk_qr', 'steps' => [
            [
                'no'     => 4,
                'label'  => 'Buat QR Code',
                'target' => '#pbkGen',
                'title'  => 'Buat QR Code',
                'text'   => 'Soal ini adalah tugas AMT yang diverifikasi SPBU lewat QR Code. Ketuk “Generate QR Code”. QR ini dipakai Petugas SPBU bila mengajukan Claim Losses (BBM yang diterima kurang atau tidak sesuai order).',
                'hint'   => '👆 Ketuk “Generate QR Code”',
                'done'   => '#pbkForm[data-qr="1"]',
            ],
            [
                // Pop up QR terbuka otomatis setelah Generate/Regenerate. Selesai bila pop up tertutup
                // (lewat "Tutup", ketukan di luar kartu, atau tombol Esc).
                'no'        => null,
                'label'     => 'QR Code Claim Loss',
                'target'    => '#pbkQrClose',
                'highlight' => '#pbkQrModal .pbk-modal__card',
                'when'      => '#pbkQrModal:not([hidden])',
                'title'     => 'QR Code Claim Loss',
                'text'      => 'Tunjukkan QR ini kepada Petugas SPBU hanya bila SPBU mengajukan Claim Losses. QR berlaku 3 menit; bila kedaluwarsa, ketuk “Regenerate” untuk membuat yang baru. Bila tidak ada Claim Losses, langsung tutup barcode ini dengan mengetuk “Tutup”.',
                'hint'      => '👆 Tidak ada Claim Losses? Ketuk “Tutup”',
                'done'      => '#pbkQrModal[hidden]',
            ],
            $nextCard,
        ]];
    }

    // ---- Soal dengan dua bagian (11) ----
    if ($type === 'dual') {
        return ['journey' => 'checklist', 'subKey' => 'amt_pbk_dual', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Dua Bagian',
                'target' => '[data-tour="pbk-card"]',
                'title'  => 'Soal dengan dua bagian',
                'text'   => 'Soal ini punya dua bagian yang wajib dijawab:',
                'list'   => [
                    'Bagian atas: verifikasi pekerjaan Anda sendiri. Pilih “Ya, dilakukan” atau “Tidak Dilakukan”. Foto bukti boleh dilewati.',
                    'Bagian bawah “Verifikasi Tugas Role Lawan”: pilih apakah Petugas SPBU sudah mengerjakan tugasnya.',
                ],
                'hint'   => '👆 Jawab kedua bagian soal',
                'done'   => $next,
                'ok'     => '✅ Semua jawaban sudah dipilih',
            ],
            $nextCard,
        ]];
    }

    // ---- Tugas AMT (tipe "self"): jawab + foto bukti opsional ----
    if ($type === 'self') {
        return ['journey' => 'checklist', 'subKey' => 'amt_pbk_self', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Tugas AMT',
                'target' => '[data-tour="pbk-card"]',
                'title'  => 'Soal Tugas AMT',
                'text'   => 'Soal ini adalah tugas Anda sendiri. Kerjakan tugasnya, lalu pilih “Ya, Dilakukan” bila sudah, atau “Tidak Dilakukan” bila belum. Jawab sesuai kenyataan.',
                'hint'   => '👆 Pilih salah satu jawaban',
                'done'   => $next,
                'ok'     => '✅ Jawaban sudah dipilih',
            ],
            $nextCard,
        ]];
    }

    // ---- Verifikasi Tugas SPBU (tipe "spbu_task"); soal 1 diawali pengantar 14 soal ----
    $steps = [];
    $steps[] = [
        'no'     => 3,
        'label'  => 'Soal Verifikasi Tugas SPBU',
        'target' => '[data-tour="pbk-card"]',
        'title'  => 'Soal Verifikasi Tugas SPBU',
        'text'   => 'Soal ini adalah tugas yang dikerjakan Petugas SPBU. Periksa langsung di lapangan, lalu pilih “Ya, Dilakukan” bila sudah dikerjakan Petugas SPBU, atau “Tidak Dilakukan” bila belum.',
        'hint'   => '👆 Pilih salah satu jawaban',
        'done'   => $next,
        'ok'     => '✅ Jawaban sudah dipilih',
    ];
    $steps[] = $nextCard;
    return ['journey' => 'checklist', 'subKey' => 'amt_pbk_spbu', 'steps' => $steps];
}

/**
 * Tutorial layar "Aktifitas di SPBU" SESUDAH checklist terkirim: arahkan ke "Verifikasi Order".
 * Kunci tutorial dibedakan dari tahap sebelumnya supaya tidak dianggap "sudah selesai".
 *
 * @return array{steps:array, journey:string, subKey:string}
 */
function amt_tour_verif_spbu_pack(array $s): array
{
    if (amt_verif_is_done($s)) {
        return ['journey' => 'kirim', 'subKey' => 'amt_spbu_verif_done', 'steps' => [[
            'no'       => null,
            'label'    => 'Verifikasi Order',
            'target'   => null,
            'title'    => 'Verifikasi Order selesai ✅',
            'text'     => 'Semua LO sudah terverifikasi. Langkah berikutnya adalah “Foto Surat Jalan”.',
            'button'   => 'Mengerti',
            'remember' => true,
            'done'     => null,
        ]]];
    }
    return ['journey' => 'kirim', 'subKey' => 'amt_spbu_verif', 'steps' => [
        [
            'no'     => null,
            'label'  => 'Verifikasi Order',
            'target' => '[data-tour="spbu-step-active"]',
            'title'  => 'Ketuk “Verifikasi Order”',
            'text'   => 'Checklist sudah terkirim. Ketuk kartu biru “Verifikasi Order” untuk membuka daftar LO yang akan diverifikasi.',
            'hint'   => '👆 Ketuk kartu biru “Verifikasi Order”',
            'done'   => null,
        ],
    ]];
}

/**
 * Tutorial Verifikasi Order (AMT): amt_verifikasi, amt_verifikasi_qr, amt_verifikasi_kode, amt_verifikasi_sukses.
 *
 * Mengikuti panduan OneFIS AMT:
 *   1) pilih LO yang sudah "Sudah Diisi" form bongkarnya (dipilih Petugas SPBU),
 *   2) pilih metode: Pindai Kode QR atau Kode Konfirmasi (seperti OTP); bila satu bermasalah pakai yang lain,
 *   3) konfirmasi langsung ke Petugas SPBU bahwa permintaan verifikasi (notifikasi terbaru) sudah terkirim,
 *      lalu pindai QR / ketik kode,
 *   4) hasil "Order Berhasil Diverifikasi" -> "Oke".
 * Elemen disorot: wrapper data-tour="verif-list" dan "verif-methods" (roles/amt/views/amt_verifikasi.php)
 * serta kelas/ID yang sudah ada di tiga layar lainnya.
 *
 * @return array{steps:array, journey:string, subKey:?string}
 */
function amt_tour_verif_steps(string $screen): array
{
    $none = ['journey' => 'verifikasi', 'subKey' => null, 'steps' => []];
    $ship = amt_ship_find(amt_verif_ship_id() ?: null);
    if ($ship === null || $ship['status'] !== 'sedang') {
        return $none;
    }
    $sim = AMT_VERIF_SIM_NOTE;

    // ---- Daftar LO + Metode Verifikasi ----
    if ($screen === 'amt_verifikasi') {
        if (amt_verif_is_done($ship)) {
            return $none;
        }
        return ['journey' => 'verifikasi', 'subKey' => 'amt_verif_list', 'steps' => [
            [
                'no'        => 1,
                'label'     => 'Verifikasi Order',
                'target'    => '.verif-lo:not(.is-disabled)',
                'highlight' => '[data-tour="verif-list"]',
                'title'     => 'Pilih LO yang akan diverifikasi',
                'text'      => 'Hanya LO yang “Form Bongkar”-nya sudah “Sudah Diisi” yang bisa dipilih. Ketuk kartu LO (atau kotak di kanan kartu) untuk mencentang. Bila daftar belum berubah, ketuk “Refresh”.',
                'hint'      => '👆 Centang minimal satu LO',
                'done'      => '.verif-lo.is-selected',
            ],
            [
                'no'        => 2,
                'label'     => 'Verifikasi Order',
                'target'    => 'a.verif-method.is-on',
                'highlight' => '[data-tour="verif-methods"]',
                'title'     => 'Pilih metode verifikasi',
                'text'      => 'Ada dua metode. “Pindai Kode QR”: Anda memindai QR dari HP Petugas SPBU. “Kode Konfirmasi”: Anda mengetik kode 6 angka seperti kode OTP. Bila satu metode bermasalah, pakai metode yang lain.',
                'hint'      => '👆 Ketuk “Pindai Kode QR” atau “Kode Konfirmasi”',
                'done'      => null,
            ],
        ]];
    }

    // ---- Pindai Kode QR ----
    if ($screen === 'amt_verifikasi_qr') {
        return ['journey' => 'verifikasi', 'subKey' => 'amt_verif_qr', 'steps' => [
            [
                'no'       => 3,
                'label'    => 'Pindai Kode QR',
                'target'   => '#vqrFrame',
                'title'    => 'Konfirmasi ke Petugas SPBU',
                'text'     => 'Minta Petugas SPBU membuka aplikasinya dan memastikan permintaan verifikasi (notifikasi terbaru) sudah terkirim. Lalu arahkan kamera ke kode QR di HP Petugas SPBU. Waktu pindai 2 menit, lihat hitungan mundur di bawah.',
                'hint'     => '👆 Ketuk “Mengerti” bila Petugas SPBU sudah siap',
                'button'   => 'Mengerti',
                'skip'     => '.vqr.is-expired',
                'done'     => null,
            ],
            [
                'no'     => null,
                'label'  => 'Pindai Kode QR',
                'target' => '#vqrNow',
                'title'  => $sim ? 'Simulasikan scan' : 'Pindai kode QR',
                'text'   => $sim
                    ? 'Aplikasi ini masih mode simulasi dan kamera tidak dinyalakan. Ketuk “Simulasikan Scan” sebagai pengganti memindai QR. Bila waktu habis, ketuk “Coba Lagi”.'
                    : 'Arahkan kamera ke QR di HP Petugas SPBU. Bila waktu habis, ketuk “Coba Lagi”.',
                'hint'   => '👆 Ketuk “Simulasikan Scan”',
                'skip'   => '.vqr.is-expired',
                'done'   => null,
            ],
        ]];
    }

    // ---- Kode Konfirmasi ----
    if ($screen === 'amt_verifikasi_kode') {
        return ['journey' => 'verifikasi', 'subKey' => 'amt_verif_kode', 'steps' => [
            [
                'no'        => 3,
                'label'     => 'Kode Konfirmasi',
                'target'    => '#vkodeBoxes',
                'highlight' => '.vkode-card',
                'title'     => 'Minta kode ke Petugas SPBU',
                'text'      => 'Minta Petugas SPBU membuka aplikasinya dan memastikan permintaan verifikasi (notifikasi terbaru) sudah terkirim. Petugas SPBU akan menyebutkan kode 6 angka, seperti kode OTP. Kode berlaku 3 menit.',
                'hint'      => '👆 Ketuk “Mengerti” bila kode sudah Anda terima',
                'button'    => 'Mengerti',
                'remember'  => true,    // tidak diulang bila halaman dimuat ulang setelah kode salah
                'skip'      => '#vkodeExpired:not([hidden])',
                'done'      => null,
            ],
            [
                'no'        => null,
                'label'     => 'Kode Konfirmasi',
                'target'    => '#vkodeBoxes',
                'highlight' => '.vkode-card',
                'title'     => 'Ketik 6 angka kode',
                'text'      => 'Ketuk kotak pertama lalu ketik semua angka. Setelah kotak terakhir terisi, kode terkirim otomatis.'
                    . ($sim ? ' Mode simulasi: angka apa saja diterima.' : '')
                    . ' Bila kode kedaluwarsa, ketuk “Kirim ulang kode” atau pakai metode Pindai Kode QR.',
                'hint'      => '👆 Ketuk kotak angka, lalu ketik kode',
                'skip'      => '#vkodeExpired:not([hidden])',
                'done'      => null,
            ],
        ]];
    }

    // ---- Order Berhasil Diverifikasi ----
    if ($screen === 'amt_verifikasi_sukses') {
        $more = !amt_verif_is_done($ship);
        return ['journey' => 'verifikasi', 'subKey' => 'amt_verif_sukses', 'steps' => [
            [
                'no'       => 4,
                'label'    => 'Order Terverifikasi',
                'target'   => '.vok-card',
                'title'    => 'Verifikasi berhasil ✅',
                'text'     => 'Nomor LO pada kartu ini sudah terverifikasi.'
                    . ($more ? ' Masih ada LO yang belum diverifikasi: setelah “Oke”, ulangi langkah yang sama untuk LO tersebut.' : ''),
                'button'   => 'Mengerti',
                'remember' => true,
                'done'     => null,
            ],
            [
                'no'     => null,
                'label'  => 'Order Terverifikasi',
                'target' => '.vok-btn',
                'title'  => 'Ketuk “Oke”',
                'text'   => 'Ketuk “Oke” untuk kembali ke Aktifitas di SPBU.',
                'hint'   => '👆 Ketuk tombol biru “Oke”',
                'done'   => null,
            ],
        ]];
    }

    return $none;
}

/**
 * Data langkah untuk satu layar.
 *
 * @param string $screen   nama layar
 * @param bool   $running  true bila timer Waktu Kerja sedang berjalan
 * @param int    $checkins jumlah Check-In yang sudah berhasil (0 = belum ada)
 * @return array{steps:array, journey:string, startDelay?:int, subKey?:?string}
 */
function amt_tour_steps_for(string $screen, bool $running, int $checkins = 0): array
{
    // ---- Beranda AMT ----
    if ($screen === amt_home_screen()) {
        // Check-In sudah berhasil -> AMT diberi tahu untuk melakukan DCU (cek
        // kesehatan) lebih dulu. DCU dilakukan LANGSUNG di lokasi, bukan lewat
        // aplikasi, jadi tutorial ini hanya pemberitahuan (kartu di tengah).
        // Muncul AMT_TOUR_DCU_DELAY_MS setelah halaman dibuka.
        if ($running && $checkins > 0) {
            // Order sudah diselesaikan -> scan segel di AVM dulu (bukan di aplikasi), lalu Check-Out
            $outPack = amt_out_tour_pack();
            if ($outPack !== null) {
                return $outPack;
            }
            // Hasil inspeksi PTI baru dikirim -> tahapan berikutnya + arahkan ke Shipments
            if (amt_flow_get('pti_done') && amt_pti_is_done()) {
                return amt_tour_pti_done_pack($checkins);
            }
            $dcuSeen = amt_flow_get('dcu_seen');
            $steps = [[
                'no'       => null,
                'label'    => 'Check-In Berhasil',
                'target'   => null,
                'title'    => 'Lakukan DCU dulu 🩺',
                'text'     => 'Check-In berhasil. Sekarang lakukan DCU (cek kesehatan) terlebih dahulu. DCU dilakukan langsung di tempat, bukan lewat aplikasi ini. Menu PTI akan menyala setelah kartu ini ditutup.',
                'button'   => 'Mengerti',
                'autoHide' => AMT_DCU_VISIBLE_FOR,                    // tertutup sendiri setelah 7 detik
                'announce' => true,                                   // PTI mulai menyala 2 detik setelah ini
                'skip'     => '.amt-home[data-dcu-seen="1"]',         // DCU sudah dibaca (halaman dimuat ulang)
                'done'     => null,
            ]];
            // Sesudah DCU: arahkan ke menu PTI (kecuali inspeksi sudah selesai)
            if (!amt_pti_is_done()) {
                $steps[] = [
                    'no'     => 1,
                    'target' => '[data-tour="amt-menu-pti"]',
                    'when'   => 'a[data-tour="amt-menu-pti"]',       // baru muncul setelah menu PTI menyala
                    'delay'  => AMT_PTI_HINT_DELAY,
                    'title'  => 'Buka menu “PTI”',
                    'text'   => 'PTI adalah pemeriksaan mobil tangki sebelum berangkat. Menu PTI sekarang sudah menyala.',
                    'hint'   => '👆 Ketuk menu “PTI”',
                    'done'   => null,
                ];
            }
            return [
                'journey'    => count($steps) > 1 ? 'pti' : 'dcu',
                'startDelay' => $dcuSeen ? 0 : AMT_TOUR_DCU_DELAY_MS,
                'steps'      => $steps,
            ];
        }
        if ($running) {
            // Sesudah Check-Out: tutorial mengarahkan ke Start / End -> End Work.
            $afterPack = amt_out_tour_after_pack();
            if ($afterPack !== null) {
                return $afterPack;
            }
            return ['journey' => 'checkin', 'steps' => [[
                'no'     => 1,
                'target' => '[data-tour="amt-menu-checkin"]',
                'title'  => 'Absen masuk berhasil 🎉',
                'text'   => 'Waktu Kerja sudah berjalan dan menu Check-In sekarang menyala. Ketuk Check-In saat Anda mendapat tugas pengiriman BBM. PTI menyala setelah Check-In dan DCU; Check-Out belum bisa dipakai.',
                'hint'   => '👆 Ketuk menu “Check-In”',
                'done'   => null,
            ]]];
        }
        return ['journey' => 'masuk', 'steps' => [
            [
                'no'     => null,
                'target' => null,
                'title'  => 'Selamat datang di OneFIS 👋',
                'text'   => 'Kita mulai dari absen masuk kerja. Ikuti saja kotak yang menyala, hanya 6 langkah singkat.',
                'button' => 'Mulai',
                'done'   => null,
            ],
            [
                'no'     => 1,
                'target' => '[data-tour="amt-menu-start_end"]',
                'title'  => 'Buka menu “Start / End”',
                'text'   => 'Setiap hari kerja diawali dengan absen. Menu yang abu-abu belum bisa dipakai sebelum Anda absen.',
                'hint'   => '👆 Ketuk menu “Start / End”',
                'done'   => null,
            ],
        ]];
    }

    // ---- Start / End Work ----
    if ($screen === 'start_end') {
        // Sesudah Check-Out: AMT diarahkan mengakhiri kerja (tanpa tombol "Mengerti", langsung End Work)
        if ($running && amt_out_after_checkout()) {
            return ['journey' => 'pulang', 'steps' => [[
                'no'     => 1,
                'target' => '[data-tour="btn-end-work"]',
                'title'  => 'Ketuk “End Work”',
                'text'   => 'Check-Out sudah selesai, jadi waktunya mengakhiri waktu kerja. Ketuk “End Work”, lalu isi form-nya.',
                'hint'   => '👆 Ketuk tombol “End Work”',
                'done'   => null,
            ]]];
        }
        if ($running) {
            return ['journey' => 'pulang', 'steps' => [[
                'no'     => 1,
                'target' => '[data-tour="btn-end-work"]',
                'title'  => 'Absen pulang: “End Work”',
                'text'   => 'Saat jam kerja selesai, ketuk “End Work”. Kalau belum waktunya pulang, ketuk “Mengerti” saja.',
                'hint'   => '👆 Ketuk “End Work” saat sudah selesai bekerja',
                'button' => 'Mengerti',
                'done'   => null,
            ]]];
        }
        return ['journey' => 'masuk', 'steps' => [[
            'no'     => 2,
            'target' => '[data-tour="btn-start-work"]',
            'title'  => 'Ketuk “Start Work”',
            'text'   => 'Ini tombol absen masuk. Pastikan Anda sudah berada di area terminal.',
            'hint'   => '👆 Ketuk tombol hijau “Start Work”',
            'done'   => null,
        ]]];
    }

    // ---- Form ----
    if ($screen === 'start_work') {
        return ['journey' => 'masuk', 'steps' => amt_tour_form_steps('start')];
    }
    if ($screen === 'checkin') {
        return ['journey' => 'checkin', 'steps' => amt_tour_form_steps('checkin')];
    }
    if ($screen === 'end_work') {
        return ['journey' => 'pulang', 'steps' => amt_tour_form_steps('end')];
    }

    // ---- Shipments: daftar & Detail Order ----
    if ($screen === 'amt_shipments' || $screen === 'amt_shipment_detail') {
        $pack = amt_tour_ship_steps($screen);
        return ['journey' => $pack['journey'], 'steps' => $pack['steps']];
    }

    // ---- Aktifitas di SPBU (Tiba di Lokasi) ----
    if ($screen === 'amt_spbu') {
        $pack = amt_tour_spbu_steps();
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    // ---- Checklist Pra-Pembongkaran: Daftar LO + 14 soal ----
    if ($screen === 'amt_checklist_lo' || $screen === 'amt_checklist') {
        $pack = $screen === 'amt_checklist_lo' ? amt_tour_pbk_lo_steps() : amt_tour_pbk_steps();
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    // ---- Verifikasi Order ----
    if (in_array($screen, AMT_VERIF_SCREENS, true)) {
        $pack = amt_tour_verif_steps($screen);
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    // ---- Check-Out: lokasi, aktivitas, foto, kirim ----
    if ($screen === AMT_OUT_SCREEN) {
        return ['journey' => 'checkout', 'steps' => amt_out_tour_form_steps()];
    }

    // ---- Hasil Inspeksi ----
    if ($screen === 'amt_pti_hasil') {
        $pack = amt_tour_pti_hasil_steps();
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    // ---- PTI ----
    if ($screen === 'amt_pti' || $screen === 'amt_pti_form') {
        $pack = amt_tour_pti_steps($screen);
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    return ['journey' => 'masuk', 'steps' => []];
}

/**
 * Konfigurasi tutorial AMT untuk sebuah layar.
 * 'subKey' memisahkan catatan "sudah selesai" untuk keadaan berbeda pada
 * layar yang sama (beranda sebelum / sesudah Start Work, dst).
 *
 * @return array{steps:array, subKey:?string, journey:?array, startDelay:int, epoch:string, persist:bool, screenOrder:array, screenLabels:array}
 */
function amt_tour_config(string $screen): array
{
    $running  = work_is_running();
    $checkins = $running ? count(work_checkins()) : 0;
    $pack     = amt_tour_steps_for($screen, $running, $checkins);
    $subKey   = null;

    if ($running && $screen === amt_home_screen()) {
        // Tiap Check-In berhasil punya catatan sendiri, jadi DCU diingatkan lagi
        // setelah Check-In berikutnya (bukan hanya sekali seumur hidup).
        // Kunci dari paket langkah (mis. tutorial "PTI selesai") diutamakan.
        $subKey = !empty($pack['subKey'])
            ? $pack['subKey']
            : ($checkins > 0 ? 'amt_home_dcu_' . $checkins : 'amt_home_running');
    } elseif ($running && $screen === 'start_end') {
        $subKey = 'start_end_running';
    } elseif (!empty($pack['subKey'])) {
        $subKey = $pack['subKey'];   // layar PTI menentukan kuncinya sendiri
    }

    return [
        'steps'        => $pack['steps'],
        'subKey'       => $subKey,
        'journey'      => AMT_TOUR_JOURNEYS[$pack['journey']] ?? null,
        'startDelay'   => (int) ($pack['startDelay'] ?? 0),
        'epoch'        => amt_tour_epoch(),
        // Layar form/aksi: JANGAN catat "selesai" permanen (Kirim bisa ditolak server
        // lalu halaman dimuat ulang) -> tutorial tampil lagi otomatis setiap dibuka.
        // Layar SPBU sebelum tiba juga tidak dicatat selesai: bila pengguna keluar lalu kembali
        // sebelum menekan "Ya, pengiriman telah tiba", tutorial tampil lagi. Kartu "tercatat" (sesudah
        // tiba) tetap dicatat supaya hanya tampil sekali.
        'persist'      => !in_array($screen, ['start_work', 'checkin', 'checkout', 'end_work', 'start_end'], true)
                          && !($screen === 'amt_spbu' && empty($pack['subKey'])),
        'screenOrder'  => AMT_TOUR_SCREEN_ORDER,
        'screenLabels' => AMT_TOUR_SCREEN_LABELS,
    ];
}