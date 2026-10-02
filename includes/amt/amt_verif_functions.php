<?php
/**
 * Verifikasi Order - role AMT (state + aksi GET).
 * Letak: includes/amt/amt_verif_functions.php
 *
 * Semua aksi memakai GET (sama seperti toggle di lo_list), jadi penjaga POST di index.php tidak perlu diubah.
 * Dipanggil dari index.php:  amt_verif_bootstrap($screen);
 */

require_once __DIR__ . '/amt_verif_data.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function amt_verif_e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** ID pengiriman (shipment) yang sedang diverifikasi: dari ?id=, atau yang tersimpan di session. */
function amt_verif_ship_id()
{
    $id = isset($_GET['id']) ? (string) $_GET['id'] : '';
    if (preg_match('/^[A-Za-z0-9-]{1,64}$/', $id)) {
        return $id;
    }
    return (isset($_SESSION['amt_verif']['ship_id']) && is_string($_SESSION['amt_verif']['ship_id']))
        ? $_SESSION['amt_verif']['ship_id'] : '';
}

function amt_verif_url($screen, array $params = [])
{
    $base = ['screen' => $screen];
    $id = amt_verif_ship_id();
    if ($id !== '') {
        $base['id'] = $id;   // supaya tombol back / "Oke" kembali ke pengiriman yang sama
    }
    return 'index.php?' . http_build_query(array_merge($base, $params));
}

function amt_verif_redirect($screen, array $params = [])
{
    header('Location: ' . amt_verif_url($screen, $params));
    exit;
}

/* ---------- State ---------- */

function amt_verif_state()
{
    if (!isset($_SESSION['amt_verif']) || !is_array($_SESSION['amt_verif'])) {
        $_SESSION['amt_verif'] = [
            'selected'      => [],  // id LO yang dicentang
            'verified'      => [],  // id LO => true
            'qr_deadline'   => 0,
            'code_deadline' => 0,
            'last'          => null // hasil verifikasi terakhir (untuk halaman berhasil)
        ];
    }
    return $_SESSION['amt_verif'];
}

/** Mulai ulang verifikasi (mis. saat Start Work / shift baru). */
function amt_verif_reset()
{
    unset($_SESSION['amt_verif']);
}

function amt_verif_find_lo($id)
{
    foreach (amt_verif_lo_list() as $lo) {
        if ($lo['id'] === (string) $id) {
            return $lo;
        }
    }
    return null;
}

function amt_verif_is_eligible($lo)
{
    return $lo !== null && $lo['form_bongkar'] === 'Sudah Diisi';
}

function amt_verif_is_verified($id)
{
    $s = amt_verif_state();
    return !empty($s['verified'][(string) $id]);
}

/** LO yang dicentang, masih eligible, dan belum terverifikasi. */
function amt_verif_selected()
{
    $s = amt_verif_state();
    $out = [];
    foreach ($s['selected'] as $id) {
        $lo = amt_verif_find_lo($id);
        if (amt_verif_is_eligible($lo) && !amt_verif_is_verified($id)) {
            $out[] = (string) $id;
        }
    }
    return $out;
}

/** true kalau semua LO yang eligible sudah terverifikasi (dipakai halaman Shipment). */
function amt_verif_is_done()
{
    $any = false;
    foreach (amt_verif_lo_list() as $lo) {
        if (!amt_verif_is_eligible($lo)) {
            continue;
        }
        $any = true;
        if (!amt_verif_is_verified($lo['id'])) {
            return false;
        }
    }
    return $any;
}

/** Teks status untuk kartu Order List di Shipment. */
function amt_verif_status_label()
{
    return amt_verif_is_done() ? 'Berhasil Diverifikasi' : 'Belum Diverifikasi';
}

/** Sisa detik: $kind = 'qr' atau 'code'. */
function amt_verif_remaining($kind)
{
    $s = amt_verif_state();
    $deadline = $kind === 'qr' ? $s['qr_deadline'] : $s['code_deadline'];
    return max(0, (int) $deadline - time());
}

function amt_verif_start_timer($kind)
{
    amt_verif_state();
    $_SESSION['amt_verif'][$kind === 'qr' ? 'qr_deadline' : 'code_deadline'] =
        time() + ($kind === 'qr' ? AMT_VERIF_QR_SECONDS : AMT_VERIF_CODE_SECONDS);
}

