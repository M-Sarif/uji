<?php
/**
 * SPBU - data domain statis: LO, segel, metode pengukuran, checklist,
 * kategori rating, objek verifikasi "Tiba di Lokasi", dan langkah aktifitas di SPBU.
 *
 * Hasil konversi dari data.ts & types.ts: semua konten yang dulu hardcoded di
 * JSX sekarang berupa array PHP yang dipakai ulang oleh view SPBU.
 *
 * Letak: roles/spbu/includes/data.php
 */

const LO_LIST = [
    '8144122089' => ['order' => 'PERTALITE 5.000 L', 'produk' => 'PERTALITE', 'qty' => '5.000 L'],
    '8144122090' => ['order' => 'PERTALITE 5.000 L', 'produk' => 'PERTALITE', 'qty' => '5.000 L'],
];

// Daftar nomor segel yang bisa dipilih/dikonfirmasi di langkah 6 (form_spp).
const SEGEL_LIST = ['N-0452553', 'N-0452554', 'N-0452555', 'N-0452563'];

// Metode pengukuran pembongkaran BBM untuk langkah 7 (form_ukur / Ajukan
// Claim Loss), masing-masing dengan field form yang berbeda.
const MEASUREMENT_METHODS = [
    'ijkbout' => [
        'title' => 'IJKBOUT',
        'desc'  => 'Serah terima custody transfer pada mobil tangki, diukur dengan dipstick.',
        'fields' => [
            ['key' => 'kompartemen',           'label' => 'Kompartemen',            'unit' => null],
            ['key' => 'level_spp',              'label' => 'Level BBM di SPP',        'unit' => 'mm', 'hint' => 'Jika depot tidak menginformasikan level minyak sebelum pengiriman, maka level minyak akan diisi sesuai dengan level saat penerimaan.'],
            ['key' => 'level_sebelum_bongkar',  'label' => 'Level BBM Sebelum Bongkar', 'unit' => 'mm'],
            ['key' => 'temperatur_obs',         'label' => 'Temperatur Obs',          'unit' => '°C'],
            ['key' => 'density_obs',            'label' => 'Density Obs',             'unit' => 'kg/m³', 'placeholder' => '0.000'],
        ],
    ],
    'flowmeter' => [
        'title' => 'Flow Meter',
        'desc'  => 'Serah terima meter arus pada mobil tangki (PTO/portabel).',
        'fields' => [
            ['key' => 'volume_meter',  'label' => 'Volume Meter',  'unit' => 'L'],
            ['key' => 'temperatur_obs', 'label' => 'Temperatur Obs', 'unit' => '°C'],
            ['key' => 'density_obs',   'label' => 'Density Obs',   'unit' => 'kg/m³', 'placeholder' => '0.000'],
        ],
    ],
];

// Rasio konversi tera (Liter per mm selisih level BBM) tiap kompartemen mobil
// tangki, dipakai untuk mengonversi selisih level dipstick (mm) menjadi
// volume (liter) pada metode pengukuran IJKBOUT.
//
// DIKALIBRASI dengan hasil sistem asli: Kompartemen 1, Level BBM di SPP 1212 mm,
// Level BBM Sebelum Bongkar 1200 mm  =>  22.285714285714285 Liter
// (selisih 12 mm x 13/7 L/mm). Isi tabel ini dengan nilai tera resmi per
// kompartemen begitu rumus "Rumus Hitung" sistem asli tersedia.
const COMPARTMENT_TERA_RATE = [
    'default' => 13 / 7, // L / mm, dipakai kalau nomor kompartemen tidak dikenali
    1 => 13 / 7,
    2 => 13 / 7,
    3 => 13 / 7,
];

// Jumlah segmen pada bar progres (sama dengan sistem asli: nn/15). Soal-soal
// checklist ada 14; segmen ke-15 adalah layar "Konfirmasi LO" yang berdiri
// sendiri (bukan soal) -- lihat views/konfirmasi_lo.php.
const CHECKLIST_PROGRESS_TOTAL = 15;

