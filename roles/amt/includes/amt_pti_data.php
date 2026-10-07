<?php
/**
 * Data Pre-Trip Inspection (PTI) - role AMT
 *
 * 11 langkah, mengikuti form inspeksi asli:
 *   1-10  = checklist per sisi kendaraan (foto acuan + item Layak / Tidak layak)
 *   11    = Pernyataan Tanggung Jawab (hasil GO / NO GO + Catatan wajib)
 *
 * Foto acuan: roles/amt/assets/img/pti/pti1.png ... pti10.png
 * Catatan: pti6.png = APAR & Spill Kit, pti7.png = sisi kiri belakang trailer,
 * sedangkan urutan form-nya kebalikan (langkah 6 = trailer, langkah 7 = APAR),
 * jadi pemetaan foto di bawah menukar keduanya. File tidak perlu diganti nama.
 * Kalau file foto belum ada, halaman menampilkan kotak placeholder.
 */

const AMT_PTI_TOTAL = 11;

/** Langkah terakhir (Pernyataan Tanggung Jawab): tanpa foto & tanpa item radio. */
const AMT_PTI_STATEMENT_STEP = 11;

/** Isi minimal Catatan pada langkah terakhir (jumlah karakter). */
const AMT_PTI_NOTE_MIN = 3;
const AMT_PTI_NOTE_MAX = 500;

/** Nilai jawaban yang dianggap "Tidak layak" -> hasil inspeksi NO GO. */
const AMT_PTI_FAIL = 'tidak';

function amt_pti_steps()
{
    $img = AMT_URL . '/img/pti/';
    $tire = [
        ['key' => 'ban',       'label' => 'Ban',       'hint' => 'Tidak bocor dan tidak gundul.'],
        ['key' => 'baut_roda', 'label' => 'Baut Roda', 'hint' => 'Keadaan lengkap terpasang dan tidak ada yang kendur.'],
    ];
    $pneumatic = [
        ['key' => 'pneumatic', 'label' => 'Pneumatic System', 'hint' => 'Tidak terdapat suara desisan tanda kebocoran.'],
    ];

    return [
        1 => [
            'title' => 'SISI KANAN DEPAN (A)',
            'image' => $img . 'pti1.png',
            'items' => [
                ['key' => 'spion_kanan',   'label' => 'Kaca Spion Kanan', 'hint' => 'Kaca tidak buram / retak / pecah.'],
                ['key' => 'pintu_kanan',   'label' => 'Pintu',            'hint' => 'Dapat dikunci dan tertutup rapat.'],
                ['key' => 'lampu_rotator', 'label' => 'Lampu Rotator',    'hint' => 'Ada dan Menyala.'],
            ],
        ],
        2 => [
            'title' => 'SISI DEPAN (B)',
            'image' => $img . 'pti2.png',
            'items' => [
                ['key' => 'luar_kabin', 'label' => 'Sisi Luar Kabin', 'hint' => 'Keadaan Lengkap.'],
                ['key' => 'kaca_depan', 'label' => 'Kaca Depan',      'hint' => 'Kaca tidak buram / retak / pecah.'],
                ['key' => 'wiper',      'label' => 'Wiper',           'hint' => 'Ada, lengkap, dan berfungsi.'],
            ],
        ],
        3 => [
            'title' => 'SISI KIRI DEPAN (C)',
            'image' => $img . 'pti3.png',
            'items' => [
                ['key' => 'spion_kiri',    'label' => 'Kaca Spion Kiri',    'hint' => 'Kaca tidak buram / retak / pecah.'],
                ['key' => 'pintu_kiri',    'label' => 'Pintu Kendaraan Kiri', 'hint' => 'Dapat dikunci dan tertutup rapat.'],
                ['key' => 'lampu_rotator', 'label' => 'Lampu Rotator',      'hint' => 'Ada dan Menyala.'],
            ],
        ],
        4 => [
            'title' => 'SISI KIRI BELAKANG HEAD TRUCK (D)',
            'image' => $img . 'pti4.png',
            'items' => array_merge($tire, [
                ['key' => 'komponen_lain', 'label' => 'Komponen Lain', 'hint' => 'Keadaan Lengkap.'],
            ]),
        ],
        5 => [
            'title' => 'AREA BOTTOM LOADER (E)',
            'image' => $img . 'pti5.png',
            'items' => $pneumatic,
        ],
        6 => [
            'title' => 'SISI KIRI BELAKANG TRAILER (F)',
            'image' => $img . 'pti7.png',          // pti7.png = trailer kiri belakang
            'items' => $pneumatic,
        ],
        7 => [
            'title' => 'SISI BELAKANG (G)',
            'image' => $img . 'pti6.png',          // pti6.png = APAR & Spill Kit
            'items' => [
                ['key' => 'komponen_lain', 'label' => 'Komponen Lain', 'hint' => 'APAR dan Kotak Spill Kit.'],
            ],
        ],
        8 => [
            'title' => 'SISI KANAN BELAKANG TRAILER (H)',
            'image' => $img . 'pti8.png',
            'items' => $pneumatic,
        ],
        9 => [
            'title' => 'SISI KANAN BELAKANG HEAD TRUCK (I)',
            'image' => $img . 'pti9.png',
            'items' => array_merge($tire, [
                ['key' => 'komponen_lain', 'label' => 'Komponen Lain', 'hint' => 'Keadaan lengkap.'],
            ]),
        ],
        10 => [
            'title' => 'DASHBOARD (J)',
            'image' => $img . 'pti10.png',
            'items' => [
                ['key' => 'tekanan_angin', 'label' => 'Tekanan Angin',            'hint' => 'Min 8 bar dalam kondisi mesin hidup.'],
                ['key' => 'alarm_lampu',   'label' => 'Alarm atau Lampu Peringatan', 'hint' => 'Ada dan tidak menyala.'],
            ],
        ],
        // Langkah 11 = Pernyataan Tanggung Jawab (dirender khusus di roles/amt/views/pti_form.php)
        11 => [
            'title' => 'Pernyataan Tanggung Jawab',
            'image' => null,
            'items' => [],
        ],
    ];
}

