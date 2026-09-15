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
    if (!isset($_SESSION['lo_selected'])) {
        $_SESSION['lo_selected'] = false;
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
}

/** Reset seluruh state alur (dipakai saat kembali ke beranda dari layar "done") */
function reset_flow_state(): void
{
    unset(
        $_SESSION['order'],
        $_SESSION['lo_selected'],
        $_SESSION['checklist_step'],
        $_SESSION['ratings'],
        $_SESSION['mt_ok'],
        $_SESSION['amt_ok']
    );
    init_session_state();
}
