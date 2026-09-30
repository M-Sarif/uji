<?php
/**
 * Data Pre-Trip Inspection (PTI) - role AMT
 *
 * Langkah 1 dan 2 mengikuti screenshot.
 * Langkah 3-11 adalah CONTOH pengisi: ganti judul, foto, dan item
 * sesuai form PTI asli (user guide AMT).
 *
 * Foto per langkah: assets/amt/pti/step-01.jpg ... step-11.jpg
 * Kalau file foto belum ada, halaman menampilkan kotak placeholder.
 */

const AMT_PTI_TOTAL = 11;

function amt_pti_steps()
{
    return [
        1 => [
            'title' => 'SISI KANAN DEPAN (A)',
            'image' => 'assets/amt/pti/step-01.jpg',
            'items' => [
                ['key' => 'spion_kanan',   'label' => 'Kaca Spion Kanan', 'hint' => 'Kaca tidak buram / retak / pecah.'],
                ['key' => 'pintu_kanan',   'label' => 'Pintu',            'hint' => 'Dapat dikunci dan tertutup rapat.'],
                ['key' => 'lampu_rotator', 'label' => 'Lampu Rotator',    'hint' => 'Ada dan Menyala.'],
            ],
        ],
        2 => [
            'title' => 'SISI DEPAN (B)',
            'image' => 'assets/amt/pti/step-02.jpg',
            'items' => [
                ['key' => 'luar_kabin', 'label' => 'Sisi Luar Kabin', 'hint' => 'Keadaan Lengkap.'],
                ['key' => 'kaca_depan', 'label' => 'Kaca Depan',      'hint' => 'Kaca tidak buram / retak / pecah.'],
                ['key' => 'wiper',      'label' => 'Wiper',           'hint' => 'Ada, lengkap, dan berfungsi.'],
            ],
        ],
        3 => [
            'title' => 'SISI KIRI DEPAN (C)',
            'image' => 'assets/amt/pti/step-03.jpg',
            'items' => [
                ['key' => 'spion_kiri', 'label' => 'Kaca Spion Kiri', 'hint' => 'Kaca tidak buram / retak / pecah.'],
                ['key' => 'pintu_kiri', 'label' => 'Pintu',           'hint' => 'Dapat dikunci dan tertutup rapat.'],
                ['key' => 'ban_depan',  'label' => 'Ban Depan',       'hint' => 'Tidak aus dan tekanan angin cukup.'],
            ],
        ],
        4 => [
            'title' => 'SISI KIRI TENGAH (D)',
            'image' => 'assets/amt/pti/step-04.jpg',
            'items' => [
                ['key' => 'tangki_kiri', 'label' => 'Badan Tangki', 'hint' => 'Tidak bocor dan tidak penyok.'],
                ['key' => 'ban_tengah',  'label' => 'Ban Tengah',   'hint' => 'Tidak aus dan tekanan angin cukup.'],
            ],
        ],
        5 => [
            'title' => 'SISI KIRI BELAKANG (E)',
            'image' => 'assets/amt/pti/step-05.jpg',
            'items' => [
                ['key' => 'ban_belakang_kiri', 'label' => 'Ban Belakang', 'hint' => 'Tidak aus dan tekanan angin cukup.'],
                ['key' => 'lampu_kiri',        'label' => 'Lampu Samping', 'hint' => 'Ada dan Menyala.'],
            ],
        ],
        6 => [
            'title' => 'SISI BELAKANG (F)',
            'image' => 'assets/amt/pti/step-06.jpg',
            'items' => [
                ['key' => 'lampu_belakang', 'label' => 'Lampu Belakang', 'hint' => 'Lampu utama, rem, dan sein menyala.'],
                ['key' => 'valve',          'label' => 'Valve Bawah',    'hint' => 'Tertutup rapat dan tidak menetes.'],
                ['key' => 'plat_belakang',  'label' => 'Plat Nomor',     'hint' => 'Terpasang dan terbaca jelas.'],
            ],
        ],
        7 => [
            'title' => 'SISI KANAN BELAKANG (G)',
            'image' => 'assets/amt/pti/step-07.jpg',
            'items' => [
                ['key' => 'ban_belakang_kanan', 'label' => 'Ban Belakang', 'hint' => 'Tidak aus dan tekanan angin cukup.'],
                ['key' => 'lampu_kanan',        'label' => 'Lampu Samping', 'hint' => 'Ada dan Menyala.'],
            ],
        ],
        8 => [
            'title' => 'SISI KANAN TENGAH (H)',
            'image' => 'assets/amt/pti/step-08.jpg',
            'items' => [
                ['key' => 'tangki_kanan', 'label' => 'Badan Tangki', 'hint' => 'Tidak bocor dan tidak penyok.'],
                ['key' => 'ban_kanan',    'label' => 'Ban Tengah',   'hint' => 'Tidak aus dan tekanan angin cukup.'],
            ],
        ],
        9 => [
            'title' => 'ATAS TANGKI (I)',
            'image' => 'assets/amt/pti/step-09.jpg',
            'items' => [
                ['key' => 'manhole',  'label' => 'Tutup Manhole', 'hint' => 'Tertutup rapat dan seal baik.'],
                ['key' => 'railing',  'label' => 'Pegangan Atas', 'hint' => 'Kokoh dan tidak longgar.'],
            ],
        ],
        10 => [
            'title' => 'KABIN DALAM (J)',
            'image' => 'assets/amt/pti/step-10.jpg',
            'items' => [
                ['key' => 'klakson', 'label' => 'Klakson',  'hint' => 'Berbunyi normal.'],
                ['key' => 'rem',     'label' => 'Rem',      'hint' => 'Berfungsi baik.'],
                ['key' => 'sabuk',   'label' => 'Sabuk Pengaman', 'hint' => 'Ada dan berfungsi.'],
            ],
        ],
        11 => [
            'title' => 'PERLENGKAPAN KESELAMATAN (K)',
            'image' => 'assets/amt/pti/step-11.jpg',
            'items' => [
                ['key' => 'apar',      'label' => 'APAR',            'hint' => 'Ada, tidak kedaluwarsa, tekanan normal.'],
                ['key' => 'segitiga',  'label' => 'Segitiga Pengaman', 'hint' => 'Ada dan lengkap.'],
                ['key' => 'ganjal',    'label' => 'Ganjal Roda',     'hint' => 'Ada minimal 2 buah.'],
            ],
        ],
    ];
}

