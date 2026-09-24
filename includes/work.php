<?php
/* Status jam kerja AMT (Start / End Work).
 * Disimpan di $_SESSION['work'] = ['started_at' => timestamp]. */

function work_is_running(): bool {
    return !empty($_SESSION['work']['started_at']);
}

function work_started_at(): int {
    return (int) ($_SESSION['work']['started_at'] ?? 0);
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

/* Menu yang hanya aktif saat timer berjalan. */
const WORK_GATED_SCREENS = ['checkin', 'pti', 'checkout'];

/* CSS tombol nonaktif (dipakai dashboard & halaman Start/End). */
function work_styles(): void { ?>
<style>
  .menu-item.is-disabled {
    opacity: .45; filter: grayscale(1);
    cursor: not-allowed; pointer-events: none; user-select: none;
  }
  .work-card { border: 1px solid; border-radius: 10px; padding: 12px; margin: 12px; }
  .work-card.green { background: #dcfce7; border-color: #22c55e; }
  .work-card.red   { background: #fee2e2; border-color: #ef4444; }
  .work-card .row { display: flex; gap: 24px; margin-bottom: 8px; }
  .work-card small { display: block; color: #6b7280; font-size: 11px; }
  .work-card strong { font-size: 13px; }
  .work-card button {
    width: 100%; margin-top: 8px; padding: 8px; border-radius: 6px;
    border: 1px solid #3b82f6; background: #f8fffb; color: #2563eb; font-weight: 600;
  }
  .work-card button:disabled {
    background: #eef1f6; border-color: #94a3b8; color: #94a3b8; cursor: not-allowed;
  }
</style>
<?php }