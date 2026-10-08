<?php
/**
 * SPBU - daftar layar, judul header, tombol back, urutan alur, dan css per layar. HANYA layar milik peran SPBU
 * (layar AMT ada di roles/amt/module.php).
 *
 * Letak: roles/spbu/includes/screens.php
 */

// Daftar layar valid + judul header (persis App.tsx -> getHeaderTitle)
const SPBU_HEADER_TITLES = [
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
    'konfirmasi_lo'         => 'Konfirmasi LO',
];

// Teks tutorial (persis App.tsx -> getTutorialText)
const SPBU_TUTORIAL_TEXTS = [
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
    // Layar Konfirmasi LO (di luar soal checklist) tanpa kotak petunjuk
    'konfirmasi_lo'        => '',
];

// (Urutan layar & label tutorial terpandu ada di includes/tour_data.php:
//  SPBU_TOUR_SCREEN_ORDER, SPBU_TOUR_SCREEN_LABELS, SPBU_TOUR_JOURNEYS.)

// Layar sebelumnya, dipakai untuk tombol "back" di header
const SPBU_PREV_SCREEN = [
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
    'konfirmasi_lo'         => 'checklist',
];

// Urutan alur maju, dipakai sebagai fallback "next" default
const SPBU_NEXT_SCREEN = [
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

// Pemetaan layar SPBU -> file CSS (roles/spbu/assets/css/<nama>.css) yang dimuat di
// luar base.css & components.css (core) yang selalu dimuat di semua halaman.
// Ini yang membuat style tidak menumpuk dalam satu file besar.
const SPBU_SCREEN_CSS = [
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
    'konfirmasi_lo'         => ['verification'],
];