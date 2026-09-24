<?php
/**
 * Data statis aplikasi OneFIS (hasil konversi dari data.ts & types.ts)
 * Semua konten yang tadinya di React (hardcoded JSX) sekarang murni
 * data PHP (array) yang dipakai ulang oleh setiap halaman.
 */

// Daftar LO (Loading Order) yang bisa dipilih untuk diisi checklist-nya.
// Key array = Nomor LO, dipakai juga sebagai id session (lo_checked / lo_done).
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

// Rasio konversi tera (Liter per mm ketinggian BBM) tiap kompartemen mobil
// tangki, dipakai untuk mengonversi selisih level dipstick (mm) menjadi
// volume (liter) pada metode pengukuran IJKBOUT.
// TODO: ganti dengan tabel tera resmi per kompartemen (dari sertifikat tera
// mobil tangki yang dicek di langkah 7 checklist) begitu datanya tersedia -
// nilai di bawah ini adalah pendekatan rata-rata sementara (linear),
// sedangkan tabel tera asli biasanya non-linear per rentang mm.
const COMPARTMENT_TERA_RATE = [
    'default' => 5.5, // L / mm, dipakai kalau nomor kompartemen tidak dikenali
    1 => 5.5,
    2 => 5.5,
    3 => 5.5,
];

// 15 langkah checklist pra-pembongkaran
const CHECKLIST_STEPS = [
    1  => ['text' => 'Pastikan tersedianya volume ruang kosong dalam tangki.', 'type' => 'self_action_photo'],
    2  => ['text' => 'Tempatkan mobil tangki pada posisi pembongkaran yang benar.', 'type' => 'action'],
    3  => ['text' => 'Tarik rem tangan, matikan mesin & aktifkan safety switch. Biarkan kunci kendaraan tetap terpasang di tempatnya. Pasang ganjal ban mobil tangki.', 'type' => 'action'],
    4  => ['text' => 'Turunkan alat pemadam api dan tempatkan pada posisi yang aman dan mudah terjangkau.', 'type' => 'action'],
    5  => ['text' => 'Pasang kabel arde dan yakinkan terpasang dengan benar.', 'type' => 'action'],
    6  => ['text' => 'Periksa kesesuaian SPP yaitu produk, nomor segel (periksa keutuhan segel bawah dan atas) nopol Mobil Tangki, dan nama AMT.', 'type' => 'form_spp'],
    7  => ['text' => 'Persiapkan alat ukur, buka tutup manhole atas mobil tangki BBM, periksa jenis dan volume BBM dari IJK bout-nya, dan pastikan sertifikat tera sesuai dengan ijk bout aktual di mobil tangki dan ditutup kembali.', 'type' => 'form_ukur'],
    8  => ['text' => 'Pemeriksaan Sampel BBM & view Test Report.', 'type' => 'action'],
    9  => ['text' => 'Pasang selang bongkar pada inlet pipa tangki (filling point), pastikan kesesuaian tangki penerima dengan produk yang akan dibongkar, kemudian pada outlet mobil tangki (gunakan quick coupling).', 'type' => 'action'],
    10 => ['text' => 'Lakukan pembongkaran dengan membuka kerangan sedikit demi sedikit. Pastikan tidak ada kebocoran pada selang maupun sambungan/coupling.', 'type' => 'action'],
    11 => ['text' => 'Selesai melakukan bongkar, pastikan: Muatan BBM di mobil tangki benar-benar telah habis dan lakukan pengukuran volume BBM di dalam tangki penerima. Pastikan manhole atas tertutup sempurna.', 'type' => 'photo'],
    12 => ['text' => 'Tutup kerangan, lepas selang bongkar dimulai dari mobil tangki dan tutup kembali lubang pengisian dari mobil tangki serta dipastikan tidak ada genangan BBM.', 'type' => 'photo'],
    13 => ['text' => 'Lepas kabel arde, kembalikan alat pemadam ke tempat semula dan Pastikan segel bekas dibawa kembali dan diserahkan ke Terminal.', 'type' => 'photo'],
    14 => ['text' => 'Selesaikan proses administrasi dan dokumen wajib ditandatangani bersama.', 'type' => 'photo'],
    15 => ['text' => 'Konfirmasi Status LO', 'type' => 'konfirmasi_lo'],
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
        'photo'    => 'assets/empty-delivery-truck.png',
        'fit'      => 'contain',
        'name'     => 'B 9170 SEJ',
        'sub'      => '16 KL',
        'question' => 'Apakah mobil tangki sesuai?',
    ],
    'amt_ok' => [
        'photo'    => 'assets/avatar-amt1.svg',
        'fit'      => 'cover',
        'name'     => 'MOHAMMAD FARHAN AWAFI',
        'sub'      => 'AMT 1',
        'question' => 'Apakah AMT 1 sesuai?',
    ],
    'amt2_ok' => [
        'photo'    => 'assets/avatar-amt2.svg',
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
        'icon'  => 'assets/step-arrive.png',
        'href'  => 'index.php?screen=verification',
    ],
    'checklist' => [
        'label' => 'Isi Checklist',
        'icon'  => 'assets/step-checklist.png',
        'href'  => 'index.php?screen=lo_list',
    ],
    'verifikasi' => [
        'label' => 'Verifikasi Order',
        'icon'  => 'assets/step-surat-jalan.png',
        'href'  => 'index.php?screen=notifikasi',
    ],
    'rating' => [
        'label' => 'Rating Petugas AMT',
        'icon'  => 'assets/step-rate-spbu.png',
        'href'  => 'index.php?screen=rating',
    ],
];

