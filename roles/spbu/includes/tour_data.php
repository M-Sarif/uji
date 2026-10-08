<?php
/* ============================================================
 * OneFIS - SPBU - Data tutorial terpandu (guided tour)
 *
 * Mesin tutorial: roles/spbu/assets/js/tutorial.js   (salinan mesin AMT, namespace SPBU)
 * Tampilan       : roles/spbu/assets/css/tutorial.css (salinan gaya AMT, awalan "sptt-")
 * Penyusun konfigurasi per layar: roles/spbu/includes/tour.php
 *
 * Tutorial SPBU SEKARANG memakai pola yang sama persis dengan tutorial AMT
 * (lihat roles/amt/includes/tutorial.php):
 *
 * CARA KERJA (dirancang untuk orang awam)
 * ---------------------------------------
 * Tutorial BERBASIS KONDISI, bukan berbasis ketukan. Mesin selalu menampilkan
 * langkah PERTAMA yang belum selesai (dibaca dari keadaan halaman), jadi tutorial
 * tidak pernah "hilang" walau pengguna melompat-lompat, menutup pop up, atau
 * mengisi form tidak berurutan. Kartu petunjuk menempel di bawah layar (atau pindah
 * ke atas bila menutupi bagian yang disorot), lengkap dengan tombol "Aa" (ukuran huruf),
 * "Kecilkan", "Penjelasan" (lembar baca + suara), "Lewati", dan tombol "?" mengambang.
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
 *   'ok'        => pesan singkat saat langkah baru saja selesai
 *   'minShow'   => (opsional) ms; langkah tetap disorot minimal selama itu walau 'done' terpenuhi
 *   'condHint'  => (opsional) teks kotak biru selama masa 'minShow'
 *   'settle'    => (opsional) ms; 'done' baru terpenuhi bila pengguna berhenti mengetik selama itu
 *   'when'      => (opsional) selector; kartu BARU muncul bila elemen ini ada (mis. pop up terbuka)
 *   'optional'  => (opsional) true bersama 'when': bila elemen 'when' tidak ada, langkah dianggap
 *                  SELESAI (dilewati) -- dipakai untuk langkah di dalam pop up
 *   'skip'      => (opsional) selector; langkah dianggap selesai bila elemen ini ada
 *   'nodim'     => (opsional) true = area lain tidak digelapkan
 *   'list'      => (opsional) array teks; tampil sebagai daftar bernomor di bawah 'text'
 *   'remember'  => (opsional) true = langkah yang sudah dibaca tidak diulang
 *   'button'    => (opsional) label tombol di kartu (mis. "Mulai", "Mengerti") -> langkah baca
 *   'skipLabel' / 'restart' / 'notice' / 'final' => sama seperti AMT
 *
 * Halaman TIDAK digulir otomatis oleh tutorial.
 *
 * Status layar dibaca dari atribut data-* / class yang dipasang view SPBU:
 *   #arrivalForm[data-all="1"]      ketiga kartu Tiba di Lokasi sudah dijawab
 *   .subject-card.is-answered       kartu verifikasi yang sudah dijawab
 *   [data-all-done="1"]             grup (Produk / Segel / LO pengukuran / Konfirmasi LO) sudah lengkap
 *   #claimForm[data-ready="1"]      semua kolom form pengukuran terisi
 *   #ratingForm[data-rated="n"]     jumlah kartu bintang yang sudah dinilai
 *   #ratingForm[data-complete="1"]  semua kartu bintang sudah dinilai
 *   .rating-card.is-rated           kartu bintang yang sudah dinilai
 * ============================================================ */

// Layar yang dilalui alur SPBU (untuk label bila langkah tidak punya alur bernomor)
const SPBU_TOUR_SCREEN_ORDER = [
    'dashboard', 'shipments_list', 'shipment', 'verification',
    'lo_list', 'checklist', 'claim_loss', 'konfirmasi_lo', 'notifikasi', 'qr_code', 'rating', 'done',
];

const SPBU_TOUR_SCREEN_LABELS = [
    'dashboard'      => 'Beranda SPBU',
    'shipments_list' => 'Shipments',
    'shipment'       => 'Aktifitas di SPBU',
    'verification'   => 'Tiba di Lokasi',
    'lo_list'        => 'Daftar LO',
    'checklist'      => 'Checklist Pra-Pembongkaran',
    'claim_loss'     => 'Metode Pengukuran',
    'konfirmasi_lo'  => 'Konfirmasi LO',
    'notifikasi'     => 'Notifikasi',
    'qr_code'        => 'Verifikasi Order',
    'rating'         => 'Rating Petugas AMT',
    'done'           => 'Pengiriman Selesai',
];

// Alur tutorial; 'total' = jumlah langkah bernomor pada alur itu
const SPBU_TOUR_JOURNEYS = [
    // Pengiriman: 1 buka menu Shipments, 2 buka Detail Order, 3 ketuk "Tiba di Lokasi"
    'kirim'      => ['label' => 'Pengiriman', 'total' => 3],
    // Tiba di Lokasi: 1 verifikasi Mobil Tangki + AMT 1 + AMT 2, 2 ketuk Simpan, 3 kirim verifikasi
    'tiba'       => ['label' => 'Tiba di Lokasi', 'total' => 3],
    // Checklist Pra-Pembongkaran: 1 pilih LO, 2 Mulai Checklist, 3 jawab soal, 4 soal 6 (SPP: Produk + Segel),
    // 5 soal 7 (form pengukuran), 6 Konfirmasi LO, 7 ketuk Kirim, 8 konfirmasi "Ya, Kirim"
    'checklist'  => ['label' => 'Checklist Pra-Pembongkaran', 'total' => 8],
    // Verifikasi Order: 1 buka kartu Verifikasi Order, 2 terima notifikasi AMT, 3 berikan QR / kode ke AMT, 4 Beri Penilaian
    'verifikasi' => ['label' => 'Verifikasi Order', 'total' => 4],
    // Rating Petugas AMT: 1 bintang AMT 1, 2 Selanjutnya, 3 bintang AMT 2, 4 Kirim
    'rating'     => ['label' => 'Rating Petugas AMT', 'total' => 4],
    // Semua aktifitas selesai (kartu penutup, tanpa nomor langkah)
    'selesai'    => ['label' => 'Aktifitas Selesai', 'total' => 1],
];

