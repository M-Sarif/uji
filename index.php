<?php
/* ============================================================
 * OneFIS - front controller / router (?screen=...).
 *
 * File ini SENGAJA tipis dan tidak berisi logika peran mana pun.
 * Semua logika khusus peran dipanggil lewat hook (core/roles.php):
 *   roles/spbu/module.php   -> peran SPBU
 *   roles/amt/module.php    -> peran AMT
 * ============================================================ */
session_start();

require __DIR__ . '/core/bootstrap.php';

init_session_state();

// Layar valid = pilih peran + semua layar milik tiap peran.
$validScreens = app_screens();

// Layar awal = pilihan peran (SPBU / AMT), bukan langsung tampilan aplikasi.
$screen = $_GET['screen'] ?? 'role_select';
if (!in_array($screen, $validScreens, true)) {
    $screen = 'role_select';
}

/* Penjagaan akses berdasarkan peran:
 * - belum memilih peran -> paksa ke layar pilihan peran
 * - layar bukan milik perannya -> arahkan ke beranda perannya sendiri
 * - aksi POST hanya boleh "select_role" atau aksi milik perannya sendiri */
$role   = current_role();
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$owner  = screen_owner($screen);   // null = layar bersama (pilih peran)

if ($isPost) {
    $postAction = (string) ($_POST['action'] ?? '');
    if ($postAction !== 'select_role' && !role_accepts_post($role, $postAction)) {
        go_to(role_home($role));
    }
} elseif ($owner !== null && $role !== $owner) {
    go_to(role_home($role));
}

/* Kembali ke halaman pilih peran => tiap peran mulai dari awal (mis. AMT: timer, Check-In, PTI, tutorial). */
if ($screen === 'role_select' && !$isPost) {
    foreach (role_keys() as $r) {
        role_call($r, 'on_role_select_screen');
    }
}

/* Penjagaan khusus peran yang harus jalan SEBELUM ada output (redirect, simpan jawaban, dst). */
role_call($role, 'bootstrap', $screen);

/* Penjagaan akses layar (GET) milik peran pemilik layar. */
if (!$isPost) {
    role_call($owner, 'guard', $screen);
}

/* Setiap kali pengguna kembali ke beranda peran -- lewat tombol back, mengetik ulang
 * alamatnya, maupun link lain -- progres alurnya dimulai dari awal lagi (diatur peran
 * masing-masing). $flowWasReset dipakai core/layout_bottom.php supaya tutorial layar-layar
 * berikutnya ikut dianggap "belum pernah dilihat" -- HANYA pada momen ini. */
$flowWasReset = false;
if (!$isPost && role_call($owner, 'on_home', $screen)) {
    $flowWasReset = true;
}

/* Proses form (POST) - pola Post/Redirect/Get. Tiap peran menangani aksinya sendiri. */
if ($isPost) {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'select_role') {
        // Layar awal: pengguna memilih SPBU atau AMT
        $picked = (string) ($_POST['role'] ?? '');
        if (!isset(ROLES[$picked])) {
            go_to('role_select');
        }
        if ($picked !== current_role()) {
            // Ganti peran -> mulai alur dari awal supaya state tidak bocor antar peran
            foreach (role_keys() as $r) {
                role_call($r, 'on_role_change');
            }
        }
        $_SESSION['role'] = $picked;
        go_to(ROLES[$picked]['home']);
    }

    role_call($role, 'handle_post', $action);
}

/* Aksi sederhana lewat GET (toggle state tanpa form) milik peran pemilik layar. */
role_call($owner, 'handle_get', $screen);

$headerTitle = (string) role_call($owner, 'screen_title', $screen);
$prevScreen  = role_call($owner, 'prev_screen', $screen);

require __DIR__ . '/core/layout_top.php';
if ($owner !== null) {
    require role_call($owner, 'view_file', $screen);   // views/ milik peran pemilik layar
} else {
    require CORE_DIR . '/views/role_select.php';       // layar bersama
}
role_call($owner, 'after_view', $screen);              // mis. beranda AMT: skrip timer
require __DIR__ . '/core/layout_bottom.php';
