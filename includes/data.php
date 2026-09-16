<?php
/**
 * Data statis aplikasi OneFIS (hasil konversi dari data.ts & types.ts)
 * Semua konten yang tadinya di React (hardcoded JSX) sekarang murni
 * data PHP (array) yang dipakai ulang oleh setiap halaman.
 */

// 15 langkah checklist pra-pembongkaran
const CHECKLIST_STEPS = [
    1  => ['text' => 'Pastikan tersedianya volume ruang kosong dalam tangki.', 'type' => 'photo'],
    2  => ['text' => 'Tempatkan mobil tangki pada posisi pembongkaran yang benar.', 'type' => 'action'],
    3  => ['text' => 'Tarik rem tangan, matikan mesin & aktifkan safety switch. Biarkan kunci kendaraan tetap terpasang di tempatnya. Pasang ganjal ban mobil tangki.', 'type' => 'action'],
    4  => ['text' => 'Turunkan alat pemadam api dan tempatkan pada posisi yang aman dan mudah terjangkau.', 'type' => 'action'],
    5  => ['text' => 'Pasang kabel arde dan yakinkan terpasang dengan benar.', 'type' => 'action'],
    6  => ['text' => 'Periksa kesesuaian SPP yaitu produk, nomor segel (periksa keutuhan segel bawah dan atas) nopol Mobil Tangki, dan nama AMT.', 'type' => 'form_spp'],
    7  => ['text' => 'Isi form data LO berdasarkan Metode Pengukuran (IJKBOUT/Flow Meter).', 'type' => 'form_ukur'],
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
    'checklist'             => 'Lihat Checklist Pra Bongkar',
    'qr_code'               => 'Permintaan Verifikasi',
    'rating'                => 'Rating AMT',
    'done'                  => 'Pengiriman Selesai',
];

// Teks tutorial (persis App.tsx -> getTutorialText)
const TUTORIAL_TEXTS = [
    'dashboard'            => "1. Ini adalah halaman utama OneFIS. Klik menu 'Shipments' atau 'Lihat Detail Order' untuk melanjutkan.",
    'shipments_list'       => "2. Pada halaman Shipments pilih menu Buat Order untuk memesan BBM.",
    'create_order_info'    => "3. Isi Informasi Umum seperti Jenis Order, Tanggal, dan Shift. Klik 'Selanjutnya'.",
    'create_order_product' => "4. Tambahkan Produk BBM, cek Stok Aktual, dan isi Qty Order (misal 8000L). Klik 'Terapkan'.",
    'create_order_review'  => "5. Cek kembali Ringkasan Order Anda, lalu klik 'Submit Order'.",
    'track_order'          => "6. Pantau order di tab Pengiriman. Setelah itu, klik tab 'Aktifitas' untuk melihat detail.",
    'shipment'             => "7. Setelah mobil tangki tiba, klik 'Tiba di Lokasi' untuk verifikasi AMT.",
    'verification'         => "8. Verifikasi kesesuaian data Mobil Tangki & AMT. Klik 'Kirim Verifikasi'.",
    'lo_list'              => "9. Pilih LO yang akan dibongkar, lalu klik 'Mulai Checklist'.",
    'checklist'            => "10. Ikuti 15 langkah checklist Pra-Pembongkaran sesuai kondisi lapangan (termasuk IJKBOUT/Flow Meter).",
    'qr_code'              => "11. Tunjukkan QR Code / Kode Konfirmasi ini kepada AMT untuk diselesaikan.",
    'rating'               => "12. Berikan penilaian mendetail (Safety, Sarfas, dll) untuk pelayanan AMT. Klik Selesai.",
    'done'                 => "Selesai! Seluruh proses dari Order BBM hingga Pembongkaran berhasil dicatat.",
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
    'lo_list'               => 'verification',
    'checklist'             => 'lo_list',
    'qr_code'               => 'checklist',
    'rating'                => 'qr_code',
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
    'verification'          => 'lo_list',
    'lo_list'               => 'checklist',
    'checklist'             => 'qr_code',
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
    'qr_code'               => ['verification'],
    'rating'                => ['verification'],
    'done'                  => ['verification'],
];