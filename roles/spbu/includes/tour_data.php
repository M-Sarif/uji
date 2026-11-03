<?php
const SPBU_TOUR_SCREEN_ORDER = [
    'dashboard', 'shipments_list', 'shipment', 'verification',
    'lo_list', 'checklist', 'claim_loss', 'claim_upload', 'claim_scan_qr', 'konfirmasi_lo', 'notifikasi', 'qr_code', 'rating', 'done',
];

const SPBU_TOUR_SCREEN_LABELS = [
    'dashboard'      => 'Beranda SPBU',
    'shipments_list' => 'Shipments',
    'shipment'       => 'Aktifitas di SPBU',
    'verification'   => 'Tiba di Lokasi',
    'lo_list'        => 'Daftar LO',
    'checklist'      => 'Checklist Pra-Pembongkaran',
    'claim_loss'     => 'Metode Pengukuran',
    'claim_upload'   => 'Upload Dokumen Klaim',
    'claim_scan_qr'  => 'Scan QR AMT',
    'konfirmasi_lo'  => 'Konfirmasi LO',
    'notifikasi'     => 'Notifikasi',
    'qr_code'        => 'Verifikasi Order',
    'rating'         => 'Rating Petugas AMT',
    'done'           => 'Pengiriman Selesai',
];

const SPBU_TOUR_JOURNEYS = [

    'kirim'      => ['label' => 'Pengiriman', 'total' => 3],

    'tiba'       => ['label' => 'Tiba di Lokasi', 'total' => 3],

    'checklist'  => ['label' => 'Checklist Pra-Pembongkaran', 'total' => 8],

    'verifikasi' => ['label' => 'Verifikasi Order', 'total' => 4],

    'rating'     => ['label' => 'Rating Petugas AMT', 'total' => 4],

    'selesai'    => ['label' => 'Aktifitas Selesai', 'total' => 1],
];

function spbu_tour_active_lo_ids(): array
{
    $ids = array_keys(array_filter($_SESSION['lo_checked'] ?? []));
    return !empty($ids) ? $ids : array_keys(LO_LIST);
}

function spbu_tour_next_card(bool $last = false): array
{
    return [
        'no'     => null,
        'label'  => 'Checklist Pra-Pembongkaran',
        'target' => '[data-tour="wizard-next"]',
        'title'  => 'Ketuk “Selanjutnya”',
        'text'   => $last
            ? 'Ini soal checklist yang terakhir. Ketuk “Selanjutnya” untuk membuka layar Konfirmasi LO. Tombol “Sebelumnya” dipakai bila ingin memperbaiki jawaban.'
            : 'Ketuk “Selanjutnya” untuk ke soal berikutnya. Tombol “Sebelumnya” dipakai bila ingin memperbaiki jawaban.',
        'hint'   => '👆 Ketuk “Selanjutnya”',
        'done'   => null,
    ];
}

function spbu_tour_dashboard_steps(bool $finished): array
{
    $welcome = $finished
        ? [
            'no'        => null,
            'label'     => 'Tutorial Selesai',
            'target'    => null,
            'notice'    => true,
            'restart'   => true,
            'title'     => 'Selamat! Tutorial selesai 🎉',
            'text'      => 'Selamat, Anda telah menyelesaikan seluruh Aktifitas di SPBU. Klik “Mulai” untuk mengulang dari awal, atau pilih “Sudah cukup sampai di sini”.',
            'button'    => 'Mulai',
            'skipLabel' => 'Sudah cukup sampai di sini',
            'done'      => null,
        ]
        : [
            'no'     => null,
            'target' => null,
            'title'  => 'Selamat datang di OneFIS 👋',
            'text'   => 'Kita mulai dari mobil tangki yang tiba di SPBU Anda. Ikuti saja kotak yang menyala; setiap aktifitas di SPBU dipandu langkah demi langkah sampai serah terima BBM selesai.',
            'button' => 'Mulai',
            'done'   => null,
        ];

    return ['journey' => 'kirim', 'subKey' => null, 'steps' => [
        $welcome,
        [
            'no'     => 1,
            'target' => '[data-tour="menu-shipment"]',
            'title'  => 'Buka menu “Shipments”',
            'text'   => 'Menu Shipments berisi daftar pengiriman BBM yang menuju SPBU Anda. Ketuk menu ini untuk melihatnya.',
            'hint'   => '👆 Ketuk menu “Shipments”',
            'done'   => null,
        ],
    ]];
}

function spbu_tour_shipments_steps(): array
{

    if ((int) ($_SESSION['activity_done'] ?? 0) >= count(ACTIVITY_STEPS)) {
        return ['journey' => 'kirim', 'subKey' => null, 'steps' => []];
    }
    return ['journey' => 'kirim', 'subKey' => null, 'steps' => [[
        'no'        => 2,
        'label'     => 'Shipments',
        'target'    => '[data-tour="lihat-detail-btn"]',
        'highlight' => '[data-tour="lihat-detail"]',
        'title'     => 'Buka detail order',
        'text'      => 'Pengiriman yang sedang berjalan berstatus “Dikirim”. Ketuk “Lihat Detail” pada pengiriman itu untuk melihat tahapan Aktifitas di SPBU.',
        'hint'      => '👆 Ketuk “Lihat Detail” pada pengiriman Dikirim',
        'done'      => null,
    ]]];
}

