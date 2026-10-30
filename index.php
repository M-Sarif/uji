<?php
/* ============================================================
 * OneFIS - front controller / router (?screen=...).
 *
 * File ini SENGAJA tipis dan tidak berisi logika peran mana pun.
 * Semua logika khusus peran dipanggil lewat hook (core/roles.php):
 *   roles/spbu/module.php   -> peran SPBU
 *   roles/amt/module.php    -> peran AMT
 *
 * AKSES BERBASIS TOKEN:
 *   role_select  -> pengguna memilih AMT / SPBU
 *   token_login  -> pengguna memasukkan token yang dibuat admin (admin/index.php)
 *   Peran HANYA aktif bila token valid untuk peran itu (app/auth.php). Tanpa token -> tidak ada jalan masuk.
 * ============================================================ */

require __DIR__ . '/app/security.php';
require __DIR__ . '/app/db.php';
require __DIR__ . '/app/auth.php';

app_boot_errors();                // pesan error teknis tidak pernah dikirim ke browser
app_send_headers(false);          // header keamanan HTTP
app_session_start('ONEFIS_SESS', app_cookie_path(0), 'Lax', (int) app_config('user_idle_timeout'));
app_require_same_origin();        // semua POST (form AMT & SPBU) harus berasal dari situs ini (anti CSRF)

require __DIR__ . '/core/bootstrap.php';

init_session_state();
auth_enforce();                   // peran tanpa bukti token dibuang; token dicek ulang ke database

// Layar valid = pilih peran + input token + semua layar milik tiap peran.
$validScreens = app_screens();

// Layar awal = pilihan peran (SPBU / AMT), bukan langsung tampilan aplikasi.
if (!isset($_GET['screen'])) {
    $prettySegment = strtolower(basename((string) parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH)));
    $prettyScreens = ['home' => 'role_select', 'amt' => 'amt_home', 'spbu' => 'dashboard', 'token' => 'token_login'];
    if (isset($prettyScreens[$prettySegment])) {
        $_GET['screen'] = $prettyScreens[$prettySegment];
    }
}

$screen = $_GET['screen'] ?? 'role_select';
if (!is_string($screen) || !in_array($screen, $validScreens, true)) {
    $screen = 'role_select';
}

/* Penjagaan akses berdasarkan peran:
 * - belum login (belum memasukkan token) -> paksa ke layar pilih peran
 * - layar bukan milik perannya -> arahkan ke beranda perannya sendiri
 * - aksi POST hanya boleh aksi inti (pilih peran / token / keluar) atau aksi milik perannya sendiri */
const CORE_POST_ACTIONS = ['select_role', 'login_token', 'logout'];

$role   = current_role();
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';
$owner  = screen_owner($screen);   // null = layar bersama (pilih peran / input token)

if ($isPost) {
    $postAction = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';
    if (!in_array($postAction, CORE_POST_ACTIONS, true) && !role_accepts_post($role, $postAction)) {
        go_to(role_home($role));
    }
} elseif ($owner !== null && $role !== $owner) {
    go_to(role_home($role));
}

// Layar input token hanya berarti bila ada peran yang sedang dipilih (lihat aksi select_role di bawah).
if (!$isPost && $screen === 'token_login') {
    $pending = $_SESSION['pending_role'] ?? null;
    if (!is_string($pending) || !in_array($pending, AUTH_ROLES, true)) {
        go_to('role_select');
    }
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

/* Proses form (POST) - pola Post/Redirect/Get. Aksi inti ditangani di sini; aksi peran oleh peran masing-masing. */
if ($isPost) {
    $action = is_string($_POST['action'] ?? null) ? $_POST['action'] : '';

    if ($action === 'select_role') {
        // Layar awal: pengguna memilih SPBU atau AMT
        $picked = is_string($_POST['role'] ?? null) ? $_POST['role'] : '';
        if (!isset(ROLES[$picked])) {
            go_to('role_select');
        }
        if ($picked === $role) {
            // Sudah masuk dengan token untuk peran ini -> langsung ke beranda perannya
            go_to(ROLES[$picked]['home']);
        }
        // Belum / peran lain -> wajib memasukkan token untuk peran yang dipilih
        $_SESSION['pending_role'] = $picked;
        go_to('token_login');
    }

    if ($action === 'login_token') {
        $pending = $_SESSION['pending_role'] ?? null;
        if (!is_string($pending) || !in_array($pending, AUTH_ROLES, true)) {
            go_to('role_select');
        }
        if (!csrf_verify()) {
            $_SESSION['auth_error'] = 'Sesi formulir tidak valid. Silakan coba lagi.';
            go_to('token_login');
        }
        if (login_is_locked('user')) {
            $_SESSION['auth_error'] = 'Terlalu banyak percobaan gagal. Coba lagi dalam ' . (int) app_config('lockout_minutes') . ' menit.';
            go_to('token_login');
        }

        $rawToken = is_string($_POST['token'] ?? null) ? substr($_POST['token'], 0, 64) : '';
        $tokenId  = auth_try_token($pending, $rawToken);
        if ($tokenId === null) {
            login_record('user', false, $pending);
            // Pesan sengaja sama untuk semua penyebab (salah / kedaluwarsa / nonaktif / beda peran).
            $_SESSION['auth_error'] = 'Token tidak valid untuk peran ' . ROLES[$pending]['label'] . ', atau sudah tidak berlaku.';
            go_to('token_login');
        }

        login_record('user', true, $pending, $tokenId);
        if ($pending !== current_role()) {
            // Ganti peran -> mulai alur dari awal supaya state tidak bocor antar peran
            foreach (role_keys() as $r) {
                role_call($r, 'on_role_change');
            }
        }
        auth_start_session($tokenId, $pending);   // juga mengganti id sesi (anti session fixation)
        go_to(ROLES[$pending]['home']);
    }

    if ($action === 'logout') {
        if (csrf_verify()) {
            auth_logout();
        }
        go_to('role_select');
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
} elseif ($screen === 'token_login') {
    require CORE_DIR . '/views/token_login.php';       // layar bersama: input token
} else {
    require CORE_DIR . '/views/role_select.php';       // layar bersama: pilih peran
}
role_call($owner, 'after_view', $screen);              // mis. beranda AMT: skrip timer
require __DIR__ . '/core/layout_bottom.php';