/** LO yang sedang dikerjakan (dicentang di Daftar LO; semua LO bila belum ada yang dicentang) -- sama seperti di views. */
function spbu_tour_active_lo_ids(): array
{
    $ids = array_keys(array_filter($_SESSION['lo_checked'] ?? []));
    return !empty($ids) ? $ids : array_keys(LO_LIST);
}

/** Kartu penutup tiap soal: ketuk "Selanjutnya" (di soal terakhir, menuju layar Konfirmasi LO). */
function spbu_tour_next_card(bool $last = false): array
{
    return [
        'no'     => null,
        'label'  => 'Checklist Pra-Pembongkaran',
        'target' => '[data-tour="wizard-next"]',
        'title'  => 'Ketuk “Selanjutnya”',
        'text'   => $last
            ? 'Ini soal checklist yang terakhir. Ketuk “Selanjutnya” untuk membuka layar Konfirmasi LO. Tombol “Sebelumnya” dipakai bila ingin memperbaiki jawaban.'
            : 'Ketuk “Selanjutnya” untuk ke soal berikutnya. Tombol “Sebelumnya” dipakai bila ingin memperbaiki jawaban.',
        'hint'   => '👆 Ketuk “Selanjutnya”',
        'done'   => null,
    ];
}

/* ------------------------------------------------------------
 * Beranda SPBU (dashboard)
 *   $finished = seluruh aktifitas SPBU baru saja tuntas sebelum kembali ke beranda
 *               -> kartu ucapan selamat (tetap tampil walau tutorial dimatikan)
 * ------------------------------------------------------------ */
function spbu_tour_dashboard_steps(bool $finished): array
{
    $welcome = $finished
        ? [
            'no'        => null,
            'label'     => 'Tutorial Selesai',
            'target'    => null,
            'notice'    => true,     // tetap tampil walau tutorial dimatikan (alur selesai menutup tutorial)
            'restart'   => true,     // "Mulai" menyalakan tutorial lagi dari awal
            'title'     => 'Selamat! Tutorial selesai 🎉',
            'text'      => 'Selamat, Anda telah menyelesaikan seluruh Aktifitas di SPBU. Klik “Mulai” untuk mengulang dari awal, atau pilih “Sudah cukup sampai di sini”.',
            'button'    => 'Mulai',
            'skipLabel' => 'Sudah cukup sampai di sini',
            'done'      => null,
        ]
        : [
            'no'     => null,
            'target' => null,
            'title'  => 'Selamat datang di OneFIS 👋',
            'text'   => 'Kita mulai dari mobil tangki yang tiba di SPBU Anda. Ikuti saja kotak yang menyala; setiap aktifitas di SPBU dipandu langkah demi langkah sampai serah terima BBM selesai.',
            'button' => 'Mulai',
            'done'   => null,
        ];

    return ['journey' => 'kirim', 'subKey' => null, 'steps' => [
        $welcome,
        [
            'no'     => 1,
            'target' => '[data-tour="menu-shipment"]',
            'title'  => 'Buka menu “Shipments”',
            'text'   => 'Menu Shipments berisi daftar pengiriman BBM yang menuju SPBU Anda. Ketuk menu ini untuk melihatnya.',
            'hint'   => '👆 Ketuk menu “Shipments”',
            'done'   => null,
        ],
    ]];
}

/* ------------------------------------------------------------
 * Shipments (daftar)
 * ------------------------------------------------------------ */
function spbu_tour_shipments_steps(): array
{
    // Order sudah selesai seluruhnya: tidak ada yang perlu dipandu
    if ((int) ($_SESSION['activity_done'] ?? 0) >= count(ACTIVITY_STEPS)) {
        return ['journey' => 'kirim', 'subKey' => null, 'steps' => []];
    }
    return ['journey' => 'kirim', 'subKey' => null, 'steps' => [[
        'no'        => 2,
        'label'     => 'Shipments',
        'target'    => '[data-tour="lihat-detail-btn"]',
        'highlight' => '[data-tour="lihat-detail"]',
        'title'     => 'Buka detail order',
        'text'      => 'Pengiriman yang sedang berjalan berstatus “Dikirim”. Ketuk “Lihat Detail” pada pengiriman itu untuk melihat tahapan Aktifitas di SPBU.',
        'hint'      => '👆 Ketuk “Lihat Detail” pada pengiriman Dikirim',
        'done'      => null,
    ]]];
}

/* ------------------------------------------------------------
 * Detail Order -> tab Aktifitas di SPBU. Satu paket langkah per tahap
 * ($_SESSION['activity_done'] = 0..4); kunci tutorial dibedakan per tahap
 * supaya tutorial tahap lain tidak dianggap "sudah selesai".
 * ------------------------------------------------------------ */