function spbu_tour_shipment_steps(): array
{
    $done  = (int) ($_SESSION['activity_done'] ?? 0);
    $total = count(ACTIVITY_STEPS);

    if ($done === 0) {
        return ['journey' => 'kirim', 'subKey' => 'shipment_0', 'steps' => [
            [
                'no'       => null,
                'label'    => 'Tiba di Lokasi',
                'target'   => '#arriveItem',
                'title'    => 'Menunggu mobil tangki tiba',
                'text'     => 'Mobil tangki sedang menuju SPBU Anda. Kartu “Tiba di Lokasi” masih redup dan akan menyala biru sendiri sekitar 5 detik lagi.',
                'hint'     => '⏳ Tunggu sebentar sampai kartu menyala biru',
                'when'     => '#arriveItem',
                'optional' => true,
                'done'     => '[data-tour="activity-active"]',
            ],
            [
                'no'     => 3,
                'label'  => 'Aktifitas di SPBU',
                'target' => '[data-tour="activity-active"]',
                'title'  => 'Ketuk “Tiba di Lokasi”',
                'text'   => 'Mobil tangki sudah tiba. Ketuk kartu biru “Tiba di Lokasi” untuk memverifikasi Mobil Tangki dan kedua AMT.',
                'hint'   => '👆 Ketuk kartu biru “Tiba di Lokasi”',
                'done'   => null,
            ],
        ]];
    }

    if ($done === 1) {
        return ['journey' => 'checklist', 'subKey' => 'shipment_1', 'steps' => [[
            'no'     => null,
            'label'  => 'Isi Checklist',
            'target' => '[data-tour="activity-active"]',
            'title'  => 'Ketuk “Isi Checklist”',
            'text'   => 'Mobil tangki dan AMT sudah terverifikasi. Sebelum BBM dibongkar, isi Checklist Pra-Pembongkaran. Ketuk kartu biru “Isi Checklist” untuk membuka Daftar LO.',
            'hint'   => '👆 Ketuk kartu biru “Isi Checklist”',
            'done'   => null,
        ]]];
    }

    if ($done === 2) {
        return ['journey' => 'verifikasi', 'subKey' => 'shipment_2', 'steps' => [[
            'no'     => 1,
            'label'  => 'Verifikasi Order',
            'target' => '[data-tour="activity-active"]',
            'title'  => 'Ketuk “Verifikasi Order”',
            'text'   => 'Checklist sudah terkirim. Sekarang AMT akan meminta Verifikasi Order. Ketuk kartu biru “Verifikasi Order” untuk membuka notifikasinya.',
            'hint'   => '👆 Ketuk kartu biru “Verifikasi Order”',
            'done'   => null,
        ]]];
    }

    if ($done === 3) {
        return ['journey' => 'rating', 'subKey' => 'shipment_3', 'steps' => [[
            'no'     => null,
            'label'  => 'Rating Petugas AMT',
            'target' => '[data-tour="activity-active"]',
            'title'  => 'Ketuk “Rating Petugas AMT”',
            'text'   => 'Verifikasi order sudah selesai. Ketuk kartu biru “Rating Petugas AMT” untuk menilai pelayanan kedua AMT yang bertugas.',
            'hint'   => '👆 Ketuk kartu biru “Rating Petugas AMT”',
            'done'   => null,
        ]]];
    }

    if ($done >= $total) {
        return ['journey' => 'selesai', 'subKey' => 'shipment_4', 'steps' => [[
            'no'     => null,
            'target' => null,
            'final'  => true,
            'title'  => 'Semua aktifitas selesai 🎉',
            'text'   => 'Tiba di Lokasi, Isi Checklist, Verifikasi Order, dan Rating Petugas AMT sudah selesai. Serah terima order BBM tuntas. Ketuk tombol ← di kiri atas untuk kembali.',
            'button' => 'Mengerti',
            'done'   => null,
        ]]];
    }

    return ['journey' => 'kirim', 'subKey' => null, 'steps' => []];
}

function spbu_tour_verification_steps(): array
{

    if ((int) ($_SESSION['activity_done'] ?? 0) >= 1) {
        return ['journey' => 'tiba', 'subKey' => null, 'steps' => []];
    }
    return ['journey' => 'tiba', 'subKey' => 'verification', 'steps' => [
        [
            'no'        => 1,
            'label'     => 'Tiba di Lokasi',
            'target'    => '.subject-card:not(.is-answered) .choice-group',
            'highlight' => '.subject-card:not(.is-answered)',
            'title'     => 'Verifikasi Mobil Tangki dan AMT',
            'text'      => 'Cocokkan data pada tiap kartu (Mobil Tangki, AMT 1, AMT 2) dengan kondisi sebenarnya di lapangan. Pilih “Ya, sesuai” bila cocok, atau “Tidak sesuai” bila berbeda. Ketiga kartu wajib dijawab.',
            'hint'      => '👆 Pilih jawaban pada kartu yang menyala',
            'done'      => '#arrivalForm[data-all="1"]',
            'ok'        => '✅ Ketiga kartu sudah dijawab',
        ],
        [
            'no'     => 2,
            'label'  => 'Tiba di Lokasi',
            'target' => '#btnSimpan',
            'title'  => 'Ketuk “Kirim”',
            'text'   => 'Semua kartu sudah dijawab, jadi tombol “Kirim” menyala. Ketuk untuk membuka konfirmasi pengiriman checklist.',
            'hint'   => '👆 Ketuk tombol biru “Kirim”',
            'done'   => '#konfirmasiModal:not([hidden])',
        ],
        [

            'no'        => 3,
            'label'     => 'Tiba di Lokasi',
            'target'    => '#btnKirim',
            'highlight' => '#konfirmasiModal .modal-sheet',
            'when'      => '#konfirmasiModal:not([hidden])',
            'title'     => 'Ketuk “Ya, Kirim”',
            'text'      => 'Pastikan data yang diisi sudah benar. Data yang sudah dikirim tidak dapat diubah lagi. Bila masih ragu, ketuk “Batal” untuk memeriksa lagi.',
            'hint'      => '👆 Ketuk tombol biru “Ya, Kirim”',
            'done'      => null,
        ],
    ]];
}

