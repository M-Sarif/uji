<?php
work_styles();
$w        = work_data();
$running  = work_is_running();
$activity = $w['activity'] ?? '-';
$hasStart = !empty($w['started_at']);
$hasEnd   = !empty($w['ended_at']);
$arrowIn  = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4M10 17l5-5-5-5M15 12H3"/></svg>';
$arrowOut = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/></svg>';
?>
<div class="wk-wrap">
  <!-- START WORK -->
  <div class="wk-card green">
    <div class="wk-row">
      <div><small>Waktu Start Work</small><strong><?= $hasStart ? work_fmt((int) $w['started_at']) : '-' ?></strong></div>
      <div><small>Foto Verifikasi</small>
        <?php if ($hasStart): ?><span class="wk-thumb" style="display:flex;align-items:center;gap:4px;color:#15803d;font-weight:600;font-size:12px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Terverifikasi</span><?php else: ?><strong>-</strong><?php endif; ?>
      </div>
    </div>
    <div><small>Aktivitas</small><strong><?= $hasStart ? htmlspecialchars($activity, ENT_QUOTES) : '-' ?></strong></div>
    <?php if (!$running): ?>
      <a class="wk-btn" href="?screen=start_work"><?= $arrowIn ?> Start Work</a>
    <?php else: ?>
      <span class="wk-btn is-off"><?= $arrowIn ?> Start Work</span>
    <?php endif; ?>
  </div>

  <!-- END WORK -->
  <div class="wk-card red">
    <div class="wk-row">
      <div><small>Waktu End Work</small><strong><?= $hasEnd ? work_fmt((int) $w['ended_at']) : '-' ?></strong></div>
      <div><small>Foto Verifikasi</small>
        <?php if ($hasEnd): ?><span class="wk-thumb" style="display:flex;align-items:center;gap:4px;color:#15803d;font-weight:600;font-size:12px"><svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>Terverifikasi</span><?php else: ?><strong>-</strong><?php endif; ?>
      </div>
    </div>
    <div><small>Aktivitas</small><strong><?= $hasEnd ? htmlspecialchars($activity, ENT_QUOTES) : '-' ?></strong></div>
    <?php if ($running): ?>
      <a class="wk-btn" href="?screen=end_work"><?= $arrowOut ?> End Work</a>
    <?php else: ?>
      <span class="wk-btn is-off"><?= $arrowOut ?> End Work</span>
    <?php endif; ?>
  </div>
</div>