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