function spbu_tour_lo_list_steps(): array
{

    if ((int) ($_SESSION['activity_done'] ?? 0) >= 2) {
        return ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    }

    $hasDraft = false;
    foreach (array_keys(LO_LIST) as $id) {
        if (!empty($_SESSION['lo_draft'][$id]) && empty($_SESSION['lo_done'][$id])) {
            $hasDraft = true;
            break;
        }
    }

    if ($hasDraft) {
        return ['journey' => 'checklist', 'subKey' => 'lo_list_kirim', 'steps' => [
            [
                'no'       => null,
                'label'    => 'Kirim Checklist',
                'target'   => '[data-tour="daftar-lo"]',
                'title'    => 'Pastikan LO yang dikirim tercentang',
                'text'     => 'Checklist LO berstatus “Draft” sudah terisi tetapi BELUM dikirim. Centang LO yang ingin dikirim, atau ketuk “Pilih Semua”. Tombol “Kirim” baru menyala setelah ada LO Draft yang dicentang.',
                'hint'     => '👆 Pastikan LO yang dikirim sudah dicentang',
                'done'     => '#btnKirimChecklist:not([disabled])',
                'minShow'  => 3000,
                'condHint' => '✅ Sudah tercentang. Pastikan LO yang dikirim sudah benar.',
            ],
            [
                'no'     => 7,
                'label'  => 'Kirim Checklist',
                'target' => '#btnKirimChecklist',
                'title'  => 'Ketuk “Kirim”',
                'text'   => 'Ketuk “Kirim”, lalu akan muncul pertanyaan konfirmasi sebelum checklist benar-benar dikirim.',
                'hint'   => '👆 Ketuk tombol biru “Kirim”',
                'done'   => '#kirimModal:not([hidden])',
            ],
            [

                'no'        => 8,
                'label'     => 'Kirim Checklist',
                'target'    => '#btnYaKirim',
                'highlight' => '#kirimModal .modal-sheet',
                'when'      => '#kirimModal:not([hidden])',
                'title'     => 'Konfirmasi pengiriman',
                'text'      => 'Pastikan semua jawaban sudah benar, karena data yang sudah dikirim tidak bisa diubah lagi. Ketuk “Ya, Kirim” untuk mengirim, atau “Batal” untuk memeriksa lagi.',
                'hint'      => '👆 Ketuk “Ya, Kirim”',
                'done'      => null,
            ],
        ]];
    }

    return ['journey' => 'checklist', 'subKey' => 'lo_list_pilih', 'steps' => [
        [
            'no'       => 1,
            'label'    => 'Daftar LO',
            'target'   => '[data-tour="daftar-lo"]',
            'title'    => 'Pastikan LO yang dibongkar tercentang',
            'text'     => 'Setiap kartu adalah satu LO (Loading Order) dengan produknya (lihat baris “Order”). Centang LO yang akan dibongkar di SPBU ini, atau ketuk “Pilih Semua”. Status “Belum Diisi” berarti checklist LO itu belum dikerjakan.',
            'hint'     => '👆 Ketuk kotak centang pada LO yang dibongkar',
            'done'     => '.lo-card.selected',
            'minShow'  => 3000,
            'condHint' => '✅ Sudah tercentang. Pastikan LO yang dibongkar sudah benar.',
        ],
        [
            'no'     => 2,
            'label'  => 'Daftar LO',
            'target' => '[data-tour="mulai-checklist"]',
            'title'  => 'Ketuk “Mulai Checklist”',
            'text'   => 'Anda akan mengisi 14 soal pemeriksaan sebelum pembongkaran BBM, lalu mengonfirmasi status tiap LO. Isi dengan jujur dan bertanggung jawab.',
            'hint'   => '👆 Ketuk tombol “Mulai Checklist”',
            'done'   => null,
        ],
    ]];
}