function spbu_tour_shipment_steps(): array
{
    $done  = (int) ($_SESSION['activity_done'] ?? 0);
    $total = count(ACTIVITY_STEPS);

    // Sebelum tiba: kartu "Tiba di Lokasi" redup ±5 detik, lalu menyala sendiri
    if ($done === 0) {
        return ['journey' => 'kirim', 'subKey' => 'shipment_0', 'steps' => [
            [
                'no'       => null,
                'label'    => 'Tiba di Lokasi',
                'target'   => '#arriveItem',
                'title'    => 'Menunggu mobil tangki tiba',
                'text'     => 'Mobil tangki sedang menuju SPBU Anda. Kartu “Tiba di Lokasi” masih redup dan akan menyala biru sendiri sekitar 5 detik lagi.',
                'hint'     => '⏳ Tunggu sebentar sampai kartu menyala biru',
                'when'     => '#arriveItem',
                'optional' => true,    // kartu sudah menyala -> langkah ini dilewati
                'done'     => '[data-tour="activity-active"]',
            ],
            [
                'no'     => 3,
                'label'  => 'Aktifitas di SPBU',
                'target' => '[data-tour="activity-active"]',
                'title'  => 'Ketuk “Tiba di Lokasi”',
                'text'   => 'Mobil tangki sudah tiba. Ketuk kartu biru “Tiba di Lokasi” untuk memverifikasi Mobil Tangki dan kedua AMT.',
                'hint'   => '👆 Ketuk kartu biru “Tiba di Lokasi”',
                'done'   => null,
            ],
        ]];
    }

    if ($done === 1) {
        return ['journey' => 'checklist', 'subKey' => 'shipment_1', 'steps' => [[
            'no'     => null,
            'label'  => 'Isi Checklist',
            'target' => '[data-tour="activity-active"]',
            'title'  => 'Ketuk “Isi Checklist”',
            'text'   => 'Mobil tangki dan AMT sudah terverifikasi. Sebelum BBM dibongkar, isi Checklist Pra-Pembongkaran. Ketuk kartu biru “Isi Checklist” untuk membuka Daftar LO.',
            'hint'   => '👆 Ketuk kartu biru “Isi Checklist”',
            'done'   => null,
        ]]];
    }

    if ($done === 2) {
        return ['journey' => 'verifikasi', 'subKey' => 'shipment_2', 'steps' => [[
            'no'     => 1,
            'label'  => 'Verifikasi Order',
            'target' => '[data-tour="activity-active"]',
            'title'  => 'Ketuk “Verifikasi Order”',
            'text'   => 'Checklist sudah terkirim. Sekarang AMT akan meminta Verifikasi Order. Ketuk kartu biru “Verifikasi Order” untuk membuka notifikasinya.',
            'hint'   => '👆 Ketuk kartu biru “Verifikasi Order”',
            'done'   => null,
        ]]];
    }

    if ($done === 3) {
        return ['journey' => 'rating', 'subKey' => 'shipment_3', 'steps' => [[
            'no'     => null,
            'label'  => 'Rating Petugas AMT',
            'target' => '[data-tour="activity-active"]',
            'title'  => 'Ketuk “Rating Petugas AMT”',
            'text'   => 'Verifikasi order sudah selesai. Ketuk kartu biru “Rating Petugas AMT” untuk menilai pelayanan kedua AMT yang bertugas.',
            'hint'   => '👆 Ketuk kartu biru “Rating Petugas AMT”',
            'done'   => null,
        ]]];
    }

    // Semua aktifitas selesai
    if ($done >= $total) {
        return ['journey' => 'selesai', 'subKey' => 'shipment_4', 'steps' => [[
            'no'     => null,
            'target' => null,
            'final'  => true,      // alur tutorial tuntas: tidak perlu muncul otomatis lagi
            'title'  => 'Semua aktifitas selesai 🎉',
            'text'   => 'Tiba di Lokasi, Isi Checklist, Verifikasi Order, dan Rating Petugas AMT sudah selesai. Serah terima order BBM tuntas. Ketuk tombol ← di kiri atas untuk kembali.',
            'button' => 'Mengerti',
            'done'   => null,
        ]]];
    }

    return ['journey' => 'kirim', 'subKey' => null, 'steps' => []];
}

/* ------------------------------------------------------------
 * Tiba di Lokasi (verifikasi Mobil Tangki, AMT 1, AMT 2)
 * ------------------------------------------------------------ */
function spbu_tour_verification_steps(): array
{
    // Verifikasi kedatangan sudah terkirim sebelumnya: tidak dipandu lagi
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 1) {
        return ['journey' => 'tiba', 'subKey' => null, 'steps' => []];
    }
    return ['journey' => 'tiba', 'subKey' => 'verification', 'steps' => [
        [
            'no'        => 1,
            'label'     => 'Tiba di Lokasi',
            'target'    => '.subject-card:not(.is-answered) .choice-group',
            'highlight' => '.subject-card:not(.is-answered)',
            'title'     => 'Verifikasi Mobil Tangki dan AMT',
            'text'      => 'Cocokkan data pada tiap kartu (Mobil Tangki, AMT 1, AMT 2) dengan kondisi sebenarnya di lapangan. Pilih “Ya, sesuai” bila cocok, atau “Tidak sesuai” bila berbeda. Ketiga kartu wajib dijawab.',
            'hint'      => '👆 Pilih jawaban pada kartu yang menyala',
            'done'      => '#arrivalForm[data-all="1"]',
            'ok'        => '✅ Ketiga kartu sudah dijawab',
        ],
        [
            'no'     => 2,
            'label'  => 'Tiba di Lokasi',
            'target' => '#btnSimpan',
            'title'  => 'Ketuk “Simpan”',
            'text'   => 'Semua kartu sudah dijawab, jadi tombol “Simpan” menyala. Ketuk untuk membuka konfirmasi pengiriman hasil verifikasi.',
            'hint'   => '👆 Ketuk tombol biru “Simpan”',
            'done'   => '#konfirmasiModal:not([hidden])',
        ],
        [
            // Pop up konfirmasi disorot penuh. Bila SPBU mengetuk "Tutup", kartu ini hilang dan
            // kartu "Ketuk Simpan" muncul lagi; kartu ini muncul lagi saat pop up dibuka ulang.
            'no'        => 3,
            'label'     => 'Tiba di Lokasi',
            'target'    => '#btnKirim',
            'highlight' => '#konfirmasiModal .modal-sheet',
            'when'      => '#konfirmasiModal:not([hidden])',
            'title'     => 'Ketuk “Kirim Verifikasi”',
            'text'      => 'Pastikan jawaban sudah benar, lalu ketuk “Kirim Verifikasi MT dan AMT”. Bila masih ragu, ketuk “Tutup” untuk memeriksa lagi.',
            'hint'      => '👆 Ketuk tombol biru “Kirim Verifikasi MT dan AMT”',
            'done'      => null,
        ],
    ]];
}

/* ------------------------------------------------------------
 * Daftar LO: (A) pilih LO + Mulai Checklist, atau (B) kirim checklist yang sudah "Draft"
 * ------------------------------------------------------------ */
