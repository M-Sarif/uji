# OneFIS - Versi PHP Native

Hasil konversi total dari project React/TypeScript (repo `uji`) menjadi
**PHP native** — tanpa React, tanpa Node/Vite, tanpa Tailwind build step.
Hanya PHP + HTML + CSS murni, bisa langsung jalan di server PHP mana pun
(Apache, Nginx+PHP-FPM, atau `php -S` bawaan).

## Cara menjalankan
```bash
cd onefis-app
php -S localhost:8000
```
Lalu buka `http://localhost:8000` di browser (disarankan lebar layar HP / mode responsive).

Atau upload seluruh folder `onefis-app/` ke hosting PHP (cPanel, dsb) dan akses `index.php`.

**Syarat**: PHP 7.4+ (disarankan PHP 8.x), tidak butuh ekstensi khusus.

## Struktur folder
```
onefis-app/
├── index.php              -> Front controller / router (?screen=...)
├── includes/
│   ├── data.php            -> Data statis (checklist, judul header, teks tutorial, alur layar)
│   ├── functions.php       -> Helper (escape output, redirect PRG, session state)
│   ├── layout_top.php      -> Bingkai HP + status bar + header + tutorial overlay
│   └── layout_bottom.php   -> Penutup bingkai HP
├── views/                  -> Satu file per "layar" (dashboard, checklist, rating, dst)
├── assets/
│   ├── style.css           -> Semua styling (murni CSS, hasil konversi dari Tailwind)
│   ├── logo-onefis.svg
│   ├── empty-delivery-truck.png
│   ├── fuel-barrel.png
│   └── order-illustration.png
└── README.md
```

## Apa yang berubah dari versi React?
- **Routing**: dulu state React (`useState<ScreenState>`), sekarang parameter
  `?screen=nama_layar` yang divalidasi di `index.php`.
- **State antar-langkah** (data order, LO terpilih, langkah checklist, rating):
  dulu `useState` di komponen, sekarang **PHP Session** (`$_SESSION`) supaya
  data tetap ada saat berpindah halaman/reload.
- **Form**: dipakai pola **POST → Redirect → GET** standar PHP, termasuk
  validasi rating di server (kalau ada bintang yang belum diisi, halaman
  ditampilkan ulang dengan pesan error dan bintang yang sudah dipilih tetap tersimpan).
- **Bintang rating**: dibuat murni CSS (tanpa JavaScript) dengan trik
  `input[type=radio]` + `flex-direction: row-reverse`.
- Satu bagian kecil JavaScript (opsional/progresif) hanya dipakai untuk
  highlight pilihan "Jenis Order" secara instan — bukan untuk logika inti.

## Alur layar (sama seperti versi asli)
`dashboard → shipments_list → create_order_info → create_order_product →
create_order_review → track_order → shipment → verification → lo_list →
checklist (15 langkah) → qr_code → rating → done → (reset) → dashboard`
