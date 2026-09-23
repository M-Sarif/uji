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

/* Setiap kali pengguna kembali ke halaman utama (dashboard) -- baik lewat
 * tombol back di header/browser, mengetik ulang alamatnya, maupun link
 * lain -- seluruh progres alur order/checklist/verifikasi dimulai dari
 * awal lagi. Hanya berlaku untuk kunjungan GET biasa, bukan saat form
 * di layar dashboard sendiri diproses (dashboard tidak punya form POST). */
if ($screen === 'dashboard' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    reset_flow_state();
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

        case 'save_spp_produk':
            // Hasil verifikasi kartu Produk pada langkah 6 checklist
            $loId = (string) ($_POST['lo'] ?? '');
            if (array_key_exists($loId, LO_LIST)) {
                $_SESSION['spp_produk'][$loId] = [
                    'kesesuaian' => ($_POST['kesesuaian'] ?? 'sesuai') === 'tidak' ? 'tidak' : 'sesuai',
                ];
            }
            go_to('checklist', ['step' => 6]);
            break;

        case 'save_spp_segel':
            // Hasil verifikasi kartu Segel pada langkah 6 checklist
            $noSegel = (string) ($_POST['segel'] ?? '');
            if (in_array($noSegel, SEGEL_LIST, true)) {
                $_SESSION['spp_segel'][$noSegel] = [
                    'kesesuaian' => ($_POST['kesesuaian'] ?? 'sesuai') === 'tidak' ? 'tidak' : 'sesuai',
                    'kondisi'    => ($_POST['kondisi'] ?? 'baik') === 'rusak' ? 'rusak' : 'baik',
                ];
            }
            go_to('checklist', ['step' => 6]);
            break;

        case 'generate_claim_loss':
            // Tombol "Generate": hitung selisih kurang claim loss dari
            // isian form, simpan sebagai DRAFT (belum final), lalu balik
            // ke layar claim_loss dengan flag "hasil=1" supaya pop up
            // "Hasil Generate Claim Losses" langsung tampil.
            $loId   = (string) ($_POST['lo'] ?? '');
            $metode = array_key_exists($_POST['metode'] ?? '', MEASUREMENT_METHODS)
                ? (string) $_POST['metode']
                : 'ijkbout';

            if (array_key_exists($loId, LO_LIST)) {
                $nilai = [];
                foreach (MEASUREMENT_METHODS[$metode]['fields'] as $field) {
                    $raw = str_replace(',', '.', (string) ($_POST[$field['key']] ?? ''));
                    $nilai[$field['key']] = (float) preg_replace('/[^0-9.\-]/', '', $raw);
                }

                $_SESSION['lo_form_draft'][$loId] = [
                    'metode'     => $metode,
                    'nilai'      => $nilai,
                    'claim_loss' => hitung_claim_loss($metode, $nilai, $loId),
                ];
            }
            go_to('claim_loss', ['lo' => $loId, 'metode' => $metode, 'hasil' => 1]);
            break;

        case 'save_claim_loss':
            // Tombol pada pop up "Hasil Generate Claim Losses" (Simpan /
            // Ajukan Claim Losses / Simpan Tanpa Klaim): jadikan draft
            // hasil "Generate" final, lalu kembali ke langkah 7 checklist
            // dengan status "Sudah Terisi".
            $loId   = (string) ($_POST['lo'] ?? '');
            $metode = array_key_exists($_POST['metode'] ?? '', MEASUREMENT_METHODS)
                ? (string) $_POST['metode']
                : 'ijkbout';
            // 'ajukan'  = tombol "Ajukan Claim Losses"
            // 'tanpa'   = tombol "Simpan Tanpa Klaim"
            // ''        = tombol "Simpan" (dipakai saat tidak ada selisih)
            $klaim = (string) ($_POST['klaim'] ?? '');

            $draft = $_SESSION['lo_form_draft'][$loId] ?? null;

            if (array_key_exists($loId, LO_LIST) && $draft !== null && $draft['metode'] === $metode) {
                $_SESSION['lo_form'][$loId] = [
                    'metode'     => $draft['metode'],
                    'nilai'      => $draft['nilai'],
                    'claim_loss' => $draft['claim_loss'],
                    'diajukan'   => $klaim === 'ajukan',
                ];
                unset($_SESSION['lo_form_draft'][$loId]);
            }
            go_to('checklist', ['step' => 7]);
            break;

        case 'kirim_checklist':
            // Tombol "Kirim" di Daftar LO, dikonfirmasi lewat pop up
            // "Ya, Kirim". Hanya LO yang dicentang DAN wizard-nya sudah
            // dituntaskan ("Draft") yang benar-benar dikunci jadi "Sudah
            // Diisi" -- LO yang belum sempat diisi sama sekali diabaikan.
            foreach ($_SESSION['lo_checked'] as $id => $isChecked) {
                if ($isChecked && !empty($_SESSION['lo_draft'][$id])) {
                    $_SESSION['lo_done'][$id] = true;
                    unset($_SESSION['lo_draft'][$id]);
                }
            }
            set_activity_done(2);   // checklist pra-pembongkaran selesai
            // Notifikasi sukses ditampilkan sekali di halaman tujuan
            // (Detail Order) via flash message, lalu otomatis hilang sendiri.
            $_SESSION['flash_success'] = 'Checklist berhasil dikirim.';
            go_to('shipment');      // kembali ke halaman Detail Order
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
if ($screen === 'lo_list' && isset($_GET['toggle'])) {
    // Centang/hilangkan centang SATU LO saja -- ini cuma menandai LO
    // tersebut ikut dikerjakan di wizard checklist, belum berarti
    // checklist-nya sudah selesai diisi.
    $id = (string) $_GET['toggle'];
    if (array_key_exists($id, LO_LIST)) {
        $_SESSION['lo_checked'][$id] = empty($_SESSION['lo_checked'][$id]);
    }
}
if ($screen === 'lo_list' && isset($_GET['toggle_all'])) {
    // "Pilih Semua": kalau semua LO sudah tercentang -> lepas semua,
    // kalau belum -> centang semua.
    $checkedCount = count(array_filter($_SESSION['lo_checked']));
    $allChecked   = $checkedCount === count(LO_LIST);
    foreach (array_keys(LO_LIST) as $id) {
        $_SESSION['lo_checked'][$id] = !$allChecked;
    }
}
if ($screen === 'lo_list' && isset($_GET['selesai'])) {
    // Wizard checklist dituntaskan sampai langkah terakhir ("Konfirmasi
    // LO" di step 15) -- LO yang sedang dikerjakan ditandai "Draft" di
    // Daftar LO. Belum "Sudah Diisi": itu baru terjadi setelah pengguna
    // menekan tombol "Kirim" & konfirmasi "Ya, Kirim".
    foreach ($_SESSION['lo_checked'] as $id => $isChecked) {
        if ($isChecked) {
            $_SESSION['lo_draft'][$id] = true;
        }
    }
    // Notifikasi sekali-tampil setelah tombol "Konfirmasi LO" ditekan.
    $_SESSION['flash_success'] = [
        'title' => 'LO Dikonfirmasi',
        'body'  => 'Status bongkar berhasil disimpan.',
    ];
}
// Progres aktifitas di SPBU ikut naik saat pengguna mencapai layar berikutnya
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

    // Jawaban "Tidak Dilakukan" / "Ya, dilakukan" pada langkah checklist
    if (isset($_GET['jawab']) && in_array($_GET['jawab'], ['ya', 'tidak'], true)) {
        $_SESSION['checklist_answers'][$step] = $_GET['jawab'];
    }

    // Langkah 15: status akhir tiap LO
    if (isset($_GET['lo'], $_GET['status'])
        && array_key_exists((string) $_GET['lo'], LO_LIST)
        && in_array($_GET['status'], ['dibongkar', 'batal'], true)
    ) {
        $_SESSION['lo_bongkar'][(string) $_GET['lo']] = $_GET['status'];
    }
}

$headerTitle  = HEADER_TITLES[$screen];
$tutorialText = TUTORIAL_TEXTS[$screen];
$prevScreen   = PREV_SCREEN[$screen] ?? null;
$nextScreen   = NEXT_SCREEN[$screen] ?? 'dashboard';

require __DIR__ . '/includes/layout_top.php';
require __DIR__ . '/views/' . $screen . '.php';
require __DIR__ . '/includes/layout_bottom.php';