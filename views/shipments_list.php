<?php
// Status order aktif: "Dikirim" selama aktifitas di SPBU berjalan, berubah
// jadi "Selesai Dikirim" setelah semua aktifitas (termasuk rating AMT) tuntas.
$orderSelesai = (int) ($_SESSION['activity_done'] ?? 0) >= count(ACTIVITY_STEPS);
$mtAktif      = ARRIVAL_SUBJECTS['mt_ok']['name']; // nomor polisi MT sama dengan di layar verifikasi
?>
<div class="pill-tabs">
    <button type="button" class="pill-tab active">Diproses</button>
    <button type="button" class="pill-tab">Draft</button>
</div>

<div class="search-row">
    <div class="search-box">
        <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="M21 21l-3.5-3.5"/></svg>
        <input type="text" placeholder="Cari">
    </div>
    <button type="button" class="filter-btn" aria-label="Filter">
        <svg viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M7 12h10M10 18h4"/></svg>
    </button>
</div>

<div class="content-pad" style="padding-top:0;">

    <div class="card shipment-card" style="margin-bottom:16px;">
        <div class="top-row">
            <p class="title-sm">24/06/26 10:00 AM</p>
            <span class="chip <?php echo $orderSelesai ? 'emerald' : 'amber'; ?>"><?php echo $orderSelesai ? 'Selesai Dikirim' : 'Dikirim'; ?></span>
        </div>
        <div class="ship-info-grid">
            <div>
                <span class="label">Nomor Polisi MT</span>
                <span class="value"><?php echo h($mtAktif); ?></span>
            </div>
            <div>
                <span class="label">Estimasi Tiba</span>
                <span class="value">24/09/2026 10:48 AM</span>
            </div>
        </div>
        <div class="ship-products">
            <span class="label">Nama Produk</span>
            <div class="tag-chip-row">
                <span class="tag-chip">PERTALITE 6000 L</span>
                <span class="tag-chip">PERTAMAX, BULK 2000 L</span>
            </div>
        </div>
        <a href="index.php?screen=shipment" class="btn-detail" data-tour="lihat-detail">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M9 13h6M9 17h6M9 9h1"/></svg>
            Lihat Detail
        </a>
    </div>

    <div class="card shipment-card">
        <div class="top-row">
            <p class="title-sm">24/06/2026 10:00 AM</p>
            <span class="chip emerald">Selesai Dikirim</span>
        </div>
        <div class="ship-info-grid">
            <div>
                <span class="label">Nomor Polisi MT</span>
                <span class="value">B1234ABC</span>
            </div>
            <div>
                <span class="label">Estimasi Tiba</span>
                <span class="value">25/06/2026 10:00 AM</span>
            </div>
        </div>
        <div class="ship-products">
            <span class="label">Nama Produk</span>
            <div class="tag-chip-row">
                <span class="tag-chip">PERTALITE 6000 L</span>
            </div>
        </div>
        <a href="index.php?screen=shipment" class="btn-detail">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path stroke-linecap="round" stroke-linejoin="round" d="M14 2v6h6M9 13h6M9 17h6M9 9h1"/></svg>
            Lihat Detail
        </a>
    </div>

</div>

<div class="sticky-footer">
    <a href="index.php?screen=create_order_info" class="btn-primary" style="display:flex;">+ Buat Order</a>
</div>