function amt_verif_has_timer($kind)
{
    $s = amt_verif_state();
    return (int) ($kind === 'qr' ? $s['qr_deadline'] : $s['code_deadline']) > 0;
}

/** Tandai LO terpilih sebagai terverifikasi, lalu ke halaman berhasil. */
function amt_verif_complete($via)
{
    $ids = amt_verif_selected();
    if (!$ids) {
        amt_verif_redirect('amt_verifikasi');
    }
    amt_verif_state();
    foreach ($ids as $id) {
        $_SESSION['amt_verif']['verified'][$id] = true;
    }
    $_SESSION['amt_verif']['last'] = ['ids' => $ids, 'via' => $via, 'at' => date('c')];
    $_SESSION['amt_verif']['selected'] = [];
    $_SESSION['amt_verif']['qr_deadline'] = 0;
    $_SESSION['amt_verif']['code_deadline'] = 0;
    amt_verif_redirect('amt_verifikasi_sukses');
}

/* ---------- Aksi per layar ---------- */

function amt_verif_bootstrap($screen)
{
    if (!in_array($screen, AMT_VERIF_SCREENS, true)) {
        return;
    }
    amt_verif_state();
    // Ingat pengiriman yang sedang diverifikasi (dibawa dari ?id= layar SPBU)
    $rid = isset($_GET['id']) ? (string) $_GET['id'] : '';
    if (preg_match('/^[A-Za-z0-9-]{1,64}$/', $rid)) {
        $_SESSION['amt_verif']['ship_id'] = $rid;
    }

    switch ($screen) {
        case 'amt_verifikasi':
            // Centang / lepas centang satu LO
            if (isset($_GET['toggle'])) {
                $id = (string) $_GET['toggle'];
                $lo = amt_verif_find_lo($id);
                if (amt_verif_is_eligible($lo) && !amt_verif_is_verified($id)) {
                    $pos = array_search($id, $_SESSION['amt_verif']['selected'], true);
                    if ($pos === false) {
                        $_SESSION['amt_verif']['selected'][] = $id;
                    } else {
                        array_splice($_SESSION['amt_verif']['selected'], $pos, 1);
                    }
                }
                amt_verif_redirect('amt_verifikasi');
            }
            break;

        case 'amt_verifikasi_qr':
            if (!amt_verif_selected()) {
                amt_verif_redirect('amt_verifikasi');
            }
            if (isset($_GET['restart'])) {
                amt_verif_start_timer('qr');
                amt_verif_redirect('amt_verifikasi_qr');
            }
            if (!amt_verif_has_timer('qr')) {
                amt_verif_start_timer('qr');
            }
            if (isset($_GET['scan'])) {
                // SIMULASI: hasil pindai selalu dianggap benar selama belum kedaluwarsa.
                if (amt_verif_remaining('qr') > 0) {
                    amt_verif_complete('qr');
                }
                amt_verif_redirect('amt_verifikasi_qr');
            }
            break;

        case 'amt_verifikasi_kode':
            if (!amt_verif_selected()) {
                amt_verif_redirect('amt_verifikasi');
            }
            if (isset($_GET['restart'])) {
                amt_verif_start_timer('code');
                amt_verif_redirect('amt_verifikasi_kode');
            }
            if (!amt_verif_has_timer('code')) {
                amt_verif_start_timer('code');
            }
            if (isset($_GET['kode'])) {
                // SIMULASI: angka apa saja diterima asalkan panjangnya pas dan belum kedaluwarsa.
                $kode = (string) $_GET['kode'];
                if (amt_verif_remaining('code') > 0 && preg_match('/^[0-9]{' . AMT_VERIF_CODE_LENGTH . '}$/', $kode)) {
                    amt_verif_complete('kode');
                }
                amt_verif_redirect('amt_verifikasi_kode', ['err' => 1]);
            }
            break;

        case 'amt_verifikasi_sukses':
            $s = amt_verif_state();
            if (empty($s['last']['ids'])) {
                amt_verif_redirect('amt_verifikasi');
            }
            break;
    }
}