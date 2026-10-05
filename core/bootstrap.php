<?php
/* ============================================================
 * OneFIS - CORE - bootstrap aplikasi (dimuat oleh index.php).
 *
 * Folder "core/" = kode yang DIPAKAI BERSAMA oleh semua peran:
 *   core/bootstrap.php      file ini (lokasi folder, zona waktu, memuat peran)
 *   core/helpers.php        h(), go_to(), current_role(), role_home()
 *   core/roles.php          daftar peran + "kontrak" hook peran + init session
 *   core/layout_top.php     bingkai HP bagian atas (head, header, wrapper konten)
 *   core/layout_bottom.php  bingkai HP bagian bawah (toast, mesin tutorial)
 *   core/views/             layar bersama (pilih peran)
 *   core/assets/            css/img bersama (base, components, role, logo)
 *
 * Kode khusus tiap peran ada di folder sendiri-sendiri:
 *   roles/spbu/   peran SPBU      (module.php = pintu masuk)
 *   roles/amt/    peran AMT       (module.php = pintu masuk)
 * ============================================================ */

define('APP_ROOT', dirname(__DIR__));   // folder yang berisi index.php
const CORE_DIR       = __DIR__;
const CORE_ASSET_DIR = __DIR__ . '/assets';
const CORE_URL       = 'core/assets';   // dipakai di href/src (relatif terhadap index.php)

// Zona waktu aplikasi (WIB). Dulu diset di kode AMT, padahal dipakai juga oleh SPBU
// (mis. waktu "Tiba di Lokasi"), jadi sekarang diatur di core.
if (date_default_timezone_get() === 'UTC') {
    date_default_timezone_set('Asia/Jakarta');
}

require CORE_DIR . '/helpers.php';
require CORE_DIR . '/roles.php';

// Muat semua peran. Setiap module.php mendaftarkan fungsi hook "<peran>_<hook>".
require APP_ROOT . '/roles/spbu/module.php';
require APP_ROOT . '/roles/amt/module.php';
