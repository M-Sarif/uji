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
const AMT_TOUR_SCREEN_ORDER = ['amt_home', 'start_end', 'start_work', 'checkin', 'end_work'];

const AMT_TOUR_SCREEN_LABELS = [
    'amt_home'   => 'Beranda AMT',
    'start_end'  => 'Start / End Work',
    'start_work' => 'Start Work',
    'checkin'    => 'Check-In',
    'end_work'   => 'End Work',
];

// Alur tutorial; 'total' = jumlah langkah bernomor pada alur itu
const AMT_TOUR_JOURNEYS = [
    'masuk'   => ['label' => 'Absen Masuk',  'total' => 6],
    'checkin' => ['label' => 'Check-In',     'total' => 5],
    'pulang'  => ['label' => 'Absen Pulang', 'total' => 4],
    'dcu'     => ['label' => 'Check-In Berhasil', 'total' => 1],   // pemberitahuan DCU (tanpa nomor langkah)
];

// Jeda (ms) sebelum tutorial DCU muncul di beranda setelah Check-In berhasil
const AMT_TOUR_DCU_DELAY_MS = 5000;

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
 * Data langkah untuk satu layar.
 *
 * @param string $screen   nama layar
 * @param bool   $running  true bila timer Waktu Kerja sedang berjalan
 * @param int    $checkins jumlah Check-In yang sudah berhasil (0 = belum ada)
 * @return array{steps:array, journey:string, startDelay?:int}
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
            return [
                'journey'    => 'dcu',
                'startDelay' => AMT_TOUR_DCU_DELAY_MS,
                'steps'      => [[
                    'no'     => null,
                    'target' => null,
                    'title'  => 'Lakukan DCU dulu 🩺',
                    'text'   => 'Check-In berhasil. Sekarang lakukan DCU (cek kesehatan) terlebih dahulu. DCU dilakukan langsung di tempat, bukan lewat aplikasi ini. Setelah Anda ketuk “Mengerti”, menu PTI akan menyala.',
                    'button' => 'Mengerti',
                    'done'   => null,
                ]],
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
        $subKey = $checkins > 0 ? 'amt_home_dcu_' . $checkins : 'amt_home_running';
    } elseif ($running && $screen === 'start_end') {
        $subKey = 'start_end_running';
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