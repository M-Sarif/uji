<?php
const LO_LIST = [
    '8144122089' => ['order' => 'PERTALITE 5.000 L', 'produk' => 'PERTALITE', 'qty' => '5.000 L'],
    '8144122090' => ['order' => 'PERTALITE 5.000 L', 'produk' => 'PERTALITE', 'qty' => '5.000 L'],
];

const SEGEL_LIST = ['N-0452553', 'N-0452554', 'N-0452555', 'N-0452563'];

const DEFAULT_MEASUREMENT_METHOD = 'flowmeter';

const MEASUREMENT_METHODS = [
    'ijkbout' => [
        'title' => 'IJKBOUT',
        'desc'  => 'Serah terima custody transfer pada mobil tangki, diukur dengan dipstick.',
        'fields' => [
            ['key' => 'kompartemen', 'label' => 'Kompartemen', 'type' => 'select', 'placeholder' => 'Pilih kompartemen'],
            ['key' => 'level_spp', 'label' => 'T2 Tera MT', 'type' => 'tera', 'unit' => 'mm'],
            ['key' => 'level_sebelum_bongkar', 'label' => 'Level BBM Sebelum Bongkar', 'unit' => 'mm', 'placeholder' => 'Level T2 BBM dalam mm', 'hint' => 'Isi level T2 BBM sebelum dibongkar (tinggi BBM hasil ukur dipstick) dalam mm, bukan jumlah pemesanan / Qty LO (8.000 L).', 'hint_class' => 'warn'],
            ['key' => 'temperatur_obs', 'label' => 'Temperatur Obs', 'unit' => '°C'],
            ['key' => 'density_obs', 'label' => 'Density Obs', 'unit' => 'kg/m³', 'placeholder' => '0.000'],
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

const COMPARTMENT_DISPATCH = 1;

const COMPARTMENT_TERA_T2 = [
    1 => 1637,
    2 => 1642,
    3 => 1651,
    4 => 1629,
];

const COMPARTMENT_TERA_RATE = [
    'default' => 13 / 7,
    1 => 13 / 7,
    2 => 13 / 7,
    3 => 13 / 7,
    4 => 13 / 7,
];

const CHECKLIST_PROGRESS_TOTAL = 14;

const CHECKLIST_STEPS = [
    1  => ['text' => 'Pastikan tersedianya volume ruang kosong dalam tangki.', 'type' => 'self_action_photo'],
    2  => ['text' => 'Tempatkan mobil tangki pada posisi pembongkaran yang benar.', 'type' => 'action'],
    3  => ['text' => 'Tarik rem tangan, matikan mesin & aktifkan safety switch. Biarkan kunci kendaraan tetap terpasang di tempatnya. Pasang ganjal ban mobil tangki.', 'type' => 'action'],
    4  => ['text' => 'Turunkan alat pemadam api dan tempatkan pada posisi yang aman dan mudah terjangkau.', 'type' => 'action'],
    5  => ['text' => 'Pasang kabel arde dan yakinkan terpasang dengan benar.', 'type' => 'action'],
    6  => ['text' => 'Periksa kesesuaian SPP yaitu produk, nomor segel (periksa keutuhan segel bawah dan atas) nopol Mobil Tangki, dan nama AMT.', 'type' => 'form_spp'],
    7  => ['text' => 'Persiapkan alat ukur, buka tutup manhole atas mobil tangki BBM, periksa jenis dan volume BBM dari IJK bout-nya, dan pastikan sertifikat tera sesuai dengan ijk bout aktual di mobil tangki dan ditutup kembali.', 'type' => 'form_ukur'],
    8  => ['text' => 'Pemeriksaan Sampel BBM & View Test Reports', 'type' => 'test_report'],
    9  => ['text' => 'Pasang selang bongkar pada inlet pipa tangki (filling point), pastikan kesesuaian tangki penerima dengan produk yang akan dibongkar, kemudian pada outlet mobil tangki (gunakan quick coupling).', 'type' => 'self_action_photo'],
    10 => ['text' => 'Lakukan pembongkaran dengan membuka kerangan sedikit demi sedikit. Pastikan tidak ada kebocoran pada selang maupun sambungan/coupling. (Khusus Pertashop): Pastikan tidak menggunakan pompa Alcon berbahan bakar bensin serta wajib menggunakan pompa yang telah memiliki izin tipe dari Metrologi dengan surat tera yang masih aktif sebagai pompa transfer BBM.', 'type' => 'self_action_photo'],
    11 => ['text' => 'Selesai melakukan bongkar, pastikan: Muatan BBM di mobil tangki benar-benar telah habis dan lakukan pengukuran volume BBM di dalam tangki penerima. Pastikan manhole atas tertutup sempurna.', 'type' => 'dual_verif'],
    12 => ['text' => 'Tutup kerangan, lepas selang bongkar dimulai dari mobil tangki dan tutup kembali lubang pengisian dari mobil tangki serta dipastikan tidak ada genangan BBM.', 'type' => 'photo'],
    13 => ['text' => 'Lepas kabel arde, kembalikan alat pemadam ke tempat semula dan Pastikan segel bekas dibawa kembali dan diserahkan ke Terminal.', 'type' => 'dual_verif'],
    14 => ['text' => 'Selesaikan proses administrasi dan dokumen wajib ditandatangani bersama.', 'type' => 'dual_verif'],
];

const RATING_CATEGORIES = [
    'safety'      => ['title' => 'Safety AMT',     'desc' => 'Apakah AMT menggunakan seragam dan APD dengan benar?'],
    'sarfas'      => ['title' => 'Sarfas',         'desc' => 'Apakah mobil tangki safety untuk proses pembongkaran?'],
    'komunikasi'  => ['title' => 'Komunikasi',     'desc' => 'Apakah AMT melakukan kroscek & menginformasikan produk?'],
    'operasional' => ['title' => 'Operasional',    'desc' => 'Apakah AMT standby di lingkungan SPBU saat bongkar?'],
    'layanan'     => ['title' => 'Aspek Layanan',  'desc' => 'Ketepatan volume BBM'],
];

const ARRIVAL_SPBU_KE    = 1;
const ARRIVAL_SPBU_TOTAL = 3;

const ARRIVAL_SUBJECTS = [
    'mt_ok' => [
        'photo'    => SPBU_URL . '/img/ilustrasi/empty-delivery-truck.png',
        'fit'      => 'contain',
        'name'     => 'B 9170 SEJ',
        'sub'      => '16 KL',
        'question' => 'Apakah mobil tangki sesuai?',
        'hint'     => 'Pastikan nomor plat fisik sama dengan yang tertera di aplikasi',
    ],
    'amt_ok' => [
        'photo'    => SPBU_URL . '/img/avatar/avatar-amt1.svg',
        'fit'      => 'cover',
        'name'     => 'MOHAMMAD FARHAN AWAFI',
        'sub'      => 'AMT 1',
        'question' => 'Apakah AMT 1 sesuai?',
        'hint'     => 'Pastikan wajah AMT 1 sesuai dengan foto',
    ],
    'amt2_ok' => [
        'photo'    => SPBU_URL . '/img/avatar/avatar-amt2.svg',
        'fit'      => 'cover',
        'name'     => 'IMAMAL KHOIR',
        'sub'      => 'AMT 2',
        'question' => 'Apakah AMT 2 sesuai?',
        'hint'     => 'Pastikan wajah AMT 2 sesuai dengan foto',
    ],
];

const ACTIVE_SHIPMENT_INFO = [
    'depot'    => 'Depot Pulang Pisau',
    'spbu'     => '65748002',
    'eta'      => '24/09/2026 10:48 AM',
    'products' => ['PERTALITE 6000 L', 'PERTAMAX, BULK 2000 L'],
];

const ACTIVE_SHIPMENT_STAGES = [
    ['key' => 'order', 'label' => 'Order Dibuat'],
    ['key' => 'depot', 'label' => 'BBM Disiapkan di Depot'],
    ['key' => 'kirim', 'label' => 'Order Sedang Dikirim'],
    ['key' => 'tiba',  'label' => 'Tiba di SPBU'],
];

const ACTIVE_SHIPMENT_CURRENT_STAGE = 3;

const ACTIVE_SHIPMENT_LOG = [
    ['stage' => 3, 'date' => 'Jumat, 25 Sep 2026', 'time' => '14.39 WIB', 'text' => 'BBM sedang dalam perjalanan ke SPBU Anda (65748002)'],
    ['stage' => 2, 'date' => 'Jumat, 25 Sep 2026', 'time' => '14.15 WIB', 'text' => 'Pengisian tangki MT dilakukan di Depot Pulang Pisau'],
    ['stage' => 1, 'date' => 'Jumat, 25 Sep 2026', 'time' => '14.10 WIB', 'text' => 'Berkas pembelian sedang diproses'],
];

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
        'icon'  => SPBU_URL . '/img/tidak-dipakai/step-verification.png',
        'href'  => 'index.php?screen=notifikasi',
    ],
    'rating' => [
        'label' => 'Rating Petugas AMT',
        'icon'  => SPBU_URL . '/img/langkah/step-rate-spbu.png',
        'href'  => 'index.php?screen=rating',
    ],
];

const SHIPMENT_INFO = [
    'shipment_no' => 'ONEFIS-1791351941655-ZQUFT1',
    'spbu_no'     => '2438203',
    'kapasitas'   => '16.000 L',
];