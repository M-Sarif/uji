<?php
/**
 * SPBU - penyusun konfigurasi tutorial terpandu untuk layar yang sedang dibuka.
 * Dipakai core/layout_bottom.php lewat hook spbu_tour_config().
 * Data langkah: roles/spbu/includes/tour_data.php.
 *
 * Bentuk konfigurasi SAMA dengan amt_tour_config() (roles/amt/includes/tutorial.php),
 * karena SPBU kini memakai mesin tutorial yang sama dengan AMT.
 *
 * Letak: roles/spbu/includes/tour.php
 */

/**
 * @return array{steps:array, subKey:?string, journey:?array, startDelay:int, epoch:string, fresh:string,
 *               persist:bool, screenOrder:array, screenLabels:array, noFab:bool}
 */
function spbu_tour_config(string $screen): array
{
    // Setiap layar menyusun paket langkahnya sendiri (berdasarkan keadaan session saat ini).
    // 'subKey' memisahkan catatan "sudah selesai" untuk keadaan berbeda pada layar yang sama
    // (mis. Detail Order sebelum / sesudah Tiba di Lokasi, soal checklist per tipe).
    switch ($screen) {
        case 'dashboard':
            // Seluruh aktifitas baru saja tuntas -> kartu ucapan selamat (sekali tampil)
            $finished = !empty($_SESSION['spbu_tour_finished']);
            unset($_SESSION['spbu_tour_finished']);
            $pack = spbu_tour_dashboard_steps($finished);
            break;
        case 'shipments_list':
            $pack = spbu_tour_shipments_steps();
            break;
        case 'shipment':
            $pack = spbu_tour_shipment_steps();
            break;
        case 'verification':
            $pack = spbu_tour_verification_steps();
            break;
        case 'lo_list':
            $pack = spbu_tour_lo_list_steps();
            break;
        case 'checklist':
            $pack = spbu_tour_checklist_steps();
            break;
        case 'claim_loss':
            $pack = spbu_tour_claim_loss_steps();
            break;
        case 'konfirmasi_lo':
            $pack = spbu_tour_konfirmasi_steps();
            break;
        case 'notifikasi':
            $pack = spbu_tour_notifikasi_steps();
            break;
        case 'qr_code':
            $pack = spbu_tour_qr_steps();
            break;
        case 'rating':
            $pack = spbu_tour_rating_steps();
            break;
        default:
            // Layar lain (pilih peran, Buat Order, Pengiriman, Selesai): belum ada tutorial.
            $pack = ['journey' => 'kirim', 'subKey' => null, 'steps' => []];
    }

    return [
        'steps'        => array_values($pack['steps']),
        'subKey'       => $pack['subKey'] ?? null,
        'journey'      => SPBU_TOUR_JOURNEYS[$pack['journey']] ?? null,
        'startDelay'   => (int) ($pack['startDelay'] ?? 0),
        'epoch'        => spbu_tour_epoch(),
        'fresh'        => spbu_tour_fresh(),
        // Layar Rating: JANGAN catat "selesai" permanen (Kirim bisa ditolak server lalu halaman dimuat
        // ulang) -> tutorial tampil lagi otomatis setiap dibuka. Layar lain mencatat "selesai" per
        // paket langkah (subKey), jadi tiap tahap hanya tampil sekali per siklus.
        'persist'      => $screen !== 'rating',
        'screenOrder'  => SPBU_TOUR_SCREEN_ORDER,
        'screenLabels' => SPBU_TOUR_SCREEN_LABELS,
        // Layar tanpa tutorial tetap punya tombol "?" (menampilkan pesan "Belum ada tutorial").
        'noFab'        => false,
    ];
}