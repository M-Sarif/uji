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
    // Layar awal: pilih peran (SPBU / AMT), tanpa header
    'role_select'           => '',
    'amt_home'              => 'Halaman AMT',
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
    'role_select'          => '',
    'amt_home'             => '',
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

// ---------------------------------------------------------------
// TUTORIAL TERPANDU (Guided Tour ala "first time play" game)
// ---------------------------------------------------------------
// Menggantikan kotak petunjuk statis lama (TUTORIAL_TEXTS di atas
// sekarang tidak lagi dirender - lihat includes/layout_top.php) dengan
// tutorial bertahap yang menyorot (spotlight) elemen asli di halaman,
// satu langkah setiap saat, persis alur pada dokumen panduan
// "Aktifitas di SPBU". Urutan konstanta ini SENGAJA mengikuti urutan
// dokumen tersebut apa adanya, bukan urutan lama yang tercampur
// dengan alur "Buat Order" (yang bukan bagian dari aktivitas SPBU).
//
// Tiap langkah:
//   'target' => selector CSS elemen asli yang disorot (harus benar-benar
//               ada di halaman, supaya tutorial menunjuk ke tombol/
//               kartu yang sungguhan, bukan ilustrasi terpisah)
//   'title'  => judul singkat pada balon petunjuk
//   'text'   => isi penjelasan (diringkas dari dokumen panduan)
//   'place'  => posisi balon relatif ke elemen: top|bottom|left|right|center
//               ('center' dipakai untuk langkah pembuka tanpa target elemen)
const TOUR_STEPS = [

    'dashboard' => [
        [
            'target' => null,
            'title'  => 'Selamat Datang di OneFIS 👋',
            'text'   => 'Tutorial singkat ini akan memandu Anda mengerjakan seluruh Aktifitas di SPBU, dari mobil tangki tiba sampai serah terima BBM selesai — persis urutan pada Panduan Aktifitas di SPBU.',
            'place'  => 'center',
        ],
        [
            'target' => '[data-tour="menu-shipment"]',
            'title'  => 'Langkah 1 · Buka Menu Shipment',
            'text'   => 'Pada halaman utama, ketuk menu "Shipments" untuk melihat daftar pengiriman BBM yang masuk ke SPBU Anda.',
            // Kartu Shipments ada di dekat bagian paling atas layar, jadi
            // tooltip ditaruh di BAWAH elemen (bukan di atas) supaya tidak
            // terpotong keluar layar.
            'place'  => 'bottom',
        ],
    ],

    'shipments_list' => [
        [
            'target' => '[data-tour="lihat-detail"]',
            'title'  => 'Langkah 2 · Lihat Detail Order',
            'text'   => 'Pilih "Lihat Detail" pada order yang dituju untuk membuka Detail Order, lalu Anda akan melihat tab "Aktifitas" berisi tahapan proses di SPBU.',
            'place'  => 'top',
            // Beri jeda ~2 detik sebelum tutorial ini muncul, supaya
            // pengguna sempat melihat dulu halaman Shipments-nya.
            'delayMs' => 2000,
        ],
    ],

    'shipment' => [
        [
            'target'      => '[data-tour="activity-active"]',
            'title'       => null, // diisi dinamis lewat JS sesuai label langkah yang aktif
            'text'        => null,
            'place'  => 'top',
            'dynamic'     => true,
        ],
    ],

    'verification' => [
        [
            'target' => '.subject-card',
            'title'  => 'Langkah 3 · Verifikasi Kedatangan',
            'text'   => 'Verifikasi kesesuaian data Mobil Tangki, AMT 1, dan AMT 2 dengan kondisi sebenarnya di lapangan sesuai tampilan pada aplikasi. Pilih "Ya, sesuai" atau "Tidak sesuai" untuk tiap kartu.',
            'place'  => 'bottom',
        ],
        [
            'target' => '#btnSimpan',
            'title'  => 'Langkah 4 · Simpan',
            'text'   => 'Setelah ketiga data (Mobil Tangki, AMT 1, AMT 2) selesai diverifikasi, ketuk "Simpan" untuk membuka konfirmasi pengiriman hasil verifikasi.',
            'place'  => 'top',
        ],
        [
            'target' => '#btnKirim',
            'title'  => 'Langkah 5 · Kirim Verifikasi',
            'text'   => 'Ketuk "Kirim Verifikasi MT dan AMT" untuk mengirimkan hasil verifikasi kedatangan.',
            'place'  => 'top',
        ],
    ],

    'lo_list' => [
        [
            'target' => '[data-tour="daftar-lo"]',
            'title'  => 'Langkah 6 · Pilih LO',
            'text'   => 'Setelah verifikasi MT dan AMT selesai, langkah berikutnya adalah Checklist Pra-Pembongkaran. Pilih LO (Loading Order) yang akan dibongkar dengan menandainya di sini.',
            'place'  => 'bottom',
        ],
        [
            'target' => '[data-tour="mulai-checklist"]',
            'title'  => 'Langkah 7 · Mulai Checklist',
            'text'   => 'Setelah LO dipilih, ketuk "Mulai Checklist" untuk mengerjakan 15 soal Checklist Pra-Pembongkaran secara berurutan.',
            'place'  => 'top',
        ],
    ],

    'checklist' => [
        [
            'target' => '.checklist-card',
            'title'  => 'Checklist Pra-Pembongkaran (15 Soal)',
            'text'   => 'Jawab tiap soal sesuai kondisi sebenarnya di lapangan (Dilakukan/Tidak Dilakukan, Sesuai/Tidak Sesuai, atau isi form), lalu ketuk "Selanjutnya". Soal 6 (SPP) dan Soal 7 (Metode Pengukuran) punya tutorial tersendiri begitu Anda sampai di soal itu.',
            'place'  => 'bottom',
        ],
    ],

    'notifikasi' => [
        [
            'target' => '[data-tour="notif-verifikasi"]',
            'title'  => 'Langkah 8 · Verifikasi Order',
            'text'   => 'Setelah Checklist Pra-Pembongkaran terkirim, tunggu inisiasi dari AMT. Begitu AMT mengirim Verifikasi Order, notifikasi berstatus "Aktif" akan muncul di sini — ketuk untuk membukanya.',
            'place'  => 'bottom',
        ],
    ],

    'qr_code' => [
        [
            'target' => '.qr-box',
            'title'  => 'Kode QR & Kode Konfirmasi',
            'text'   => 'Segera berikan Kode QR atau Kode Konfirmasi ini kepada AMT agar Verifikasi Order dapat diselesaikan olehnya — kode ini punya waktu kadaluwarsa, jadi jangan ditunda.',
            'place'  => 'top',
        ],
        [
            'target' => '#btnBeriPenilaian',
            'title'  => 'Langkah 9 · Beri Penilaian',
            'text'   => 'Setelah AMT menyelesaikan verifikasi, ketuk "Beri Penilaian" untuk menilai pelayanan AMT yang bertugas.',
            'place'  => 'top',
        ],
    ],

    'rating' => [
        [
            'target' => '.rating-card',
            'title'  => 'Langkah 10 · Rating AMT',
            'text'   => 'Berikan penilaian bintang untuk Safety AMT, Sarfas, Komunikasi, Operasional, dan Aspek Layanan pada tiap AMT yang bertugas. AMT juga menilai pelayanan SPBU dari sisi aplikasinya.',
            'place'  => 'bottom',
        ],
        [
            'target' => '#btnRatingLanjut',
            'title'  => 'Selesai Menilai',
            'text'   => 'Setelah semua kategori dinilai, tombol akan aktif. Ketuk untuk lanjut / mengirim penilaian.',
            'place'  => 'top',
        ],
    ],

    'done' => [
        [
            'target' => null,
            'title'  => 'Selesai! 🎉',
            'text'   => 'Seluruh Aktifitas di SPBU — dari Tiba di Lokasi, Checklist Pra-Pembongkaran, Verifikasi Order, hingga Rating AMT — telah selesai dilakukan. Tombol "Selesai" akan aktif setelah rating terkirim, menandakan serah terima order BBM tuntas.',
            'place'  => 'center',
        ],
    ],
];