function spbu_tour_checklist_steps(): array
{
    $none = ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 2) {
        return $none;
    }

    $total = count(CHECKLIST_STEPS);
    $step  = max(1, min($total, (int) ($_SESSION['checklist_step'] ?? 1)));
    $type  = CHECKLIST_STEPS[$step]['type'] ?? '';
    $next  = '[data-tour="wizard-next"]';

    $ownGroup   = '.checklist-card .choice-group:not([data-tour="verif-lawan"])';
    $lawanGroup = '[data-tour="verif-lawan"]';

    if ($type === 'form_spp') {
        $modalProduk = '.spp-modal input[name="action"][value="save_spp_produk"]';
        $modalSegel  = '.spp-modal input[name="action"][value="save_spp_segel"]';
        return ['journey' => 'checklist', 'subKey' => 'checklist_spp', 'steps' => [

            [
                'no'        => 4,
                'label'     => 'Soal 6 · Produk',
                'target'    => '[data-tour="spp-produk-group"] .nav-item-card:not(.done)',
                'highlight' => '[data-tour="spp-produk-group"]',
                'title'     => 'Ketuk kartu Produk',
                'text'      => 'Periksa kesesuaian SPP: produk, nomor segel (periksa keutuhan segel bawah dan atas), nopol Mobil Tangki, dan nama AMT. Mulai dari Produk: ketuk kartu Produk yang menyala. Bila ada beberapa produk, semuanya wajib diisi satu per satu.',
                'hint'      => '👆 Ketuk kartu Produk yang menyala',
                'done'      => '.spp-modal, [data-tour="spp-produk-group"][data-all-done="1"]',
                'remember'  => true,
            ],
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Produk',
                'target'    => '[data-tour="spp-produk-kesesuaian"]',
                'highlight' => '.spp-modal',
                'when'      => $modalProduk,
                'optional'  => true,
                'title'     => 'Pilih Sesuai / Tidak Sesuai',
                'text'      => 'Bandingkan Nomor LO, Produk, dan Quantity pada kartu ini dengan kondisi sebenarnya, lalu pilih “Sesuai” atau “Tidak Sesuai”.',
                'hint'      => '👆 Pilih “Sesuai” atau “Tidak Sesuai”',
                'done'      => '.spp-modal input[name="kesesuaian"]:checked',
                'ok'        => '✅ Jawaban sudah dipilih',
                'remember'  => true,
            ],
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Produk',
                'target'    => '.spp-modal-save',
                'highlight' => '.spp-modal',
                'when'      => $modalProduk,
                'optional'  => true,
                'title'     => 'Ketuk “Simpan”',
                'text'      => 'Ketuk “Simpan” untuk menyimpan verifikasi Produk ini.',
                'hint'      => '👆 Ketuk tombol “Simpan”',
                'done'      => null,
                'remember'  => true,
            ],

            [
                'no'        => 4,
                'label'     => 'Soal 6 · Produk',
                'target'    => '[data-tour="spp-produk-group"] .nav-item-card:not(.done)',
                'highlight' => '[data-tour="spp-produk-group"]',
                'title'     => 'Isi Produk berikutnya',
                'text'      => 'Ketuk kartu Produk berikutnya yang menyala dan isi seperti tadi. Semua Produk wajib diisi.',
                'hint'      => '👆 Ketuk kartu Produk yang menyala',
                'done'      => '.spp-modal, [data-tour="spp-produk-group"][data-all-done="1"]',
            ],

            [
                'no'        => 4,
                'label'     => 'Soal 6 · Segel',
                'target'    => '[data-tour="spp-segel-group"] .nav-item-card:not(.done)',
                'highlight' => '[data-tour="spp-segel-group"]',
                'title'     => 'Ketuk nomor Segel',
                'text'      => 'Semua Produk sudah terverifikasi. Sekarang periksa Segel: ketuk salah satu nomor Segel yang dibongkar di SPBU saat ini. Semua nomor Segel yang masih “Belum Dibongkar” wajib diisi satu per satu.',
                'hint'      => '👆 Ketuk nomor Segel yang menyala',
                'done'      => '.spp-modal, [data-tour="spp-segel-group"][data-all-done="1"]',
                'remember'  => true,
            ],
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Segel',
                'target'    => '[data-tour="spp-segel-kesesuaian"]',
                'highlight' => '.spp-modal',
                'when'      => $modalSegel,
                'optional'  => true,
                'title'     => 'Kesesuaian nomor segel',
                'text'      => 'Jawab “Bagaimana nomor segel yang didapat?”. Pilih “Sesuai” bila nomornya cocok dengan SPP, atau “Tidak Sesuai” bila berbeda.',
                'hint'      => '👆 Pilih “Sesuai” atau “Tidak Sesuai”',
                'done'      => '.spp-modal input[name="kesesuaian"]:checked',
                'ok'        => '✅ Jawaban sudah dipilih',
                'remember'  => true,
            ],
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Segel',
                'target'    => '[data-tour="spp-segel-kondisi"]',
                'highlight' => '.spp-modal',
                'when'      => $modalSegel,
                'optional'  => true,
                'title'     => 'Kondisi segel',
                'text'      => 'Jawab “Bagaimana kondisi segel yang didapat?”. Pilih “Sesuai” bila segel masih utuh, atau “Tidak Sesuai” bila rusak.',
                'hint'      => '👆 Pilih “Sesuai” atau “Tidak Sesuai”',
                'done'      => '.spp-modal input[name="kondisi"]:checked',
                'ok'        => '✅ Jawaban sudah dipilih',
                'remember'  => true,
            ],
            [
                'no'        => 4,
                'label'     => 'Soal 6 · Segel',
                'target'    => '.spp-modal-save',
                'highlight' => '.spp-modal',
                'when'      => $modalSegel,
                'optional'  => true,
                'title'     => 'Ketuk “Simpan”',
                'text'      => 'Ketuk “Simpan” untuk menyimpan verifikasi Segel ini.',
                'hint'      => '👆 Ketuk tombol “Simpan”',
                'done'      => null,
                'remember'  => true,
            ],

            [
                'no'        => 4,
                'label'     => 'Soal 6 · Segel',
                'target'    => '[data-tour="spp-segel-group"] .nav-item-card:not(.done)',
                'highlight' => '[data-tour="spp-segel-group"]',
                'title'     => 'Isi Segel berikutnya',
                'text'      => 'Ketuk nomor Segel berikutnya yang menyala dan isi seperti tadi. Semua nomor Segel wajib diisi.',
                'hint'      => '👆 Ketuk nomor Segel yang menyala',
                'done'      => '.spp-modal, [data-tour="spp-segel-group"][data-all-done="1"]',
            ],

            [
                'no'     => 4,
                'label'  => 'Soal 6 · Selesai',
                'target' => $next,
                'when'   => $next,
                'title'  => 'Ketuk “Selanjutnya”',
                'text'   => 'Semua Produk dan Segel sudah diverifikasi. Ketuk “Selanjutnya” untuk melanjutkan ke soal berikutnya.',
                'hint'   => '👆 Ketuk “Selanjutnya”',
                'done'   => null,
            ],
        ]];
    }

    if ($type === 'form_ukur') {
        return ['journey' => 'checklist', 'subKey' => 'checklist_ukur', 'steps' => [
            [
                'no'        => 5,
                'label'     => 'Soal 7 · Daftar LO',
                'target'    => '[data-tour="ukur-lo-group"] .measure-lo-card:not(.done)',
                'highlight' => '[data-tour="ukur-lo-group"]',
                'title'     => 'Pilih LO untuk diisi',
                'text'      => 'Ketuk LO dengan Form Bongkar “Belum Terisi” untuk mengisi data pengukuran pembongkaran BBM-nya di layar berikutnya. Semua LO wajib diisi, satu per satu.',
                'hint'      => '👆 Ketuk kartu LO yang menyala',
                'done'      => '[data-tour="ukur-lo-group"][data-all-done="1"]',
            ],
            [
                'no'     => 5,
                'label'  => 'Soal 7 · Selesai',
                'target' => $next,
                'title'  => 'Ketuk “Selanjutnya”',
                'text'   => 'Semua LO sudah diisi form pengukurannya. Ketuk “Selanjutnya” untuk melanjutkan ke soal berikutnya.',
                'hint'   => '👆 Ketuk “Selanjutnya”',
                'done'   => null,
            ],
        ]];
    }

    if ($type === 'test_report') {
        return ['journey' => 'checklist', 'subKey' => 'checklist_report', 'steps' => [[
            'no'     => 3,
            'label'  => 'Soal 8 · Test Report',
            'target' => $next,
            'title'  => 'Ketuk “Selanjutnya”',
            'text'   => 'Dokumen Test Report sudah otomatis tercatat “telah dilihat”, jadi tidak ada yang perlu dijawab di soal ini. Ketuk “Selanjutnya” untuk melanjutkan.',
            'hint'   => '👆 Ketuk “Selanjutnya”',
            'done'   => null,
        ]]];
    }

    if ($step === $total) {
        return ['journey' => 'checklist', 'subKey' => 'checklist_last', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Terakhir · Bagian 1',
                'target' => $ownGroup,
                'title'  => 'Soal terakhir: tugas Anda',
                'text'   => 'Soal terakhir punya dua bagian. Bagian pertama: verifikasi pekerjaan Anda sendiri. Pilih “Ya, dilakukan” atau “Tidak Dilakukan”. Foto bukti boleh dilewati.',
                'hint'   => '👆 Pilih salah satu jawaban',
                'done'   => $ownGroup . ' .btn-choice.is-selected',
                'ok'     => '✅ Bagian 1 sudah dijawab',
            ],
            [
                'no'     => 3,
                'label'  => 'Soal Terakhir · Bagian 2',
                'target' => $lawanGroup,
                'title'  => 'Verifikasi Tugas Role Lawan',
                'text'   => 'Bagian kedua: pilih apakah AMT sudah mengerjakan tugasnya. Periksa langsung di lapangan, lalu jawab sesuai kenyataan.',
                'hint'   => '👆 Pilih salah satu jawaban',
                'done'   => $lawanGroup . ' .btn-choice.is-selected',
                'ok'     => '✅ Semua jawaban sudah dipilih',
            ],
            spbu_tour_next_card(true),
        ]];
    }

    if ($type === 'dual_verif') {
        return ['journey' => 'checklist', 'subKey' => 'checklist_dual', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Dua Bagian · Bagian 1',
                'target' => $ownGroup,
                'title'  => 'Bagian 1: tugas Anda',
                'text'   => 'Soal ini punya dua bagian yang wajib dijawab. Bagian pertama: verifikasi pekerjaan Anda sendiri. Pilih “Ya, dilakukan” atau “Tidak Dilakukan”. Foto bukti boleh dilewati.',
                'hint'   => '👆 Pilih salah satu jawaban',
                'done'   => $ownGroup . ' .btn-choice.is-selected',
                'ok'     => '✅ Bagian 1 sudah dijawab',
            ],
            [
                'no'     => 3,
                'label'  => 'Soal Dua Bagian · Bagian 2',
                'target' => $lawanGroup,
                'title'  => 'Bagian 2: Verifikasi Tugas Role Lawan',
                'text'   => 'Bagian kedua: pilih apakah AMT sudah mengerjakan tugasnya. Periksa langsung di lapangan, lalu jawab sesuai kenyataan.',
                'hint'   => '👆 Pilih salah satu jawaban',
                'done'   => $lawanGroup . ' .btn-choice.is-selected',
                'ok'     => '✅ Semua jawaban sudah dipilih',
            ],
            spbu_tour_next_card(),
        ]];
    }

    if ($type === 'action') {
        return ['journey' => 'checklist', 'subKey' => 'checklist_action', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Tugas AMT',
                'target' => $ownGroup,
                'title'  => 'Soal Verifikasi Tugas AMT',
                'text'   => 'Soal ini adalah tugas yang dikerjakan AMT. Periksa langsung di lapangan, lalu pilih “Ya, dilakukan” bila AMT sudah mengerjakannya, atau “Tidak Dilakukan” bila belum. Jawab sesuai kenyataan.',
                'hint'   => '👆 Pilih salah satu jawaban',
                'done'   => $next,
                'ok'     => '✅ Jawaban sudah dipilih',
            ],
            spbu_tour_next_card(),
        ]];
    }

    $steps = [];
    $steps[] = [
        'no'     => 3,
        'label'  => 'Soal Tugas Anda',
        'target' => $ownGroup,
        'title'  => 'Soal Tugas Petugas SPBU',
        'text'   => 'Soal ini adalah tugas Anda sendiri sebagai Petugas SPBU. Kerjakan tugasnya, lalu pilih “Ya, Dilakukan” bila sudah, atau “Tidak Dilakukan” bila belum. Foto bukti boleh dilewati.',
        'hint'   => '👆 Pilih salah satu jawaban',
        'done'   => $next,
        'ok'     => '✅ Jawaban sudah dipilih',
    ];
    $steps[] = spbu_tour_next_card();
    return ['journey' => 'checklist', 'subKey' => 'checklist_self', 'steps' => $steps];
}

