<?php
/**
 * Check-Out peran AMT + arahan sesudahnya (Start / End -> End Work).
 * Letak: roles/amt/includes/amt_out.php
 *
 * Scan segel bekas TIDAK dilakukan di aplikasi: AMT melakukannya di AVM di depot.
 * Aplikasi hanya MENGARAHKAN lewat tutorial supaya AMT scan segel di AVM lebih dulu,
 * baru melakukan Check-Out.
 *
 * Alur satu ritase penutup:
 *   1. Order diselesaikan (pop up "Menyelesaikan Order" -> Kirim) -> beranda AMT.
 *   2. Beranda: tutorial "Scan segel di AVM dulu" (tombol Mengerti / link Lewati; kalau tidak
 *      diklik hilang sendiri setelah 20 detik). Menu Check-Out masih terkunci selama kartu tampil.
 *   3. Kartu tertutup -> 0,3 detik kemudian menu Check-Out menyala; 1 detik kemudian tutorial
 *      menyorot menu Check-Out. Failsafe: menyala sendiri 30 detik setelah order selesai.
 *   4. Layar Check-Out: Riwayat Check-In + form (lokasi di depot, aktivitas, foto, Kirim Check-Out);
 *      logikanya sama seperti Check-In.
 *   5. Check-Out berhasil -> Riwayat Check-In dikosongkan -> beranda menampilkan TUTORIAL yang mengarahkan
 *      AMT mengakhiri kerja: menu Start / End -> End Work -> form End Work -> selesai.
 *      (Hanya tutorial; aplikasi tidak menanyakan apa pun ke pengguna.)
 *
 * State:
 *   $_SESSION['amt_out'] = ['seal_seen_at' => float, 'unlocked' => bool, 'after_at' => unix (Check-Out terakhir; memicu tutorial End Work)]
 *   $_SESSION['work']['checkouts'][] = riwayat Check-Out (waktu, aktivitas, foto)
 *
 * Dimuat dari roles/amt/module.php (setelah amt_rating.php).
 */

const AMT_OUT_SCREEN = 'checkout';

const AMT_OUT_SEAL_SHOW_DELAY  = 1000;   // tutorial muncul 1 detik setelah beranda dibuka
const AMT_OUT_SEAL_VISIBLE_FOR = 20000;  // hilang sendiri setelah 20 detik kalau "Mengerti" tidak diklik
const AMT_OUT_UNLOCK_DELAY     = 300;    // menu Check-Out menyala 0,3 detik setelah tutorial hilang
const AMT_OUT_HINT_DELAY       = 1000;   // petunjuk "ketuk Check-Out" muncul 1 detik setelah menu menyala
const AMT_OUT_FAILSAFE_SECONDS = 30;     // menu menyala sendiri 30 detik setelah order selesai (harus > 20 detik)

/* ------------------------------------------------------------
 * State
 * ------------------------------------------------------------ */

function amt_out_get(string $key)
{
    return $_SESSION['amt_out'][$key] ?? null;
}

function amt_out_reset(): void
{
    unset($_SESSION['amt_out']);
}

/** Waktu (unix) order TERAKHIR diselesaikan pada siklus ini; 0 = belum ada. */
function amt_out_finished_at(): int
{
    $f = $_SESSION['amt_spbu']['finished'] ?? [];
    return (is_array($f) && $f) ? (int) max($f) : 0;
}

/** Siap Check-Out: sedang bekerja, sudah Check-In, dan ada order yang sudah diselesaikan. */
function amt_out_order_done(): bool
{
    return work_is_running() && amt_last_checkin_at() > 0 && amt_out_finished_at() > 0;
}

/** Tutorial "scan segel di AVM" sudah ditutup (Mengerti / Lewati / hilang sendiri)? */
function amt_out_seal_seen(): bool
{
    return (float) amt_out_get('seal_seen_at') > 0 || !empty(amt_out_get('unlocked'));
}

/** Sisa detik sampai menu Check-Out menyala otomatis (failsafe); 0 bila sudah lewat. */
function amt_out_failsafe_remaining(): int
{
    $at = amt_out_finished_at();
    return $at > 0 ? max(0, $at + AMT_OUT_FAILSAFE_SECONDS - time()) : 0;
}

/** Menu Check-Out aktif? Order selesai DAN tutorial "scan segel di AVM" sudah ditutup. */
function amt_out_unlocked(): bool
{
    if (!amt_out_order_done()) {
        return false;
    }
    return amt_out_seal_seen() || amt_out_failsafe_remaining() === 0;
}

/** Check-Out baru saja dilakukan dan belum End Work? (memicu tutorial Start / End -> End Work) */
function amt_out_after_checkout(): bool
{
    return work_is_running() && (int) amt_out_get('after_at') > 0;
}