function spbu_tour_lo_list_steps(): array
{
    // Checklist sudah terkirim: tidak dipandu lagi
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 2) {
        return ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    }

    $hasDraft = false;
    foreach (array_keys(LO_LIST) as $id) {
        if (!empty($_SESSION['lo_draft'][$id]) && empty($_SESSION['lo_done'][$id])) {
            $hasDraft = true;
            break;
        }
    }

    // ---- Ada LO "Draft": kirim checklist ----
    if ($hasDraft) {
        return ['journey' => 'checklist', 'subKey' => 'lo_list_kirim', 'steps' => [
            [
                'no'       => null,
                'label'    => 'Kirim Checklist',
                'target'   => '[data-tour="daftar-lo"]',
                'title'    => 'Pastikan LO yang dikirim tercentang',
                'text'     => 'Checklist LO berstatus “Draft” sudah terisi tetapi BELUM dikirim. Centang LO yang ingin dikirim, atau ketuk “Pilih Semua”. Tombol “Kirim” baru menyala setelah ada LO Draft yang dicentang.',
                'hint'     => '👆 Pastikan LO yang dikirim sudah dicentang',
                'done'     => '#btnKirimChecklist:not([disabled])',
                'minShow'  => 3000,    // sudah tercentang dari awal pun tetap disorot 3 detik agar diperiksa
                'condHint' => '✅ Sudah tercentang. Pastikan LO yang dikirim sudah benar.',
            ],
            [
                'no'     => 7,
                'label'  => 'Kirim Checklist',
                'target' => '#btnKirimChecklist',
                'title'  => 'Ketuk “Kirim”',
                'text'   => 'Ketuk “Kirim”, lalu akan muncul pertanyaan konfirmasi sebelum checklist benar-benar dikirim.',
                'hint'   => '👆 Ketuk tombol biru “Kirim”',
                'done'   => '#kirimModal:not([hidden])',
            ],
            [
                // Pop up "Kirim Checklist" disorot penuh. Bila SPBU mengetuk "Batal", kartu ini hilang dan
                // kartu "Ketuk Kirim" muncul lagi; kartu ini muncul lagi saat pop up dibuka ulang.
                'no'        => 8,
                'label'     => 'Kirim Checklist',
                'target'    => '#btnYaKirim',
                'highlight' => '#kirimModal .modal-sheet',
                'when'      => '#kirimModal:not([hidden])',
                'title'     => 'Konfirmasi pengiriman',
                'text'      => 'Pastikan semua jawaban sudah benar, karena data yang sudah dikirim tidak bisa diubah lagi. Ketuk “Ya, Kirim” untuk mengirim, atau “Batal” untuk memeriksa lagi.',
                'hint'      => '👆 Ketuk “Ya, Kirim”',
                'done'      => null,
            ],
        ]];
    }

    // ---- Belum ada Draft: pilih LO lalu mulai checklist ----
    return ['journey' => 'checklist', 'subKey' => 'lo_list_pilih', 'steps' => [
        [
            'no'       => 1,
            'label'    => 'Daftar LO',
            'target'   => '[data-tour="daftar-lo"]',
            'title'    => 'Pastikan LO yang dibongkar tercentang',
            'text'     => 'Setiap kartu adalah satu LO (Loading Order) dengan produknya (lihat baris “Order”). Centang LO yang akan dibongkar di SPBU ini, atau ketuk “Pilih Semua”. Status “Belum Diisi” berarti checklist LO itu belum dikerjakan.',
            'hint'     => '👆 Ketuk kotak centang pada LO yang dibongkar',
            'done'     => '.lo-card.selected',
            'minShow'  => 3000,    // sudah tercentang dari awal pun tetap disorot 3 detik agar diperiksa
            'condHint' => '✅ Sudah tercentang. Pastikan LO yang dibongkar sudah benar.',
        ],
        [
            'no'     => 2,
            'label'  => 'Daftar LO',
            'target' => '[data-tour="mulai-checklist"]',
            'title'  => 'Ketuk “Mulai Checklist”',
            'text'   => 'Anda akan mengisi 14 soal pemeriksaan sebelum pembongkaran BBM, lalu mengonfirmasi status tiap LO. Isi dengan jujur dan bertanggung jawab.',
            'hint'   => '👆 Ketuk tombol “Mulai Checklist”',
            'done'   => null,
        ],
    ]];
}

/* ------------------------------------------------------------
 * Checklist Pra-Pembongkaran (14 soal, satu soal per halaman) -- layar 'checklist'
 *
 * Tiap TIPE soal dijelaskan SEKALI (kunci tutorial per tipe), jadi 14 soal tidak diulang-ulang.
 * Tombol "?" menampilkan lagi penjelasan tipe soal yang sedang dibuka.
 *   self_action_photo / photo : tugas Petugas SPBU sendiri (+ foto bukti opsional)
 *   action                    : tugas AMT yang diverifikasi Petugas SPBU
 *   dual_verif                : dua bagian (mandiri + "Verifikasi Tugas Role Lawan")
 *   form_spp (soal 6)         : kesesuaian SPP -> Produk lalu Segel (lewat pop up)
 *   form_ukur (soal 7)        : form pengukuran tiap LO (di layar claim_loss)
 *   test_report (soal 8)      : tidak ada yang dijawab
 * ------------------------------------------------------------ */