// Daftar layar valid + judul header (persis App.tsx -> getHeaderTitle)
const HEADER_TITLES = [
    'dashboard'             => '',
    'shipments_list'        => 'Shipments',
    'create_order_info'     => 'Buat Order',
    'create_order_product'  => 'Buat Order',
    'create_order_review'   => 'Buat Order',
    'track_order'           => 'Detail Order',
    'shipment'              => 'Detail Order',
    'verification'          => 'Tiba di Lokasi',
    'lo_list'               => 'Checklist Pra-Pembongkaran',
    'checklist'             => 'Checklist Pra Bongkar BBM SPBU',
    'notifikasi'            => 'Notifikasi',
    'qr_code'               => 'Permintaan Verifikasi',
    'rating'                => 'Beri Penilaian',
    'done'                  => 'Pengiriman Selesai',
    'claim_loss'            => 'Ajukan Claim Loss',
];

// Teks tutorial (persis App.tsx -> getTutorialText)
const TUTORIAL_TEXTS = [
    'dashboard'            => "1. Ini adalah halaman utama OneFIS. Klik menu 'Shipments' atau 'Lihat Detail Order' untuk melanjutkan.",
    'shipments_list'       => "2. Pada halaman Shipments pilih menu Buat Order untuk memesan BBM.",
    'create_order_info'    => "3. Isi Informasi Umum seperti Jenis Order, Tanggal, dan Shift. Klik 'Selanjutnya'.",
    'create_order_product' => "4. Tambahkan Produk BBM, cek Stok Aktual, dan isi Qty Order (misal 8000L). Klik 'Terapkan'.",
    'create_order_review'  => "5. Cek kembali Ringkasan Order Anda, lalu klik 'Submit Order'.",
    'track_order'          => "6. Pantau order di tab Pengiriman. Setelah itu, klik tab 'Aktifitas' untuk melihat detail.",
    'shipment'             => "7. Ikuti aktifitas di SPBU sesuai urutan. Langkah yang aktif (bertanda panah) bisa diklik untuk dikerjakan.",
    // Layar "Tiba di Lokasi" tanpa kotak petunjuk agar isi kartu tidak tertutup
    'verification'         => '',
    'lo_list'              => "9. Pilih LO yang akan dibongkar, lalu klik 'Mulai Checklist'.",
    // Wizard checklist tanpa kotak petunjuk agar tampilan persis seperti aplikasi
    'checklist'            => '',
    // Halaman notifikasi tanpa kotak petunjuk supaya pop up tidak tertutup
    'notifikasi'           => '',
    'qr_code'              => "11. Tunjukkan QR Code / Kode Konfirmasi ini kepada AMT untuk diselesaikan.",
    'rating'               => "12. Berikan penilaian mendetail (Safety, Sarfas, dll) untuk pelayanan AMT. Klik Selesai.",
    'done'                 => "Selesai! Seluruh proses dari Order BBM hingga Pembongkaran berhasil dicatat.",
    // Halaman detail "Ajukan Claim Loss" tanpa kotak petunjuk (form fokus penuh)
    'claim_loss'           => '',
];

// Layar sebelumnya, dipakai untuk tombol "back" di header
const PREV_SCREEN = [
    'shipments_list'        => 'dashboard',
    'create_order_info'     => 'shipments_list',
    'create_order_product'  => 'create_order_info',
    'create_order_review'   => 'create_order_product',
    'track_order'           => 'shipments_list',
    'shipment'              => 'shipments_list',
    'verification'          => 'shipment',
    'lo_list'               => 'shipment',
    'checklist'             => 'lo_list',
    'notifikasi'            => 'shipment',
    // Halaman "Permintaan Verifikasi" (qr_code) dibuka dari notifikasi
    // verifikasi order, tapi tombol kembali di pojok kiri atas harus
    // langsung menuju Detail Order, bukan ke halaman Checklist.
    'qr_code'               => 'shipment',
    'rating'                => 'shipment',
    'claim_loss'            => 'checklist',
];

// Urutan alur maju, dipakai sebagai fallback "next" default
const NEXT_SCREEN = [
    'dashboard'             => 'shipments_list',
    'shipments_list'        => 'create_order_info',
    'create_order_info'     => 'create_order_product',
    'create_order_product'  => 'create_order_review',
    'create_order_review'   => 'track_order',
    'track_order'           => 'shipment',
    'shipment'              => 'verification',
    'verification'          => 'shipment',
    'lo_list'               => 'checklist',
    'checklist'             => 'notifikasi',
    'notifikasi'            => 'qr_code',
    'qr_code'               => 'rating',
    'rating'                => 'done',
    'done'                  => 'dashboard',
];

// Pemetaan layar -> file CSS spesifik yang perlu dimuat (di luar
// base.css & components.css yang selalu dimuat di semua halaman).
// Ini yang membuat style tidak lagi menumpuk dalam satu file besar.
const SCREEN_CSS = [
    'dashboard'             => ['dashboard'],
    'shipments_list'        => ['shipments'],
    'create_order_info'     => ['order-form'],
    'create_order_product'  => ['order-form'],
    'create_order_review'   => ['order-form'],
    'track_order'           => ['tracking'],
    'shipment'              => ['tracking'],
    'verification'          => ['verification'],
    'lo_list'               => ['verification'],
    'checklist'             => ['verification'],
    'notifikasi'            => ['verification'],
    'qr_code'               => ['verification'],
    'rating'                => ['verification'],
    'done'                  => ['verification'],
    'claim_loss'            => ['verification'],
];