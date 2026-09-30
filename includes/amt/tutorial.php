<?php
/* ============================================================
 * OneFIS - AMT - Data tutorial terpandu (guided tour)
 *
 * Mesin tutorial: assets/amt/js/tutorial-amt.js  (khusus AMT)
 * Tampilan       : assets/amt/css/tutorial-amt.css (khusus AMT)
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
 * oleh views/amt/_work_form.php:
 *   data-loc="1"   lokasi sudah sesuai titik kerja
 *   data-pin="1"   lokasi sesuai DAN peta (pin) sudah selesai dimuat
 *   data-act="1"   aktivitas sudah dipilih (End Work: selalu 1)
 *   data-photo="1" foto verifikasi sudah tersimpan
 *
 * Dipanggil dari includes/layout_bottom.php lewat amt_tour_config($screen).
 * ============================================================ */

// Nama layar untuk label (dipakai bila langkah tidak punya alur bernomor)
const AMT_TOUR_SCREEN_ORDER = ['amt_home', 'start_end', 'start_work', 'checkin', 'amt_pti', 'amt_pti_form', 'end_work'];

const AMT_TOUR_SCREEN_LABELS = [
    'amt_home'   => 'Beranda AMT',
    'start_end'  => 'Start / End Work',
    'start_work' => 'Start Work',
    'checkin'    => 'Check-In',
    'amt_pti'      => 'Pre-Trip Inspection',
    'amt_pti_form' => 'Form Inspeksi',
    'end_work'   => 'End Work',
];

// Alur tutorial; 'total' = jumlah langkah bernomor pada alur itu
const AMT_TOUR_JOURNEYS = [
    'masuk'   => ['label' => 'Absen Masuk',  'total' => 6],
    'checkin' => ['label' => 'Check-In',     'total' => 5],
    'pulang'  => ['label' => 'Absen Pulang', 'total' => 4],
    'dcu'     => ['label' => 'Check-In Berhasil', 'total' => 1],   // pemberitahuan DCU (tanpa nomor langkah)
    // Inspeksi PTI: 1 buka menu, 2-3 ringkasan, 4-5 jawab item, 6 catatan, 7 kirim
    'pti'     => ['label' => 'Inspeksi PTI', 'total' => 7],
];

// Jeda (ms) sebelum tutorial DCU muncul di beranda setelah Check-In berhasil
// (nilainya diatur di includes/amt/amt_pti_data.php)
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
            return ['journey' => 'pti', 'subKey' => 'amt_pti_done', 'steps' => [[
                'no'     => null,
                'target' => null,
                'title'  => 'Inspeksi selesai 🎉',
                'text'   => 'Hasil inspeksi sudah terkirim dan status PTI sekarang “Sudah Inspeksi”. Ketuk panah kembali (←) di kiri atas untuk kembali ke Beranda.',
                'button' => 'Mengerti',
                'done'   => null,
            ]]];
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
            'text'   => 'Semua sudah lengkap. Ketuk “Kirim”, lalu pilih “Ya, Kirim” pada pertanyaan konfirmasi. Setelah terkirim, Anda kembali ke Beranda.',
            'hint'   => '👆 Ketuk tombol biru “Kirim”',
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
            'text'   => 'Setelah sampai di SPBU tujuan, ketuk menu Shipments.',
            'hint'   => '📍 Menu Shipments ada di kotak yang menyala',
            'button' => 'Mengerti',
            'done'   => null,
        ],
    ]];
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
        'persist'      => !in_array($screen, ['start_work', 'checkin', 'end_work', 'start_end'], true),
        'screenOrder'  => AMT_TOUR_SCREEN_ORDER,
        'screenLabels' => AMT_TOUR_SCREEN_LABELS,
    ];
}