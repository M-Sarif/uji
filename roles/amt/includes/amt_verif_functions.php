<?php

require_once __DIR__ . '/amt_verif_data.php';

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
        $base['id'] = $id;
    }
    return 'index.php?' . http_build_query(array_merge($base, $params));
}

function amt_verif_redirect($screen, array $params = [])
{
    header('Location: ' . amt_verif_url($screen, $params));
    exit;
}

function amt_verif_state()
{
    if (!isset($_SESSION['amt_verif']) || !is_array($_SESSION['amt_verif'])) {
        $_SESSION['amt_verif'] = [
            'selected'      => [],
            'verified'      => [],
            'qr_deadline'   => 0,
            'code_deadline' => 0,
            'last'          => null
        ];
    }
    return $_SESSION['amt_verif'];
}

function amt_verif_reset()
{
    unset($_SESSION['amt_verif']);
}

function amt_verif_apply_selection($csv)
{
    amt_verif_state();
    $out = [];
    foreach (explode(',', substr((string) $csv, 0, 600)) as $id) {
        $id = trim($id);
        if ($id === '' || in_array($id, $out, true)) {
            continue;
        }
        $lo = amt_verif_find_lo($id);
        if (amt_verif_is_eligible($lo) && !amt_verif_is_verified($id)) {
            $out[] = $id;
        }
    }
    $_SESSION['amt_verif']['selected'] = $out;
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

function amt_verif_is_done(?array $s = null)
{
    $any = false;
    foreach (amt_verif_lo_list($s) as $lo) {
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

function amt_verif_bootstrap($screen)
{
    if (!in_array($screen, AMT_VERIF_SCREENS, true)) {
        return;
    }
    amt_verif_state();
    $rid = isset($_GET['id']) ? (string) $_GET['id'] : '';
    if (preg_match('/^[A-Za-z0-9-]{1,64}$/', $rid)) {
        $_SESSION['amt_verif']['ship_id'] = $rid;
    }

    if (!amt_work_ok()) {
        go_to(amt_home_screen());
    }
    $ship = amt_ship_find(amt_verif_ship_id() ?: null);
    if ($ship === null || $ship['status'] !== 'sedang') {
        go_to('amt_shipments');
    }
    if (!amt_spbu_arrived($ship) || !amt_pbk_done($ship['id'])) {
        go_to('amt_spbu', ['id' => $ship['id']]);
    }

    if (isset($_GET['sel']) && is_string($_GET['sel'])) {
        amt_verif_apply_selection($_GET['sel']);
    }
    if ($screen === 'amt_verifikasi' && isset($_GET['ajax'])) {
        http_response_code(204);
        exit;
    }

    switch ($screen) {
        case 'amt_verifikasi':
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