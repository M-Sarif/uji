<?php


session_start();

require __DIR__ . '/includes/data.php';
require __DIR__ . '/includes/functions.php';

init_session_state();

$validScreens = array_keys(HEADER_TITLES);
$screen = $_GET['screen'] ?? 'dashboard';
if (!in_array($screen, $validScreens, true)) {
    $screen = 'dashboard';
}

/* --------------------------------------------------------------
 * Proses form (POST) - setiap layar yang punya form menangani
 * aksinya sendiri di sini, lalu redirect (pola Post/Redirect/Get)
 * -------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    switch ($action) {

        case 'save_order_info':
            $_SESSION['order']['jenis']   = ($_POST['jenis'] ?? 'Reguler') === 'Emergency' ? 'Emergency' : 'Reguler';
            $_SESSION['order']['tanggal'] = trim($_POST['tanggal'] ?? $_SESSION['order']['tanggal']);
            go_to('create_order_product');
            break;

        case 'apply_product':
            $qty = (int) preg_replace('/[^0-9]/', '', $_POST['qty'] ?? '0');
            $_SESSION['order']['qty'] = $qty > 0 ? $qty : $_SESSION['order']['qty'];
            go_to('create_order_review');
            break;

        case 'submit_order':
            go_to('track_order');
            break;

        case 'kirim_verifikasi':
            // Simpan jawaban verifikasi Mobil Tangki, AMT 1, dan AMT 2
            $belumDijawab = [];
            foreach (ARRIVAL_SUBJECTS as $key => $subject) {
                $jawaban = $_POST[$key] ?? null;
                if ($jawaban !== 'ya' && $jawaban !== 'tidak') {
                    $_SESSION[$key] = null;
                    $belumDijawab[] = $subject['sub'];
                    continue;
                }
                $_SESSION[$key] = ($jawaban === 'ya');
            }

            if (!empty($belumDijawab)) {
                $_SESSION['arrival_error'] = 'Mohon jawab pertanyaan untuk: ' . implode(', ', $belumDijawab);
                go_to('verification');
            }

            unset($_SESSION['arrival_error']);
            set_activity_done(1);   // langkah "Tiba di Lokasi" selesai
            go_to('shipment');      // kembali ke halaman aktifitas SPBU
            break;

        case 'submit_rating':
            $errors = [];
            foreach (RATING_CATEGORIES as $key => $cat) {
                $val = (int) ($_POST['rating'][$key] ?? 0);
                $_SESSION['ratings'][$key] = $val;
                if ($val < 1) {
                    $errors[] = $cat['title'];
                }
            }
            $_SESSION['ratings']['review'] = trim($_POST['review'] ?? '');

            if (!empty($errors)) {
                // Simpan pesan error sementara lalu tampilkan ulang form rating
                $_SESSION['rating_error'] = 'Mohon beri bintang untuk: ' . implode(', ', $errors);
                go_to('rating');
            }
            unset($_SESSION['rating_error']);
            go_to('done');
            break;

        case 'reset_flow':
            reset_flow_state();
            go_to('dashboard');
            break;
    }
}

/* --------------------------------------------------------------
 * Aksi sederhana lewat GET (toggle state tanpa perlu form)
 * -------------------------------------------------------------- */
if ($screen === 'create_order_product' && isset($_GET['added'])) {
    $_SESSION['order']['produk_added'] = true;
}
if ($screen === 'lo_list' && isset($_GET['select'])) {
    $_SESSION['lo_selected'] = true;
}
// Progres aktifitas di SPBU ikut naik saat pengguna mencapai layar berikutnya
if ($screen === 'qr_code') {
    set_activity_done(2);   // checklist pra-pembongkaran selesai
}
if ($screen === 'rating') {
    set_activity_done(3);   // verifikasi order selesai
}
if ($screen === 'done') {
    set_activity_done(4);   // rating AMT selesai
}
if ($screen === 'verification' && empty($_SESSION['arrival_time'])) {
    // Waktu tiba dicatat saat pertama kali layar "Tiba di Lokasi" dibuka
    $_SESSION['arrival_time'] = date('d/m/Y H:i:s');
}
if ($screen === 'checklist') {
    $step = isset($_GET['step']) ? (int) $_GET['step'] : ($_SESSION['checklist_step'] ?? 1);
    $step = max(1, min(15, $step));
    $_SESSION['checklist_step'] = $step;
}

$headerTitle  = HEADER_TITLES[$screen];
$tutorialText = TUTORIAL_TEXTS[$screen];
$prevScreen   = PREV_SCREEN[$screen] ?? null;
$nextScreen   = NEXT_SCREEN[$screen] ?? 'dashboard';

require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/views/' . $screen . '.php';
require __DIR__ . '/includes/layout_bottom.php';