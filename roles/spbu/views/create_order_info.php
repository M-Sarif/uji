<?php $order = $_SESSION['order']; ?>
<form method="post" action="index.php" class="content-pad">
    <input type="hidden" name="action" value="save_order_info">

    <h2 style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:16px;">1. Isi Informasi Umum</h2>

    <div class="field">
        <label>No SPBU</label>
        <div class="static-grey"><?php echo h($order['no_spbu']); ?></div>
    </div>

    <div class="field">
        <label>Ship-to Number</label>
        <div class="static-grey"><?php echo h($order['ship_to']); ?></div>
    </div>

    <div class="field">
        <label>Terminal Supply *</label>
        <div class="static-white"><?php echo h($order['terminal']); ?></div>
    </div>

    <div class="field">
        <label>Jenis Order *</label>
        <div class="row">
            <label class="radio-pill<?php echo $order['jenis'] === 'Reguler' ? ' checked' : ''; ?>">
                <input type="radio" name="jenis" value="Reguler" <?php echo $order['jenis'] === 'Reguler' ? 'checked' : ''; ?>>
                <span class="dot"></span> Reguler
            </label>
            <label class="radio-pill<?php echo $order['jenis'] === 'Emergency' ? ' checked' : ''; ?>">
                <input type="radio" name="jenis" value="Emergency" <?php echo $order['jenis'] === 'Emergency' ? 'checked' : ''; ?>>
                <span class="dot"></span> Emergency
            </label>
        </div>
    </div>

    <div class="field">
        <label>Tanggal Pengiriman *</label>
        <div class="static-white">
            <input type="text" name="tanggal" value="<?php echo h($order['tanggal']); ?>" style="border:none;outline:none;font:inherit;width:100%;color:#1e293b;font-weight:500;">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" stroke-linejoin="round" d="M12 7v5l3 2"/></svg>
        </div>
    </div>

    <div class="form-footer">
        <button type="button" class="btn-outline" onclick="history.back()">Draft</button>
        <button type="submit" class="btn-primary">Selanjutnya</button>
    </div>
</form>

<script>
// Highlight radio pill terpilih tanpa perlu reload (murni progresif, non-esensial)
document.querySelectorAll('.radio-pill input').forEach(function (input) {
    input.addEventListener('change', function () {
        document.querySelectorAll('.radio-pill').forEach(function (el) { el.classList.remove('checked'); });
        input.closest('.radio-pill').classList.add('checked');
    });
});
</script>
