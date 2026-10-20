<?php
/**
 * Selesai Ritase peran AMT + arahan sesudahnya (Mulai / Selesai Kerja -> Selesai Kerja).
 * Letak: roles/amt/includes/amt_out.php
 *
 * Scan segel bekas TIDAK dilakukan di aplikasi: AMT melakukannya di AVM di depot.
 * Aplikasi hanya MENGARAHKAN lewat tutorial supaya AMT scan segel di AVM lebih dulu,
 * baru melakukan Selesai Ritase.
 *
 * Alur satu ritase penutup:
 *   1. Order diselesaikan (pop up "Menyelesaikan Order" -> Kirim) -> beranda AMT.
 *   2. Beranda: tutorial "Scan segel di AVM dulu" (tombol Mengerti / link Lewati; kalau tidak
 *      diklik hilang sendiri setelah 20 detik). Menu Selesai Ritase masih terkunci selama kartu tampil.
 *   3. Kartu tertutup -> 0,3 detik kemudian menu Selesai Ritase menyala; 1 detik kemudian tutorial
 *      menyorot menu Selesai Ritase. Failsafe: menyala sendiri 30 detik setelah order selesai.
 *   4. Layar Selesai Ritase: Riwayat Ritase + form (lokasi di depot, aktivitas, foto Selesai Ritase, Kirim Selesai Ritase);
 *      logikanya sama seperti Mulai Ritase. Foto yang sudah diambil tampil sebagai pratinjau + "Hapus Foto";
 *      tombol kirim menempel di bawah layar (terkunci "Tekan Mulai Ritase Terlebih Dahulu" bila tak ada Mulai Ritase).
 *   5. Selesai Ritase berhasil -> pop up "Selesai Ritase Berhasil" (tombol OK) -> Riwayat Ritase dikosongkan
 *      -> beranda menampilkan TUTORIAL yang mengarahkan
 *      AMT mengakhiri kerja: menu Mulai / Selesai Kerja -> Selesai Kerja -> form Selesai Kerja -> selesai.
 *      (Hanya tutorial; aplikasi tidak menanyakan apa pun ke pengguna.)
 *
 * State:
 *   $_SESSION['amt_out'] = ['seal_seen_at' => float, 'unlocked' => bool, 'after_at' => unix (Selesai Ritase terakhir; memicu tutorial Selesai Kerja)]
 *   $_SESSION['work']['checkouts'][] = riwayat Selesai Ritase (waktu, aktivitas, foto)
 *
 * Dimuat dari roles/amt/module.php (setelah amt_rating.php).
 */

const AMT_OUT_SCREEN = 'checkout';

const AMT_OUT_SEAL_SHOW_DELAY  = 1000;   // tutorial muncul 1 detik setelah beranda dibuka
const AMT_OUT_SEAL_VISIBLE_FOR = 20000;  // hilang sendiri setelah 20 detik kalau "Mengerti" tidak diklik
const AMT_OUT_UNLOCK_DELAY     = 300;    // menu Selesai Ritase menyala 0,3 detik setelah tutorial hilang
const AMT_OUT_HINT_DELAY       = 1000;   // petunjuk "ketuk Selesai Ritase" muncul 1 detik setelah menu menyala
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

/** Siap Selesai Ritase: sedang bekerja, sudah Mulai Ritase, dan ada order yang sudah diselesaikan. */
function amt_out_order_done(): bool
{
    return work_is_running() && amt_last_checkin_at() > 0 && amt_out_finished_at() > 0;
}

/** Tutorial "scan segel di AVM" sudah ditutup (Mengerti / Lewati / hilang sendiri)? */
function amt_out_seal_seen(): bool
{
    return (float) amt_out_get('seal_seen_at') > 0 || !empty(amt_out_get('unlocked'));
}

/** Sisa detik sampai menu Selesai Ritase menyala otomatis (failsafe); 0 bila sudah lewat. */
function amt_out_failsafe_remaining(): int
{
    $at = amt_out_finished_at();
    return $at > 0 ? max(0, $at + AMT_OUT_FAILSAFE_SECONDS - time()) : 0;
}