// Tutorial terpandu KHUSUS Soal 6 ("Periksa kesesuaian SPP yaitu produk,
// nomor segel...") pada wizard Checklist Pra-Pembongkaran. Dipakai lewat
// $tourSubKey di includes/layout_bottom.php (bukan lewat TOUR_STEPS
// biasa) supaya statusnya "sudah ditonton" dilacak TERPISAH dari tutorial
// umum layar 'checklist' - jadi tetap tampil pertama kali soal ini
// dicapai, walau tutorial umum langkah 1-5 sudah pernah ditonton
// sebelumnya. Urutan & isi teks mengikuti dokumen panduan "Aktifitas di
// SPBU" persis, termasuk sub-langkah verifikasi Produk lalu Segel yang
// masing-masing dibuka lewat pop up (lihat views/checklist.php).
//
// Elemen target-nya sengaja "menempel" pada aksi asli (mis. baris pilihan
// Sesuai/Tidak Sesuai) supaya sentuhan pengguna untuk MENJAWAB pertanyaan
// itu SEKALIGUS jadi sentuhan untuk melanjutkan tutorial - persis pola
// yang sudah dipakai pada tutorial layar 'verification'.
const CHECKLIST_SOAL6_TOUR_STEPS = [
    [
        'target' => '[data-tour="spp-produk-group"]',
        'title'  => 'Soal 6 · Periksa Kesesuaian SPP',
        'text'   => 'Periksa kesesuaian SPP yaitu produk, nomor segel (periksa keutuhan segel bawah dan atas), nopol Mobil Tangki, dan nama AMT. Ketuk salah satu kartu Produk untuk memulai verifikasi.',
        'place'  => 'bottom',
    ],
    [
        'target' => '[data-tour="spp-produk-kesesuaian"]',
        'title'  => 'Verifikasi Produk',
        'text'   => 'Bandingkan Nomor LO, Produk, dan Qty pada pop up ini dengan kondisi sebenarnya, lalu pilih "Sesuai" atau "Tidak Sesuai".',
        'place'  => 'top',
    ],
    [
        'target' => '.spp-modal-save',
        'title'  => 'Simpan Verifikasi Produk',
        'text'   => 'Ketuk "Simpan". Ulangi langkah yang sama untuk produk lainnya.',
        'place'  => 'top',
    ],
    [
        'target' => '[data-tour="spp-segel-group"]',
        'title'  => 'Verifikasi Segel',
        'text'   => 'Setelah semua Produk terverifikasi, ketuk salah satu nomor Segel yang dibongkar di SPBU saat ini.',
        'place'  => 'bottom',
    ],
    [
        'target' => '[data-tour="spp-segel-kesesuaian"]',
        'title'  => 'Kesesuaian Nomor Segel',
        'text'   => 'Verifikasi "Bagaimana nomor segel yang didapat?" — pilih "Sesuai" atau "Tidak Sesuai".',
        'place'  => 'top',
    ],
    [
        'target' => '[data-tour="spp-segel-kondisi"]',
        'title'  => 'Kondisi Segel',
        'text'   => 'Verifikasi "Bagaimana kondisi segel yang didapat?" — pilih "Baik" atau "Rusak".',
        'place'  => 'top',
    ],
    [
        'target' => '.spp-modal-save',
        'title'  => 'Simpan Verifikasi Segel',
        'text'   => 'Ketuk "Simpan". Ulangi untuk segel lainnya, lalu ketuk "Selanjutnya" setelah semua Produk dan Segel selesai diverifikasi.',
        'place'  => 'top',
    ],
];