// 14 soal checklist pra-pembongkaran
const CHECKLIST_STEPS = [
    1  => ['text' => 'Pastikan tersedianya volume ruang kosong dalam tangki.', 'type' => 'self_action_photo'],
    2  => ['text' => 'Tempatkan mobil tangki pada posisi pembongkaran yang benar.', 'type' => 'action'],
    3  => ['text' => 'Tarik rem tangan, matikan mesin & aktifkan safety switch. Biarkan kunci kendaraan tetap terpasang di tempatnya. Pasang ganjal ban mobil tangki.', 'type' => 'action'],
    4  => ['text' => 'Turunkan alat pemadam api dan tempatkan pada posisi yang aman dan mudah terjangkau.', 'type' => 'action'],
    5  => ['text' => 'Pasang kabel arde dan yakinkan terpasang dengan benar.', 'type' => 'action'],
    6  => ['text' => 'Periksa kesesuaian SPP yaitu produk, nomor segel (periksa keutuhan segel bawah dan atas) nopol Mobil Tangki, dan nama AMT.', 'type' => 'form_spp'],
    7  => ['text' => 'Persiapkan alat ukur, buka tutup manhole atas mobil tangki BBM, periksa jenis dan volume BBM dari IJK bout-nya, dan pastikan sertifikat tera sesuai dengan ijk bout aktual di mobil tangki dan ditutup kembali.', 'type' => 'form_ukur'],
    8  => ['text' => 'Pemeriksaan Sampel BBM & View Test Reports', 'type' => 'test_report'],
    9  => ['text' => 'Pasang selang bongkar pada inlet pipa tangki (filling point), pastikan kesesuaian tangki penerima dengan produk yang akan dibongkar, kemudian pada outlet mobil tangki (gunakan quick coupling).', 'type' => 'action'],
    10 => ['text' => 'Lakukan pembongkaran dengan membuka kerangan sedikit demi sedikit. Pastikan tidak ada kebocoran pada selang maupun sambungan/coupling. (Khusus Pertashop): Pastikan tidak menggunakan pompa Alcon berbahan bakar bensin serta wajib menggunakan pompa yang telah memiliki izin tipe dari Metrologi dengan surat tera yang masih aktif sebagai pompa transfer BBM.', 'type' => 'self_action_photo'],
    11 => ['text' => 'Selesai melakukan bongkar, pastikan: Muatan BBM di mobil tangki benar-benar telah habis dan lakukan pengukuran volume BBM di dalam tangki penerima. Pastikan manhole atas tertutup sempurna.', 'type' => 'dual_verif'],
    12 => ['text' => 'Tutup kerangan, lepas selang bongkar dimulai dari mobil tangki dan tutup kembali lubang pengisian dari mobil tangki serta dipastikan tidak ada genangan BBM.', 'type' => 'photo'],
    13 => ['text' => 'Lepas kabel arde, kembalikan alat pemadam ke tempat semula dan Pastikan segel bekas dibawa kembali dan diserahkan ke Terminal.', 'type' => 'dual_verif'],
    14 => ['text' => 'Selesaikan proses administrasi dan dokumen wajib ditandatangani bersama.', 'type' => 'dual_verif'],
];

// Kategori penilaian AMT
const RATING_CATEGORIES = [
    'safety'      => ['title' => 'Safety AMT',     'desc' => 'Apakah AMT menggunakan seragam dan APD dengan benar?'],
    'sarfas'      => ['title' => 'Sarfas',         'desc' => 'Apakah mobil tangki safety untuk proses pembongkaran?'],
    'komunikasi'  => ['title' => 'Komunikasi',     'desc' => 'Apakah AMT melakukan kroscek & menginformasikan produk?'],
    'operasional' => ['title' => 'Operasional',    'desc' => 'Apakah AMT standby di lingkungan SPBU saat bongkar?'],
    'layanan'     => ['title' => 'Aspek Layanan',  'desc' => 'Ketepatan volume BBM'],
];

/* --------------------------------------------------------------
 * Data layar "Tiba di Lokasi" (verifikasi saat MT sampai di SPBU)
 * -------------------------------------------------------------- */

// SPBU keberapa dari total rute pengiriman mobil tangki hari ini
const ARRIVAL_SPBU_KE    = 1;
const ARRIVAL_SPBU_TOTAL = 3;

// Objek yang harus diverifikasi (1 mobil tangki + 2 awak mobil tangki).
// Key array = nama input radio sekaligus key session penyimpan jawaban.
const ARRIVAL_SUBJECTS = [
    'mt_ok' => [
        'photo'    => SPBU_URL . '/img/ilustrasi/empty-delivery-truck.png',
        'fit'      => 'contain',
        'name'     => 'B 9170 SEJ',
        'sub'      => '16 KL',
        'question' => 'Apakah mobil tangki sesuai?',
    ],
    'amt_ok' => [
        'photo'    => SPBU_URL . '/img/avatar/avatar-amt1.svg',
        'fit'      => 'cover',
        'name'     => 'MOHAMMAD FARHAN AWAFI',
        'sub'      => 'AMT 1',
        'question' => 'Apakah AMT 1 sesuai?',
    ],
    'amt2_ok' => [
        'photo'    => SPBU_URL . '/img/avatar/avatar-amt2.svg',
        'fit'      => 'cover',
        'name'     => 'IMAMAL KHOIR',
        'sub'      => 'AMT 2',
        'question' => 'Apakah AMT 2 sesuai?',
    ],
];

// Langkah aktifitas di SPBU (timeline pada layar Detail Order / shipment).
// Urutan array = urutan langkah; 'href' = layar yang dibuka saat diklik.
const ACTIVITY_STEPS = [
    'arrive' => [
        'label' => 'Tiba di Lokasi',
        'icon'  => SPBU_URL . '/img/langkah/step-arrive.png',
        'href'  => 'index.php?screen=verification',
    ],
    'checklist' => [
        'label' => 'Isi Checklist',
        'icon'  => SPBU_URL . '/img/langkah/step-checklist.png',
        'href'  => 'index.php?screen=lo_list',
    ],
    'verifikasi' => [
        'label' => 'Verifikasi Order',
        'icon'  => SPBU_URL . '/img/langkah/step-surat-jalan.png',
        'href'  => 'index.php?screen=notifikasi',
    ],
    'rating' => [
        'label' => 'Rating Petugas AMT',
        'icon'  => SPBU_URL . '/img/langkah/step-rate-spbu.png',
        'href'  => 'index.php?screen=rating',
    ],
];