function spbu_tour_checklist_steps(): array
{
    $none = ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 2) {
        return $none;   // checklist sudah terkirim
    }

    $total = count(CHECKLIST_STEPS);
    $step  = max(1, min($total, (int) ($_SESSION['checklist_step'] ?? 1)));
    $type  = CHECKLIST_STEPS[$step]['type'] ?? '';
    $next  = '[data-tour="wizard-next"]';   // tombol lanjut ada = soal ini sudah lengkap dijawab

    // ---- Soal 6: kesesuaian SPP (Produk lalu Segel) ----
    if ($type === 'form_spp') {
        $modalProduk = '.spp-modal input[name="action"][value="save_spp_produk"]';
        $modalSegel  = '.spp-modal input[name="action"][value="save_spp_segel"]';
        return ['journey' => 'checklist', 'subKey' => 'checklist_spp', 'steps' => [
            // 1) Produk: ketuk kartu Produk yang belum terisi (berulang untuk produk berikutnya)
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Produk',
                'target'    => '[data-tour="spp-produk-group"] .nav-item-card:not(.done)',
                'highlight' => '[data-tour="spp-produk-group"]',
                'title'     => 'Ketuk kartu Produk',
                'text'      => 'Periksa kesesuaian SPP: produk, nomor segel (periksa keutuhan segel bawah dan atas), nopol Mobil Tangki, dan nama AMT. Mulai dari Produk: ketuk kartu Produk yang menyala. Bila ada beberapa produk, semuanya wajib diisi satu per satu.',
                'hint'      => '👆 Ketuk kartu Produk yang menyala',
                'done'      => '.spp-modal, [data-tour="spp-produk-group"][data-all-done="1"]',
            ],
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Produk',
                'target'    => '[data-tour="spp-produk-kesesuaian"]',
                'highlight' => '.spp-modal',
                'when'      => $modalProduk,
                'optional'  => true,
                'title'     => 'Pilih Sesuai / Tidak Sesuai',
                'text'      => 'Bandingkan Nomor LO, Produk, dan Quantity pada kartu ini dengan kondisi sebenarnya, lalu pilih “Sesuai” atau “Tidak Sesuai”.',
                'hint'      => '👆 Pilih “Sesuai” atau “Tidak Sesuai”',
                'done'      => '.spp-modal input[name="kesesuaian"]:checked',
                'ok'        => '✅ Jawaban sudah dipilih',
            ],
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Produk',
                'target'    => '.spp-modal-save',
                'highlight' => '.spp-modal',
                'when'      => $modalProduk,
                'optional'  => true,
                'title'     => 'Ketuk “Simpan”',
                'text'      => 'Ketuk “Simpan” untuk menyimpan verifikasi Produk ini.',
                'hint'      => '👆 Ketuk tombol “Simpan”',
                'done'      => null,
            ],
            // 2) Segel: setelah SEMUA produk terisi
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Segel',
                'target'    => '[data-tour="spp-segel-group"] .nav-item-card:not(.done)',
                'highlight' => '[data-tour="spp-segel-group"]',
                'title'     => 'Ketuk nomor Segel',
                'text'      => 'Semua Produk sudah terverifikasi. Sekarang periksa Segel: ketuk salah satu nomor Segel yang dibongkar di SPBU saat ini. Semua nomor Segel yang masih “Belum Dibongkar” wajib diisi satu per satu.',
                'hint'      => '👆 Ketuk nomor Segel yang menyala',
                'done'      => '.spp-modal, [data-tour="spp-segel-group"][data-all-done="1"]',
            ],
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Segel',
                'target'    => '[data-tour="spp-segel-kesesuaian"]',
                'highlight' => '.spp-modal',
                'when'      => $modalSegel,
                'optional'  => true,
                'title'     => 'Kesesuaian nomor segel',
                'text'      => 'Jawab “Bagaimana nomor segel yang didapat?”. Pilih “Sesuai” bila nomornya cocok dengan SPP, atau “Tidak Sesuai” bila berbeda.',
                'hint'      => '👆 Pilih “Sesuai” atau “Tidak Sesuai”',
                'done'      => '.spp-modal input[name="kesesuaian"]:checked',
                'ok'        => '✅ Jawaban sudah dipilih',
            ],
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Segel',
                'target'    => '[data-tour="spp-segel-kondisi"]',
                'highlight' => '.spp-modal',
                'when'      => $modalSegel,
                'optional'  => true,
                'title'     => 'Kondisi segel',
                'text'      => 'Jawab “Bagaimana kondisi segel yang didapat?”. Pilih “Sesuai” bila segel masih utuh, atau “Tidak Sesuai” bila rusak.',
                'hint'      => '👆 Pilih “Sesuai” atau “Tidak Sesuai”',
                'done'      => '.spp-modal input[name="kondisi"]:checked',
                'ok'        => '✅ Jawaban sudah dipilih',
            ],
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Segel',
                'target'    => '.spp-modal-save',
                'highlight' => '.spp-modal',
                'when'      => $modalSegel,
                'optional'  => true,
                'title'     => 'Ketuk “Simpan”',
                'text'      => 'Ketuk “Simpan” untuk menyimpan verifikasi Segel ini.',
                'hint'      => '👆 Ketuk tombol “Simpan”',
                'done'      => null,
            ],
            // 3) Lanjut
            [
                'no'     => 4,
                'label'  => 'Soal 6 · Selesai',
                'target' => $next,
                'title'  => 'Ketuk “Selanjutnya”',
                'text'   => 'Semua Produk dan Segel sudah diverifikasi. Ketuk “Selanjutnya” untuk melanjutkan ke soal berikutnya.',
                'hint'   => '👆 Ketuk “Selanjutnya”',
                'done'   => null,
            ],
        ]];
    }

    // ---- Soal 7: form pengukuran tiap LO (daftar LO di sini, form-nya di layar claim_loss) ----
    if ($type === 'form_ukur') {
        return ['journey' => 'checklist', 'subKey' => 'checklist_ukur', 'steps' => [
            [
                'no'        => 5,
                'label'     => 'Soal 7 · Daftar LO',
                'target'    => '[data-tour="ukur-lo-group"] .measure-lo-card:not(.done)',
                'highlight' => '[data-tour="ukur-lo-group"]',
                'title'     => 'Pilih LO untuk diisi',
                'text'      => 'Ketuk LO dengan Form Bongkar “Belum Terisi” untuk mengisi data pengukuran pembongkaran BBM-nya di layar berikutnya. Semua LO wajib diisi, satu per satu.',
                'hint'      => '👆 Ketuk kartu LO yang menyala',
                'done'      => '[data-tour="ukur-lo-group"][data-all-done="1"]',
            ],
            [
                'no'     => 5,
                'label'  => 'Soal 7 · Selesai',
                'target' => $next,
                'title'  => 'Ketuk “Selanjutnya”',
                'text'   => 'Semua LO sudah diisi form pengukurannya. Ketuk “Selanjutnya” untuk melanjutkan ke soal berikutnya.',
                'hint'   => '👆 Ketuk “Selanjutnya”',
                'done'   => null,
            ],
        ]];
    }

    // ---- Soal 8: Test Report (tidak ada yang dijawab) ----
    if ($type === 'test_report') {
        return ['journey' => 'checklist', 'subKey' => 'checklist_report', 'steps' => [[
            'no'     => 3,
            'label'  => 'Soal 8 · Test Report',
            'target' => $next,
            'title'  => 'Ketuk “Selanjutnya”',
            'text'   => 'Dokumen Test Report sudah otomatis tercatat “telah dilihat”, jadi tidak ada yang perlu dijawab di soal ini. Ketuk “Selanjutnya” untuk melanjutkan.',
            'hint'   => '👆 Ketuk “Selanjutnya”',
            'done'   => null,
        ]]];
    }

    // ---- Soal terakhir (14): dua bagian, lalu ke Konfirmasi LO ----
    if ($step === $total) {
        return ['journey' => 'checklist', 'subKey' => 'checklist_last', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Terakhir',
                'target' => '.checklist-card',
                'title'  => 'Soal terakhir',
                'text'   => 'Jawab kedua bagian soal ini: verifikasi pekerjaan Anda sendiri, dan “Verifikasi Tugas Role Lawan” untuk tugas AMT.',
                'hint'   => '👆 Jawab kedua bagian soal',
                'done'   => $next,
                'ok'     => '✅ Semua jawaban sudah dipilih',
            ],
            spbu_tour_next_card(true),
        ]];
    }

    // ---- Soal dengan dua bagian (11, 13) ----
    if ($type === 'dual_verif') {
        return ['journey' => 'checklist', 'subKey' => 'checklist_dual', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Dua Bagian',
                'target' => '.checklist-card',
                'title'  => 'Soal dengan dua bagian',
                'text'   => 'Soal ini punya dua bagian yang wajib dijawab:',
                'list'   => [
                    'Bagian atas: verifikasi pekerjaan Anda sendiri. Pilih “Ya, dilakukan” atau “Tidak Dilakukan”. Foto bukti boleh dilewati.',
                    'Bagian bawah “Verifikasi Tugas Role Lawan”: pilih apakah AMT sudah mengerjakan tugasnya.',
                ],
                'hint'   => '👆 Jawab kedua bagian soal',
                'done'   => $next,
                'ok'     => '✅ Semua jawaban sudah dipilih',
            ],
            spbu_tour_next_card(),
        ]];
    }

    // ---- Tugas AMT yang diverifikasi SPBU (tipe "action") ----
    if ($type === 'action') {
        return ['journey' => 'checklist', 'subKey' => 'checklist_action', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Tugas AMT',
                'target' => '.checklist-card',
                'title'  => 'Soal Verifikasi Tugas AMT',
                'text'   => 'Soal ini adalah tugas yang dikerjakan AMT. Periksa langsung di lapangan, lalu pilih “Ya, dilakukan” bila AMT sudah mengerjakannya, atau “Tidak Dilakukan” bila belum. Jawab sesuai kenyataan.',
                'hint'   => '👆 Pilih salah satu jawaban',
                'done'   => $next,
                'ok'     => '✅ Jawaban sudah dipilih',
            ],
            spbu_tour_next_card(),
        ]];
    }

    // ---- Tugas SPBU sendiri (tipe "self_action_photo" / "photo"); soal 1 diawali pengantar ----
    $steps = [];
    if ($step === 1) {
        $steps[] = [
            'no'       => 3,
            'label'    => 'Checklist Pra-Pembongkaran',
            'target'   => '.progress-row',
            'title'    => 'Checklist 14 soal',
            'text'     => 'Anda akan menjawab 14 soal satu per satu, lalu mengonfirmasi status tiap LO. Jawab sesuai kondisi sebenarnya di lapangan. Angka di kanan atas bar (mis. 01/15) menunjukkan posisi Anda.',
            'button'   => 'Mengerti',
            'remember' => true,
            'done'     => null,
        ];
    }
    $steps[] = [
        'no'     => 3,
        'label'  => 'Soal Tugas Anda',
        'target' => '.checklist-card',
        'title'  => 'Soal Tugas Petugas SPBU',
        'text'   => 'Soal ini adalah tugas Anda sendiri sebagai Petugas SPBU. Kerjakan tugasnya, lalu pilih “Ya, Dilakukan” bila sudah, atau “Tidak Dilakukan” bila belum. Foto bukti boleh dilewati.',
        'hint'   => '👆 Pilih salah satu jawaban',
        'done'   => $next,
        'ok'     => '✅ Jawaban sudah dipilih',
    ];
    $steps[] = spbu_tour_next_card();
    return ['journey' => 'checklist', 'subKey' => 'checklist_self', 'steps' => $steps];
}

