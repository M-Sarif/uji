<?php
/**
 * Fungsi bantuan (helper) native PHP.
 */

/** Escape output supaya aman dari XSS */
function h(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Redirect PRG (Post/Redirect/Get) ke layar tertentu lalu hentikan eksekusi */
function go_to(string $screen, array $extraParams = []): void
{
    $params = array_merge(['screen' => $screen], $extraParams);
    header('Location: index.php?' . http_build_query($params));
    exit;
}

/** Set default nilai session bila belum ada (state awal aplikasi) */
function init_session_state(): void
{
    if (!isset($_SESSION['order'])) {
        $_SESSION['order'] = [
            'no_spbu'      => '3210829 - PT Lorem Ipsum',
            'ship_to'      => '1234567',
            'terminal'     => 'Depot Plumpang',
            'jenis'        => 'Reguler',
            'tanggal'      => '25/05/2026',
            'produk_added' => false,
            'produk'       => 'PERTALITE',
            'qty'          => 8000,
        ];
    }
    if (!isset($_SESSION['lo_checked'])) {
        // Nomor LO yang dicentang pengguna untuk dikerjakan checklist-nya
        // (id => true). Ini hanya menandai "dipilih", BUKAN "sudah diisi".
        $_SESSION['lo_checked'] = [];
    }
    if (!isset($_SESSION['lo_done'])) {
        // Nomor LO yang checklist-nya BENAR-BENAR sudah selesai dikerjakan
        // (id => true). Baru diisi setelah wizard checklist dituntaskan
        // sampai langkah terakhir ("Kirim Checklist").
        $_SESSION['lo_done'] = [];
    }
    if (!isset($_SESSION['checklist_step'])) {
        $_SESSION['checklist_step'] = 1;
    }
    if (!isset($_SESSION['checklist_answers'])) {
        // Jawaban tiap langkah checklist: nomor langkah => 'ya' | 'tidak'
        $_SESSION['checklist_answers'] = [];
    }
    if (!isset($_SESSION['spp_produk'])) {
        // Langkah 6 - kesesuaian produk per Nomor LO (id => true bila sudah terisi)
        $_SESSION['spp_produk'] = [];
    }
    if (!isset($_SESSION['spp_segel'])) {
        // Langkah 6 - nomor segel yang sudah dibongkar (nomor => true)
        $_SESSION['spp_segel'] = [];
    }
    if (!isset($_SESSION['lo_form'])) {
        // Langkah 7 - isian form bongkar / claim loss per Nomor LO YANG
        // SUDAH FINAL (Simpan / Ajukan Claim Losses / Simpan Tanpa Klaim).
        $_SESSION['lo_form'] = [];
    }
    if (!isset($_SESSION['lo_form_draft'])) {
        // Hasil "Generate" yang belum dikonfirmasi user lewat pop up Hasil
        // Generate Claim Losses (tombol Simpan / Ajukan / Simpan Tanpa
        // Klaim). Dipisah dari lo_form supaya status "Sudah Terisi" di
        // langkah 7 checklist tidak berubah sebelum user benar-benar
        // menyimpan hasilnya.
        $_SESSION['lo_form_draft'] = [];
    }
    if (!isset($_SESSION['lo_bongkar'])) {
        // Langkah 15 - status akhir tiap LO (id => 'dibongkar' | 'batal')
        $_SESSION['lo_bongkar'] = [];
    }
    if (!isset($_SESSION['ratings'])) {
        $_SESSION['ratings'] = [
            'safety'      => 0,
            'sarfas'      => 0,
            'komunikasi'  => 0,
            'operasional' => 0,
            'layanan'     => 0,
            'review'      => '',
        ];
    }
    if (!isset($_SESSION['mt_ok'])) {
        $_SESSION['mt_ok'] = null; // null = belum dipilih, true/false setelah verifikasi
    }
    if (!isset($_SESSION['amt_ok'])) {
        $_SESSION['amt_ok'] = null;
    }
    if (!isset($_SESSION['amt2_ok'])) {
        $_SESSION['amt2_ok'] = null;
    }
    if (!isset($_SESSION['arrival_time'])) {
        $_SESSION['arrival_time'] = null;
    }
    if (!isset($_SESSION['activity_done'])) {
        // Jumlah langkah aktifitas di SPBU yang sudah selesai (0-4)
        $_SESSION['activity_done'] = 0;
    }
}

/** Tandai langkah aktifitas di SPBU selesai sampai urutan ke-$jumlah (tidak pernah mundur) */
function set_activity_done(int $jumlah): void
{
    $current = (int) ($_SESSION['activity_done'] ?? 0);
    $_SESSION['activity_done'] = max($current, min($jumlah, count(ACTIVITY_STEPS)));
}

/** Reset seluruh state alur (dipakai saat kembali ke beranda dari layar "done") */
function reset_flow_state(): void
{
    unset(
        $_SESSION['order'],
        $_SESSION['lo_checked'],
        $_SESSION['lo_done'],
        $_SESSION['checklist_step'],
        $_SESSION['checklist_answers'],
        $_SESSION['spp_produk'],
        $_SESSION['spp_segel'],
        $_SESSION['lo_form'],
        $_SESSION['lo_form_draft'],
        $_SESSION['lo_bongkar'],
        $_SESSION['ratings'],
        $_SESSION['mt_ok'],
        $_SESSION['amt_ok'],
        $_SESSION['amt2_ok'],
        $_SESSION['arrival_time'],
        $_SESSION['arrival_error'],
        $_SESSION['activity_done']
    );
    init_session_state();
}
/** Jawaban yang tersimpan untuk satu langkah checklist ('ya' | 'tidak' | null) */
function checklist_answer(int $step): ?string
{
    return $_SESSION['checklist_answers'][$step] ?? null;
}

/**
 * Apakah langkah checklist ke-$step sudah lengkap diisi?
 * Dipakai untuk mengaktifkan/menonaktifkan tombol "Selanjutnya",
 * persis seperti aplikasi aslinya.
 */
function checklist_step_done(int $step, array $activeLoIds): bool
{
    $type = CHECKLIST_STEPS[$step]['type'] ?? '';

    switch ($type) {
        case 'self_action_photo':
        case 'action':
            return checklist_answer($step) !== null;

        case 'form_spp':
            foreach ($activeLoIds as $loId) {
                if (empty($_SESSION['spp_produk'][$loId])) {
                    return false;
                }
            }
            foreach (SEGEL_LIST as $segel) {
                if (empty($_SESSION['spp_segel'][$segel])) {
                    return false;
                }
            }
            return true;

        case 'form_ukur':
            foreach ($activeLoIds as $loId) {
                if (empty($_SESSION['lo_form'][$loId])) {
                    return false;
                }
            }
            return true;

        case 'konfirmasi_lo':
            foreach ($activeLoIds as $loId) {
                if (empty($_SESSION['lo_bongkar'][$loId])) {
                    return false;
                }
            }
            return true;

        // Langkah foto memakai kamera perangkat, tidak ikut mengunci tombol lanjut.
        default:
            return true;
    }
}

/**
 * Faktor koreksi volume (VCF - Volume Correction Factor) dari suhu &
 * density observasi ke kondisi standar 15°C, memakai rumus generalized
 * products ASTM/API/IP MPMS Chapter 11.1 - Table 54B (K0=341.0957,
 * K1=K2=0). Berlaku untuk BBM jenis bensin/kerosene dengan density
 * observasi sekitar 653-778 kg/m3 (mencakup Pertalite/Pertamax/Premium).
 *
 * NOTE: ini pendekatan generalized table, bukan tabel ASTM 54B asli yang
 * dipecah per rentang density persis - cukup akurat untuk taksiran claim
 * loss, tapi sebaiknya diganti dengan tabel resmi untuk kebutuhan legal/audit.
 */
function hitung_vcf(float $densityObs, float $suhuObs): float
{
    if ($densityObs <= 0.0) {
        // Data density tidak valid (belum diisi) -> tidak ada koreksi.
        return 1.0;
    }

    $alpha15 = 341.0957 / ($densityObs ** 2);
    $deltaT  = $suhuObs - 15.0;

    return exp(-$alpha15 * $deltaT * (1 + 0.8 * $alpha15 * $deltaT));
}

/**
 * Hitung selisih kurang (claim loss) dalam liter untuk satu Nomor LO,
 * berdasarkan metode pengukuran & isian form-nya.
 *
 * - Flow Meter: volume yang benar-benar terukur (volume_meter) dibandingkan
 *   dengan Qty Order pada LO. Selisih kurang = Qty Order - volume_meter
 *   (tidak pernah negatif; kalau volume terukur >= order, tidak ada claim loss).
 * - IJKBOUT: selisih level dipstick (mm) antara "Level BBM di SPP" (saat
 *   muat) dan "Level BBM Sebelum Bongkar" (saat sampai tujuan) dikonversi
 *   ke liter memakai rasio tera kompartemen (COMPARTMENT_TERA_RATE), lalu
 *   dikoreksi ke suhu standar 15°C memakai VCF dari suhu & density obs.
 *   Kalau level tidak turun (selisih <= 0 mm), tidak ada claim loss.
 */
function hitung_claim_loss(string $metode, array $nilai, string $loId): float
{
    if ($metode === 'flowmeter') {
        $qtyOrder    = (float) preg_replace('/[^0-9]/', '', LO_LIST[$loId]['qty'] ?? '0');
        $volumeMeter = (float) ($nilai['volume_meter'] ?? 0);

        return max(0.0, $qtyOrder - $volumeMeter);
    }

    // IJKBOUT
    $kompartemen = (int) ($nilai['kompartemen'] ?? 0);
    $literPerMm  = COMPARTMENT_TERA_RATE[$kompartemen] ?? COMPARTMENT_TERA_RATE['default'];

    // Level SPP (saat muat) seharusnya >= level sebelum bongkar (saat
    // tujuan) kalau ada kekurangan BBM di jalan. Kalau levelnya malah naik
    // atau sama, anggap tidak ada selisih (0), bukan angka negatif.
    $selisihMm = max(0.0, ($nilai['level_spp'] ?? 0) - ($nilai['level_sebelum_bongkar'] ?? 0));
    $volumeObs = $selisihMm * $literPerMm;

    $vcf = hitung_vcf((float) ($nilai['density_obs'] ?? 0), (float) ($nilai['temperatur_obs'] ?? 15));

    return round($volumeObs * $vcf, 2);
}