// Judul singkat tiap layar untuk indikator "Bagian X dari Y" pada
// tutorial terpandu (hanya layar yang memang dilalui alur SPBU).
const TOUR_SCREEN_ORDER = [
    'dashboard', 'shipments_list', 'shipment', 'verification',
    'lo_list', 'checklist', 'notifikasi', 'qr_code', 'rating', 'done',
];
const TOUR_SCREEN_LABELS = [
    'dashboard'      => 'Beranda',
    'shipments_list' => 'Daftar Shipment',
    'shipment'       => 'Detail Order',
    'verification'   => 'Tiba di Lokasi',
    'lo_list'        => 'Daftar LO',
    'checklist'      => 'Checklist',
    'notifikasi'     => 'Notifikasi',
    'qr_code'        => 'Verifikasi Order',
    'rating'         => 'Rating AMT',
    'done'           => 'Selesai',
];

// Layar sebelumnya, dipakai untuk tombol "back" di header
const PREV_SCREEN = [
    'amt_home'              => 'role_select',
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
    'role_select'           => ['role'],
    'amt_home'              => ['role', 'amt'],
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

// ---------------------------------------------------------------
// Peran pengguna (dipilih di layar awal "role_select")
//   - 'spbu' : alur aplikasi yang sekarang berjalan (dashboard -> ... -> done)
//   - 'amt'  : halaman khusus AMT
// ---------------------------------------------------------------
const ROLES = [
    'spbu' => ['label' => 'SPBU', 'desc' => 'Kelola order BBM, pantau pengiriman, dan verifikasi pembongkaran.', 'home' => 'dashboard'],
    'amt'  => ['label' => 'AMT',  'desc' => 'Awak Mobil Tangki: antar BBM dan layani serah terima di SPBU.',      'home' => 'amt_home'],
];

// Layar yang hanya boleh dibuka oleh peran AMT. Semua layar lain (kecuali
// role_select yang terbuka untuk semua) hanya untuk peran SPBU.
const AMT_SCREENS = ['amt_home'];