/** Dipanggil bila AMT Check-In lagi: tutorial arahan End Work tidak berlaku lagi. */
function amt_out_clear_after(): void
{
    unset($_SESSION['amt_out']['after_at']);
}

/** Jumlah Check-Out yang sudah dilakukan (dipakai sebagai kunci tutorial). */
function amt_out_count(): int
{
    $c = $_SESSION['work']['checkouts'] ?? [];
    return is_array($c) ? count($c) : 0;
}

/** Nomor urut dalam kata untuk "Check-In pertama / kedua / ...". */
function amt_out_ordinal(int $n): string
{
    $words = [1 => 'pertama', 'kedua', 'ketiga', 'keempat', 'kelima', 'keenam', 'ketujuh', 'kedelapan', 'kesembilan', 'kesepuluh'];
    return $words[$n] ?? 'ke-' . $n;
}

/* ------------------------------------------------------------
 * Hook: sebelum ada output (dipanggil dari amt_bootstrap di module.php)
 *  - out_action=seal_ack : JS melapor tutorial "scan segel di AVM" sudah ditutup (fetch, tanpa pindah halaman)
 * ------------------------------------------------------------ */
function amt_out_bootstrap(string $screen): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST' || ($_POST['out_action'] ?? '') !== 'seal_ack') {
        return;
    }
    $key = (string) ($_POST['key'] ?? '');
    if (amt_out_order_done() && in_array($key, ['seal_seen', 'unlocked'], true)) {
        if ((float) amt_out_get('seal_seen_at') <= 0) {
            $_SESSION['amt_out']['seal_seen_at'] = microtime(true);
        }
        if ($key === 'unlocked') {
            $_SESSION['amt_out']['unlocked'] = true;
        }
    }
    http_response_code(204);
    exit;
}

/* ------------------------------------------------------------
 * Penjagaan akses layar Check-Out (GET) - dipanggil dari amt_guard_work()
 * ------------------------------------------------------------ */
function amt_out_guard(string $screen): void
{
    if ($screen === AMT_OUT_SCREEN && !amt_out_unlocked()) {
        go_to(amt_home_screen());
    }
}

/* ------------------------------------------------------------
 * POST "Kirim Check-Out" - pola Post/Redirect/Get
 * Logika sama seperti Check-In (timer berjalan, dalam radius, aktivitas, foto),
 * ditambah: menu Check-Out aktif (order selesai + tutorial segel sudah dibaca).
 * ------------------------------------------------------------ */
function amt_out_handle_post(): void
{
    $akt = (string) ($_POST['aktivitas'] ?? '');
    if (!work_is_running()
        || !amt_out_unlocked()
        || !work_post_in_range()
        || !in_array($akt, CHECKIN_ACTIVITIES, true)
        || ($_POST['photo'] ?? '') !== '1'
    ) {
        go_to(AMT_OUT_SCREEN);
    }

    // Foto: hanya gambar yang ukurannya wajar (sama seperti Check-In)
    $photo = (string) ($_POST['photo_data'] ?? '');
    if (!preg_match('#^data:image/(jpeg|png|webp);base64,[A-Za-z0-9+/=]+$#', $photo)
        || strlen($photo) > 3000000
    ) {
        $photo = '';
    }

    $_SESSION['work']['checkouts'][] = [
        'at'       => time(),
        'activity' => $akt,
        'photo'    => $photo,
        'checkins' => count(work_checkins()),
    ];

    // Ritase selesai: Riwayat Check-In dikosongkan, alur berikutnya mulai dari awal.
    $_SESSION['work']['checkins'] = [];
    amt_pti_reset();
    amt_spbu_reset();
    amt_verif_reset();
    amt_out_reset();
    $_SESSION['amt_out']['after_at'] = time();   // beranda menampilkan tutorial langkah berikutnya
    amt_tour_bump();

    amt_work_success('Berhasil Check-Out', 'Check-Out tercatat. Riwayat Check-In sudah direset.');
}

/* ------------------------------------------------------------
 * Tutorial (dipakai tutorial.php)
 * ------------------------------------------------------------ */