/** Menu Selesai Ritase aktif? Order selesai DAN tutorial "scan segel di AVM" sudah ditutup. */
function amt_out_unlocked(): bool
{
    if (!amt_out_order_done()) {
        return false;
    }
    return amt_out_seal_seen() || amt_out_failsafe_remaining() === 0;
}

/** Selesai Ritase baru saja dilakukan dan belum Selesai Kerja? (memicu tutorial Mulai / Selesai Kerja -> Selesai Kerja) */
function amt_out_after_checkout(): bool
{
    return work_is_running() && (int) amt_out_get('after_at') > 0;
}

/** Dipanggil bila AMT Mulai Ritase lagi: tutorial arahan Selesai Kerja tidak berlaku lagi. */
function amt_out_clear_after(): void
{
    unset($_SESSION['amt_out']['after_at']);
}

/** Jumlah Selesai Ritase yang sudah dilakukan (dipakai sebagai kunci tutorial). */
function amt_out_count(): int
{
    $c = $_SESSION['work']['checkouts'] ?? [];
    return is_array($c) ? count($c) : 0;
}

/** Nomor urut dalam kata untuk "Mulai Ritase pertama / kedua / ...". */
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
 * Penjagaan akses layar Selesai Ritase (GET) - dipanggil dari amt_guard_work()
 * ------------------------------------------------------------ */
function amt_out_guard(string $screen): void
{
    if ($screen === AMT_OUT_SCREEN && !amt_out_unlocked()) {
        go_to(amt_home_screen());
    }
}

/* ------------------------------------------------------------
 * POST "Kirim Selesai Ritase" - pola Post/Redirect/Get
 * Logika sama seperti Mulai Ritase (timer berjalan, dalam radius, aktivitas, foto),
 * ditambah: menu Selesai Ritase aktif (order selesai + tutorial segel sudah dibaca).
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

    // Foto: hanya gambar yang ukurannya wajar (sama seperti Mulai Ritase)
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

    // Selesai Ritase: Jam Kerja Aktif DIJEDA (lanjut lagi saat Mulai Ritase berikutnya); Jam Kerja tetap berjalan.
    work_active_pause();

    // Ritase selesai: Riwayat Ritase dikosongkan, alur berikutnya mulai dari awal.
    $_SESSION['work']['checkins'] = [];
    amt_pti_reset();
    amt_spbu_reset();
    amt_verif_reset();
    amt_kendala_reset();   // ritase selesai: laporan kendala ritase ini dibersihkan (tutorial "Laporkan Kendala" tampil lagi di ritase berikutnya)
    amt_out_reset();
    $_SESSION['amt_out']['after_at'] = time();   // beranda menampilkan tutorial langkah berikutnya
    amt_tour_bump();

    // Pop up sesuai desain: "Selesai Ritase Berhasil" + tombol "OK" (OK membawa ke beranda, tempat tutorial arahan Selesai Kerja).
    amt_work_success('Selesai Ritase Berhasil', 'Anda berhasil melakukan Selesai Ritase. Selamat beristirahat!', 'OK');
}

/* ------------------------------------------------------------
 * Tutorial (dipakai tutorial.php)
 * ------------------------------------------------------------ */

