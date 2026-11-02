<?php
$info    = ACTIVE_SHIPMENT_INFO;
$stages  = ACTIVE_SHIPMENT_STAGES;
$current = ACTIVE_SHIPMENT_CURRENT_STAGE;
$log     = ACTIVE_SHIPMENT_LOG;
$mtPlat  = ARRIVAL_SUBJECTS['mt_ok']['name'];

$icons = [
    'order' => 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 012-2h2a2 2 0 012 2v0a2 2 0 01-2 2h-2a2 2 0 01-2-2zM9 14l2 2 4-4',
    'depot' => 'M4 6c0-1.1 3.6-2 8-2s8 .9 8 2-3.6 2-8 2-8-.9-8-2zM4 6v6c0 1.1 3.6 2 8 2s8-.9 8-2V6M4 12v6c0 1.1 3.6 2 8 2s8-.9 8-2v-6',
    'kirim' => 'M3 7h11v9H3zM14 10h4l3 3v3h-7M7.5 18.5a1.5 1.5 0 100-.01M17.5 18.5a1.5 1.5 0 100-.01',
    'tiba'  => 'M5 21V5a2 2 0 012-2h6a2 2 0 012 2v16M3 21h14M15 9h2a2 2 0 012 2v5a1.5 1.5 0 003 0V9l-3-3M7 7h6v4H7z',
];
?>
<div class="card active-card" id="activeShip" data-tour="active-shipment">
    <div class="active-head">
        <h2 class="active-title">Pengiriman Aktif</h2>
        <a class="active-track" href="index.php?screen=track_order">
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="2.5"/><path d="M12 2v3M12 19v3M2 12h3M19 12h3"/></svg>
            Lacak Pengiriman
        </a>
    </div>

    <div class="active-tabs" role="tablist">
        <button type="button" class="active-tab is-active" role="tab" aria-selected="true" data-tab="progress">Progress Pengiriman</button>
        <button type="button" class="active-tab" role="tab" aria-selected="false" data-tab="urutan">Urutan Pengiriman</button>
    </div>

    <div class="active-panel" data-panel="progress" role="tabpanel">
        <div class="active-stages">
            <?php foreach ($stages as $i => $st): $n = $i + 1; $reached = $n <= $current; ?>
                <?php if ($i > 0): ?><span class="active-line<?php echo $reached ? ' is-on' : ''; ?>"></span><?php endif; ?>
                <button type="button" class="active-stage<?php echo $reached ? ' is-on' : ''; ?><?php echo $n === $current ? ' is-selected' : ''; ?>"
                        data-stage="<?php echo $n; ?>" data-label="<?php echo h($st['label']); ?>"
                        aria-label="<?php echo h($st['label']); ?>"<?php echo $reached ? '' : ' disabled'; ?>>
                    <svg viewBox="0 0 24 24" width="22" height="22" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="<?php echo h($icons[$st['key']]); ?>"/></svg>
                    <svg class="active-chev" viewBox="0 0 24 24" width="12" height="12" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
                </button>
            <?php endforeach; ?>
        </div>

        <p class="active-cap">Progress Pengiriman</p>
        <p class="active-now" id="activeNow"><?php echo h($stages[$current - 1]['label']); ?></p>

        <ol class="active-log" id="activeLog">
            <?php foreach ($log as $e): ?>
                <li class="active-log-item" data-stage="<?php echo (int) $e['stage']; ?>">
                    <span class="active-dot">
                        <svg viewBox="0 0 24 24" width="10" height="10" fill="none" stroke="currentColor" stroke-width="3.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 13l4 4L19 7"/></svg>
                    </span>
                    <div class="active-log-body">
                        <div class="active-log-row">
                            <strong><?php echo h($e['date']); ?></strong>
                            <span><?php echo h($e['time']); ?></span>
                        </div>
                        <p><?php echo h($e['text']); ?></p>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>

        <button type="button" class="active-more" id="activeMore" aria-expanded="false">
            <span id="activeMoreText">Lihat Semua Progress</span>
            <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 9l6 6 6-6"/></svg>
        </button>
    </div>

    <div class="active-panel" data-panel="urutan" role="tabpanel" hidden>
        <ol class="active-seq">
            <li class="active-seq-item">
                <span class="active-seq-no">1</span>
                <div class="active-seq-body">
                    <strong><?php echo h($info['depot']); ?> → SPBU <?php echo h($info['spbu']); ?></strong>
                    <p>Nomor Polisi MT: <b><?php echo h($mtPlat); ?></b></p>
                    <p>Estimasi Tiba: <b><?php echo h($info['eta']); ?></b></p>
                    <div class="active-seq-tags">
                        <?php foreach ($info['products'] as $pn): ?>
                            <span class="tag-chip"><?php echo h($pn); ?></span>
                        <?php endforeach; ?>
                    </div>
                </div>
            </li>
        </ol>
    </div>
</div>

<script>
(function () {
    var root = document.getElementById('activeShip');
    if (!root) { return; }

    var tabs   = root.querySelectorAll('.active-tab');
    var panels = root.querySelectorAll('.active-panel');
    var stages = root.querySelectorAll('.active-stage');
    var items  = root.querySelectorAll('.active-log-item');
    var now    = document.getElementById('activeNow');
    var more   = document.getElementById('activeMore');
    var moreTx = document.getElementById('activeMoreText');
    var current = <?php echo (int) $current; ?>;
    var selected = current;
    var expanded = false;

    function renderLog() {
        var shown = 0;
        items.forEach(function (li) {
            var inStage = parseInt(li.getAttribute('data-stage'), 10) <= selected;
            var visible = inStage && (expanded || shown === 0);
            if (inStage) { shown++; }
            li.hidden = !visible;
            li.classList.toggle('is-latest', inStage && shown === 1);
        });
        more.hidden = shown <= 1;
        more.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        more.classList.toggle('is-open', expanded);
        moreTx.textContent = expanded ? 'Sembunyikan Progress' : 'Lihat Semua Progress';
    }

    tabs.forEach(function (tab) {
        tab.addEventListener('click', function () {
            var key = tab.getAttribute('data-tab');
            tabs.forEach(function (t) {
                var on = t === tab;
                t.classList.toggle('is-active', on);
                t.setAttribute('aria-selected', on ? 'true' : 'false');
            });
            panels.forEach(function (p) { p.hidden = p.getAttribute('data-panel') !== key; });
        });
    });

    stages.forEach(function (btn) {
        btn.addEventListener('click', function () {
            if (btn.disabled) { return; }
            selected = parseInt(btn.getAttribute('data-stage'), 10);
            stages.forEach(function (b) { b.classList.toggle('is-selected', b === btn); });
            now.textContent = btn.getAttribute('data-label');
            renderLog();
        });
    });

    more.addEventListener('click', function () {
        expanded = !expanded;
        renderLog();
    });

    renderLog();
})();
</script>