/** Tutorial beranda sesudah order selesai: scan segel di AVM dulu -> menu Check-Out menyala -> buka Check-Out. */
function amt_out_tour_pack(): ?array
{
    if (!amt_out_order_done()) {
        return null;
    }
    return [
        'journey'    => 'checkout',
        'subKey'     => 'amt_home_seal_' . amt_out_finished_at(),
        'startDelay' => amt_out_seal_seen() ? 0 : AMT_OUT_SEAL_SHOW_DELAY,
        'steps'      => [
            [
                'no'       => null,
                'label'    => 'Order Selesai',
                'target'   => null,
                'notice'   => true,                                // pemberitahuan: tetap tampil walau tutorial dimatikan
                'title'    => 'Scan segel di AVM dulu 🔖',
                'text'     => 'Order sudah selesai. Kembali ke depot, lalu scan segel bekas di mesin AVM terlebih dahulu. Scan segel dilakukan di AVM, bukan di aplikasi ini. Setelah segel discan, barulah Check-Out bisa dilakukan. Menu Check-Out akan menyala setelah kartu ini ditutup.',
                'button'   => 'Mengerti',
                'autoHide' => AMT_OUT_SEAL_VISIBLE_FOR,            // hilang sendiri setelah 20 detik
                'announce' => true,                                // menu Check-Out menyala setelah ini
                'skip'     => '.amt-home[data-seal-seen="1"]',     // sudah dibaca (halaman dimuat ulang)
                'done'     => null,
            ],
            [
                'no'     => 1,
                'target' => '[data-tour="amt-menu-checkout"]',
                'when'   => 'a[data-tour="amt-menu-checkout"]',    // baru muncul setelah menu Check-Out menyala
                'delay'  => AMT_OUT_HINT_DELAY,
                'title'  => 'Buka menu “Check-Out”',
                'text'   => 'Sudah scan segel di AVM? Ketuk menu Check-Out untuk menutup satu ritase pengiriman.',
                'hint'   => '👆 Ketuk menu “Check-Out” setelah scan segel di AVM',
                'done'   => null,
            ],
        ],
    ];
}

/**
 * Tutorial beranda setelah Check-Out: arahkan AMT mengakhiri kerja (Start / End -> End Work).
 * Kartu ini menyorot menu Start / End; langkah "End Work" dan form-nya ditangani tutorial
 * layar Start / End dan End Work (lihat amt_tour_steps_for() di tutorial.php).
 */
function amt_out_tour_after_pack(): ?array
{
    if (!amt_out_after_checkout()) {
        return null;
    }
    return [
        'journey'    => 'pulang',
        'subKey'     => 'amt_home_after_' . amt_out_count(),
        'startDelay' => 1000,
        'steps'      => [[
            'no'     => null,
            'label'  => 'Check-Out Berhasil',
            'target' => '[data-tour="amt-menu-start_end"]',
            'title'  => 'Check-Out berhasil: akhiri kerja',
            'text'   => 'Satu ritase sudah ditutup. Sekarang akhiri waktu kerja Anda. Buka menu Start / End, lalu ketuk End Work.',
            'hint'   => '👆 Ketuk menu “Start / End”',
            'done'   => null,
        ]],
    ];
}

/** Langkah tutorial form Check-Out (lokasi, aktivitas, foto, kirim). */
function amt_out_tour_form_steps(): array
{
    return [
        [
            'no'        => 2,
            'target'    => '#wk-refresh',
            'highlight' => '#wk-loc',
            'title'     => 'Cek lokasi Anda',
            'text'      => 'Pin di peta harus ada di area depot. Bila pin sudah tepat, tutorial lanjut sendiri. Bila belum, ketuk “Perbarui Lokasi”, lalu tunggu sampai pin muncul.',
            'hint'      => '👆 Ketuk “Perbarui Lokasi”',
            'wait'      => '⏳ Menunggu lokasi sesuai… perhatikan pin di peta',
            'done'      => '#wk-form[data-pin="1"]',   // tanpa needTap: lokasi sudah sesuai = lanjut sendiri
            'minShow'   => 3000,                       // tetap disorot minimal 3 detik, tidak langsung dilewati
            'loadSel'   => '#wk-loc[data-inrange="1"]',
            'loadHint'  => '⏳ Memeriksa lokasi… tunggu pin muncul di peta',
            'condHint'  => '✅ Lokasi sudah sesuai. Pastikan pin tepat di area depot.',
        ],
        [
            'no'        => 3,
            'target'    => '#wk-akt',
            'highlight' => '#wk-act-box',
            'title'     => 'Pilih aktivitas',
            'text'      => 'Ketuk kolom ini, lalu pilih “Tugas Rutin” (tugas harian biasa) atau “Tugas Lembur” (di luar jam biasa).',
            'hint'      => '👆 Ketuk kolom “Pilih aktivitas”, lalu pilih salah satu',
            'done'      => '#wk-form[data-act="1"]',
            'ok'        => '✅ Aktivitas sudah dipilih',
        ],
        // Foto selfie: ketuk kartu -> "Foto" -> "Simpan Foto" (3 aksi, nomor sama)
        ...amt_tour_photo_steps(4),
        [
            'no'     => 5,
            'target' => '#wk-submit',
            'title'  => 'Kirim Check-Out',
            'text'   => 'Semua sudah lengkap. Ketuk “Kirim Check-Out”. Riwayat Check-In akan direset setelah Check-Out berhasil.',
            'hint'   => '👆 Ketuk tombol biru “Kirim Check-Out”',
            'done'   => null,
        ],
        // Pop up sukses: Lanjutkan ke Homepage
        amt_tour_success_step(5, 'checkout'),
    ];
}