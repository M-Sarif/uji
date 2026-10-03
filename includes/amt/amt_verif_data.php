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
 * Daftar LO milik pengiriman yang sedang diverifikasi.
 * Sumbernya sama dengan Order List di layar SPBU (amt_ship_data.php); "Form Bongkar" mengikuti
 * hasil Checklist Pra-Pembongkaran (amt_pbk.php): LO baru bisa diverifikasi setelah checklist-nya
 * terkirim ("Sudah Diisi").
 *
 * @param array|null $s pengiriman; null = pengiriman dari ?id= / session / yang sedang berjalan
 * @return array<int, array{id:string, order:string, form_bongkar:string}>
 */
function amt_verif_lo_list(?array $s = null): array
{
    $s = $s ?? amt_ship_find(amt_verif_ship_id() ?: null);
    if ($s === null) {
        return [];
    }
    $out = [];
    foreach ($s['products'] as $p) {
        $out[] = [
            'id'           => (string) $p['lo'],
            'order'        => (string) $p['name'],
            'form_bongkar' => amt_pbk_lo_status($s['id'], (string) $p['lo']) === 'done' ? 'Sudah Diisi' : 'Belum Diisi',
        ];
    }
    return $out;
}