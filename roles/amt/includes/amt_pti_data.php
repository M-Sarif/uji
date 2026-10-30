<?php
const AMT_PTI_TOTAL = 11;

const AMT_PTI_STATEMENT_STEP = 11;

const AMT_PTI_NOTE_MIN = 3;
const AMT_PTI_NOTE_MAX = 500;

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
            'image' => $img . 'pti7.png',
            'items' => $pneumatic,
        ],
        7 => [
            'title' => 'SISI BELAKANG (G)',
            'image' => $img . 'pti6.png',
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

        11 => [
            'title' => 'Pernyataan Tanggung Jawab',
            'image' => null,
            'items' => [],
        ],
    ];
}

function amt_pti_statement_lines()
{
    return [
        'Saya menyatakan bahwa hasil pemeriksaan pada checklist ini benar adanya.',
        'Jika ditemukan kerusakan atau kendala, AMT wajib segera melapor ke pengawas.',
        'Pengoperasian mobil tangki dilarang sebelum dilakukan pemeriksaan dan perbaikan yang diperlukan.',
    ];
}

const AMT_DASHBOARD_SCREEN = 'amt_home';

const AMT_PTI_GUARD = true;

const AMT_FLOW_DEMO = false;

const AMT_DCU_SHOW_DELAY         = 1000;
const AMT_DCU_VISIBLE_FOR        = 7000;
const AMT_PTI_UNLOCK_DELAY       = 2000;
const AMT_PTI_HINT_DELAY         = 1000;
const AMT_PTI_FAILSAFE_SECONDS   = 30;
const AMT_PTI_DONE_VISIBLE_FOR   = 60000;
const AMT_PTI_DONE_PAGE_VISIBLE_FOR = 8000;
const AMT_ROUTE_INFO_VISIBLE_FOR = 20000;
const AMT_ROUTE_INFO_DELAY        = 3000;
const AMT_PTI_DONE_SHIPMENT_DELAY = 3000;
const AMT_PTI_DONE_START_DELAY    = 800;

function amt_flow_tutorials()
{
    return [
        'dcu' => [
            'eyebrow' => 'Mulai Ritase Berhasil',
            'title'   => 'Lakukan DCU dulu 🩺',
            'body'    => 'Mulai Ritase berhasil. Sekarang lakukan DCU (cek kesehatan) terlebih dahulu. DCU dilakukan langsung di tempat, bukan lewat aplikasi ini.',
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