/* ---------- Alur setelah Check-In (tutorial + aktivasi PTI) ---------- */

/** Nama layar dashboard AMT (tujuan redirect kalau PTI belum aktif). Sesuaikan. */
const AMT_DASHBOARD_SCREEN = 'dashboard';

/** true = halaman PTI tidak bisa dibuka lewat URL sebelum PTI aktif. */
const AMT_PTI_GUARD = true;

/**
 * true = buka  ?screen=<dashboard>&amt_flow_demo=1  untuk mensimulasikan Check-In berhasil
 * (berguna untuk uji alur tanpa handler Check-In). Set false di produksi.
 */
const AMT_FLOW_DEMO = true;

/** Timing dalam milidetik. */
const AMT_DCU_SHOW_DELAY         = 1000; // tutorial DCU muncul 1 detik setelah Check-In berhasil
const AMT_DCU_VISIBLE_FOR        = 7000; // hilang sendiri setelah 7 detik kalau tidak diklik
const AMT_PTI_UNLOCK_DELAY       = 2000; // PTI aktif 2 detik setelah tutorial DCU hilang
const AMT_PTI_HINT_DELAY         = 1000; // petunjuk "tekan PTI" muncul 1 detik setelah PTI aktif
const AMT_PTI_TUTORIAL_DELAY     = 1000; // tutorial PTI muncul 1 detik setelah halaman PTI dibuka
const AMT_PTI_TUTORIAL_VISIBLE_FOR = 0;  // 0 = tetap tampil sampai ditutup pengguna

/** Isi tutorial. Teks 'dcu' dari screenshot; teks 'pti' contoh, sesuaikan dengan user guide. */
function amt_flow_tutorials()
{
    return [
        'dcu' => [
            'eyebrow' => 'Check-In Berhasil',
            'title'   => 'Lakukan DCU dulu 🩺',
            'body'    => 'Check-In berhasil. Sekarang lakukan DCU (cek kesehatan) terlebih dahulu. DCU dilakukan langsung di tempat, bukan lewat aplikasi ini.',
        ],
        'pti_hint' => [
            'eyebrow' => 'Langkah berikutnya',
            'title'   => 'Buka PTI',
            'body'    => 'Tekan ikon PTI untuk memeriksa kendaraan sebelum berangkat.',
        ],
        'pti' => [
            'eyebrow' => 'Pre-Trip Inspection',
            'title'   => 'Periksa kendaraan dulu',
            'body'    => 'Tekan Isi Inspeksi, lalu periksa setiap sisi kendaraan. Semua item bertanda * wajib diisi sebelum lanjut.',
        ],
    ];
}