function spbu_tour_claim_loss_steps(): array
{
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 2) {
        return ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    }
    $loKey = preg_replace('/[^A-Za-z0-9]/', '', (string) ($_GET['lo'] ?? ''));
    $steps = [
        [
            'no'       => 5,
            'label'    => 'Soal 7 · Metode Pengukuran',
            'target'   => '[data-tour="claim-metode-group"]',
            'title'    => 'Pilih metode pengukuran',
            'text'     => 'Ada dua metode: “IJKBOUT” (serah terima custody transfer, diukur dengan dipstick) dan “Flow Meter” (meter arus pada mobil tangki PTO/portabel, terpilih lebih dulu). Pilih yang sesuai dengan pembongkaran di lapangan.',
            'hint'     => '👆 Pilih metode, atau ketuk “Mengerti” bila sudah sesuai',
            'button'   => 'Mengerti',
            'remember' => true,
            'done'     => null,
        ],
        [
            'no'       => 5,
            'label'    => 'Soal 7 · Form Pengukuran',
            'target'   => '.claim-form',
            'title'    => 'Isi form pengukuran',
            'text'     => 'Isi semua kolom bertanda * sesuai hasil pengukuran sebenarnya. Flow Meter: Volume Meter, Temperatur Obs, Density Obs. IJKBOUT: pilih Kompartemen (T2 Tera MT terisi otomatis), lalu isi Level BBM Sebelum Bongkar, Temperatur Obs, Density Obs. Tombol “Generate” menyala setelah semuanya terisi.',
            'hint'     => '👆 Isi semua kolom yang bertanda *',
            'done'     => '#claimForm[data-ready="1"]',
            'settle'   => 1500,
            'ok'       => '✅ Semua kolom sudah terisi',
        ],
        [
            'no'     => 5,
            'label'  => 'Soal 7 · Form Pengukuran',
            'target' => '#btnGenerate',
            'title'  => 'Ketuk “Generate”',
            'text'   => 'Semua kolom sudah terisi, jadi tombol “Generate” menyala. Ketuk untuk menghitung selisih kekurangan (Claim Losses).',
            'hint'   => '👆 Ketuk tombol biru “Generate”',
            'done'   => '#hasilModal',
        ],
        [
            'no'        => 5,
            'label'     => 'Soal 7 · Hasil Generate',
            'target'    => '[data-tour="hasil-primary-action"]',
            'highlight' => '.hasil-generate-sheet',
            'when'      => '#hasilModal[data-claim="0"]',
            'optional'  => true,
            'title'     => 'Tidak ada Claim Loss',
            'text'      => 'Hasil Generate menunjukkan selisih 0 Liter dengan status “Tidak Bisa di Klaim”, artinya tidak ada Claim Loss. Tombol “Ajukan Klaim” nonaktif. Ketuk “Simpan Tanpa Klaim” untuk menyimpan hasil pengukuran dan kembali ke halaman checklist.',
            'hint'      => '👆 Ketuk “Simpan Tanpa Klaim”',
            'done'      => null,
        ],
        [
            'no'        => 5,
            'label'     => 'Soal 7 · Hasil Generate',
            'target'    => '[data-tour="hasil-primary-action"]',
            'highlight' => '.hasil-generate-sheet',
            'when'      => '#hasilModal[data-claim="1"]',
            'optional'  => true,
            'title'     => 'Ada Claim Loss',
            'text'      => 'Hasil Generate menunjukkan selisih kekurangan dengan status “Dapat di Klaim”, artinya ada Claim Loss. Ketuk “Ajukan Klaim” untuk melengkapi dokumen klaim: upload Sertifikat Dipstick lalu scan QR Code AMT (pada tutorial, scan disimulasikan otomatis).',
            'hint'      => '👆 Ketuk tombol biru “Ajukan Klaim”',
            'done'      => null,
        ],
    ];
    return ['journey' => 'checklist', 'subKey' => 'claim_loss_' . $loKey, 'steps' => $steps];
}

