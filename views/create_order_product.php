<?php $order = $_SESSION['order']; ?>
<div class="content-pad" style="display:flex;flex-direction:column;min-height:100%;">
    <h2 style="font-size:14px;font-weight:700;color:#1e293b;margin-bottom:16px;">2. Pilih Produk BBM</h2>

    <?php if (!$order['produk_added']): ?>
        <div class="empty-state">
            <div class="empty-icon">
                <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14m-7-7h14"/></svg>
            </div>
            <h3>Produk BBM masih kosong</h3>
            <p>Silakan tambah produk BBM terlebih dahulu</p>
            <a href="index.php?screen=create_order_product&added=1" class="btn-outline" style="width:100%;text-align:center;border-color:#2563eb;color:#2563eb;">+ Tambah Produk BBM</a>
        </div>
    <?php else: ?>
        <form method="post" action="index.php" style="flex:1;display:flex;flex-direction:column;">
            <input type="hidden" name="action" value="apply_product">

            <div class="card" style="margin-bottom:24px;">
                <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:8px;">Produk BBM *</label>
                <div class="static-grey" style="margin-bottom:20px;font-weight:700;color:#1e293b;"><?php echo h($order['produk']); ?></div>

                <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:8px;">Stok Aktual Produk BBM</label>
                <div class="kv-row"><span class="k">Tank 1 (60.000L)</span><span class="v">40.000 L</span></div>
                <div class="kv-row total" style="margin-bottom:24px;"><span class="k">Total Kapasitas</span><span class="v">60.000 L</span></div>

                <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:8px;">Qty Rekomendasi Sistem</label>
                <div class="static-grey" style="margin-bottom:24px;font-weight:700;">60.000 Liter</div>

                <label style="font-size:12px;font-weight:600;color:#475569;display:block;margin-bottom:8px;">Qty Order *</label>
                <div class="qty-input">
                    <input type="number" name="qty" value="<?php echo (int) $order['qty']; ?>" min="1" required>
                    <span class="unit">Liter</span>
                </div>
            </div>

            <button type="submit" class="btn-primary" style="margin-top:auto;">Terapkan &amp; Selanjutnya</button>
        </form>
    <?php endif; ?>
</div>