/** Tutorial beranda sesudah order selesai: scan segel di AVM dulu -> menu Selesai Ritase menyala -> buka Selesai Ritase. */
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
                'text'     => 'Order sudah selesai. Kembali ke depot, lalu scan segel bekas di mesin AVM terlebih dahulu. Scan segel dilakukan di AVM, bukan di aplikasi ini. Setelah segel discan, barulah Selesai Ritase bisa dilakukan. Menu Selesai Ritase akan menyala setelah kartu ini ditutup.',
                'button'   => 'Mengerti',
                'autoHide' => AMT_OUT_SEAL_VISIBLE_FOR,            // hilang sendiri setelah 20 detik
                'announce' => true,                                // menu Selesai Ritase menyala setelah ini
                'skip'     => '.amt-home[data-seal-seen="1"]',     // sudah dibaca (halaman dimuat ulang)
                'done'     => null,
            ],
            [
                'no'     => 1,
                'target' => '[data-tour="amt-menu-checkout"]',
                'when'   => 'a[data-tour="amt-menu-checkout"]',    // baru muncul setelah menu Selesai Ritase menyala
                'delay'  => AMT_OUT_HINT_DELAY,
                'title'  => 'Buka menu “Selesai Ritase”',
                'text'   => 'Sudah scan segel di AVM? Ketuk menu Selesai Ritase untuk menutup satu ritase pengiriman.',
                'hint'   => '👆 Ketuk menu “Selesai Ritase” setelah scan segel di AVM',
                'done'   => null,
            ],
        ],
    ];
}

/**
 * Tutorial beranda setelah Selesai Ritase: arahkan AMT mengakhiri kerja (Mulai / Selesai Kerja -> Selesai Kerja).
 * Kartu ini menyorot menu Mulai / Selesai Kerja; langkah "Selesai Kerja" dan form-nya ditangani tutorial
 * layar Mulai / Selesai Kerja dan Selesai Kerja (lihat amt_tour_steps_for() di tutorial.php).
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
            'label'  => 'Selesai Ritase Berhasil',
            'target' => '[data-tour="amt-menu-start_end"]',
            'title'  => 'Selesai Ritase berhasil: akhiri kerja',
            'text'   => 'Satu ritase sudah ditutup. Sekarang akhiri jam kerja Anda. Buka menu Mulai / Selesai Kerja, lalu ketuk Selesai Kerja.',
            'hint'   => '👆 Ketuk menu “Mulai / Selesai Kerja”',
            'done'   => null,
        ]],
    ];
}

/** Langkah tutorial layar Selesai Ritase (riwayat, lokasi, aktivitas, foto, kirim, pop up OK). */
function amt_out_tour_form_steps(): array
{
    return [
        // 2) Riwayat Ritase (bagian atas layar)
        [
            'no'        => 2,
            'target'    => '.co-hist',
            'title'     => 'Riwayat Ritase',
            'text'      => 'Di atas ada daftar Mulai Ritase yang Anda lakukan pada ritase ini. Daftar ini otomatis kosong setelah Selesai Ritase berhasil.',
            'button'    => 'Mengerti',
            'done'      => null,
        ],
        // 3) Lokasi
        [
            'no'        => 3,
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
        // 4) Aktivitas
        [
            'no'        => 4,
            'target'    => '#wk-akt',
            'highlight' => '#wk-act-box',
            'title'     => 'Pilih aktivitas',
            'text'      => 'Ketuk kolom ini, lalu pilih “Tugas Rutin” (tugas harian biasa) atau “Tugas Lembur” (di luar jam biasa).',
            'hint'      => '👆 Ketuk kolom “Pilih aktivitas”, lalu pilih salah satu',
            'done'      => '#wk-form[data-act="1"]',
            'ok'        => '✅ Aktivitas sudah dipilih',
        ],
        // 5) Foto Selesai Ritase: ketuk kartu -> "Foto" -> "Simpan Foto" (3 aksi, nomor sama); sesudahnya foto tampil + "Hapus Foto"
        ...amt_tour_photo_steps(5, true),
        // 6) Kirim (tombol menempel di bawah layar)
        [
            'no'     => 6,
            'target' => '#wk-submit',
            'title'  => 'Kirim Selesai Ritase',
            'text'   => 'Semua sudah lengkap. Ketuk “Kirim Selesai Ritase” di bagian bawah layar. Riwayat Ritase akan direset setelah Selesai Ritase berhasil.',
            'hint'   => '👆 Ketuk tombol biru “Kirim Selesai Ritase”',
            'done'   => null,
        ],
        // Pop up "Selesai Ritase Berhasil": ketuk OK
        amt_tour_success_step(6, 'checkout'),
    ];
}