function spbu_tour_claim_upload_steps(): array
{
    $claimIds = spbu_claim_lo_ids();
    $loKey    = preg_replace('/[^A-Za-z0-9]/', '', (string) ($claimIds[0] ?? ''));
    return ['journey' => 'checklist', 'subKey' => 'claim_upload_' . $loKey, 'steps' => [
        [
            'no'     => 5,
            'label'  => 'Claim Loss · Sertifikat Dipstick',
            'target' => '[data-tour="cu-dipstick"] .cu-action',
            'highlight' => '[data-tour="cu-dipstick"]',
            'title'  => 'Upload Sertifikat Dipstick',
            'text'   => 'Ketuk “Upload”, lalu pilih foto atau file (PDF, JPG, PNG, maksimal 5 MB) Sertifikat Dipstick. Dokumen ini wajib untuk mengajukan Claim Loss.',
            'hint'   => '👆 Ketuk “Upload”',
            'done'   => '[data-tour="cu-dipstick"].is-filled',
            'ok'     => '✅ Sertifikat Dipstick sudah diupload',
        ],
        [
            'no'     => 5,
            'label'  => 'Claim Loss · QR Code AMT',
            'target' => '[data-tour="cu-qr"] .cu-action',
            'highlight' => '[data-tour="cu-qr"]',
            'title'  => 'Scan QR Code AMT',
            'text'   => 'Ketuk “Scan Sekarang” untuk memindai QR Code Claim Loss yang ditunjukkan AMT. Pada tutorial ini pemindaian disimulasikan otomatis, jadi kamera tidak perlu dipakai.',
            'hint'   => '👆 Ketuk “Scan Sekarang”',
            'done'   => '[data-tour="cu-qr"].is-filled',
            'ok'     => '✅ QR Code AMT sudah discan',
        ],
        [
            'no'     => 5,
            'label'  => 'Claim Loss · Kirim Klaim',
            'target' => '#btnKirimKlaim',
            'title'  => 'Ketuk “Kirim Klaim”',
            'text'   => 'Kedua dokumen sudah lengkap, jadi tombol “Kirim Klaim” menyala. Ketuk untuk mengirim pengajuan Claim Loss. Setelah terkirim, Anda kembali ke halaman checklist soal 7 untuk melanjutkan checklist.',
            'hint'   => '👆 Ketuk tombol biru “Kirim Klaim”',
            'done'   => null,
        ],
    ]];
}

