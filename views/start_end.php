<?php
work_styles();
$running = work_is_running();
$startAt = work_started_at();
?>
<form method="post" action="?screen=start_end">
  <div class="work-card green">
    <div class="row">
      <div><small>Waktu Start Work</small><strong><?= $running ? date('d/m/Y H:i:s', $startAt) : '-' ?></strong></div>
      <div><small>Foto Verifikasi</small><strong>-</strong></div>
    </div>
    <div><small>Aktivitas</small><strong>-</strong></div>
    <button type="submit" name="action" value="start_work" <?= $running ? 'disabled' : '' ?>>Start Work</button>
  </div>

  <div class="work-card red">
    <div class="row">
      <div><small>Waktu End Work</small><strong>-</strong></div>
      <div><small>Foto Verifikasi</small><strong>-</strong></div>
    </div>
    <div><small>Aktivitas</small><strong>-</strong></div>
    <button type="submit" name="action" value="end_work" <?= $running ? '' : 'disabled' ?>>End Work</button>
  </div>
</form>