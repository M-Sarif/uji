<?php
/* Fitur jam kerja AMT (Start Work / End Work).
 * State di $_SESSION['work'] = [
 *   'started_at' => timestamp, 'ended_at' => timestamp|null,
 *   'activity'   => 'Hadir' dst, 'start_photo' => bool, 'end_photo' => bool ] */

// Cadangan opsional (ikon AMT sebagai data URI). Tidak wajib ada.
if (is_file(__DIR__ . '/avatar_amt.php')) {
    require_once __DIR__ . '/avatar_amt.php';
}

if (date_default_timezone_get() === 'UTC') {
    date_default_timezone_set('Asia/Jakarta'); // WIB
}

// Lokasi tetap untuk verifikasi
const WORK_LAT = -2.723962;
const WORK_LNG = 114.261573;

// Pilihan aktivitas saat Start Work
const WORK_ACTIVITIES = ['Hadir', 'Sakit', 'Izin', 'Cuti', 'Alpa', 'Dinas Luar'];

// Menu yang hanya aktif saat timer berjalan
const WORK_GATED_SCREENS = ['checkin', 'pti', 'checkout'];

// Judul header untuk layar tambahan (tidak ada di data.php)
const WORK_SCREENS = [
    'start_end'  => 'Start / End Work',
    'start_work' => 'Start Work',
    'end_work'   => 'End Work',
];

function work_data(): array {
    return $_SESSION['work'] ?? [];
}

function work_is_running(): bool {
    $w = work_data();
    return !empty($w['started_at']) && empty($w['ended_at']);
}

/* Timestamp mulai, hanya saat timer berjalan (0 = timer berhenti) */
function work_started_at(): int {
    return work_is_running() ? (int) $_SESSION['work']['started_at'] : 0;
}

function work_fmt(?int $ts): string {
    return $ts ? date('d/m/Y H:i:s', $ts) : '-';
}

/* reset_flow_state() bawaan tidak boleh menghapus status kerja,
 * jadi disimpan dulu lalu dikembalikan. */
function reset_flow_keep_work(): void {
    $work = $_SESSION['work'] ?? null;
    reset_flow_state();
    if ($work !== null) {
        $_SESSION['work'] = $work;
    }
}

/* Ikon/avatar AMT (SVG inline, tanpa kamera) */
function work_avatar_svg(int $size = 120): string {
    return '<svg width="' . $size . '" height="' . round($size * 1.08) . '" viewBox="0 0 120 130" xmlns="http://www.w3.org/2000/svg" aria-label="AMT">'
        . '<ellipse cx="60" cy="124" rx="34" ry="5" fill="#000" opacity=".08"/>'
        . '<path d="M22 122c0-26 15-42 38-42s38 16 38 42z" fill="#2f5fa8"/>'
        . '<path d="M44 84l16 18 16-18" fill="#e9eef7"/>'
        . '<rect x="53" y="70" width="14" height="14" rx="6" fill="#f2b98f"/>'
        . '<circle cx="60" cy="52" r="24" fill="#f6c7a0"/>'
        . '<path d="M34 50c0-20 11-32 26-32s26 12 26 32z" fill="#fff" stroke="#d8dee9" stroke-width="2"/>'
        . '<rect x="30" y="47" width="60" height="7" rx="3.5" fill="#e8edf5"/>'
        . '<rect x="56" y="17" width="8" height="30" rx="4" fill="#dc2626"/>'
        . '<circle cx="51" cy="58" r="2.6" fill="#2b2b2b"/><circle cx="69" cy="58" r="2.6" fill="#2b2b2b"/>'
        . '<path d="M52 68q8 7 16 0" stroke="#a5583a" stroke-width="2.4" fill="none" stroke-linecap="round"/>'
        . '<rect x="86" y="70" width="16" height="28" rx="3" fill="#1f2937"/>'
        . '<rect x="88" y="73" width="12" height="20" rx="1.5" fill="#86efac"/>'
        . '<circle cx="93" cy="82" r="3" fill="#166534"/>'
        . '<path d="M78 100c2-8 6-12 10-12" stroke="#2f5fa8" stroke-width="9" fill="none" stroke-linecap="round"/>'
        . '</svg>';
}

/* Ikon AMT: dipakai dari folder assets/ (nama file di bawah). Kalau file itu
 * tidak ada, dipakai ikon tertanam (avatar_amt.php) bila tersedia, atau SVG
 * cadangan. Tidak akan menyebabkan error walau file-nya tidak ada. */
const WORK_AVATAR_FILE = 'Halaman Check-In_Form Check-In.png';

function work_avatar_url(): ?string {
    if (WORK_AVATAR_FILE !== '' && is_file(dirname(__DIR__) . '/assets/' . WORK_AVATAR_FILE)) {
        return 'assets/' . rawurlencode(WORK_AVATAR_FILE);
    }
    return defined('WORK_AVATAR_DATA') ? WORK_AVATAR_DATA : null;
}

function work_avatar_html(int $size = 120): string {
    $url = work_avatar_url();
    if ($url === null) {
        return work_avatar_svg($size);
    }
    return '<img src="' . htmlspecialchars($url, ENT_QUOTES) . '" alt="AMT" style="width:' . $size . 'px;height:auto;max-height:' . round($size * 1.2) . 'px;object-fit:contain;display:block;margin:0 auto">';
}