function spbu_tour_claim_scan_steps(): array
{
    $claimIds = spbu_claim_lo_ids();
    $loKey    = preg_replace('/[^A-Za-z0-9]/', '', (string) ($claimIds[0] ?? ''));
    return ['journey' => 'checklist', 'subKey' => 'claim_scan_' . $loKey, 'steps' => [
        [
            'no'       => 5,
            'label'    => 'Claim Loss · Scan QR AMT',
            'target'   => '.scan-frame',
            'title'    => 'Simulasi scan QR AMT',
            'text'     => 'Pada kondisi sebenarnya, AMT menampilkan QR Code Claim Loss lalu Anda mengarahkan kamera ke QR tersebut. Di tutorial ini pemindaian disimulasikan: ketuk “Mengerti”, QR AMT akan terbaca otomatis dalam beberapa detik, lalu Anda kembali ke halaman dokumen.',
            'button'   => 'Mengerti',
            'announce' => true,
            'done'     => null,
        ],
    ]];
}

function spbu_tour_konfirmasi_steps(): array
{
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 2) {
        return ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    }
    return ['journey' => 'checklist', 'subKey' => 'konfirmasi_lo', 'steps' => [
        [
            'no'        => 6,
            'label'     => 'Konfirmasi LO',
            'target'    => '[data-tour="konfirmasi-lo-group"] .konfirmasi-card:not(.is-set) .konfirmasi-actions',
            'highlight' => '[data-tour="konfirmasi-lo-group"] .konfirmasi-card:not(.is-set)',
            'title'     => 'Tentukan status tiap LO',
            'text'      => 'Ketuk “Sudah Dibongkar” bila BBM untuk LO ini jadi dibongkar, atau “Tidak Jadi Bongkar” bila pembongkaran dibatalkan. Semua LO wajib ditentukan statusnya.',
            'hint'      => '👆 Pilih status pada LO yang menyala',
            'done'      => '[data-tour="konfirmasi-lo-group"][data-all-done="1"]',
            'ok'        => '✅ Semua LO sudah ditentukan statusnya',
        ],
        [
            'no'     => 6,
            'label'  => 'Konfirmasi LO',
            'target' => '[data-tour="wizard-next"]',
            'title'  => 'Ketuk “Konfirmasi LO”',
            'text'   => 'Semua LO sudah punya status. Ketuk “Konfirmasi LO” untuk menyimpan dan kembali ke Daftar LO. Setelah itu checklist berstatus “Draft” dan siap dikirim.',
            'hint'   => '👆 Ketuk tombol “Konfirmasi LO”',
            'done'   => null,
        ],
    ]];
}

function spbu_tour_notifikasi_steps(): array
{
    if ((int) ($_SESSION['activity_done'] ?? 0) >= 3) {
        return ['journey' => 'verifikasi', 'subKey' => null, 'steps' => []];
    }
    $dismissed = '#verifOrderModal[data-dismissed]';
    $muncul    = '#verifOrderModal:not([hidden])';
    return ['journey' => 'verifikasi', 'subKey' => 'notifikasi', 'steps' => [
        [
            'no'     => 2,
            'label'  => 'Verifikasi Order',
            'target' => null,
            'title'  => 'Tunggu pemberitahuan dari AMT',
            'text'   => 'Sebentar lagi akan ada pemberitahuan dari AMT yang meminta Verifikasi Order. AMT akan memindai (scan) kode yang muncul di layar HP Anda. Anda tidak perlu melakukan apa-apa dulu, cukup tunggu sampai pemberitahuannya muncul.',
            'button' => 'Mengerti',
            'autoHide' => 20000,
            'done'   => null,
        ],
        [

            'no'        => 2,
            'label'     => 'Verifikasi Order',
            'target'    => '#btnLihatNotifikasi',
            'highlight' => '#verifOrderModal .modal-sheet',
            'when'      => $muncul,
            'skip'      => $dismissed,
            'title'     => 'AMT meminta verifikasi',
            'text'      => 'AMT sedang meminta Verifikasi Order. Ketuk “Lihat Notifikasi” untuk membuka kode yang akan dipindai (scan) oleh AMT.',
            'hint'      => '👆 Ketuk tombol biru “Lihat Notifikasi”',
            'done'      => null,
        ],
        [
            'no'     => 2,
            'label'  => 'Verifikasi Order',
            'target' => '#notifVerifikasiCard',
            'when'   => '#notifVerifikasi:not([hidden])',
            'title'  => 'Ketuk pemberitahuan ini',
            'text'   => 'Pemberitahuan “Verifikasi Order” dari AMT ada di sini. Ketuk untuk membuka kode yang akan dipindai (scan) oleh AMT.',
            'hint'   => '👆 Ketuk kartu “Verifikasi Order”',
            'done'   => null,
        ],
    ]];
}

