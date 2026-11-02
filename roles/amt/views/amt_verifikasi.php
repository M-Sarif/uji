<?php
$los      = amt_verif_lo_list();
$selected = amt_verif_selected();
$hasPick  = count($selected) > 0;
$allDone  = amt_verif_is_done();
$qrBase   = amt_verif_url('amt_verifikasi_qr', ['restart' => 1]);
$kodeBase = amt_verif_url('amt_verifikasi_kode', ['restart' => 1]);
$selQuery = '&sel=' . rawurlencode(implode(',', $selected));
?>

<section class="verif" id="verif" data-sync="<?= amt_e(amt_verif_url('amt_verifikasi', ['ajax' => 1])) ?>">
    <div data-tour="verif-list">
    <div class="verif-head">
        <h2 class="verif-title">Daftar LO</h2>
        <a class="verif-refresh" href="<?= amt_e(amt_verif_url('amt_verifikasi')) ?>">
            <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M20 11a8 8 0 0 0-14.9-3M4 13a8 8 0 0 0 14.9 3M4 4v4h4M20 20v-4h-4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Refresh
        </a>
    </div>
    <p class="verif-hint">Hanya LO yang sudah isi form bongkar yang bisa untuk verifikasi.</p>

    <?php foreach ($los as $lo):
        $eligible = amt_verif_is_eligible($lo);
        $verified = amt_verif_is_verified($lo['id']);
        $pickable = $eligible && !$verified;
        $checked  = in_array($lo['id'], $selected, true);
        $classes  = 'verif-lo' . ($checked ? ' is-selected' : '') . ($pickable ? '' : ' is-disabled');
    ?>
        <div class="<?= $classes ?>"<?= $pickable ? ' data-lo="' . amt_e($lo['id']) . '"' : '' ?>>
            <dl class="verif-rows">
                <div><dt>Nomor LO</dt><dd class="verif-strong"><?= amt_e($lo['id']) ?></dd></div>
                <div><dt>Order</dt><dd><span class="verif-pill"><?= amt_e($lo['order']) ?></span></dd></div>
                <div><dt>Form Bongkar</dt><dd>
                    <span class="verif-badge <?= $eligible ? 'is-ok' : 'is-wait' ?>"><?= amt_e($lo['form_bongkar']) ?></span>
                </dd></div>
                <?php if ($verified): ?>
                    <div><dt>Verifikasi</dt><dd><span class="verif-badge is-ok">Terverifikasi</span></dd></div>
                <?php endif; ?>
            </dl>

            <?php if ($pickable): ?>
                <a class="verif-check<?= $checked ? ' is-on' : '' ?>"
                   href="<?= amt_e(amt_verif_url('amt_verifikasi', ['toggle' => $lo['id']])) ?>"
                   role="checkbox" aria-checked="<?= $checked ? 'true' : 'false' ?>"
                   aria-label="Pilih LO <?= amt_e($lo['id']) ?>">
                    <svg viewBox="0 0 24 24" width="14" height="14" aria-hidden="true"><path d="M5 12.5l4.5 4.5L19 7.5" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>
    </div>

    <?php if ($allDone): ?>
        <p class="verif-note">Semua LO sudah diverifikasi.</p>
        <a class="verif-method is-on" href="<?= amt_e(amt_verif_url(AMT_VERIF_RETURN_SCREEN)) ?>">Kembali ke Shipment</a>
    <?php else: ?>
        <div data-tour="verif-methods">
        <h2 class="verif-title verif-title--gap">Metode Verifikasi</h2>
        <p class="verif-hint">Pilih metode untuk verifikasi order</p>

        <a class="verif-method<?= $hasPick ? ' is-on' : '' ?>" id="verifQr"
           data-base="<?= amt_e($qrBase) ?>" href="<?= amt_e($qrBase . $selQuery) ?>"
           aria-disabled="<?= $hasPick ? 'false' : 'true' ?>"<?= $hasPick ? '' : ' tabindex="-1"' ?>>
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M4 8V5a1 1 0 0 1 1-1h3M16 4h3a1 1 0 0 1 1 1v3M20 16v3a1 1 0 0 1-1 1h-3M8 20H5a1 1 0 0 1-1-1v-3M8 12h8" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Pindai Kode QR
        </a>
        <a class="verif-method<?= $hasPick ? ' is-on' : '' ?>" id="verifKode"
           data-base="<?= amt_e($kodeBase) ?>" href="<?= amt_e($kodeBase . $selQuery) ?>"
           aria-disabled="<?= $hasPick ? 'false' : 'true' ?>"<?= $hasPick ? '' : ' tabindex="-1"' ?>>
            <svg viewBox="0 0 24 24" width="16" height="16" aria-hidden="true"><path d="M6 3h9l4 4v14H6zM14 3v5h5M9 13h6M9 17h6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            Kode Konfirmasi
        </a>
        <p class="verif-note" id="verifPickNote"<?= $hasPick ? ' hidden' : '' ?>>Centang LO di atas untuk memilih metode.</p>
        </div>
    <?php endif; ?>
</section>

<script>
(function () {
    var root = document.getElementById('verif');
    if (!root) { return; }
    var cards = [].slice.call(root.querySelectorAll('.verif-lo[data-lo]'));
    var methods = [].slice.call(root.querySelectorAll('.verif-method[data-base]'));
    var note = document.getElementById('verifPickNote');
    var syncUrl = root.getAttribute('data-sync');
    var timer = 0;

    function selected() {
        var ids = [];
        for (var i = 0; i < cards.length; i++) {
            if (cards[i].classList.contains('is-selected')) { ids.push(cards[i].getAttribute('data-lo')); }
        }
        return ids;
    }

    function render() {
        var ids = selected();
        var has = ids.length > 0;
        var query = '&sel=' + encodeURIComponent(ids.join(','));
        for (var i = 0; i < methods.length; i++) {
            var m = methods[i];
            m.classList.toggle('is-on', has);
            m.setAttribute('aria-disabled', has ? 'false' : 'true');
            m.setAttribute('href', m.getAttribute('data-base') + query);
            if (has) { m.removeAttribute('tabindex'); } else { m.setAttribute('tabindex', '-1'); }
        }
        if (note) { note.hidden = has; }
    }

    function save() {
        clearTimeout(timer);
        timer = setTimeout(function () {
            if (!window.fetch) { return; }
            var url = syncUrl + '&sel=' + encodeURIComponent(selected().join(','));
            fetch(url, { credentials: 'same-origin', cache: 'no-store', keepalive: true }).catch(function () {});
        }, 120);
    }

    function toggle(card) {
        var on = !card.classList.contains('is-selected');
        card.classList.toggle('is-selected', on);
        var box = card.querySelector('.verif-check');
        if (box) {
            box.classList.toggle('is-on', on);
            box.setAttribute('aria-checked', on ? 'true' : 'false');
        }
        render();
        save();
    }

    root.addEventListener('click', function (e) {
        var t = e.target;
        if (!t || !t.closest) { return; }
        var method = t.closest('.verif-method');
        if (method) {
            if (method.getAttribute('aria-disabled') === 'true') { e.preventDefault(); }
            return;
        }
        var card = t.closest('.verif-lo[data-lo]');
        if (!card) { return; }
        e.preventDefault();
        toggle(card);
    });

    window.addEventListener('pageshow', function (e) {
        if (e.persisted) { window.location.reload(); }
    });

    render();
})();
</script>