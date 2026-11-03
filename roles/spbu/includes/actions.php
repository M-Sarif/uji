<?php
const SPBU_POST_ACTIONS = [
    'save_order_info',
    'apply_product',
    'submit_order',
    'kirim_verifikasi',
    'save_rating_step',
    'submit_rating',
    'save_spp_produk',
    'save_spp_segel',
    'generate_claim_loss',
    'save_claim_loss',
    'ajukan_claim_bulk',
    'upload_dipstick',
    'hapus_dipstick',
    'save_qr_amt',
    'kirim_klaim',
    'kirim_checklist',
    'reset_flow',
];

function spbu_handle_post(string $action): void
{
    switch ($action) {
        case 'save_order_info':
            $_SESSION['order']['jenis'] = ($_POST['jenis'] ?? 'Reguler') === 'Emergency' ? 'Emergency' : 'Reguler';
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
            set_activity_done(1);
            go_to('shipment');
            break;

        case 'save_rating_step':

            $subjectKey = 'amt_ok';
            $subject = ARRIVAL_SUBJECTS[$subjectKey];
            $errors = [];

            $overall = (int) ($_POST['rating'][$subjectKey]['overall'] ?? 0);
            $_SESSION['ratings'][$subjectKey]['overall'] = $overall;
            $_SESSION['ratings'][$subjectKey]['review']  = trim((string) ($_POST['review'] ?? ''));
            if ($overall < 1) {
                $errors[] = 'Penilaian Keseluruhan ' . $subject['sub'];
            }
            foreach (RATING_CATEGORIES as $key => $cat) {
                $val = (int) ($_POST['rating'][$subjectKey][$key] ?? 0);
                $_SESSION['ratings'][$subjectKey][$key] = $val;
                if ($val < 1) {
                    $errors[] = $cat['title'];
                }
            }

            if (!empty($errors)) {
                $_SESSION['rating_error'] = 'Mohon beri bintang untuk: ' . implode(', ', $errors);
                go_to('rating', ['step' => 1]);
            }
            unset($_SESSION['rating_error']);
            go_to('rating', ['step' => 2]);
            break;

        case 'submit_rating':

            $subjectKey2 = 'amt2_ok';
            $subject2 = ARRIVAL_SUBJECTS[$subjectKey2];
            $errors2 = [];

            $overall2 = (int) ($_POST['rating'][$subjectKey2]['overall'] ?? 0);
            $_SESSION['ratings'][$subjectKey2]['overall'] = $overall2;
            if ($overall2 < 1) {
                $errors2[] = 'Penilaian Keseluruhan ' . $subject2['sub'];
            }
            foreach (RATING_CATEGORIES as $key => $cat) {
                $val = (int) ($_POST['rating'][$subjectKey2][$key] ?? 0);
                $_SESSION['ratings'][$subjectKey2][$key] = $val;
                if ($val < 1) {
                    $errors2[] = $cat['title'];
                }
            }
            $_SESSION['ratings'][$subjectKey2]['review'] = trim((string) ($_POST['review'] ?? ''));

            if (!empty($errors2)) {
                $_SESSION['rating_error'] = 'Mohon beri bintang untuk: ' . implode(', ', $errors2);
                go_to('rating', ['step' => 2]);
            }

            $errors1 = [];
            if ((int) ($_SESSION['ratings']['amt_ok']['overall'] ?? 0) < 1) {
                $errors1[] = 'Penilaian Keseluruhan AMT 1';
            }
            foreach (RATING_CATEGORIES as $key => $cat) {
                if ((int) ($_SESSION['ratings']['amt_ok'][$key] ?? 0) < 1) {
                    $errors1[] = $cat['title'] . ' (AMT 1)';
                }
            }
            if (!empty($errors1)) {
                $_SESSION['rating_error'] = 'Mohon lengkapi penilaian AMT 1 terlebih dahulu: ' . implode(', ', $errors1);
                go_to('rating', ['step' => 1]);
            }

            unset($_SESSION['rating_error']);

            set_activity_done(4);
            go_to('shipment');
            break;

        case 'save_spp_produk':

            $loId = (string) ($_POST['lo'] ?? '');
            if (array_key_exists($loId, LO_LIST)) {
                $_SESSION['spp_produk'][$loId] = [
                    'kesesuaian' => ($_POST['kesesuaian'] ?? 'sesuai') === 'tidak' ? 'tidak' : 'sesuai',
                ];
            }
            go_to('checklist', ['step' => 6]);
            break;

        case 'save_spp_segel':

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

            $loId = (string) ($_POST['lo'] ?? '');
            $metode = array_key_exists($_POST['metode'] ?? '', MEASUREMENT_METHODS)
                ? (string) $_POST['metode']
                : DEFAULT_MEASUREMENT_METHOD;

            if (array_key_exists($loId, LO_LIST)) {
                $nilai = [];
                foreach (MEASUREMENT_METHODS[$metode]['fields'] as $field) {
                    $raw = str_replace(',', '.', (string) ($_POST[$field['key']] ?? ''));
                    $nilai[$field['key']] = (float) preg_replace('/[^0-9.\-]/', '', $raw);
                }
                if ($metode === 'ijkbout') {
                    $komp = (int) $nilai['kompartemen'];
                    if (!isset(COMPARTMENT_TERA_T2[$komp])) {
                        go_to('claim_loss', ['lo' => $loId, 'metode' => $metode]);
                    }
                    $nilai['kompartemen'] = (float) $komp;
                    if ($nilai['level_spp'] <= 0) {
                        $nilai['level_spp'] = (float) COMPARTMENT_TERA_T2[$komp];
                    }
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

            $loId = (string) ($_POST['lo'] ?? '');
            $metode = array_key_exists($_POST['metode'] ?? '', MEASUREMENT_METHODS)
                ? (string) $_POST['metode']
                : DEFAULT_MEASUREMENT_METHOD;

            $klaim = (string) ($_POST['klaim'] ?? '');

            $draft = $_SESSION['lo_form_draft'][$loId] ?? null;
            $saved = false;
            if (array_key_exists($loId, LO_LIST) && $draft !== null && $draft['metode'] === $metode) {

                $_SESSION['lo_form'][$loId] = [
                    'metode'     => $draft['metode'],
                    'nilai'      => $draft['nilai'],
                    'claim_loss' => $draft['claim_loss'],
                    'diajukan'   => false,
                ];
                unset($_SESSION['lo_form_draft'][$loId]);
                $saved = true;
            }

            if ($saved && $klaim === 'ajukan' && (float) $_SESSION['lo_form'][$loId]['claim_loss'] > 0) {
                spbu_claim_start([$loId]);
                go_to('claim_upload');
            }
            go_to('checklist', ['step' => 7]);
            break;

        case 'ajukan_claim_bulk':

            $loIdsToClaim = [];
            foreach ((array) ($_POST['lo_ids'] ?? []) as $idToClaim) {
                $idToClaim = (string) $idToClaim;
                $form = $_SESSION['lo_form'][$idToClaim] ?? null;
                if (array_key_exists($idToClaim, LO_LIST) && $form && (float) $form['claim_loss'] > 0 && empty($form['diajukan'])) {
                    $loIdsToClaim[] = $idToClaim;
                }
            }
            if (empty($loIdsToClaim)) {
                go_to('checklist', ['step' => 7]);
            }
            spbu_claim_start($loIdsToClaim);
            go_to('claim_upload');
            break;

        case 'upload_dipstick':

            if (empty(spbu_claim_lo_ids())) {
                go_to('checklist', ['step' => 7]);
            }
            $uploadError = spbu_claim_store_upload($_FILES['dipstick'] ?? null);
            if ($uploadError !== null) {
                $_SESSION['claim_error'] = $uploadError;
            } else {
                unset($_SESSION['claim_error']);
            }
            go_to('claim_upload');
            break;

        case 'hapus_dipstick':
            spbu_claim_remove_dipstick();
            unset($_SESSION['claim_error']);
            go_to('claim_upload');
            break;

        case 'save_qr_amt':

            if (empty(spbu_claim_lo_ids())) {
                go_to('checklist', ['step' => 7]);
            }
            $qrResult = spbu_claim_parse_qr((string) ($_POST['qr'] ?? ''));
            if (!$qrResult['ok']) {
                $_SESSION['claim_scan_error'] = $qrResult['error'];
                go_to('claim_scan_qr');
            }
            unset($_SESSION['claim_scan_error']);
            $_SESSION['claim_docs']['qr'] = $qrResult['data'];
            go_to('claim_upload');
            break;

        case 'kirim_klaim':

            $loIdsKlaim = spbu_claim_lo_ids();
            if (empty($loIdsKlaim)) {
                go_to('checklist', ['step' => 7]);
            }
            if (!spbu_claim_ready()) {
                $_SESSION['claim_error'] = 'Lengkapi Sertifikat Dipstick dan QR Code AMT terlebih dahulu.';
                go_to('claim_upload');
            }
            $dok = spbu_claim_docs();
            foreach ($loIdsKlaim as $idKlaim) {
                if (isset($_SESSION['lo_form'][$idKlaim])) {
                    $_SESSION['lo_form'][$idKlaim]['diajukan']  = true;
                    $_SESSION['lo_form'][$idKlaim]['dokumen']   = [
                        'dipstick' => $dok['dipstick']['file'],
                        'qr_token' => $dok['qr']['token'],
                        'dikirim'  => time(),
                    ];
                }
            }

            $_SESSION['claim_docs']   = ['dipstick' => null, 'qr' => null];
            $_SESSION['claim_submit'] = ['lo_ids' => []];
            unset($_SESSION['claim_error'], $_SESSION['claim_scan_error']);
            $_SESSION['flash_success'] = [
                'title' => 'Klaim Terkirim',
                'body'  => 'Pengajuan Claim Loss berhasil dikirim.',
            ];
            go_to('checklist', ['step' => 7]);
            break;

        case 'kirim_checklist':

            foreach ($_SESSION['lo_checked'] as $id => $isChecked) {
                if ($isChecked && !empty($_SESSION['lo_draft'][$id])) {
                    $_SESSION['lo_done'][$id] = true;
                    unset($_SESSION['lo_draft'][$id]);
                }
            }
            set_activity_done(2);

            $_SESSION['flash_success'] = 'Checklist berhasil dikirim.';
            go_to('shipment');
            break;

        case 'reset_flow':
            if ((int) ($_SESSION['activity_done'] ?? 0) >= count(ACTIVITY_STEPS)) {
                $_SESSION['spbu_active_card_off'] = true;
            }
            spbu_reset_flow_state();
            go_to('dashboard');
            break;
    }
}

function spbu_handle_get(string $screen): void
{
    if ($screen === 'shipments_list') {
        unset($_SESSION['spbu_active_card_off']);
    }

    if ($screen === 'create_order_product' && isset($_GET['added'])) {
        $_SESSION['order']['produk_added'] = true;
    }

    if ($screen === 'lo_list' && isset($_GET['toggle'])) {

        $id = (string) $_GET['toggle'];
        if (array_key_exists($id, LO_LIST)) {
            $_SESSION['lo_checked'][$id] = empty($_SESSION['lo_checked'][$id]);
        }
    }

    if ($screen === 'lo_list' && isset($_GET['toggle_all'])) {

        $checkedCount = count(array_filter($_SESSION['lo_checked']));
        $allChecked = $checkedCount === count(LO_LIST);
        foreach (array_keys(LO_LIST) as $id) {
            $_SESSION['lo_checked'][$id] = !$allChecked;
        }
    }

    if ($screen === 'lo_list' && isset($_GET['selesai'])) {

        foreach ($_SESSION['lo_checked'] as $id => $isChecked) {
            if ($isChecked) {
                $_SESSION['lo_draft'][$id] = true;
            }
        }

        $_SESSION['flash_success'] = [
            'title' => 'LO Dikonfirmasi',
            'body'  => 'Status bongkar berhasil disimpan.',
        ];
    }

    if ($screen === 'rating') {
        set_activity_done(3);

        $step = isset($_GET['step']) ? (int) $_GET['step'] : ($_SESSION['rating_step'] ?? 1);
        $step = max(1, min(2, $step));
        $_SESSION['rating_step'] = $step;
    }

    if ($screen === 'done') {
        set_activity_done(4);
    }

    if ($screen === 'verification' && empty($_SESSION['arrival_time'])) {

        $_SESSION['arrival_time'] = date('d/m/Y H:i:s');
    }

    if ($screen === 'checklist') {
        $step = isset($_GET['step']) ? (int) $_GET['step'] : ($_SESSION['checklist_step'] ?? 1);
        $step = max(1, min(count(CHECKLIST_STEPS), $step));
        $_SESSION['checklist_step'] = $step;

        if (isset($_GET['jawab']) && in_array($_GET['jawab'], ['ya', 'tidak'], true)) {
            $_SESSION['checklist_answers'][$step] = $_GET['jawab'];
        }

        if (isset($_GET['jawab_lawan']) && in_array($_GET['jawab_lawan'], ['ya', 'tidak'], true)
            && (CHECKLIST_STEPS[$step]['type'] ?? '') === 'dual_verif'
        ) {
            $_SESSION['checklist_answers_lawan'][$step] = $_GET['jawab_lawan'];
        }

    }

    if ($screen === 'konfirmasi_lo') {

        $_SESSION['checklist_step'] = count(CHECKLIST_STEPS);

        if (isset($_GET['lo'], $_GET['status'])
            && array_key_exists((string) $_GET['lo'], LO_LIST)
            && in_array($_GET['status'], ['dibongkar', 'batal'], true)
        ) {
            $_SESSION['lo_bongkar'][(string) $_GET['lo']] = $_GET['status'];
        }
    }
}