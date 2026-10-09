<?php
/**
 * SPBU - fungsi bantu alur SPBU: state session, progres aktifitas, checklist,
 * QR code simulasi, dan hitungan claim loss.
 * (Fungsi umum seperti h() / go_to() ada di core/helpers.php.)
 *
 * Letak: roles/spbu/includes/functions.php
 */

/** Set default nilai session SPBU bila belum ada (state awal alur SPBU) */
function spbu_init_session(): void
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
    if (!isset($_SESSION['lo_draft'])) {
        // Nomor LO yang isian wizard checklist-nya sudah dituntaskan sampai
        // langkah terakhir (id => true), tapi BELUM ditekan tombol "Kirim"
        // di halaman Daftar LO. Statusnya masih "Draft" dan datanya masih
        // bisa diubah lagi lewat "Mulai Checklist".
        $_SESSION['lo_draft'] = [];
    }
    if (!isset($_SESSION['lo_done'])) {
        // Nomor LO yang checklist-nya BENAR-BENAR sudah dikirim & terkunci
        // (id => true). Baru diisi setelah pengguna menekan tombol "Kirim"
        // di halaman Daftar LO dan mengonfirmasi lewat pop up "Ya, Kirim".
        $_SESSION['lo_done'] = [];
    }
    if (!isset($_SESSION['checklist_step'])) {
        $_SESSION['checklist_step'] = 1;
    }
    if (!isset($_SESSION['checklist_answers'])) {
        // Jawaban tiap langkah checklist: nomor langkah => 'ya' | 'tidak'
        $_SESSION['checklist_answers'] = [];
    }
    if (!isset($_SESSION['checklist_answers_lawan'])) {
        // Jawaban verifikasi TUGAS ROLE LAWAN pada langkah bertipe
        // 'dual_verif' (11, 13, 14): nomor langkah => 'ya' | 'tidak'.
        // Dipisah dari checklist_answers (verifikasi mandiri).
        $_SESSION['checklist_answers_lawan'] = [];
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
    if (!isset($_SESSION['claim_submit'])) {
        // Pengajuan Claim Loss yang sedang berjalan (halaman "Upload Dokumen
        // Klaim"): daftar Nomor LO yang diklaim. Kosong = tidak ada pengajuan.
        $_SESSION['claim_submit'] = ['lo_ids' => []];
    }
    if (!isset($_SESSION['claim_docs'])) {
        // Dokumen pengajuan Claim Loss: 'dipstick' = Sertifikat Dipstick
        // (file yang diupload), 'qr' = hasil scan QR Code AMT. null = belum ada.
        $_SESSION['claim_docs'] = ['dipstick' => null, 'qr' => null];
    }
    if (!isset($_SESSION['lo_bongkar'])) {
        // Langkah 15 - status akhir tiap LO (id => 'dibongkar' | 'batal')
        $_SESSION['lo_bongkar'] = [];
    }
    if (!isset($_SESSION['ratings'])) {
        // Penilaian dipisah per AMT (ARRIVAL_SUBJECTS: amt_ok = AMT 1,
        // amt2_ok = AMT 2) -- keduanya WAJIB dinilai. 'review' dipakai
        // bersama untuk kedua AMT (satu kolom "Keterangan Lainnya").
        $blank = [
            'overall'     => 0,
            'safety'      => 0,
            'sarfas'      => 0,
            'komunikasi'  => 0,
            'operasional' => 0,
            'layanan'     => 0,
        ];
        $_SESSION['ratings'] = [
            'amt_ok'  => $blank,
            'amt2_ok' => $blank,
            'review'  => '',
        ];
    }
    if (!isset($_SESSION['rating_step'])) {
        // Penilaian dibagi 2 langkah: 1 = AMT 1, 2 = AMT 2 + kirim.
        $_SESSION['rating_step'] = 1;
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

/** Reset seluruh state alur SPBU (kembali ke beranda dari layar "done", ganti peran, dst) */
function spbu_reset_flow_state(): void
{
    spbu_claim_remove_dipstick();   // file upload yang belum dikirim ikut dibersihkan
    unset(
        $_SESSION['order'],
        $_SESSION['lo_checked'],
        $_SESSION['lo_draft'],
        $_SESSION['lo_done'],
        $_SESSION['checklist_step'],
        $_SESSION['checklist_answers'],
        $_SESSION['checklist_answers_lawan'],
        $_SESSION['spp_produk'],
        $_SESSION['spp_segel'],
        $_SESSION['lo_form'],
        $_SESSION['lo_form_draft'],
        $_SESSION['claim_submit'],
        $_SESSION['claim_docs'],
        $_SESSION['claim_error'],
        $_SESSION['claim_scan_error'],
        $_SESSION['lo_bongkar'],
        $_SESSION['ratings'],
        $_SESSION['rating_step'],
        $_SESSION['mt_ok'],
        $_SESSION['amt_ok'],
        $_SESSION['amt2_ok'],
        $_SESSION['arrival_time'],
        $_SESSION['arrival_error'],
        $_SESSION['activity_done'],
        $_SESSION['arrive_unlock_at']
    );
    spbu_tour_bump();   // siklus baru -> catatan tutorial "selesai" dihapus, tutorial muncul lagi dari awal
    spbu_init_session();
}

/**
 * "Epoch" tutorial SPBU: penanda siklus. Setiap kali alur SPBU dimulai dari awal (kembali ke beranda,
 * ganti peran), nilainya berganti. Mesin tutorial (roles/spbu/assets/js/tutorial.js) membandingkannya dengan
 * yang tersimpan di browser dan otomatis menghapus catatan "tutorial sudah selesai / dilewati" bila berbeda.
 * Jadi tutorial selalu muncul lagi di siklus baru. (Pola yang sama dengan amt_tour_epoch() milik AMT.)
 */
function spbu_tour_epoch(): string
{
    if (empty($_SESSION['spbu_tour_epoch'])) {
        $_SESSION['spbu_tour_epoch'] = bin2hex(random_bytes(6));
    }
    return (string) $_SESSION['spbu_tour_epoch'];
}

function spbu_tour_bump(): void
{
    $_SESSION['spbu_tour_epoch'] = bin2hex(random_bytes(6));
}

/**
 * "Fresh" tutorial SPBU: penanda KUNJUNGAN BARU ke peran SPBU. Berganti HANYA saat layar pilih peran dibuka.
 * Begitu berbeda, status tutorial "dimatikan" (hasil Lewati / selesai) dihapus, sehingga:
 *   - memilih SPBU dari layar pilih peran -> tutorial otomatis muncul lagi;
 *   - setelah Lewati / selesai -> tutorial tidak muncul lagi (kecuali lewat tombol "?").
 */
function spbu_tour_fresh(): string
{
    if (empty($_SESSION['spbu_tour_fresh'])) {
        $_SESSION['spbu_tour_fresh'] = bin2hex(random_bytes(6));
    }
    return (string) $_SESSION['spbu_tour_fresh'];
}

function spbu_tour_fresh_bump(): void
{
    $_SESSION['spbu_tour_fresh'] = bin2hex(random_bytes(6));
}

/** Jawaban yang tersimpan untuk satu langkah checklist ('ya' | 'tidak' | null) */
function checklist_answer(int $step): ?string
{
    return $_SESSION['checklist_answers'][$step] ?? null;
}

/** Jawaban verifikasi Tugas Role Lawan untuk satu langkah ('ya' | 'tidak' | null) */
function checklist_answer_lawan(int $step): ?string
{
    return $_SESSION['checklist_answers_lawan'][$step] ?? null;
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
        case 'photo':
            return checklist_answer($step) !== null;

        // Langkah dengan DUA verifikasi (mandiri + role lawan): tombol
        // "Selanjutnya" baru aktif kalau keduanya sudah dijawab. Foto opsional.
        case 'dual_verif':
            return checklist_answer($step) !== null
                && checklist_answer_lawan($step) !== null;

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

        // Langkah 8 (Test Report): tidak ada pilihan Ya/Tidak. Dokumen otomatis
        // dianggap sudah dilihat, jadi tombol "Selanjutnya" langsung aktif.
        case 'test_report':
            return true;

        case 'konfirmasi_lo':
            foreach ($activeLoIds as $loId) {
                if (empty($_SESSION['lo_bongkar'][$loId])) {
                    return false;
                }
            }
            return true;

        // Tipe langkah lain yang tidak dikenali tidak mengunci tombol lanjut.
        default:
            return true;
    }
}

/**
 * Simulasi pola kotak QR Code (bukan encoding QR standar yang sebenarnya,
 * hanya representasi visual) dari sebuah kode konfirmasi. Dibuat
 * deterministik dari karakter kodenya supaya kode yang sama selalu
 * menghasilkan tampilan QR yang sama persis, lengkap dengan 3 "finder
 * pattern" di pojok seperti QR Code sungguhan.
 *
 * Return: matriks 2 dimensi ($size x $size) berisi true (kotak terisi/hitam)
 * atau false (kotak kosong/putih).
 */
function generate_qr_matrix(string $code, int $size = 21): array
{
    $seed = 0;
    foreach (str_split($code) as $ch) {
        $seed += ord($ch);
    }

    $grid = [];
    for ($y = 0; $y < $size; $y++) {
        $row = [];
        for ($x = 0; $x < $size; $x++) {
            $row[] = ((($x * 31 + $y * 17 + $seed * 7 + $x * $y) % 5) < 2);
        }
        $grid[] = $row;
    }

    // Tempatkan finder pattern 7x7 (kotak konsentris) di 3 pojok, persis QR Code asli.
    $drawFinder = function (array &$grid, int $ox, int $oy): void {
        for ($y = 0; $y < 7; $y++) {
            for ($x = 0; $x < 7; $x++) {
                $border = ($x === 0 || $x === 6 || $y === 0 || $y === 6);
                $inner  = ($x >= 2 && $x <= 4 && $y >= 2 && $y <= 4);
                $grid[$oy + $y][$ox + $x] = $border || $inner;
            }
        }
        // Zona kosong (quiet zone) satu kotak di sekeliling finder, kalau masih dalam batas grid.
        $size = count($grid);
        for ($y = -1; $y <= 7; $y++) {
            for ($x = -1; $x <= 7; $x++) {
                $isBorderRing = ($y === -1 || $y === 7 || $x === -1 || $x === 7);
                $gy = $oy + $y;
                $gx = $ox + $x;
                if ($isBorderRing && $gy >= 0 && $gy < $size && $gx >= 0 && $gx < $size) {
                    $grid[$gy][$gx] = false;
                }
            }
        }
    };

    $drawFinder($grid, 0, 0);
    $drawFinder($grid, $size - 7, 0);
    $drawFinder($grid, 0, $size - 7);

    return $grid;
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

    // BUG LAMA: field "Density Obs" di form diisi pengguna dalam satuan
    // kg/L / g/cm3 (mis. 0.7150 untuk Pertalite -- sesuai placeholder
    // "0.000" di halaman Ajukan Claim Loss), padahal rumus K0/density^2 di
    // bawah ini butuh density dalam kg/m3 (mis. 715.0). Kalau angka kecil
    // (0.7150) langsung dipangkatkan dua sebagai penyebut, alpha15 jadi
    // sangat besar sehingga exp(-alpha*deltaT*...) dibulatkan jadi 0 oleh
    // PHP untuk hampir semua suhu observasi yang tidak persis 15°C --
    // inilah sebabnya hasil Generate selalu "0 L" walau Level BBM di SPP
    // dan Level BBM Sebelum Bongkar sudah diisi berbeda. Auto-konversi ke
    // kg/m3 di sini supaya isian wajar (kg/L, nilai < 10) tetap benar.
    $densityKgM3 = $densityObs < 10.0 ? $densityObs * 1000.0 : $densityObs;

    $alpha15 = 341.0957 / ($densityKgM3 ** 2);
    $deltaT  = $suhuObs - 15.0;

    return exp(-$alpha15 * $deltaT * (1 + 0.8 * $alpha15 * $deltaT));
}

/**
 * Hitung selisih kurang (claim loss) dalam liter untuk satu Nomor LO,
 * berdasarkan metode pengukuran & isian form-nya.
 *
 * - Flow Meter: Qty Order pada LO dikurangi volume_meter (tidak pernah negatif).
 * - IJKBOUT: selisih level dipstick (mm) antara "Level BBM di SPP" dan
 *   "Level BBM Sebelum Bongkar" dikali rasio tera kompartemen
 *   (COMPARTMENT_TERA_RATE, L/mm). Hasil TIDAK dibulatkan -- sama persis
 *   dengan sistem asli (mis. 12 mm => 22.285714285714285 L).
 *
 * Catatan: koreksi suhu/density (hitung_vcf) tidak lagi dipakai di sini karena
 * hasil sistem asli untuk isian yang sama (kompartemen 1, 1212 mm, 1200 mm,
 * 28 C, 0.778) = 22.285714285714285 L, bukan 65.52 L.
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
    $selisihMm   = abs(($nilai['level_spp'] ?? 0) - ($nilai['level_sebelum_bongkar'] ?? 0));

    return (float) ($selisihMm * $literPerMm);
}

/**
 * Tampilkan angka liter apa adanya (presisi penuh, tanpa pembulatan) seperti
 * sistem asli: 22.285714285714285 -> "22.285714285714285".
 */
function format_liter_asli(float $nilai): string
{
    $str = json_encode($nilai);
    if ($str === false) {
        return (string) $nilai;
    }
    // json_encode(0.0) menghasilkan "0.0" -> tampilkan "0" (seperti JavaScript)
    return substr($str, -2) === '.0' ? substr($str, 0, -2) : $str;
}

/* ============================================================
 * Pengajuan Claim Loss - halaman "Upload Dokumen Klaim" + "Scan QR AMT"
 * Alur: Ajukan Claim Loss -> claim_upload (Sertifikat Dipstick + QR Code AMT)
 *       -> tombol "Kirim Klaim" -> kembali ke Daftar LO (langkah 7).
 * ============================================================ */

const CLAIM_UPLOAD_MAX_BYTES = 5 * 1024 * 1024;                 // batas ukuran Sertifikat Dipstick (5 MB)
const CLAIM_UPLOAD_EXT       = ['pdf', 'jpg', 'jpeg', 'png'];   // jenis dokumen yang diterima
const CLAIM_QR_PREFIX        = 'ONEFIS-PBK';                    // awalan isi QR Code yang dibuat AMT

/** Folder penyimpanan Sertifikat Dipstick (di luar folder kode: <folder index.php>/uploads/claim) */
function spbu_claim_upload_dir(): string
{
    return APP_ROOT . '/uploads/claim';
}

/** Mulai pengajuan untuk daftar Nomor LO. Dokumen lama dipertahankan bila daftar LO-nya sama. */
function spbu_claim_start(array $loIds): void
{
    $valid = [];
    foreach ($loIds as $id) {
        $id = (string) $id;
        if (array_key_exists($id, LO_LIST) && !in_array($id, $valid, true)) {
            $valid[] = $id;
        }
    }
    sort($valid);

    $current = (array) ($_SESSION['claim_submit']['lo_ids'] ?? []);
    sort($current);
    if ($current !== $valid) {
        spbu_claim_remove_dipstick();
        $_SESSION['claim_docs'] = ['dipstick' => null, 'qr' => null];
    }
    $_SESSION['claim_submit'] = ['lo_ids' => $valid];
}

/** Nomor LO yang sedang diajukan klaimnya */
function spbu_claim_lo_ids(): array
{
    return array_values((array) ($_SESSION['claim_submit']['lo_ids'] ?? []));
}

/** Dokumen pengajuan saat ini: ['dipstick' => ?array, 'qr' => ?array] */
function spbu_claim_docs(): array
{
    $d = $_SESSION['claim_docs'] ?? [];
    return ['dipstick' => $d['dipstick'] ?? null, 'qr' => $d['qr'] ?? null];
}

/** Tombol "Kirim Klaim" aktif hanya bila KEDUA dokumen sudah terisi */
function spbu_claim_ready(): bool
{
    $d = spbu_claim_docs();
    return $d['dipstick'] !== null && $d['qr'] !== null;
}

/** Hapus file Sertifikat Dipstick yang tersimpan (bila ada) dan kosongkan datanya */
function spbu_claim_remove_dipstick(): void
{
    $file = $_SESSION['claim_docs']['dipstick']['file'] ?? null;
    if (is_string($file) && $file !== '' && preg_match('/^dipstick_[a-f0-9]{16}\.(pdf|jpe?g|png)$/', $file)) {
        $path = spbu_claim_upload_dir() . '/' . $file;
        if (is_file($path)) {
            @unlink($path);
        }
    }
    if (isset($_SESSION['claim_docs'])) {
        $_SESSION['claim_docs']['dipstick'] = null;
    }
}

/**
 * Simpan file Sertifikat Dipstick yang diupload.
 * @return string|null pesan error, atau null bila berhasil
 */
function spbu_claim_store_upload($file): ?string
{
    if (!is_array($file) || !isset($file['error'])) {
        return 'Dokumen tidak terbaca. Silakan coba lagi.';
    }
    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return 'Pilih dokumen terlebih dahulu.';
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return 'Ukuran dokumen terlalu besar (maksimal 5 MB).';
        default:
            return 'Dokumen gagal diupload. Silakan coba lagi.';
    }
    if (!is_uploaded_file($file['tmp_name']) || (int) $file['size'] <= 0) {
        return 'Dokumen tidak valid.';
    }
    if ((int) $file['size'] > CLAIM_UPLOAD_MAX_BYTES) {
        return 'Ukuran dokumen terlalu besar (maksimal 5 MB).';
    }

    $ext = strtolower(pathinfo((string) $file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, CLAIM_UPLOAD_EXT, true)) {
        return 'Format dokumen harus PDF, JPG, atau PNG.';
    }
    // Cek isi file (bukan hanya nama/ekstensi) bila ekstensi fileinfo tersedia
    if (class_exists('finfo')) {
        $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
        if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png'], true)) {
            return 'Format dokumen harus PDF, JPG, atau PNG.';
        }
    }

    $dir = spbu_claim_upload_dir();
    if (!is_dir($dir) && !@mkdir($dir, 0755, true)) {
        return 'Folder penyimpanan tidak bisa dibuat. Periksa izin folder "uploads".';
    }
    // Lindungi folder upload: tidak ada daftar isi & tidak ada eksekusi skrip (Apache)
    $ht = $dir . '/.htaccess';
    if (!is_file($ht)) {
        @file_put_contents($ht, "Options -Indexes\n<FilesMatch \"\\.(php|phtml|phar)$\">\n    Require all denied\n</FilesMatch>\n");
    }

    $stored = 'dipstick_' . bin2hex(random_bytes(8)) . '.' . ($ext === 'jpeg' ? 'jpg' : $ext);
    if (!@move_uploaded_file($file['tmp_name'], $dir . '/' . $stored)) {
        return 'Dokumen gagal disimpan. Periksa izin folder "uploads".';
    }

    spbu_claim_remove_dipstick();   // ganti file lama (bila ada)
    $name = preg_replace('/[^\w.\- ()]+/u', '_', basename((string) $file['name']));
    $_SESSION['claim_docs']['dipstick'] = [
        'name' => (string) $name,
        'file' => $stored,
        'size' => (int) $file['size'],
    ];
    return null;
}

