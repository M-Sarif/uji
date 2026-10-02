<?php
/**
 * Verifikasi Order - role AMT (data dan pengaturan).
 * Letak: includes/amt/amt_verif_data.php
 */

/** Layar Shipment AMT (halaman "SPBU ..." berisi Aktifitas di SPBU) = amt_spbu (butuh &id= pengiriman). */
const AMT_VERIF_RETURN_SCREEN = 'amt_spbu';

/** Semua layar Verifikasi Order */
const AMT_VERIF_SCREENS = ['amt_verifikasi', 'amt_verifikasi_qr', 'amt_verifikasi_kode', 'amt_verifikasi_sukses'];

const AMT_VERIF_QR_SECONDS     = 120;  // batas waktu pindai kode QR (2 menit)
const AMT_VERIF_CODE_SECONDS   = 180;  // batas waktu kode konfirmasi (3 menit, sesuai tampilan 02:53)
const AMT_VERIF_CODE_LENGTH    = 6;    // jumlah kotak angka
const AMT_VERIF_QR_AUTOSCAN_MS = 3000; // simulasi: QR "terpindai" otomatis setelah 3 detik (0 = hanya lewat tombol)
const AMT_VERIF_SIM_NOTE       = true; // tampilkan catatan "mode simulasi"
const AMT_VERIF_ORDER_TYPE     = 'Produk'; // tulisan pada kartu halaman berhasil

/**
 * Daftar LO milik shipment ini. DATA CONTOH (sesuai screenshot): ganti dengan sumber data asli.
 * Hanya LO dengan form_bongkar = 'Sudah Diisi' yang bisa dipilih untuk verifikasi.
 */
function amt_verif_lo_list()
{
    return [
        ['id' => '01102609053', 'order' => 'PERTAMAX,BULK 8.000 L', 'form_bongkar' => 'Sudah Diisi'],
    ];
}