/* ------------------------------------------------------------
 * Metode Pengukuran (layar claim_loss) -- bagian dari soal 7
 * Status form dibaca dari #claimForm[data-ready="1"] (semua kolom wajib terisi).
 * ------------------------------------------------------------ */
function spbu_tour_claim_loss_steps(): array
{
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 2) {
        return ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    }
    return ['journey' => 'checklist', 'subKey' => 'claim_loss', 'steps' => [
        [
            'no'       => 5,
            'label'    => 'Soal 7 · Metode Pengukuran',
            'target'   => '[data-tour="claim-metode-group"]',
            'title'    => 'Pilih metode pengukuran',
            'text'     => 'Ada dua metode: “IJKBOUT” (serah terima custody transfer, diukur dengan dipstick) dan “Flow Meter” (meter arus pada mobil tangki PTO/portabel). Pilih yang sesuai dengan pembongkaran di lapangan.',
            'hint'     => '👆 Pilih metode, atau ketuk “Mengerti” bila sudah sesuai',
            'button'   => 'Mengerti',
            'remember' => true,
            'done'     => null,
        ],
        [
            'no'       => 5,
            'label'    => 'Soal 7 · Form Pengukuran',
            'target'   => '.claim-form',
            'title'    => 'Isi form pengukuran',
            'text'     => 'Isi semua kolom bertanda * sesuai hasil pengukuran sebenarnya (mis. Kompartemen, Level BBM di SPP, Level BBM Sebelum Bongkar, Temperatur Obs, Density Obs). Tombol “Generate” menyala setelah semuanya terisi.',
            'hint'     => '👆 Isi semua kolom yang bertanda *',
            'done'     => '#claimForm[data-ready="1"]',
            'settle'   => 1500,
            'ok'       => '✅ Semua kolom sudah terisi',
        ],
        [
            'no'     => 5,
            'label'  => 'Soal 7 · Form Pengukuran',
            'target' => '#btnGenerate',
            'title'  => 'Ketuk “Generate”',
            'text'   => 'Semua kolom sudah terisi, jadi tombol “Generate” menyala. Ketuk untuk menghitung selisih kekurangan (Claim Losses).',
            'hint'   => '👆 Ketuk tombol biru “Generate”',
            'done'   => '#hasilModal',
        ],
        [
            // Pop up hasil disorot penuh. Bila SPBU mengetuk "Batal", kartu ini hilang dan kartu
            // "Ketuk Generate" muncul lagi; kartu ini muncul lagi saat pop up dibuka ulang.
            'no'        => 5,
            'label'     => 'Soal 7 · Hasil Generate',
            'target'    => '[data-tour="hasil-primary-action"]',
            'highlight' => '.hasil-generate-sheet',
            'when'      => '#hasilModal',
            'optional'  => true,
            'title'     => 'Hasil Generate Claim Loss',
            'text'      => 'Sistem menampilkan selisih kekurangan (Claim Losses) beserta statusnya. Bila “Dapat di Klaim”, ketuk “Ajukan Klaim” (atau “Simpan Tanpa Klaim”). Bila tidak ada selisih, ketuk “Simpan”. Anda kembali ke daftar LO.',
            'hint'      => '👆 Ketuk tombol biru di bawah',
            'done'      => null,
        ],
    ]];
}