function spbu_tour_qr_steps(): array
{

    if ((int) ($_SESSION['activity_done'] ?? 0) >= 3) {
        return ['journey' => 'verifikasi', 'subKey' => null, 'steps' => []];
    }
    return ['journey' => 'verifikasi', 'subKey' => 'qr_code', 'steps' => [
        [
            'no'     => 3,
            'label'  => 'Verifikasi Order',
            'target' => '.qr-box',
            'title'  => 'Minta AMT memindai kode',
            'text'   => 'Minta AMT memindai (scan) Kode QR ini. Kode ini punya waktu kadaluwarsa (hitung mundur di bawah kode), jadi jangan ditunda.',
            'hint'   => '⏳ Menunggu AMT memindai kode…',
            'done'   => '#verifBerhasilModal:not([hidden])',
            'ok'     => '✅ AMT sudah memverifikasi',
        ],
        [
            'no'        => 4,
            'label'     => 'Verifikasi Order',
            'target'    => '#btnBeriPenilaian',
            'highlight' => '#verifBerhasilModal .modal-sheet',
            'when'      => '#verifBerhasilModal:not([hidden])',
            'title'     => 'Ketuk “Beri Penilaian”',
            'text'      => 'Verifikasi sudah berhasil dilakukan AMT. Ketuk “Beri Penilaian” untuk menilai pelayanan kedua AMT yang bertugas.',
            'hint'      => '👆 Ketuk tombol biru “Beri Penilaian”',
            'done'      => null,
        ],
    ]];
}

function spbu_tour_rating_steps(): array
{
    if ((int) ($_SESSION['activity_done'] ?? 0) >= count(ACTIVITY_STEPS)) {
        return ['journey' => 'rating', 'subKey' => null, 'steps' => []];
    }

    $isAmt2 = (int) ($_SESSION['rating_step'] ?? 1) >= 2;
    $who    = $isAmt2 ? 'AMT 2' : 'AMT 1';
    $card   = '.rating-card[data-rating-card]:not(.is-rated)';

    $explain = [
        'Penilaian keseluruhan untuk ' . $who . ' (kartu paling atas).',
        'Safety AMT: seragam dan APD dipakai dengan benar.',
        'Sarfas: mobil tangki aman untuk proses pembongkaran.',
        'Komunikasi: AMT melakukan kroscek dan menginformasikan produk.',
        'Operasional: AMT standby di lingkungan SPBU saat bongkar.',
        'Aspek Layanan: ketepatan volume BBM.',
    ];

    return ['journey' => 'rating', 'subKey' => $isAmt2 ? 'rating_amt2' : 'rating_amt1', 'steps' => [

        [
            'no'        => $isAmt2 ? 3 : 1,
            'label'     => 'Rating ' . $who,
            'target'    => $card . ' .stars',
            'highlight' => $card,
            'title'     => 'Beri bintang untuk ' . $who,
            'text'      => 'Ketuk bintang pada tiap kartu: 1 bintang berarti sangat kurang, 5 bintang berarti luar biasa. Semua kartu wajib dinilai:',
            'list'      => $explain,
            'hint'      => '👆 Ketuk bintang pada kartu yang menyala',
            'done'      => '.rating-card.is-rated',
        ],

        [
            'no'        => $isAmt2 ? 3 : 1,
            'label'     => 'Rating ' . $who,
            'target'    => $card . ' .stars',
            'highlight' => $card,
            'when'      => '#ratingForm[data-rated="1"]',
            'nodim'     => true,
            'title'     => 'Lanjutkan mengisi bintang',
            'text'      => 'Bagus! Ketuk bintang pada kartu penilaian lain yang masih kosong sampai semuanya terisi.',
            'hint'      => '👆 Isi bintang di kartu yang masih kosong',
            'done'      => '#ratingForm[data-complete="1"]',
            'ok'        => '✅ Semua penilaian sudah terisi',
            'okText'    => $isAmt2 ? 'Tinggal kirim penilaian Anda.' : 'Lanjut menilai AMT 2.',
            'hold'      => 1500,
        ],

        $isAmt2
            ? [
                'no'     => 4,
                'label'  => 'Rating ' . $who,
                'target' => '#btnRatingLanjut',
                'when'   => '#ratingForm[data-complete="1"]',
                'title'  => 'Ketuk “Kirim”',
                'text'   => 'Semua penilaian AMT 1 dan AMT 2 sudah lengkap. Kolom “Keterangan Lainnya” di tiap AMT boleh dikosongkan. Ketuk “Kirim” untuk mengirim rating dan menyelesaikan seluruh aktifitas di SPBU.',
                'hint'   => '👆 Ketuk tombol biru “Kirim”',
                'done'   => null,
            ]
            : [
                'no'     => 2,
                'label'  => 'Rating ' . $who,
                'target' => '#btnRatingLanjut',
                'when'   => '#ratingForm[data-complete="1"]',
                'title'  => 'Ketuk “Selanjutnya”',
                'text'   => 'Penilaian AMT 1 sudah lengkap, jadi tombol “Selanjutnya” menyala. Kolom “Keterangan Lainnya” boleh dikosongkan. Ketuk “Selanjutnya” untuk menilai AMT 2.',
                'hint'   => '👆 Ketuk tombol “Selanjutnya”',
                'done'   => null,
            ],
    ]];
}