/**
 * Periksa isi QR Code AMT hasil scan. Format (dibuat AMT, lihat amt_pbk_qr_payload()):
 *   ONEFIS-PBK|<id pengiriman>|<no SPBU>|<token 24 hex>|<waktu kedaluwarsa unix>
 * @return array{ok:bool,error:string,data:?array}
 */
function spbu_claim_parse_qr(string $raw): array
{
    $raw   = trim($raw);
    $parts = explode('|', $raw);
    if ($raw === '' || strlen($raw) > 300 || count($parts) !== 5 || $parts[0] !== CLAIM_QR_PREFIX
        || !preg_match('/^[a-f0-9]{24}$/', $parts[3]) || !ctype_digit($parts[4])) {
        return ['ok' => false, 'error' => 'QR Code bukan QR Code AMT dari OneFIS. Silakan scan ulang.', 'data' => null];
    }
    if ((int) $parts[4] <= time()) {
        return ['ok' => false, 'error' => 'QR Code AMT sudah kedaluwarsa. Minta AMT menekan "Regenerate", lalu scan ulang.', 'data' => null];
    }
    return ['ok' => true, 'error' => '', 'data' => [
        'shipment' => $parts[1],
        'spbu'     => $parts[2],
        'token'    => $parts[3],
        'exp'      => (int) $parts[4],
        'scan_at'  => time(),
    ]];
}