/* CSS: menu nonaktif + halaman Start/End + form verifikasi */
function work_styles(): void { ?>
<style>
  /* Menu nonaktif: hanya pudar (tetap berwarna), tidak hitam-putih */
  .menu-item.is-disabled { opacity: .45; cursor: not-allowed; pointer-events: none; user-select: none; }

  .wk-wrap { padding: 12px; font-size: 12px; color: #111827; }
  .wk-card { border: 1px solid; border-radius: 10px; padding: 12px; margin-bottom: 12px; }
  .wk-card.green { background: #dcfce7; border-color: #22c55e; }
  .wk-card.red   { background: #fee2e2; border-color: #ef4444; }
  .wk-row { display: flex; gap: 20px; margin-bottom: 8px; }
  .wk-row > div { flex: 1; min-width: 0; }
  .wk-card small, .wk-label { display: block; color: #6b7280; font-size: 11px; margin-bottom: 2px; }
  .wk-card strong { font-size: 12px; font-weight: 600; }
  .wk-thumb { display: inline-flex; }
  .wk-btn {
    display: flex; align-items: center; justify-content: center; gap: 6px;
    width: 100%; margin-top: 8px; padding: 8px; border-radius: 6px; box-sizing: border-box;
    border: 1px solid #3b82f6; background: #f8fffb; color: #2563eb;
    font-weight: 600; font-size: 12px; text-decoration: none; cursor: pointer;
  }
  .wk-btn.is-off { background: #eef1f6; border-color: #94a3b8; color: #94a3b8; cursor: not-allowed; pointer-events: none; }

  /* Form verifikasi */
  .wk-title { font-size: 13px; font-weight: 600; margin: 0 0 12px; }
  .wk-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; }
  .wk-head a { color: #2563eb; font-size: 11px; text-decoration: none; display: inline-flex; gap: 4px; align-items: center; }
  .wk-map { width: 100%; height: 150px; border: 0; border-radius: 10px; display: block; background: #e5e7eb; }
  .wk-coord { font-size: 10px; color: #6b7280; margin: 4px 0 12px; }
  .wk-select {
    width: 100%; padding: 9px 8px; border: 1px solid #d1d5db; border-radius: 6px;
    background: #fff; font-size: 12px; margin-bottom: 12px; box-sizing: border-box;
  }
  .wk-select:disabled { background: #f3f4f6; color: #374151; cursor: not-allowed; }
  .wk-hint { font-size: 10px; color: #6b7280; margin: -8px 0 12px; }
  .wk-photo {
    background: #fff; border: 1px solid #d1d5db; border-radius: 12px;
    padding: 16px 12px; text-align: center; margin-bottom: 14px;
  }
  .wk-photo b { display: block; margin-top: 6px; font-size: 12px; }
  .wk-photo p { margin: 4px 0 10px; font-size: 10px; color: #6b7280; }
  .wk-photo button {
    background: none; border: 0; color: #2563eb; font-weight: 600; font-size: 12px;
    display: inline-flex; gap: 6px; align-items: center; cursor: pointer;
  }
  .wk-photo.taken { border-color: #22c55e; background: #f0fdf4; }
  .wk-photo.taken button { color: #16a34a; cursor: default; }
  .wk-submit {
    width: 100%; padding: 10px; border: 0; border-radius: 8px; box-sizing: border-box;
    background: #2563eb; color: #fff; font-weight: 600; font-size: 12px;
    display: flex; gap: 6px; align-items: center; justify-content: center; cursor: pointer;
  }
  .wk-submit:disabled { background: #d1d5db; color: #f9fafb; cursor: not-allowed; }

  /* Simulasi kamera */
  .wk-wrap { position: relative; }
  .wk-cam {
    position: absolute; inset: 0; z-index: 50; background: #0b1220; color: #fff;
    display: none; flex-direction: column; align-items: center; justify-content: center;
    gap: 14px; text-align: center; padding: 16px;
  }
  .wk-cam.open { display: flex; }
  .wk-cam-view {
    position: relative; width: 220px; height: 280px; border-radius: 16px; overflow: hidden;
    background: linear-gradient(160deg, #24314f, #0f172a);
    display: flex; align-items: center; justify-content: center;
  }
  .wk-cam-view img, .wk-cam-view svg { position: relative; z-index: 1; }
  .wk-oval {
    position: absolute; z-index: 2; width: 150px; height: 195px; border-radius: 50%;
    border: 2px dashed rgba(255,255,255,.65); left: 50%; top: 50%; transform: translate(-50%, -50%);
  }
  .wk-flash { position: absolute; inset: 0; z-index: 3; background: #fff; opacity: 0; transition: opacity .25s; pointer-events: none; }
  .wk-flash.fire { opacity: 1; transition: none; }
  .wk-cam small { color: #cbd5e1; font-size: 11px; }
  .wk-shutter {
    width: 58px; height: 58px; border-radius: 50%; border: 4px solid #fff; background: transparent;
    padding: 0; cursor: pointer; position: relative;
  }
  .wk-shutter::after { content: ""; position: absolute; inset: 5px; border-radius: 50%; background: #fff; }
  .wk-shutter:active::after { background: #cbd5e1; }
  .wk-cancel { background: none; border: 0; color: #cbd5e1; font-size: 12px; cursor: pointer; }
  .wk-spin {
    width: 30px; height: 30px; border-radius: 50%;
    border: 3px solid rgba(255,255,255,.25); border-top-color: #fff; animation: wkspin .8s linear infinite;
  }
  @keyframes wkspin { to { transform: rotate(360deg); } }

  /* Foto sudah terverifikasi (ikon hilang) */
  .wk-done { display: none; padding: 14px 0; }
  .wk-photo.taken .wk-idle { display: none; }
  .wk-photo.taken .wk-done { display: block; }
  .wk-ok {
    width: 54px; height: 54px; border-radius: 50%; background: #22c55e; margin: 0 auto 8px;
    display: flex; align-items: center; justify-content: center;
  }
  .wk-done b { color: #15803d; }
  .wk-redo { background: none; border: 0; color: #2563eb; font-size: 11px; cursor: pointer; margin-top: 4px; }
</style>
<?php }