/* ------------------------------------------------------------
 * Konfirmasi LO (layar berdiri sendiri setelah soal 14)
 * ------------------------------------------------------------ */
function spbu_tour_konfirmasi_steps(): array
{
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 2) {
        return ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    }
    return ['journey' => 'checklist', 'subKey' => 'konfirmasi_lo', 'steps' => [
        [
            'no'        => 6,
            'label'     => 'Konfirmasi LO',
            'target'    => '[data-tour="konfirmasi-lo-group"] .konfirmasi-card:not(.is-set) .konfirmasi-actions',
            'highlight' => '[data-tour="konfirmasi-lo-group"] .konfirmasi-card:not(.is-set)',
            'title'     => 'Tentukan status tiap LO',
            'text'      => 'Ketuk “Sudah Dibongkar” bila BBM untuk LO ini jadi dibongkar, atau “Tidak Jadi Bongkar” bila pembongkaran dibatalkan. Semua LO wajib ditentukan statusnya.',
            'hint'      => '👆 Pilih status pada LO yang menyala',
            'done'      => '[data-tour="konfirmasi-lo-group"][data-all-done="1"]',
            'ok'        => '✅ Semua LO sudah ditentukan statusnya',
        ],
        [
            'no'     => 6,
            'label'  => 'Konfirmasi LO',
            'target' => '[data-tour="wizard-next"]',
            'title'  => 'Ketuk “Konfirmasi LO”',
            'text'   => 'Semua LO sudah punya status. Ketuk “Konfirmasi LO” untuk menyimpan dan kembali ke Daftar LO. Setelah itu checklist berstatus “Draft” dan siap dikirim.',
            'hint'   => '👆 Ketuk tombol “Konfirmasi LO”',
            'done'   => null,
        ],
    ]];
}

/* ------------------------------------------------------------
 * Notifikasi (permintaan Verifikasi Order dari AMT masuk ±5 detik setelah dibuka)
 * ------------------------------------------------------------ */
function spbu_tour_notifikasi_steps(): array
{
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 3) {
        return ['journey' => 'verifikasi', 'subKey' => null, 'steps' => []];
    }
    $dismissed = '#verifOrderModal[data-dismissed]';   // pop up ditutup ("Tutup")
    return ['journey' => 'verifikasi', 'subKey' => 'notifikasi', 'steps' => [
        [
            'no'     => 2,
            'label'  => 'Verifikasi Order',
            'target' => '.notif-date',
            'title'  => 'Menunggu permintaan dari AMT',
            'text'   => 'AMT sedang mengirim permintaan Verifikasi Order ke SPBU Anda. Tunggu sekitar 5 detik sampai muncul pop up “Permintaan Verifikasi Order”.',
            'hint'   => '⏳ Menunggu permintaan dari AMT…',
            'skip'   => $dismissed,
            'done'   => '#verifOrderModal:not([hidden])',
        ],
        [
            // Pop up disorot penuh; bila ditutup, kartu cadangan di bawah mengarahkan ke kartu notifikasi.
            'no'        => 2,
            'label'     => 'Verifikasi Order',
            'target'    => '#btnLihatNotifikasi',
            'highlight' => '#verifOrderModal .modal-sheet',
            'when'      => '#verifOrderModal:not([hidden])',
            'skip'      => $dismissed,
            'title'     => 'Ketuk “Lihat Notifikasi”',
            'text'      => 'AMT meminta Verifikasi Order. Ketuk “Lihat Notifikasi” untuk membuka Kode QR / Kode Konfirmasi yang harus Anda berikan kepada AMT.',
            'hint'      => '👆 Ketuk tombol biru “Lihat Notifikasi”',
            'done'      => null,
        ],
        [
            'no'     => 2,
            'label'  => 'Verifikasi Order',
            'target' => '#notifVerifikasiCard',
            'when'   => '#notifVerifikasi:not([hidden])',
            'title'  => 'Buka notifikasi Verifikasi Order',
            'text'   => 'Notifikasi Verifikasi Order berstatus “Aktif” dari AMT ada di sini. Ketuk kartunya untuk membuka Kode QR / Kode Konfirmasi.',
            'hint'   => '👆 Ketuk kartu “Verifikasi Order”',
            'done'   => null,
        ],
    ]];
}

/* ------------------------------------------------------------
 * Permintaan Verifikasi (Kode QR / Kode Konfirmasi untuk AMT)
 * ------------------------------------------------------------ */
