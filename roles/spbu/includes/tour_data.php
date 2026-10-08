<?php
/**
 * SPBU - data tutorial terpandu (guided tour) tiap layar SPBU.
 * Mesin tutorial: roles/spbu/assets/js/tutorial.js
 * Penyusun konfigurasi per layar: roles/spbu/includes/tour.php
 *
 * Letak: roles/spbu/includes/tour_data.php
 */

// ---------------------------------------------------------------
// TUTORIAL TERPANDU (Guided Tour ala "first time play" game)
// ---------------------------------------------------------------
// Menggantikan kotak petunjuk statis lama (TUTORIAL_TEXTS di atas
// sekarang tidak lagi dirender - lihat core/layout_top.php) dengan
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
const SPBU_TOUR_STEPS = [

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
            'target' => '[data-tour="verif-subjects"]',
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
            'target'    => '#btnKirim',
            // Sorot SELURUH pop up konfirmasi (judul, keterangan, tombol
            // Kirim & Tutup), bukan hanya tombol "Kirim"-nya.
            'highlight' => '#konfirmasiModal .modal-sheet',
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
        // 0. Notifikasi dari AMT masuk (kartu "Aktif" muncul + pop up
        //    "Permintaan Verifikasi Order"): sorot SELURUH pop up-nya.
        //    Tutorial baru tampil begitu pop up terbuka (~5 detik setelah
        //    halaman dibuka) -- sebelum itu halaman polosan dan tutorial
        //    menunggu tanpa batas waktu ('waitForever'). Kalau pengguna
        //    menutup pop up ("Tutup"), halaman menandai
        //    data-dismissed dan langkah ini dilewati ('skipIf').
        [
            'target'      => '#btnLihatNotifikasi',
            'highlight'   => '#verifOrderModal .modal-sheet',
            'waitForever' => true,
            'skipIf'      => '#verifOrderModal[data-dismissed]',
            'title'       => 'Langkah 8 · Verifikasi Order',
            'text'        => 'AMT sudah mengirim Verifikasi Order. Ketuk "Lihat Notifikasi" untuk membuka Kode QR / Kode Konfirmasi yang harus Anda berikan kepada AMT.',
            'place'       => 'top',
        ],
        // 1. Cadangan kalau pop up ditutup: sorot kartu notifikasi "Aktif".
        [
            'target'      => '[data-tour="notif-verifikasi"]',
            'waitForever' => true,
            'title'       => 'Langkah 8 · Verifikasi Order',
            'text'        => 'Notifikasi Verifikasi Order berstatus "Aktif" dari AMT ada di sini — ketuk untuk membukanya.',
            'place'       => 'bottom',
        ],
    ],

    'qr_code' => [
        // 0. Kode QR / Kode Konfirmasi. Begitu AMT selesai memindai
        //    (pop up "Berhasil Melakukan Verifikasi" terbuka), langkah ini
        //    otomatis dilewati lewat 'skipIf' (halaman memanggil
        //    OneFISTour.rescan() saat pop up muncul).
        [
            'target' => '.qr-box',
            'skipIf' => '#verifBerhasilModal:not([hidden])',
            'title'  => 'Langkah 9 · Minta AMT Scan Kode QR',
            'text'   => 'Minta AMT untuk memindai (scan) Kode QR ini. Kalau tidak bisa dipindai, berikan Kode Konfirmasi di bawahnya kepada AMT agar Verifikasi Order dapat diselesaikan — kode ini punya waktu kadaluwarsa, jadi jangan ditunda.',
            'place'  => 'top',
        ],
        // 1. Setelah verifikasi berhasil: sorot SELURUH pop up.
        [
            'target'      => '#btnBeriPenilaian',
            'highlight'   => '#verifBerhasilModal .modal-sheet',
            'waitForever' => true,
            'title'       => 'Langkah 9 · Beri Penilaian',
            'text'        => 'Verifikasi sudah berhasil dilakukan AMT. Ketuk "Beri Penilaian" untuk menilai pelayanan AMT yang bertugas.',
            'place'       => 'top',
        ],
    ],

    // Rating AMT 1 (langkah 1 dari 2). Layar ini dimuat ulang untuk AMT 2,
    // jadi tutorial AMT 2 ada di RATING_AMT2_TOUR_STEPS (lihat bawah) dan
    // dipilih lewat $tourSubKey di roles/spbu/includes/tour.php.
    // Langkah 0 dilewati otomatis ('skipIf') begitu semua kategori sudah
    // dinilai (halaman menandai #ratingForm[data-complete]).
    'rating' => [
        [
            'target' => '.rating-card',
            'skipIf' => '#ratingForm[data-complete]',
            'title'  => 'Langkah 10 · Rating AMT 1',
            'text'   => 'Berikan penilaian bintang untuk Safety AMT, Sarfas, Komunikasi, Operasional, dan Aspek Layanan pada AMT 1. Semua kategori wajib dinilai.',
            'place'  => 'bottom',
        ],
        [
            'target'      => '#btnRatingLanjut',
            'waitForever' => true,
            'title'       => 'Lanjut ke AMT 2',
            'text'        => 'Penilaian AMT 1 sudah lengkap. Ketuk "Selanjutnya" untuk menilai AMT 2.',
            'place'       => 'top',
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

// Tutorial Rating AMT 2 (langkah 2 dari 2, tombol "Kirim"). Dipakai lewat
// $tourSubKey 'rating_amt2' di roles/spbu/includes/tour.php.
const RATING_AMT2_TOUR_STEPS = [
    [
        'target' => '.rating-card',
        'skipIf' => '#ratingForm[data-complete]',
        'title'  => 'Langkah 10 · Rating AMT 2',
        'text'   => 'Sekarang nilai AMT 2: berikan bintang untuk semua kategori. Kolom "Keterangan Lainnya" di bagian bawah boleh dikosongkan.',
        'place'  => 'bottom',
    ],
    [
        'target'      => '#btnRatingLanjut',
        'waitForever' => true,
        'title'       => 'Kirim Penilaian',
        'text'        => 'Semua penilaian sudah lengkap. Ketuk "Kirim" untuk mengirim rating AMT dan menyelesaikan seluruh aktifitas di SPBU.',
        'place'       => 'top',
    ],
];

// Tutorial terpandu KHUSUS Soal 6 ("Periksa kesesuaian SPP yaitu produk,
// nomor segel...") pada wizard Checklist Pra-Pembongkaran. Dipakai lewat
// $tourSubKey di roles/spbu/includes/tour.php (bukan lewat TOUR_STEPS
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
    // 0. Perkenalan Produk -- HANYA tampil SATU KALI, tidak pernah diulang
    //    lagi walau ada beberapa produk yang harus diverifikasi satu per
    //    satu (lihat langkah 3 "Lanjutkan ke Produk Berikutnya").
    [
        'target' => '[data-tour="spp-produk-group"]',
        'title'  => 'Soal 6 · Periksa Kesesuaian SPP',
        'text'   => 'Periksa kesesuaian SPP yaitu produk, nomor segel (periksa keutuhan segel bawah dan atas), nopol Mobil Tangki, dan nama AMT. Ketuk salah satu kartu Produk untuk memulai verifikasi.',
        'place'  => 'bottom',
    ],
    // 1. Isi pop up Produk. 'highlight' sengaja diarahkan ke SELURUH kartu
    //    pop up (.spp-modal) -- jadi sorotan hijaunya membingkai semua isi
    //    kartu (Nomor LO, Produk, Qty, dan pilihannya), bukan cuma kotak
    //    kecil di sekitar baris Sesuai/Tidak Sesuai saja. 'target' tetap
    //    menempel pada baris pilihan itu supaya tutorial baru lanjut
    //    begitu pengguna BENAR-BENAR menjawab.
    //    'quietIf': penjelasan pop up ini HANYA ditampilkan untuk produk
    //    PERTAMA. Begitu ada minimal satu kartu Produk lain yang sudah
    //    berstatus "Sudah Terisi" (chip-done), berarti pengguna sudah
    //    pernah melihat penjelasan ini -- jadi untuk produk-produk
    //    berikutnya, langkah ini tetap MELACAK sentuhan pengguna (supaya
    //    tutorial tahu kapan harus lanjut), tapi TIDAK menampilkan
    //    sorotan/tooltip-nya lagi. Ini yang membuat tutorial pop up Produk
    //    terasa "cukup sekali saja", bukan berulang di setiap produk.
    [
        'target'    => '[data-tour="spp-produk-kesesuaian"]',
        'highlight' => '.spp-modal',
        'title'     => 'Verifikasi Produk',
        'text'      => 'Bandingkan Nomor LO, Produk, dan Qty pada kartu ini dengan kondisi sebenarnya, lalu pilih "Sesuai" atau "Tidak Sesuai".',
        'place'     => 'top',
        'quietIf'   => '[data-tour="spp-produk-group"] .chip-done',
    ],
    // 2. Simpan pop up Produk. Sama seperti langkah 1: diam-diam setelah
    //    produk pertama (lihat 'quietIf' di atas).
    [
        'target'    => '.spp-modal-save',
        'highlight' => '.spp-modal',
        'title'     => 'Simpan Verifikasi Produk',
        'text'      => 'Ketuk "Simpan".',
        'place'     => 'top',
        'quietIf'   => '[data-tour="spp-produk-group"] .chip-done',
    ],
    // 3. 'requireTarget' dicek dulu SETIAP kali langkah ini hendak
    //    ditampilkan: kalau MASIH ada kartu Produk lain berstatus "Belum
    //    Terisi" (mis. baru 1 dari 2 produk yang selesai diverifikasi),
    //    tutorial LANGSUNG menyorot kartu Produk berikutnya yang belum
    //    terisi itu -- TANPA mengulang penjelasan langkah 0 dari awal.
    //    Begitu SEMUA Produk sudah terisi, selector-nya tidak lagi
    //    ditemukan sehingga langkah ini otomatis dilewati (tidak pernah
    //    "nyangkut" menyorot sesuatu yang sudah tidak relevan) dan
    //    tutorial lanjut sendiri ke bagian Segel (langkah 4).
    [
        'target'        => '[data-tour="spp-produk-group"] .nav-item-card:not(.done)',
        'requireTarget' => '[data-tour="spp-produk-group"] .nav-item-card:not(.done)',
        'jumpOnClickTo' => 1,
        'title'         => 'Lanjutkan ke Produk Berikutnya',
        'text'          => 'Masih ada Produk lain yang belum diverifikasi. Ketuk kartu ini untuk melanjutkan.',
        'place'         => 'bottom',
    ],
    // 4. Perkenalan Segel -- HANYA tampil SATU KALI, tepat setelah SEMUA
    //    Produk selesai diverifikasi (baru tercapai lewat langkah 2 atau 3
    //    di atas).
    [
        'target' => '[data-tour="spp-segel-group"]',
        'title'  => 'Verifikasi Segel',
        'text'   => 'Setelah semua Produk terverifikasi, ketuk salah satu nomor Segel yang dibongkar di SPBU saat ini.',
        'place'  => 'bottom',
    ],
    // 5-6. Isi pop up Segel. Sama seperti Produk: 'highlight' menyorot
    //      SELURUH kartu pop up (.spp-modal), bukan cuma baris pilihannya
    //      saja. 'quietIf': penjelasan ini juga HANYA tampil untuk Segel
    //      PERTAMA -- begitu ada minimal satu Segel lain yang sudah
    //      "Sudah Dibongkar" (chip-done), langkah ini tetap melacak
    //      klik pengguna secara diam-diam tapi TIDAK menampilkan sorotan/
    //      tooltip-nya lagi untuk Segel kedua dan seterusnya.
    [
        'target'     => '[data-tour="spp-segel-kesesuaian"]',
        'highlight'  => '.spp-modal',
        'title'      => 'Kesesuaian Nomor Segel',
        'text'       => 'Verifikasi "Bagaimana nomor segel yang didapat?" — pilih "Sesuai" atau "Tidak Sesuai".',
        'place'      => 'top',
        'quietIf'    => '[data-tour="spp-segel-group"] .chip-done',
        'quietIfMin' => [
            'selector' => '[data-tour="spp-segel-group"]',
            'attr'     => 'data-tour-done-count',
            'min'      => 1,
        ],
    ],
    [
        'target'     => '[data-tour="spp-segel-kondisi"]',
        'highlight'  => '.spp-modal',
        'title'      => 'Kondisi Segel',
        'text'       => 'Verifikasi "Bagaimana kondisi segel yang didapat?" — pilih "Sesuai" atau "Tidak Sesuai".',
        'place'      => 'top',
        'quietIf'    => '[data-tour="spp-segel-group"] .chip-done',
        'quietIfMin' => [
            'selector' => '[data-tour="spp-segel-group"]',
            'attr'     => 'data-tour-done-count',
            'min'      => 1,
        ],
    ],
    // 7. Simpan pop up Segel. Sama juga: diam-diam mulai Segel kedua.
    [
        'target'     => '.spp-modal-save',
        'highlight'  => '.spp-modal',
        'title'      => 'Simpan Verifikasi Segel',
        'text'       => 'Ketuk "Simpan".',
        'place'      => 'top',
        'quietIf'    => '[data-tour="spp-segel-group"] .chip-done',
        'quietIfMin' => [
            'selector' => '[data-tour="spp-segel-group"]',
            'attr'     => 'data-tour-done-count',
            'min'      => 1,
        ],
    ],
    // 8. Setelah Segel PERTAMA disimpan (langkah 7), langkah ini muncul
    //    TEPAT SATU KALI: menyorot SELURUH bagian Segel ('highlight' =
    //    seluruh grup, bukan cuma satu kartu) supaya pengguna melihat
    //    semua nomor Segel yang masih "Belum Dibongkar" sekaligus, lalu
    //    memintanya melengkapi semuanya satu per satu. 'target' tetap
    //    menempel ke kartu Segel pertama yang belum terisi (dipakai untuk
    //    mendeteksi klik & requireTarget), sementara 'quietIfMin' membuat
    //    langkah ini DIAM (tidak menyorot apa pun lagi, tapi tetap
    //    melacak klik secara diam-diam) begitu SUDAH ADA 2 atau lebih
    //    Segel yang terisi -- artinya hint ini sudah pernah tampil
    //    sebelumnya. Jadi hint "Isi Sisa Segel" ini betul-betul hanya
    //    tampil SATU KALI; sesudah itu tidak ada tutorial lagi sampai
    //    SEMUA Segel selesai (baru langkah 9 di bawah yang muncul).
    [
        'target'        => '[data-tour="spp-segel-group"] .nav-item-card:not(.done)',
        'highlight'     => '[data-tour="spp-segel-group"]',
        'requireTarget' => '[data-tour="spp-segel-group"] .nav-item-card:not(.done)',
        'quietIfMin'    => [
            'selector' => '[data-tour="spp-segel-group"]',
            'attr'     => 'data-tour-done-count',
            'min'      => 2,
        ],
        'jumpOnClickTo' => 5,
        'title'         => 'Lengkapi Sisa Segel',
        'text'          => 'Masih ada nomor Segel lain yang belum diverifikasi (ditandai "Belum Dibongkar"). Lengkapi semuanya satu per satu.',
        'place'         => 'bottom',
    ],
    // 9. Baru setelah BENAR-BENAR SEMUA Produk dan SEMUA Segel selesai
    //    diverifikasi (tombol "Selanjutnya" otomatis aktif -- lihat
    //    checklist_step_done() di roles/spbu/includes/functions.php), tutorial
    //    menyorot tombol tsb dan meminta pengguna melanjutkan ke soal
    //    checklist berikutnya.
    [
        'target' => '[data-tour="wizard-next"]',
        'title'  => 'Lanjutkan Checklist',
        'text'   => 'Semua Produk dan Segel sudah diverifikasi. Ketuk "Selanjutnya" untuk melanjutkan ke soal checklist berikutnya.',
        'place'  => 'top',
    ],
];

// Tutorial terpandu KHUSUS Soal 7 ("user perlu mengisi form data LO
// berdasarkan Metode Pengukuran yang dipilih") pada wizard Checklist
// Pra-Pembongkaran. Sama seperti CHECKLIST_SOAL6_TOUR_STEPS di atas,
// dipakai lewat $tourSubKey ('checklist_soal7') di roles/spbu/includes/tour.php
// supaya statusnya "sudah ditonton" + posisi langkahnya dilacak sebagai
// SATU rangkaian tutorial yang sama, walau langkah-langkahnya sendiri
// tersebar di DUA layar berbeda: daftar LO ada di layar 'checklist'
// (step 7), sedangkan pemilihan metode + pengisian form + hasil generate
// ada di layar terpisah 'claim_loss' (lihat views/claim_loss.php).
//
// Pola "tampil lengkap SATU KALI untuk LO pertama, lalu diam-diam untuk LO
// kedua dan seterusnya, baru menyorot lagi begitu SEMUA LO selesai" persis
// mengikuti pola yang sudah dipakai pada Produk & Segel di
// CHECKLIST_SOAL6_TOUR_STEPS ('quietIf' / 'quietIfMin' / 'requireTarget' /
// 'jumpOnClickTo' -- lihat catatan di masing-masing langkah situ untuk
// penjelasan mekanismenya).
const CHECKLIST_SOAL7_TOUR_STEPS = [
    // 0. Perkenalan Daftar LO (layar 'checklist', step 7) -- HANYA tampil
    //    SATU KALI. 'gotoOnLoad': kalau halaman dimuat dan sudah ada LO yang
    //    terisi, langsung loncat ke langkah 4 (arahkan ke LO sisanya).
    [
        'screen' => 'checklist',
        'target' => '[data-tour="ukur-lo-group"]',
        'title'  => 'Soal 7 · Isi Form Metode Pengukuran',
        'text'   => 'Pilih LO dengan status Form Bongkar "Belum Terisi" untuk mengisi data pengukuran pembongkaran BBM-nya.',
        'place'  => 'bottom',
        'gotoOnLoad' => [
            ['ifPresent' => '[data-tour="ukur-lo-group"]:not([data-tour-done-count="0"])', 'to' => 4],
        ],
    ],
    // 1. Pilih Metode Pengukuran (layar 'claim_loss'). Hanya tampil untuk LO
    //    PERTAMA ('quietIf'); untuk LO berikutnya berjalan diam-diam.
    //    'alsoAdvanceOn': kalau pengguna langsung mengetik di form (tanpa
    //    mengetuk kartu metode), tutorial tetap lanjut -- tidak macet.
    //    'skipIf': kalau pop up Hasil Generate sudah terbuka, langkah lewat.
    [
        'screen'        => 'claim_loss',
        'target'        => '[data-tour="claim-metode-group"]',
        'title'         => 'Pilih Metode Pengukuran',
        'text'          => 'Pilih salah satu metode pengukuran pembongkaran BBM: "IJKBOUT" (serah terima custody transfer, diukur dengan dipstick) atau "Flow Meter" (serah terima meter arus pada mobil tangki PTO/portable).',
        'place'         => 'bottom',
        'quietIf'       => '.claim-pad[data-tour-any-done="1"]',
        'skipIf'        => '.hasil-generate-sheet',
        'alsoAdvanceOn' => ['selector' => '.claim-form', 'event' => 'input'],
    ],
    // 2. Isi seluruh kolom form lalu ketuk "Generate" (tombol baru aktif
    //    setelah semua kolom wajib terisi).
    [
        'screen'    => 'claim_loss',
        'target'    => '#btnGenerate',
        'highlight' => '.claim-form',
        'title'     => 'Isi Form Pengukuran',
        'text'      => 'Isi seluruh kolom wajib (Kompartemen, Level BBM di SPP, Level BBM Sebelum Bongkar, dan kolom lain sesuai metode yang dipilih) sesuai kondisi sebenarnya, lalu ketuk "Generate".',
        'place'     => 'top',
        // 'dock' => 'top': halaman TIDAK bergulir otomatis & tooltip ditaruh
        // di pita tersendiri di bawah header (konten didorong turun), jadi
        // kolom input form tidak tertutup tutorial (lihat _layoutDock di
        // roles/spbu/assets/js/tutorial.js).
        'dock'      => 'top',
        'quietIf'   => '.claim-pad[data-tour-any-done="1"]',
        'skipIf'    => '.hasil-generate-sheet',
    ],
    // 3. Pop up "Hasil Generate Claim Losses". 'gotoOnLoad': kalau pop up
    //    sudah tidak ada (mis. pengguna menekan "Batal"), kembali ke langkah 2.
    [
        'screen'    => 'claim_loss',
        'target'    => '[data-tour="hasil-primary-action"]',
        'highlight' => '.hasil-generate-sheet',
        'title'     => 'Hasil Generate Claim Loss',
        'text'      => 'Sistem menampilkan selisih kekurangan (Claim Losses) beserta status generate-nya. Untuk mengetahui apakah kekurangan ini dapat diklaim, baca "Syarat Claim Losses". Kalau sudah sesuai, ketuk "Ajukan Klaim" atau "Simpan Tanpa Klaim" di bawah.',
        'place'     => 'top',
        'quietIf'   => '.hasil-generate-sheet[data-tour-any-done="1"]',
        'gotoOnLoad' => [
            ['ifAbsent' => '.hasil-generate-sheet', 'to' => 2],
        ],
    ],
    // 4. Kembali di layar 'checklist' setelah satu LO tersimpan.
    //    PERBAIKAN: 'screen' => 'checklist' membuat langkah ini TIDAK dievaluasi
    //    selagi pengguna masih di layar 'claim_loss' (sebelumnya langkah ini
    //    keliru dilewati karena kartu LO-nya belum ada di layar itu, sehingga
    //    tutorial langsung loncat ke tombol "Selanjutnya").
    //    Yang disorot HANYA kartu LO yang masih "Belum Terisi". Begitu diketuk,
    //    tutorial kembali ke langkah 1 (isi LO tsb). 'requireTarget': kalau
    //    SEMUA LO sudah terisi, langkah ini dilewati -> langkah 5.
    //    'gotoOnLoad': kalau belum ada LO terisi sama sekali -> kembali ke 0.
    [
        'screen'        => 'checklist',
        'target'        => '[data-tour="ukur-lo-group"] .measure-lo-card:not(.done)',
        'requireTarget' => '[data-tour="ukur-lo-group"] .measure-lo-card:not(.done)',
        'quietIfMin'    => [
            'selector' => '[data-tour="ukur-lo-group"]',
            'attr'     => 'data-tour-done-count',
            'min'      => 2,
        ],
        'jumpOnClickTo' => 1,
        'title'         => 'Lanjutkan ke LO Berikutnya',
        'text'          => 'Satu LO sudah terisi. Masih ada LO lain dengan Form Bongkar "Belum Terisi". Ketuk kartu LO yang menyala ini, lalu isi form pengukurannya dengan langkah yang sama.',
        'place'         => 'top',
        'gotoOnLoad'    => [
            ['ifPresent' => '[data-tour="ukur-lo-group"][data-tour-done-count="0"]', 'to' => 0],
        ],
    ],
    // 5. Baru setelah SEMUA LO terisi (tombol "Selanjutnya" aktif), tutorial
    //    menyorot tombol tsb dan meminta pengguna lanjut ke soal berikutnya.
    [
        'screen' => 'checklist',
        'target' => '[data-tour="wizard-next"]',
        'title'  => 'Lanjutkan Checklist',
        'text'   => 'Semua LO sudah diisi form pengukurannya. Ketuk "Selanjutnya" untuk melanjutkan ke soal checklist berikutnya.',
        'place'  => 'top',
    ],
];

// Tutorial terpandu KHUSUS Soal 8 ("Pemeriksaan Sampel BBM & View Test
// Reports"). Soal ini tidak punya tombol Ya/Tidak -- dokumen Test Report
// otomatis berstatus "telah dilihat" -- jadi satu-satunya aksi pengguna
// adalah mengetuk "Selanjutnya". 'showAfter' (milidetik): pengguna diberi
// waktu membaca halaman dulu tanpa gangguan; baru kalau dalam waktu segitu
// tombol "Selanjutnya" belum diketuk, tutorial MUNCUL menyorot tombol itu
// untuk mengarahkan pengguna. Tutorial TIDAK berpindah halaman sendiri
// (lihat 'showAfter' di Tour.prototype._tryShowCurrent, roles/spbu/assets/js/tutorial.js).
// Dipakai lewat $tourSubKey 'checklist_soal8' di roles/spbu/includes/tour.php.
const CHECKLIST_SOAL8_TOUR_STEPS = [
    [
        'target'    => '[data-tour="wizard-next"]',
        'title'     => 'Soal 8 · Test Report',
        'text'      => 'Dokumen Test Report sudah otomatis tercatat "telah dilihat", jadi tidak ada yang perlu dijawab di soal ini. Ketuk "Selanjutnya" untuk melanjutkan.',
        'place'     => 'top',
        'showAfter' => 5000,
    ],
];

// Tutorial terpandu layar "Konfirmasi LO" (screen 'konfirmasi_lo', bukan
// lagi soal checklist). Pola sama seperti Soal 7: intro SATU KALI, lalu diarahkan ke LO
// yang statusnya BELUM dipilih, baru menyorot tombol "Konfirmasi LO" begitu
// SEMUA LO sudah dipilih statusnya. Dipakai lewat $tourSubKey
// 'checklist_soal15' di roles/spbu/includes/tour.php.
const CHECKLIST_SOAL15_TOUR_STEPS = [
    // 0. Perkenalan. Kalau halaman dimuat dan sudah ada LO yang statusnya
    //    terpilih, langsung loncat ke langkah 1.
    [
        'target'    => '[data-tour="konfirmasi-lo-group"]',
        // Yang disorot SELURUH kartu Konfirmasi Status LO (judul, keterangan,
        // dan semua kartu LO di dalamnya); ketukan pada LO mana pun di dalam
        // grup tetap melanjutkan tutorial.
        'highlight' => '[data-tour="konfirmasi-lo-card"]',
        'title'  => 'Konfirmasi Status LO',
        'text'   => 'Tentukan status bongkar tiap LO: ketuk "Sudah Dibongkar" kalau BBM untuk LO ini jadi dibongkar, atau "Tidak Jadi" kalau pembongkaran dibatalkan. Mulai dari LO pertama.',
        'place'  => 'bottom',
        'gotoOnLoad' => [
            ['ifPresent' => '.konfirmasi-card.is-set', 'to' => 1],
        ],
    ],
    // 1. Arahkan ke LO yang BELUM dipilih statusnya. Dilewati otomatis
    //    ('requireTarget') begitu semua LO sudah punya status.
    [
        'target'        => '.konfirmasi-card:not(.is-set)',
        'highlight'     => '[data-tour="konfirmasi-lo-card"]',
        'requireTarget' => '.konfirmasi-card:not(.is-set)',
        'jumpOnClickTo' => 1,
        'title'         => 'Lanjutkan ke LO Berikutnya',
        'text'          => 'Masih ada LO yang statusnya belum dipilih. Pilih "Tidak Jadi" atau "Sudah Dibongkar" untuk setiap LO yang belum ditentukan statusnya.',
        'place'         => 'top',
        'gotoOnLoad'    => [
            ['ifPresent' => '[data-tour="konfirmasi-lo-group"][data-tour-done-count="0"]', 'to' => 0],
        ],
    ],
    // 2. Semua LO sudah punya status -> sorot "Konfirmasi LO".
    [
        'target' => '[data-tour="wizard-next"]',
        'title'  => 'Konfirmasi LO',
        'text'   => 'Semua LO sudah ditentukan statusnya. Ketuk "Konfirmasi LO" untuk menyimpan dan kembali ke Daftar LO.',
        'place'  => 'top',
    ],
];

// Tutorial terpandu Daftar LO SETELAH checklist dikonfirmasi (LO berstatus
// "Draft"): pilih LO -> tombol Kirim -> pop up "Kirim Checklist". Dipisah
// dari TOUR_STEPS['lo_list'] (tutorial awal "Pilih LO / Mulai Checklist")
// lewat $tourSubKey 'lo_list_kirim' supaya tetap tampil walau tutorial
// awal layar ini sudah pernah ditonton.
const LO_KIRIM_TOUR_STEPS = [
    // 0. Pilih LO yang mau dikirim. Dilewati kalau tombol Kirim sudah aktif
    //    (LO Draft sudah tercentang).
    [
        'target'  => '[data-tour="daftar-lo"]',
        'title'   => 'Pilih LO yang Akan Dikirim',
        'text'    => 'Checklist tiap LO sudah berstatus "Draft". Centang LO yang ingin dikirim, atau ketuk "Pilih Semua". Tombol Kirim baru aktif setelah ada LO Draft yang dicentang.',
        'place'   => 'bottom',
        'skipIf'  => '#btnKirimChecklist:not([disabled])',
    ],
    // 1. Tombol Kirim (baru disorot saat sudah aktif). Kalau halaman dimuat
    //    dan Kirim masih nonaktif (mis. centang dilepas), kembali ke langkah 0.
    [
        'target' => '#btnKirimChecklist',
        'title'  => 'Kirim Checklist',
        'text'   => 'Semua data sudah terisi. Ketuk "Kirim" untuk mengirim checklist ke sistem.',
        'place'  => 'top',
        'gotoOnLoad' => [
            ['ifPresent' => '#btnKirimChecklist[disabled]', 'to' => 0],
        ],
    ],
    // 2. Pop up konfirmasi. 'cancelOn': kalau pengguna menekan "Batal",
    //    tutorial mundur ke langkah 1 (menyorot tombol Kirim lagi).
    [
        'target'    => '#btnYaKirim',
        'highlight' => '#kirimModal .modal-sheet',
        'title'     => 'Konfirmasi Pengiriman',
        'text'      => 'Pastikan data sudah benar -- data yang sudah dikirim tidak dapat diubah lagi. Kalau sudah yakin, ketuk "Ya, Kirim".',
        'place'     => 'top',
        'cancelOn'  => ['selector' => '#btnBatalKirim', 'event' => 'click', 'to' => 1],
    ],
];