/** Teks pernyataan pada langkah 11 (satu paragraf per baris). */
function amt_pti_statement_lines()
{
    return [
        'Saya menyatakan bahwa hasil pemeriksaan pada checklist ini benar adanya.',
        'Jika ditemukan kerusakan atau kendala, AMT wajib segera melapor ke pengawas.',
        'Pengoperasian mobil tangki dilarang sebelum dilakukan pemeriksaan dan perbaikan yang diperlukan.',
    ];
}

/* ---------- Alur setelah Check-In (tutorial + aktivasi PTI) ---------- */

/** Nama layar dashboard AMT (tujuan redirect kalau PTI belum aktif). Sesuaikan. */
const AMT_DASHBOARD_SCREEN = 'amt_home';

/** true = halaman PTI tidak bisa dibuka lewat URL sebelum PTI aktif. */
const AMT_PTI_GUARD = true;

/**
 * true = buka  ?screen=<dashboard>&amt_flow_demo=1  untuk mensimulasikan Check-In berhasil
 * (berguna untuk uji alur tanpa handler Check-In). Set false di produksi.
 */
const AMT_FLOW_DEMO = false;

/** Timing dalam milidetik. */
const AMT_DCU_SHOW_DELAY         = 1000; // tutorial DCU muncul 1 detik setelah Check-In berhasil
const AMT_DCU_VISIBLE_FOR        = 7000; // hilang sendiri setelah 7 detik kalau tidak diklik
const AMT_PTI_UNLOCK_DELAY       = 2000; // PTI aktif 2 detik setelah tutorial DCU hilang
const AMT_PTI_HINT_DELAY         = 1000; // petunjuk "tekan PTI" muncul 1 detik setelah PTI aktif
const AMT_PTI_FAILSAFE_SECONDS   = 30;  // PTI otomatis aktif 30 detik setelah Check-In walau tutorial tidak sempat ditutup
const AMT_PTI_DONE_VISIBLE_FOR   = 20000; // tutorial "PTI selesai" di dashboard hilang sendiri setelah 20 detik
const AMT_PTI_DONE_PAGE_VISIBLE_FOR = 8000; // kartu tutorial "Inspeksi selesai" di halaman PTI hilang sendiri setelah 5 detik
const AMT_PTI_DONE_SHIPMENT_DELAY = 3000; // 3 detik setelah itu, tutorial yang menyorot ikon Shipments muncul

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