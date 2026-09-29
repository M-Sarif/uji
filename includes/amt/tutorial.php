<?php
/* ============================================================
 * OneFIS - AMT - Tutorial terpandu (guided tour)
 *
 * Tampilan & cara kerjanya SAMA PERSIS dengan tutorial peran SPBU:
 * menyorot (spotlight) elemen asli di halaman, satu langkah setiap saat.
 * Mesinnya dipakai bersama (assets/js/tutorial.js + assets/css/tutorial.css);
 * yang khusus AMT hanya DATA langkahnya, yaitu file ini.
 *
 * Isi langkah mengikuti dokumen "User Guide Aplikasi OneFIS Role AMT":
 *   A. Absen Kehadiran (Start Work)   B. Check-In   G. Absen Selesai Bekerja
 *
 * Tiap langkah:
 *   'target'    => selector CSS elemen yang HARUS diketuk pengguna untuk lanjut
 *   'highlight' => (opsional) elemen lain yang disorot sebagai gantinya
 *   'title'/'text' => isi balon petunjuk
 *   'place'     => top | bottom (null target = balon di tengah layar)
 *   'hint'      => (opsional) kalimat aksi pengganti bawaan
 *
 * Dipanggil dari includes/layout_bottom.php lewat amt_tour_config($screen).
 * Dimuat dari includes/amt/amt.php.
 * ============================================================ */

// Urutan bagian (indikator "Bagian X dari Y" + garis kemajuan)
const AMT_TOUR_SCREEN_ORDER = ['amt_home', 'start_end', 'start_work', 'checkin'];

const AMT_TOUR_SCREEN_LABELS = [
    'amt_home'   => 'Beranda AMT',
    'start_end'  => 'Start / End Work',
    'start_work' => 'Start Work',
    'checkin'    => 'Check-In',
    'end_work'   => 'End Work',
];

// Kalimat petunjuk untuk tombol "Kirim" yang baru aktif setelah form lengkap
const HINT_READY_AMT = '👉 Sudah siap! Sekarang ketuk tombol yang menyala biru';