function spbu_tour_qr_steps(): array
{
    // Verifikasi sudah dilakukan: layar hanya menampilkan "Verifikasi Telah Dilakukan"
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 3) {
        return ['journey' => 'verifikasi', 'subKey' => null, 'steps' => []];
    }
    return ['journey' => 'verifikasi', 'subKey' => 'qr_code', 'steps' => [
        [
            'no'     => 3,
            'label'  => 'Verifikasi Order',
            'target' => '.qr-box',
            'title'  => 'Minta AMT memindai kode',
            'text'   => 'Minta AMT memindai (scan) Kode QR ini. Kalau tidak bisa dipindai, berikan Kode Konfirmasi di bawahnya kepada AMT. Kode ini punya waktu kadaluwarsa, jadi jangan ditunda.',
            'hint'   => '⏳ Menunggu AMT memindai kode…',
            'done'   => '#verifBerhasilModal:not([hidden])',
            'ok'     => '✅ AMT sudah memverifikasi',
        ],
        [
            'no'        => 4,
            'label'     => 'Verifikasi Order',
            'target'    => '#btnBeriPenilaian',
            'highlight' => '#verifBerhasilModal .modal-sheet',
            'when'      => '#verifBerhasilModal:not([hidden])',
            'title'     => 'Ketuk “Beri Penilaian”',
            'text'      => 'Verifikasi sudah berhasil dilakukan AMT. Ketuk “Beri Penilaian” untuk menilai pelayanan kedua AMT yang bertugas.',
            'hint'      => '👆 Ketuk tombol biru “Beri Penilaian”',
            'done'      => null,
        ],
    ]];
}

/* ------------------------------------------------------------
 * Rating Petugas AMT (2 halaman: AMT 1 lalu AMT 2). Status dibaca dari #ratingForm.
 * ------------------------------------------------------------ */
function spbu_tour_rating_steps(): array
{
    if ((int) ($_SESSION['activity_done'] ?? 0) >= count(ACTIVITY_STEPS)) {
        return ['journey' => 'rating', 'subKey' => null, 'steps' => []];
    }

    $isAmt2 = (int) ($_SESSION['rating_step'] ?? 1) >= 2;
    $who    = $isAmt2 ? 'AMT 2' : 'AMT 1';
    $card   = '.rating-card[data-rating-card]:not(.is-rated)';   // kartu bintang berikutnya yang belum dinilai

    $explain = [
        'Penilaian keseluruhan untuk ' . $who . ' (kartu paling atas).',
        'Safety AMT: seragam dan APD dipakai dengan benar.',
        'Sarfas: mobil tangki aman untuk proses pembongkaran.',
        'Komunikasi: AMT melakukan kroscek dan menginformasikan produk.',
        'Operasional: AMT standby di lingkungan SPBU saat bongkar.',
        'Aspek Layanan: ketepatan volume BBM.',
    ];

    return ['journey' => 'rating', 'subKey' => $isAmt2 ? 'rating_amt2' : 'rating_amt1', 'steps' => [
        // 1) Penjelasan bintang: tampil SATU KALI, menyorot kartu pertama yang belum dinilai.
        [
            'no'        => $isAmt2 ? 3 : 1,
            'label'     => 'Rating ' . $who,
            'target'    => $card . ' .stars',
            'highlight' => $card,
            'title'     => 'Beri bintang untuk ' . $who,
            'text'      => 'Ketuk bintang pada tiap kartu: 1 bintang berarti sangat kurang, 5 bintang berarti luar biasa. Semua kartu wajib dinilai:',
            'list'      => $explain,
            'hint'      => '👆 Ketuk bintang pada kartu yang menyala',
            'done'      => '.rating-card.is-rated',
        ],
        // 2) Pengingat singkat: HANYA tampil sesaat setelah bintang PERTAMA diisi. Begitu kartu kedua diisi,
        //    tutorial menghilang sampai SEMUA kartu terisi (lalu muncul langkah "Selanjutnya" / "Kirim").
        [
            'no'        => $isAmt2 ? 3 : 1,
            'label'     => 'Rating ' . $who,
            'target'    => $card . ' .stars',
            'highlight' => $card,
            'when'      => '#ratingForm[data-rated="1"]',
            'nodim'     => true,    // kartu penilaian tidak digelapkan, supaya mudah dilihat & diketuk
            'title'     => 'Lanjutkan mengisi bintang',
            'text'      => 'Bagus! Ketuk bintang pada kartu penilaian lain yang masih kosong sampai semuanya terisi.',
            'hint'      => '👆 Isi bintang di kartu yang masih kosong',
            'done'      => '#ratingForm[data-complete="1"]',
            'ok'        => '✅ Semua penilaian sudah terisi',
            'okText'    => $isAmt2 ? 'Tinggal kirim penilaian Anda.' : 'Lanjut menilai AMT 2.',
            'hold'      => 1500,
        ],
        // 3) Tombol lanjut / kirim (tampil hanya setelah SEMUA bintang terisi)
        $isAmt2
            ? [
                'no'     => 4,
                'label'  => 'Rating ' . $who,
                'target' => '#btnRatingLanjut',
                'when'   => '#ratingForm[data-complete="1"]',
                'title'  => 'Ketuk “Kirim”',
                'text'   => 'Semua penilaian AMT 1 dan AMT 2 sudah lengkap. Kolom “Keterangan Lainnya” boleh dikosongkan. Ketuk “Kirim” untuk mengirim rating dan menyelesaikan seluruh aktifitas di SPBU.',
                'hint'   => '👆 Ketuk tombol biru “Kirim”',
                'done'   => null,
            ]
            : [
                'no'     => 2,
                'label'  => 'Rating ' . $who,
                'target' => '#btnRatingLanjut',
                'when'   => '#ratingForm[data-complete="1"]',
                'title'  => 'Ketuk “Selanjutnya”',
                'text'   => 'Penilaian AMT 1 sudah lengkap, jadi tombol “Selanjutnya” menyala. Ketuk untuk menilai AMT 2.',
                'hint'   => '👆 Ketuk tombol biru “Selanjutnya”',
                'done'   => null,
            ],
    ]];
}