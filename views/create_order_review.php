<?php $order = $_SESSION['order']; ?>
<form method="post" action="index.php" class="content-pad" style="display:flex;flex-direction:column;min-height:100%;">
    <input type="hidden" name="action" value="submit_order">

    <h2 style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:16px;">3. Review Order</h2>

    <div class="review-card" style="margin-bottom:20px;">
        <div class="review-head">Informasi Umum</div>
        <div class="review-body">
            <div class="review-line"><span class="k">No SPBU</span><span class="v"><?php echo h(explode(' - ', $order['no_spbu'])[0]); ?></span></div>
            <div class="review-line"><span class="k">Ship-to</span><span class="v"><?php echo h($order['ship_to']); ?></span></div>
            <div class="review-line"><span class="k">Jenis Order</span><span class="v blue"><?php echo h($order['jenis']); ?></span></div>
            <div class="review-line"><span class="k">Tanggal</span><span class="v"><?php echo h($order['tanggal']); ?></span></div>
        </div>
    </div>

    <div class="review-card" style="margin-bottom:auto;">
        <div class="review-head">Produk BBM</div>
        <div class="review-body review-product">
            <div>
                <p class="name"><?php echo h($order['produk']); ?></p>
                <p class="qty">Qty Order: <b><?php echo number_format((int) $order['qty'], 0, ',', '.'); ?> L</b></p>
            </div>
            <div class="check-circle">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
        </div>
    </div>

    <div class="form-footer">
        <button type="button" class="btn-outline" onclick="history.back()">Draft</button>
        <button type="submit" class="btn-primary">Submit Order</button>
    </div>
</form>
