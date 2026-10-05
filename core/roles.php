<?php
/* ============================================================
 * OneFIS - CORE - daftar peran + "kontrak" hook.
 *
 * Tiap peran (roles/<peran>/module.php) menyediakan fungsi dengan awalan
 * nama perannya, mis. spbu_view_file() atau amt_view_file(). Core memanggil
 * fungsi-fungsi itu lewat role_call(), jadi core TIDAK perlu tahu isi
 * peran mana pun, dan kode SPBU / AMT tidak saling bercampur.
 *
 * Hook yang dikenali core (semua OPSIONAL - bila tidak ada, dilewati):
 *
 *  Layar & tampilan
 *   <peran>_screens(): array                daftar layar milik peran
 *   <peran>_card_image(): string            gambar kartu peran di layar pilih peran
 *   <peran>_view_file($screen): string      path file view layar tsb
 *   <peran>_screen_title($screen): string   judul header / <title>
 *   <peran>_prev_screen($screen): ?string   tujuan tombol back header
 *   <peran>_next_screen($screen): ?string   layar "berikutnya" (default dashboard)
 *   <peran>_tutorial_text($screen): string  teks tutorial statis (lama)
 *   <peran>_screen_css($screen): array      daftar URL css untuk layar tsb
 *   <peran>_header_partial($screen): ?string  file header khusus (mis. beranda)
 *   <peran>_header_style($screen): array    ['class'=>' centered', 'arrow'=>bool, 'spacer'=>bool]
 *   <peran>_content_class($screen): string  kelas tambahan pada div.content
 *   <peran>_after_view($screen): void       dipanggil setelah view dirender
 *
 *  Tutorial terpandu (guided tour)
 *   <peran>_global_css(): array             css global peran (dimuat di <head>)
 *   <peran>_tour_assets(): array            ['engine'=>url js, 'css'=>[url css]]
 *   <peran>_tour_config($screen): array     data tutorial layar tsb
 *
 *  Alur & state
 *   <peran>_init_session(): void            isi default $_SESSION milik peran
 *   <peran>_on_role_change(): void          dipanggil saat pengguna GANTI peran
 *   <peran>_on_role_select_screen(): void   dipanggil saat layar pilih peran dibuka
 *   <peran>_bootstrap($screen): void        sebelum output (guard/redirect khusus peran)
 *   <peran>_guard($screen): void            penjagaan akses layar (GET)
 *   <peran>_on_home($screen): bool          layar beranda peran dibuka (true = progres alur di-reset)
 *   <peran>_accepts_post($action): bool     aksi POST ini milik peran?
 *   <peran>_handle_post($action): void      proses form POST (pola PRG)
 *   <peran>_handle_get($screen): void       aksi GET sederhana (toggle, dll)
 * ============================================================ */

// Peran pengguna (dipilih di layar awal "role_select"):
//   'spbu' : alur SPBU (dashboard -> ... -> done)    -> roles/spbu/
//   'amt'  : halaman AMT (Start/End Work, PTI, ...)  -> roles/amt/
const ROLES = [
    'spbu' => ['label' => 'SPBU', 'desc' => 'Kelola order BBM, pantau pengiriman, dan verifikasi pembongkaran.', 'home' => 'dashboard'],
    'amt'  => ['label' => 'AMT',  'desc' => 'Awak Mobil Tangki: antar BBM dan layani serah terima di SPBU.',      'home' => 'amt_home'],
];

// Peran yang mesin tutorialnya dipakai bila pengguna BELUM memilih peran
// (layar pilih peran).
const DEFAULT_TOUR_ROLE = 'spbu';

/** Kunci semua peran terdaftar (['spbu', 'amt']) */
function role_keys(): array
{
    return array_keys(ROLES);
}

/** Panggil hook peran. Mengembalikan null bila peran/hook tidak ada. */
function role_call(?string $role, string $hook, ...$args)
{
    if ($role === null) {
        return null;
    }
    $fn = $role . '_' . $hook;
    return function_exists($fn) ? $fn(...$args) : null;
}

/** Semua layar yang valid: role_select + layar milik tiap peran */
function app_screens(): array
{
    $all = ['role_select'];
    foreach (role_keys() as $role) {
        $all = array_merge($all, (array) role_call($role, 'screens'));
    }
    return $all;
}

/** Peran pemilik sebuah layar (null = terbuka untuk semua, yaitu role_select) */
function screen_owner(string $screen): ?string
{
    foreach (role_keys() as $role) {
        if (in_array($screen, (array) role_call($role, 'screens'), true)) {
            return $role;
        }
    }
    return null;
}

/** Apakah peran ini boleh mengirim aksi POST tsb? (select_role ditangani core) */
function role_accepts_post(?string $role, string $action): bool
{
    return (bool) role_call($role, 'accepts_post', $action);
}

/** Set default nilai session bila belum ada (state awal aplikasi) */
function init_session_state(): void
{
    if (!isset($_SESSION['role'])) {
        // Peran belum dipilih -> layar awal menampilkan pilihan SPBU / AMT
        $_SESSION['role'] = null;
    }
    foreach (role_keys() as $role) {
        role_call($role, 'init_session');
    }
}