const AMT_TOUR_STEPS = [

    // ---- Beranda AMT, SEBELUM Start Work ----
    'amt_home' => [
        [
            'target' => null,
            'title'  => 'Selamat Datang di OneFIS 👋',
            'text'   => 'Tutorial singkat ini memandu AMT dari absen kehadiran (Start Work) sampai Check-In, sesuai Panduan Aplikasi OneFIS Role AMT.',
            'place'  => 'center',
        ],
        [
            'target' => '[data-tour="amt-menu-start_end"]',
            'title'  => 'Langkah 1 · Buka Menu Start / End',
            'text'   => 'Setiap hari kerja diawali dengan absen. Ketuk menu "Start / End". Menu lain seperti Check-In masih terkunci sampai Anda Start Work.',
            'place'  => 'bottom',
        ],
    ],

    // ---- Beranda AMT, SETELAH Start Work (timer berjalan) ----
    'amt_home_running' => [
        [
            'target' => '[data-tour="amt-menu-checkin"]',
            'title'  => 'Langkah 7 · Buka Menu Check-In',
            'text'   => 'Waktu kerja sudah berjalan. Sekarang menu Check-In aktif: lakukan Check-In jika sudah mendapat tugas pengiriman BBM. Menu PTI dan Check-Out belum aktif.',
            'place'  => 'bottom',
        ],
    ],

    // ---- Start / End Work ----
    'start_end' => [
        [
            'target' => '[data-tour="btn-start-work"]',
            'title'  => 'Langkah 2 · Pilih Start Work',
            'text'   => 'Sebelum absen, pastikan AMT sudah berada di wilayah terminal agar absensi valid. Lalu ketuk "Start Work".',
            'place'  => 'bottom',
        ],
    ],
    // Dibuka lagi SETELAH Start Work (tombol Start Work sudah nonaktif)
    'start_end_running' => [
        [
            'target' => '[data-tour="btn-end-work"]',
            'title'  => 'Selesai Bekerja · End Work',
            'text'   => 'Setelah waktu kerja berakhir, AMT wajib absen selesai bekerja: ketuk "End Work", lalu foto selfie di lokasi dan kirim.',
            'place'  => 'bottom',
        ],
    ],

    // ---- Form Start Work ----
    'start_work' => [
        [
            'target'    => '#wk-loc .wk-head',
            'highlight' => '#wk-loc',
            'title'     => 'Langkah 3 · Pastikan Lokasi Anda',
            'text'      => 'Titik biru pada peta adalah lokasi Anda. Pastikan berada di wilayah terminal, jika perlu ketuk "Perbarui Lokasi".',
            'place'     => 'bottom',
            'hint'      => '👉 Ketuk tulisan "Lokasi Anda" yang menyala untuk lanjut',
        ],
        [
            'target' => '#wk-akt',
            'title'  => 'Langkah 4 · Pilih Aktivitas',
            'text'   => 'Pilih aktivitas Anda hari ini, misalnya "Hadir". Kolom ini wajib diisi.',
            'place'  => 'bottom',
            'alsoAdvanceOn' => ['selector' => '#wk-akt', 'event' => 'change'],
        ],
        [
            'target' => '#wk-take',
            'title'  => 'Langkah 5 · Foto Selfie Verifikasi',
            'text'   => 'Ketuk "Ambil Foto", arahkan wajah ke kamera depan handphone, lalu ambil selfie sebagai verifikasi.',
            'place'  => 'top',
        ],
        [
            'target' => '#wk-submit',
            'title'  => 'Langkah 6 · Kirim',
            'text'   => 'Form sudah terisi. Ketuk "Kirim". Setelah berhasil, Anda kembali ke halaman utama dan waktu kerja mulai berjalan.',
            'place'  => 'top',
            'hint'   => HINT_READY_AMT,
        ],
    ],

    // ---- Form Check-In ----
    'checkin' => [
        [
            'target' => '#ci-tab-verif',
            'title'  => 'Langkah 8 · Form Verifikasi Check-In',
            'text'   => 'Tab "Verifikasi" untuk mengisi form Check-In. Tab "Riwayat Check-In" menampilkan Check-In yang sudah dilakukan, karena Check-In dilakukan di setiap cycle/ritase pengiriman BBM.',
            'place'  => 'bottom',
            'hint'   => '👉 Ketuk tab "Verifikasi" yang menyala untuk lanjut',
        ],
        [
            'target' => '#wk-akt',
            'title'  => 'Langkah 9 · Pilih Aktivitas',
            'text'   => 'Pilih aktivitas Anda. Pengisiannya hampir sama dengan Start Work.',
            'place'  => 'bottom',
            'alsoAdvanceOn' => ['selector' => '#wk-akt', 'event' => 'change'],
        ],
        [
            'target' => '#wk-take',
            'title'  => 'Langkah 10 · Foto Selfie Verifikasi',
            'text'   => 'Ketuk "Ambil Foto" lalu ambil selfie dengan kamera depan sebagai verifikasi Check-In.',
            'place'  => 'top',
        ],
        [
            'target' => '#wk-submit',
            'title'  => 'Langkah 11 · Kirim Check-In',
            'text'   => 'Ketuk "Kirim". Check-In akan tercatat di tab "Riwayat Check-In".',
            'place'  => 'top',
            'hint'   => HINT_READY_AMT,
        ],
    ],

    // ---- Form End Work ----
    'end_work' => [
        [
            'target' => '#wk-take',
            'title'  => 'End Work · Foto Selfie Verifikasi',
            'text'   => 'Aktivitas mengikuti pilihan saat Start Work. Ketuk "Ambil Foto" lalu foto selfie di lokasi sebagai verifikasi.',
            'place'  => 'top',
        ],
        [
            'target' => '#wk-submit',
            'title'  => 'End Work · Kirim',
            'text'   => 'Ketuk "Kirim" untuk mengakhiri waktu kerja hari ini.',
            'place'  => 'top',
            'hint'   => HINT_READY_AMT,
        ],
    ],
];

/**
 * Konfigurasi tutorial AMT untuk sebuah layar.
 * Dipilih sesuai keadaan (timer berjalan atau belum) supaya tiap keadaan
 * mendapat tutorialnya sendiri, dan tercatat "sudah ditonton" terpisah
 * lewat 'subKey' (lihat assets/js/tutorial.js -> posKey).
 *
 * @return array{steps:array, subKey:?string, screenOrder:array, screenLabels:array}
 */
function amt_tour_config(string $screen): array
{
    $steps  = AMT_TOUR_STEPS[$screen] ?? [];
    $subKey = null;

    if (work_is_running()) {
        if ($screen === amt_home_screen()) {
            $steps  = AMT_TOUR_STEPS['amt_home_running'];
            $subKey = 'amt_home_running';
        } elseif ($screen === 'start_end') {
            $steps  = AMT_TOUR_STEPS['start_end_running'];
            $subKey = 'start_end_running';
        }
    }

    return [
        'steps'        => $steps,
        'subKey'       => $subKey,
        'screenOrder'  => AMT_TOUR_SCREEN_ORDER,
        'screenLabels' => AMT_TOUR_SCREEN_LABELS,
    ];
}