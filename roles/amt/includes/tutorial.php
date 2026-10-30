<?php

const AMT_TOUR_SCREEN_ORDER = ['amt_home', 'start_end', 'start_work', 'checkin', 'checkout', 'amt_pti', 'amt_pti_form', 'amt_pti_hasil', 'amt_shipments', 'amt_shipment_detail', 'amt_spbu', 'amt_checklist_lo', 'amt_checklist', 'amt_verifikasi', 'amt_verifikasi_qr', 'amt_verifikasi_kode', 'amt_verifikasi_sukses', 'amt_surat_jalan', 'amt_rating', 'amt_kendala', 'end_work'];

const AMT_TOUR_SCREEN_LABELS = [
    'amt_home'   => 'Beranda AMT',
    'start_end'  => 'Absensi Kerja',
    'start_work' => 'Mulai Kerja',
    'checkin'    => 'Mulai Ritase',
    'checkout'   => 'Selesai Ritase',
    'amt_pti'      => 'Pre-Trip Inspection',
    'amt_pti_form' => 'Form Inspeksi',
    'amt_pti_hasil' => 'Hasil Inspeksi',
    'amt_shipments' => 'Shipments',
    'amt_shipment_detail' => 'Detail Order',
    'amt_spbu' => 'Aktifitas di SPBU',
    'amt_checklist_lo' => 'Daftar LO',
    'amt_checklist' => 'Checklist Pra-Pembongkaran',
    'amt_verifikasi' => 'Verifikasi Order',
    'amt_verifikasi_qr' => 'Pindai Kode QR',
    'amt_verifikasi_kode' => 'Kode Konfirmasi',
    'amt_verifikasi_sukses' => 'Order Terverifikasi',
    'amt_surat_jalan' => 'Foto Surat Jalan',
    'amt_rating' => 'Beri Penilaian',
    'amt_kendala' => 'Laporkan Kendala',
    'end_work'   => 'Selesai Kerja',
];

const AMT_TOUR_JOURNEYS = [
    'masuk'   => ['label' => 'Absen Masuk',  'total' => 7],
    'checkin' => ['label' => 'Mulai Ritase',     'total' => 5],
    'pulang'  => ['label' => 'Absen Pulang', 'total' => 6],
    'dcu'     => ['label' => 'Mulai Ritase Berhasil', 'total' => 1],
    'pti'     => ['label' => 'Inspeksi PTI', 'total' => 8],
    'kirim'   => ['label' => 'Pengiriman', 'total' => 5],
    'checklist' => ['label' => 'Checklist Pra-Pembongkaran', 'total' => 6],
    'verifikasi' => ['label' => 'Verifikasi Order', 'total' => 4],
    'suratjalan' => ['label' => 'Foto Surat Jalan', 'total' => 3],
    'rating' => ['label' => 'Rating Petugas SPBU', 'total' => 6],
    'checkout' => ['label' => 'Selesai Ritase', 'total' => 6],
    'kendala' => ['label' => 'Laporkan Kendala', 'total' => 6],
];

const AMT_TOUR_DCU_DELAY_MS = AMT_DCU_SHOW_DELAY;

function amt_tour_photo_steps(int $no, bool $preview = false): array
{
    $shot = '.amtcam.is-open .amtcam-shot:not([hidden])';
    return [
        [
            'no'        => $no,
            'target'    => '#wk-box',
            'highlight' => '#wk-photo-box',
            'title'     => 'Ambil foto selfie',
            'text'      => 'Ketuk kartu selfie (“Ambil Foto”) untuk membuka kamera depan.',
            'hint'      => '👆 Ketuk kartu selfie',
            'done'      => '#wk-form[data-photo="1"], .amtcam.is-open',
        ],
        [
            'no'        => $no,
            'camera'    => true,
            'target'    => '.amtcam [data-act="shoot"]',
            'title'     => 'Ketuk “Foto”',
            'text'      => 'Hadapkan wajah ke kamera depan dan pastikan wajah terlihat jelas. Lalu ketuk tombol “Foto”.',
            'hint'      => '👆 Ketuk tombol “Foto”',
            'done'      => $shot . ', #wk-form[data-photo="1"]',
        ],
        [
            'no'        => $no,
            'camera'    => true,
            'target'    => '.amtcam [data-act="save"]',
            'title'     => 'Ketuk “Simpan Foto”',
            'text'      => 'Periksa hasil fotonya. Bila wajah sudah jelas, ketuk “Simpan Foto”. Bila kurang bagus, ketuk “Ambil Ulang”.',
            'hint'      => '👆 Ketuk “Simpan Foto”',
            'done'      => '#wk-form[data-photo="1"]',
            'ok'        => $preview
                ? '✅ Foto sudah tersimpan dan tampil di form. Bila kurang bagus, ketuk “Hapus Foto” lalu ambil lagi.'
                : '✅ Foto sudah tersimpan',
        ],
    ];
}

function amt_tour_success_step(int $no, string $mode): array
{
    $text = [
        'start'    => 'Absen masuk berhasil dan Jam Kerja sudah berjalan. Ketuk “Lanjutkan ke Homepage” untuk kembali ke beranda.',
        'checkin'  => 'Mulai Ritase sudah tercatat. Ketuk “Lanjutkan ke Homepage”. Berikutnya lakukan DCU, lalu menu PTI akan menyala.',
        'end'      => 'Jam kerja Anda sudah berakhir. Ketuk “Lanjutkan ke Homepage” untuk kembali ke beranda.',
        'checkout' => 'Selesai Ritase berhasil dan Riwayat Ritase sudah direset. Ketuk “OK”, lalu akhiri kerja lewat menu Mulai / Selesai Kerja.',
    ][$mode] ?? 'Ketuk “Lanjutkan ke Homepage” untuk kembali ke beranda.';
    $isOk = ($mode === 'checkout');
    return [
        'no'        => $no,
        'final'     => $mode === 'end',
        'over'      => true,
        'target'    => '#wk-success-go',
        'highlight' => '.wk-modal-card',
        'when'      => '#wk-success:not([hidden])',
        'title'     => $isOk ? 'Ketuk “OK”' : 'Ketuk “Lanjutkan ke Homepage”',
        'text'      => $text,
        'hint'      => $isOk ? '👆 Ketuk tombol hijau “OK”' : '👆 Ketuk tombol hijau “Lanjutkan ke Homepage”',
        'done'      => null,
    ];
}

function amt_tour_form_steps(string $mode): array
{
    $isCheckin = ($mode === 'checkin');
    $isEnd     = ($mode === 'end');

    $what = $isCheckin ? 'Mulai Ritase' : ($isEnd ? 'absen pulang' : 'absen masuk');
    $n    = $isCheckin ? 2 : 3;
    $steps = [];

    if ($isEnd) {
        $steps[] = [
            'no'     => 2,
            'target' => '#ew-timer',
            'title'  => 'Total jam kerja Anda',
            'text'   => 'Kartu biru ini menunjukkan sudah berapa lama Anda bekerja hari ini, dihitung sejak Mulai Kerja. Pastikan pekerjaan hari ini memang sudah selesai sebelum mengakhirinya.',
            'button' => 'Mengerti',
            'done'   => null,
        ];
    }

    if ($isCheckin) {
        $steps[] = [
            'no'     => null,
            'target' => '#ci-tab-verif',
            'title'  => 'Buka tab “Verifikasi”',
            'text'   => 'Mulai Ritase dilakukan di tab “Verifikasi”. Tab “Riwayat Ritase” hanya untuk melihat daftar Mulai Ritase sebelumnya.',
            'hint'   => '👆 Ketuk tab “Verifikasi”',
            'done'   => '#ci-pane-verifikasi:not([hidden])',
        ];
    }

    $steps[] = [
        'no'        => $n,
        'target'    => '#wk-refresh',
        'highlight' => '#wk-loc',
        'title'     => 'Cek lokasi Anda',
        'text'      => 'Pin di peta harus ada di area kerja. Bila pin sudah tepat, tutorial lanjut sendiri. Bila belum, ketuk “Perbarui Lokasi”, lalu tunggu sampai pin muncul.',
        'hint'      => '👆 Ketuk “Perbarui Lokasi”',
        'wait'      => '⏳ Menunggu lokasi sesuai… perhatikan pin di peta',
        'done'      => '#wk-form[data-pin="1"]',
        'minShow'   => 3000,
        'loadSel'   => '#wk-loc[data-inrange="1"]',
        'loadHint'  => '⏳ Memeriksa lokasi… tunggu pin muncul di peta',
        'condHint'  => '✅ Lokasi sudah sesuai. Pastikan pin tepat di area kerja.',
    ];

    if (!$isEnd) {
        $n++;
        $steps[] = [
            'no'        => $n,
            'target'    => '#wk-akt',
            'highlight' => '#wk-act-box',
            'title'     => 'Pilih aktivitas',
            'text'      => $isCheckin
                ? 'Ketuk kolom ini, lalu pilih “Tugas Rutin” (tugas harian biasa) atau “Tugas Lembur” (di luar jam biasa).'
                : 'Ketuk kolom ini, lalu pilih “Hadir” bila Anda masuk kerja hari ini.',
            'hint'      => '👆 Ketuk kolom “Pilih aktivitas”, lalu pilih salah satu',
            'done'      => '#wk-form[data-act="1"]',
            'ok'        => '✅ Aktivitas sudah dipilih',
        ];
    }

    $n++;
    foreach (amt_tour_photo_steps($n, $isEnd) as $ps) { $steps[] = $ps; }

    $n++;
    $steps[] = [
        'no'     => $n,
        'target' => '#wk-submit',
        'title'  => $isEnd ? 'Ketuk “Akhiri Pekerjaan”' : 'Kirim ' . $what,
        'text'   => $isCheckin
            ? 'Semua sudah lengkap. Ketuk “Kirim”. Mulai Ritase Anda akan tercatat di tab “Riwayat Ritase”.'
            : ($isEnd
                ? 'Semua sudah lengkap. Ketuk tombol “Akhiri Pekerjaan” di bagian bawah layar. Anda akan ditanya sekali lagi sebelum jam kerja benar-benar berakhir.'
                : 'Semua sudah lengkap. Ketuk “Kirim”. Anda akan ditanya sekali lagi sebelum Jam Kerja benar-benar berjalan.'),
        'hint'   => $isEnd ? '👆 Ketuk tombol biru “Akhiri Pekerjaan”' : '👆 Ketuk tombol biru “Kirim”',
        'done'   => null,
    ];

    if (!$isCheckin) {
        $n++;
        $confirmAsk  = $isEnd ? 'Apakah Anda yakin ingin mengakhiri pekerjaan hari ini?' : 'Apakah Anda yakin ingin absen kehadiran?';
        $confirmDone = $isEnd ? 'memang sudah selesai bekerja' : 'memang ingin absen masuk';
        $steps[] = [
            'no'        => $n,
            'over'      => true,
            'target'    => '#ew-yes',
            'highlight' => '.wk-confirm-card',
            'when'      => '#ew-confirm:not([hidden])',
            'title'     => 'Ketuk “Ya”',
            'text'      => 'Muncul pertanyaan “' . $confirmAsk . '”. Ketuk “Ya” bila Anda ' . $confirmDone . '. Ketuk “Batal” bila belum.',
            'hint'      => '👆 Ketuk tombol biru “Ya”',
            'done'      => null,
        ];
    }

    $steps[] = amt_tour_success_step($n, $mode);

    return $steps;
}

function amt_tour_pti_steps(string $screen): array
{
    if ($screen === 'amt_pti') {
        if (amt_pti_shipment() === null) {
            return ['journey' => 'pti', 'steps' => [], 'subKey' => null];
        }
        if (amt_pti_is_done()) {
            return ['journey' => 'pti', 'subKey' => 'amt_pti_done', 'steps' => [
                [
                    'no'       => null,
                    'label'    => 'Inspeksi Terkirim',
                    'target'   => '[data-tour="pti-card"]',
                    'title'    => 'Inspeksi selesai ✅',
                    'text'     => 'Hasil inspeksi sudah terkirim dan status PTI sekarang “Sudah Inspeksi”. Anda bisa melihat hasilnya lewat “Lihat Hasil Inspeksi”, atau mengisi ulang lewat “Isi Inspeksi Lagi”.',
                    'button'   => 'Mengerti',
                    'autoHide' => AMT_PTI_DONE_PAGE_VISIBLE_FOR,
                    'remember' => true,
                    'done'     => null,
                ],
                [
                    'no'     => null,
                    'label'  => 'Kembali ke Beranda',
                    'target' => 'a.back-btn',
                    'title'  => 'Kembali ke Beranda',
                    'text'   => 'Ketuk panah kembali (←) di kiri atas untuk kembali ke Beranda. Tahapan berikutnya (ambil segel, scan segel, dan seterusnya) dijelaskan di sana.',
                    'hint'   => '👆 Ketuk panah ← di kiri atas',
                    'done'   => null,
                ],
            ]];
        }
        return ['journey' => 'pti', 'subKey' => null, 'steps' => [
            [
                'no'     => 2,
                'target' => '[data-tour="pti-card"]',
                'title'  => 'Cek data mobil tangki',
                'text'   => 'Pastikan Nomor Polisi MT dan Kapasitas Tangki sesuai dengan mobil tangki yang Anda bawa.',
                'hint'   => '👆 Ketuk “Mengerti” bila data sudah sesuai',
                'button' => 'Mengerti',
                'done'   => null,
            ],
            [
                'no'     => 3,
                'target' => '[data-tour="pti-start"]',
                'title'  => 'Ketuk “Isi Inspeksi”',
                'text'   => 'Inspeksi terdiri dari 11 langkah pendek. Anda akan memeriksa kondisi mobil tangki dari beberapa sisi.',
                'hint'   => '👆 Ketuk tombol biru “Isi Inspeksi”',
                'done'   => null,
            ],
        ]];
    }

    $step = isset($_GET['step']) ? (int) $_GET['step'] : 1;
    $step = max(1, min(AMT_PTI_TOTAL, $step));
    $next = '#ptiForm .pti-next:not(.is-locked)';

    if ($step === 1) {
        return ['journey' => 'pti', 'subKey' => 'amt_pti_form_1', 'steps' => [
            [
                'no'        => 4,
                'target'    => '[data-tour="pti-items"]',
                'title'     => 'Periksa lalu jawab tiap item',
                'text'      => 'Lihat foto acuan, cek kondisi mobil tangki, lalu pilih “Layak” bila baik atau “Tidak layak” bila ada masalah. Semua item bertanda * wajib dijawab.',
                'hint'      => '👆 Pilih Layak atau Tidak layak di setiap item',
                'done'      => $next,
                'ok'        => '✅ Semua item sudah dijawab',
            ],
            [
                'no'     => 5,
                'target' => '#ptiForm .pti-next',
                'title'  => 'Ketuk “Selanjutnya”',
                'text'   => 'Ulangi hal yang sama untuk sisi mobil berikutnya, sampai langkah 11. Tombol “Sebelumnya” dipakai bila ingin memperbaiki jawaban.',
                'hint'   => '👆 Ketuk “Selanjutnya”',
                'done'   => null,
            ],
        ]];
    }

    if ($step < AMT_PTI_TOTAL) {
        return ['journey' => 'pti', 'subKey' => 'amt_pti_form_mid', 'steps' => [[
            'no'     => null,
            'label'  => 'Form Inspeksi · Bagian ' . $step . ' dari ' . AMT_PTI_TOTAL,
            'target' => '[data-tour="pti-items"]',
            'title'  => 'Sama seperti tadi',
            'text'   => 'Jawab semua item di sisi ini, lalu ketuk “Selanjutnya”. Ulangi sampai langkah ' . AMT_PTI_TOTAL . '.',
            'button' => 'Mengerti',
            'done'   => null,
        ]]];
    }

    return ['journey' => 'pti', 'subKey' => 'amt_pti_form_11', 'steps' => [
        [
            'no'        => 6,
            'target'    => '#ptiNote',
            'highlight' => '[data-tour="pti-note"]',
            'title'     => 'Tulis catatan dulu',
            'text'      => 'Kolom Catatan wajib diisi. Tuliskan kondisi mobil tangki, misalnya “MT baik.” Tombol Kirim baru menyala setelah catatan terisi.',
            'hint'      => '👆 Ketuk kolom Catatan, lalu ketik',
            'done'      => $next,
            'settle'    => 1500,
            'ok'        => '✅ Catatan sudah terisi',
        ],
        [
            'no'     => 7,
            'target' => '#ptiForm .pti-btn--send',
            'title'  => 'Ketuk “Kirim”',
            'text'   => 'Semua sudah lengkap. Ketuk “Kirim”, lalu konfirmasi pada pertanyaan yang muncul.',
            'hint'   => '👆 Ketuk tombol biru “Kirim”',
            'done'   => null,
        ],
        [
            'no'        => 8,
            'target'    => '#ptiConfirmYes',
            'highlight' => '[data-tour="pti-confirm"]',
            'when'      => '.pti-confirm.is-open',
            'title'     => 'Konfirmasi pengiriman',
            'text'      => 'Pastikan semua jawaban sudah benar, karena data yang sudah dikirim tidak bisa diubah lagi. Ketuk “Ya, kirim hasil inspeksi” untuk mengirim, atau “Batal” untuk memeriksa lagi.',
            'hint'      => '👆 Ketuk “Ya, kirim hasil inspeksi”',
            'done'      => null,
        ],
    ]];
}

function amt_tour_ship_steps(string $screen): array
{
    if ($screen === 'amt_shipments') {
        return ['journey' => 'kirim', 'steps' => [[
            'no'        => 1,
            'label'     => 'Shipments',
            'target'    => '[data-tour="ship-detail-active"]',
            'highlight' => '[data-tour="ship-card-active"]',
            'title'  => 'Buka detail order',
            'text'   => 'Pengiriman yang sedang berjalan berstatus “Sedang Dikirim”. Ketuk “Lihat Detail Order” pada pengiriman itu untuk melihat rutenya.',
            'hint'   => '👆 Ketuk “Lihat Detail Order” pada pengiriman Sedang Dikirim',
            'done'   => null,
        ]]];
    }

    $s = amt_ship_current();
    if ($s === null || $s['status'] !== 'sedang') {
        return ['journey' => 'kirim', 'steps' => []];
    }
    return ['journey' => 'kirim', 'steps' => [[
        'no'     => 2,
        'label'  => 'Detail Order',
        'target' => '[data-tour="ship-spbu-card"]',
        'title'  => 'Buka kartu SPBU',
        'text'   => 'Kartu abu-abu ini berisi SPBU tujuan dan produk yang Anda bawa. Ketuk kartunya untuk membuka aktifitas di SPBU.',
        'hint'   => '👆 Ketuk kartu abu-abu “SPBU”',
        'done'   => null,
    ]]];
}

function amt_tour_spbu_steps(): array
{
    $s = amt_ship_current();
    if ($s === null || $s['status'] !== 'sedang') {
        return ['journey' => 'kirim', 'subKey' => null, 'steps' => []];
    }

    if (amt_spbu_arrived($s)) {
        if (amt_pbk_done($s['id'])) {
            return amt_tour_verif_spbu_pack($s);
        }
        $steps = [[
            'no'     => null,
            'label'  => 'Isi Checklist',
            'target' => '[data-tour="spbu-step-active"]',
            'title'  => 'Ketuk “Isi Checklist”',
            'text'   => 'Sebelum BBM dibongkar, Anda mengisi Checklist Pra-Pembongkaran. Ketuk kartu biru “Isi Checklist” untuk membuka Daftar LO.',
            'hint'   => '👆 Ketuk kartu biru “Isi Checklist”',
            'done'   => null,
        ]];
        return ['journey' => 'kirim', 'subKey' => 'amt_spbu_arrived', 'steps' => $steps];
    }

    return ['journey' => 'kirim', 'subKey' => null, 'steps' => [
        [
            'no'     => 3,
            'label'  => 'Aktifitas di SPBU',
            'target' => '[data-tour="spbu-step-active"]',
            'title'  => 'Ketuk “Tiba di Lokasi”',
            'text'   => 'Bila mobil tangki sudah sampai di SPBU tujuan, ketuk kartu biru “Tiba di Lokasi” untuk memberi tahu bahwa Anda sudah tiba.',
            'hint'   => '👆 Ketuk kartu biru “Tiba di Lokasi”',
            'done'   => '#arrSheet.is-open',
        ],
        [
            'no'        => 4,
            'label'     => 'Tiba di Lokasi',
            'target'    => '#arr-refresh',
            'highlight' => '#arr-loc',
            'title'     => 'Cek lokasi Anda',
            'text'      => 'Pin di peta harus ada di SPBU tujuan. Bila pin sudah tepat, tutorial lanjut sendiri. Bila belum, ketuk “Perbarui Lokasi”, lalu tunggu sampai pin muncul dan tombol biru di bawahnya aktif.',
            'hint'      => '👆 Ketuk “Perbarui Lokasi”',
            'wait'      => '⏳ Menunggu lokasi sesuai… perhatikan pin di peta',
            'when'      => '#arrSheet.is-open',
            'done'      => '#arrSheet[data-pin="1"]',
            'minShow'   => 3000,
            'loadSel'   => '#arrSheet[data-loc="1"]',
            'loadHint'  => '⏳ Memeriksa lokasi… tunggu pin muncul di peta',
            'condHint'  => '✅ Lokasi sudah sesuai. Pastikan pin tepat di SPBU tujuan.',
        ],
        [
            'no'     => 5,
            'label'  => 'Tiba di Lokasi',
            'target' => '#arr-yes',
            'when'   => '#arrSheet.is-open',
            'title'  => 'Ketuk “Ya, pengiriman telah tiba”',
            'text'   => 'Lokasi sudah sesuai. Ketuk tombol biru ini untuk mencatat bahwa mobil tangki sudah tiba di SPBU.',
            'hint'   => '👆 Ketuk tombol biru “Ya, pengiriman telah tiba”',
            'done'   => null,
        ],
    ]];
}

function amt_tour_pti_hasil_steps(): array
{
    $nogo = (amt_pti_submitted()['result'] ?? 'GO') === 'NO GO';
    return ['journey' => 'pti', 'subKey' => 'amt_pti_hasil', 'steps' => [
        [
            'no'       => null,
            'label'    => 'Hasil Inspeksi',
            'target'   => '[data-tour="hasil-banner"]',
            'title'    => $nogo ? 'Hasil: NO GO' : 'Hasil: GO',
            'text'     => $nogo
                ? 'Ada item yang Tidak layak, jadi mobil tangki belum boleh berangkat. Laporkan ke pengawas, lalu periksa ulang setelah diperbaiki.'
                : 'Semua item Layak, jadi mobil tangki siap beroperasi. Jika ada item Tidak layak, hasilnya NO GO dan Anda wajib lapor ke pengawas.',
            'button'   => 'Mengerti',
            'remember' => true,
            'done'     => null,
        ],
        [
            'no'       => null,
            'label'    => 'Hasil Inspeksi',
            'target'   => '[data-tour="hasil-list"]',
            'title'    => 'Jawaban Anda',
            'text'     => 'Daftar ini menampilkan jawaban Layak / Tidak layak untuk setiap sisi mobil tangki, beserta catatan Anda. Halaman ini hanya untuk dilihat.',
            'button'   => 'Mengerti',
            'remember' => true,
            'done'     => null,
        ],
        [
            'no'     => null,
            'label'  => 'Hasil Inspeksi',
            'target' => 'a.back-btn',
            'title'  => 'Kembali ke halaman PTI',
            'text'   => 'Ketuk panah kembali (←) di kiri atas untuk kembali ke halaman PTI, lalu kembali lagi ke Beranda.',
            'hint'   => '👆 Ketuk panah ← di kiri atas',
            'done'   => null,
        ],
    ]];
}

function amt_tour_pti_done_pack(int $checkins): array
{
    $subKey = 'amt_home_ptidone_' . $checkins;
    $result = amt_pti_state()['result'] ?? null;

    if ($result === 'NO GO') {
        return ['journey' => 'pti', 'subKey' => $subKey, 'steps' => [[
            'no'     => null,
            'label'  => 'PTI Selesai',
            'target' => null,
            'title'  => 'Hasil inspeksi: NO GO',
            'text'   => 'Mobil tangki belum layak beroperasi. Laporkan ke pengawas sebelum berangkat.',
            'button' => 'Mengerti',
            'done'   => null,
        ]]];
    }

    return ['journey' => 'pti', 'subKey' => $subKey, 'startDelay' => AMT_PTI_DONE_START_DELAY, 'steps' => [
        [
            'no'       => null,
            'label'    => 'PTI Selesai',
            'target'   => null,
            'title'    => 'PTI selesai ✅',
            'text'     => 'PTI sudah selesai. Lanjutkan dengan tahapan berikut:',
            'list'     => [
                'Ambil segel.',
                'Scan segel.',
                'Lakukan Get In.',
                'Masuk ke Filling Shed.',
                'Lakukan Get Out.',
                'Ambil surat jalan.',
                'Menuju SPBU tujuan.',
            ],
            'button'   => 'Mengerti',
            'autoHide' => AMT_PTI_DONE_VISIBLE_FOR,
            'remember' => true,
            'done'     => null,
        ],
        [
            'no'     => null,
            'label'  => 'Langkah berikutnya',
            'target' => '[data-tour="amt-menu-shipments"]',
            'when'   => '[data-tour="amt-menu-shipments"]',
            'delay'  => AMT_PTI_DONE_SHIPMENT_DELAY,
            'remember' => true,
            'title'  => 'Sudah tiba di SPBU? Buka “Shipments”',
            'text'   => 'Selesaikan dulu tahapan di kartu sebelumnya (segel sampai menuju SPBU). Begitu sudah tiba di SPBU tujuan, ketuk menu Shipments untuk membuka daftar pengiriman.',
            'hint'   => '👆 Sudah tiba di SPBU? Ketuk menu “Shipments”',
            'done'   => null,
        ],
    ]];
}

function amt_tour_pbk_lo_steps(): array
{
    $s = amt_ship_current();
    if ($s === null || !amt_pbk_ship_ready($s)) {
        return ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    }

    $hasDraft = false;
    foreach (amt_pbk_lo_ids($s) as $lo) {
        if (amt_pbk_lo_status($s['id'], $lo) === 'draft') {
            $hasDraft = true;
            break;
        }
    }

    if ($hasDraft) {
        return ['journey' => 'checklist', 'subKey' => 'amt_pbl_draft', 'steps' => [
            [
                'no'     => null,
                'target' => '[data-tour="pbl-list"]',
                'title'  => 'Pastikan produk yang dibongkar tercentang',
                'text'   => 'LO berstatus “Draft” sudah terisi tetapi BELUM dikirim ke SPBU. Centang LO (produk) yang dibongkar dan ingin dikirim. Tombol “Kirim” baru menyala setelah ada LO Draft yang dicentang.',
                'hint'   => '👆 Pastikan produk yang dibongkar sudah dicentang',
                'done'   => '#pblSend:not(:disabled)',
                'minShow'  => 3000,
                'condHint' => '✅ Sudah tercentang. Pastikan produk yang dibongkar sudah benar.',
            ],
            [
                'no'     => 5,
                'target' => '[data-tour="pbl-send"]',
                'title'  => 'Ketuk “Kirim”',
                'text'   => 'Ketuk “Kirim”, lalu akan muncul pertanyaan konfirmasi sebelum checklist benar-benar dikirim.',
                'hint'   => '👆 Ketuk tombol “Kirim”',
                'done'   => '#pblModal:not([hidden])',
            ],
            [
                'no'        => 6,
                'target'    => '#pblYes',
                'highlight' => '#pblModal .pbl-modal__card',
                'when'      => '#pblModal:not([hidden])',
                'title'     => 'Konfirmasi pengiriman',
                'text'      => 'Pastikan semua jawaban sudah benar, karena data yang sudah dikirim tidak bisa diubah lagi. Ketuk “Ya, Kirim” untuk mengirim, atau “Batal” untuk memeriksa lagi.',
                'hint'      => '👆 Ketuk “Ya, Kirim”',
                'done'      => null,
            ],
        ]];
    }

    return ['journey' => 'checklist', 'subKey' => 'amt_pbl_belum', 'steps' => [
        [
            'no'     => 1,
            'label'  => 'Daftar LO',
            'target' => '[data-tour="pbl-list"]',
            'title'  => 'Pastikan produk yang dibongkar tercentang',
            'text'   => 'Setiap kartu adalah satu LO dengan produknya (lihat kolom “Order”). Centang LO yang produknya akan dibongkar di SPBU ini, atau ketuk “Pilih Semua”. Status “Belum Diisi” berarti checklist LO itu belum dikerjakan.',
            'hint'   => '👆 Pastikan produk yang dibongkar sudah dicentang',
            'done'   => '.pbl-card.is-selected',
            'minShow'  => 3000,
            'condHint' => '✅ Sudah tercentang. Pastikan produk yang dibongkar sudah benar.',
        ],
        [
            'no'     => 2,
            'label'  => 'Daftar LO',
            'target' => '[data-tour="pbl-start"]',
            'title'  => 'Ketuk “Mulai Checklist”',
            'text'   => 'Anda akan mengisi 14 soal pemeriksaan sebelum pembongkaran BBM. Isi dengan jujur dan bertanggung jawab.',
            'hint'   => '👆 Ketuk tombol biru “Mulai Checklist”',
            'done'   => null,
        ],
    ]];
}

function amt_tour_pbk_steps(): array
{
    $s = amt_ship_current();
    if ($s === null || !amt_pbk_ship_ready($s)) {
        return ['journey' => 'checklist', 'subKey' => null, 'steps' => []];
    }

    $defs  = amt_pbk_steps();
    $total = count($defs);
    $step  = isset($_GET['step']) ? (int) $_GET['step'] : 1;
    $step  = max(1, min($total, $step));
    $type  = $defs[$step]['type'];
    $next  = '#pbkNext:not(.is-locked)';

    $nextCard = [
        'no'     => null,
        'label'  => 'Checklist Pra-Pembongkaran',
        'target' => '#pbkNext',
        'title'  => 'Ketuk “Selanjutnya”',
        'text'   => 'Ketuk “Selanjutnya” untuk ke soal berikutnya. Tombol “Sebelumnya” dipakai bila ingin memperbaiki jawaban.',
        'hint'   => '👆 Ketuk “Selanjutnya”',
        'done'   => null,
    ];

    if ($step === $total) {
        return ['journey' => 'checklist', 'subKey' => 'amt_pbk_last', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Terakhir',
                'target' => '[data-tour="pbk-card"]',
                'title'  => 'Soal terakhir',
                'text'   => 'Jawab kedua bagian soal ini: verifikasi pekerjaan Anda sendiri, dan “Verifikasi Tugas Role Lawan” untuk tugas Petugas SPBU.',
                'hint'   => '👆 Jawab kedua bagian soal',
                'done'   => $next,
                'ok'     => '✅ Semua jawaban sudah dipilih',
            ],
            [
                'no'     => null,
                'label'  => 'Soal Terakhir',
                'target' => '#pbkNext',
                'title'  => 'Ketuk “Selesai”',
                'text'   => 'Jawaban tersimpan dan Anda kembali ke Daftar LO. LO yang baru diisi berstatus “Draft” dan baru terkirim setelah Anda mengetuk “Kirim”.',
                'hint'   => '👆 Ketuk tombol biru “Selesai”',
                'done'   => null,
            ],
        ]];
    }

    if ($type === 'qr') {
        return ['journey' => 'checklist', 'subKey' => 'amt_pbk_qr', 'steps' => [
            [
                'no'     => 4,
                'label'  => 'Buat QR Code',
                'target' => '#pbkGen',
                'title'  => 'Buat QR Code',
                'text'   => 'Soal ini adalah tugas AMT yang diverifikasi SPBU lewat QR Code. Ketuk “Generate QR Code”. QR ini dipakai Petugas SPBU bila mengajukan Claim Losses (BBM yang diterima kurang atau tidak sesuai order).',
                'hint'   => '👆 Ketuk “Generate QR Code”',
                'done'   => '#pbkForm[data-qr="1"]',
            ],
            [
                'no'        => null,
                'label'     => 'QR Code Claim Loss',
                'target'    => '#pbkQrClose',
                'highlight' => '#pbkQrModal .pbk-modal__card',
                'when'      => '#pbkQrModal:not([hidden])',
                'title'     => 'QR Code Claim Loss',
                'text'      => 'Tunjukkan QR ini kepada Petugas SPBU hanya bila SPBU mengajukan Claim Losses. QR berlaku 3 menit; bila kedaluwarsa, ketuk “Regenerate” untuk membuat yang baru. Bila tidak ada Claim Losses, langsung tutup barcode ini dengan mengetuk “Tutup”.',
                'hint'      => '👆 Tidak ada Claim Losses? Ketuk “Tutup”',
                'done'      => '#pbkQrModal[hidden]',
            ],
            $nextCard,
        ]];
    }

    if ($type === 'dual') {
        return ['journey' => 'checklist', 'subKey' => 'amt_pbk_dual', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Dua Bagian',
                'target' => '[data-tour="pbk-card"]',
                'title'  => 'Soal dengan dua bagian',
                'text'   => 'Soal ini punya dua bagian yang wajib dijawab:',
                'list'   => [
                    'Bagian atas: verifikasi pekerjaan Anda sendiri. Pilih “Ya, dilakukan” atau “Tidak Dilakukan”. Foto bukti boleh dilewati.',
                    'Bagian bawah “Verifikasi Tugas Role Lawan”: pilih apakah Petugas SPBU sudah mengerjakan tugasnya.',
                ],
                'hint'   => '👆 Jawab kedua bagian soal',
                'done'   => $next,
                'ok'     => '✅ Semua jawaban sudah dipilih',
            ],
            $nextCard,
        ]];
    }

    if ($type === 'self') {
        return ['journey' => 'checklist', 'subKey' => 'amt_pbk_self', 'steps' => [
            [
                'no'     => 3,
                'label'  => 'Soal Tugas AMT',
                'target' => '[data-tour="pbk-card"]',
                'title'  => 'Soal Tugas AMT',
                'text'   => 'Soal ini adalah tugas Anda sendiri. Kerjakan tugasnya, lalu pilih “Ya, Dilakukan” bila sudah, atau “Tidak Dilakukan” bila belum. Jawab sesuai kenyataan.',
                'hint'   => '👆 Pilih salah satu jawaban',
                'done'   => $next,
                'ok'     => '✅ Jawaban sudah dipilih',
            ],
            $nextCard,
        ]];
    }

    $steps = [];
    $steps[] = [
        'no'     => 3,
        'label'  => 'Soal Verifikasi Tugas SPBU',
        'target' => '[data-tour="pbk-card"]',
        'title'  => 'Soal Verifikasi Tugas SPBU',
        'text'   => 'Soal ini adalah tugas yang dikerjakan Petugas SPBU. Periksa langsung di lapangan, lalu pilih “Ya, Dilakukan” bila sudah dikerjakan Petugas SPBU, atau “Tidak Dilakukan” bila belum.',
        'hint'   => '👆 Pilih salah satu jawaban',
        'done'   => $next,
        'ok'     => '✅ Jawaban sudah dipilih',
    ];
    $steps[] = $nextCard;
    return ['journey' => 'checklist', 'subKey' => 'amt_pbk_spbu', 'steps' => $steps];
}

function amt_tour_verif_spbu_pack(array $s): array
{
    if (amt_verif_is_done($s)) {
        if (!amt_sj_done($s['id'])) {
            return ['journey' => 'suratjalan', 'subKey' => 'amt_spbu_sj', 'steps' => [[
                'no'     => 1,
                'label'  => 'Foto Surat Jalan',
                'target' => '[data-tour="spbu-step-active"]',
                'title'  => 'Ketuk “Foto Surat Jalan”',
                'text'   => 'Semua LO sudah terverifikasi. Langkah berikutnya memotret surat jalan yang sudah dicap stempel atau ditandatangani pihak SPBU. Ketuk kartu biru “Foto Surat Jalan”.',
                'hint'   => '👆 Ketuk kartu biru “Foto Surat Jalan”',
                'done'   => null,
            ]]];
        }
        if (!amt_rating_done($s['id'])) {
            return ['journey' => 'rating', 'subKey' => 'amt_spbu_rate', 'steps' => [[
                'no'     => 1,
                'label'  => 'Rating Petugas SPBU',
                'target' => '[data-tour="spbu-step-active"]',
                'title'  => 'Ketuk “Rating Petugas SPBU”',
                'text'   => 'Foto surat jalan sudah tersimpan. Langkah terakhir adalah menilai pelayanan Petugas SPBU. Ketuk kartu biru “Rating Petugas SPBU”.',
                'hint'   => '👆 Ketuk kartu biru “Rating Petugas SPBU”',
                'done'   => null,
            ]]];
        }
        return amt_tour_finish_pack();
    }
    return ['journey' => 'kirim', 'subKey' => 'amt_spbu_verif', 'steps' => [
        [
            'no'     => null,
            'label'  => 'Verifikasi Order',
            'target' => '[data-tour="spbu-step-active"]',
            'title'  => 'Ketuk “Verifikasi Order”',
            'text'   => 'Checklist sudah terkirim. Ketuk kartu biru “Verifikasi Order” untuk membuka daftar LO yang akan diverifikasi.',
            'hint'   => '👆 Ketuk kartu biru “Verifikasi Order”',
            'done'   => null,
        ],
    ]];
}

function amt_tour_verif_steps(string $screen): array
{
    $none = ['journey' => 'verifikasi', 'subKey' => null, 'steps' => []];
    $ship = amt_ship_find(amt_verif_ship_id() ?: null);
    if ($ship === null || $ship['status'] !== 'sedang') {
        return $none;
    }
    $sim = AMT_VERIF_SIM_NOTE;

    if ($screen === 'amt_verifikasi') {
        if (amt_verif_is_done($ship)) {
            return $none;
        }
        return ['journey' => 'verifikasi', 'subKey' => 'amt_verif_list', 'steps' => [
            [
                'no'        => 1,
                'label'     => 'Verifikasi Order',
                'target'    => '.verif-lo:not(.is-disabled)',
                'highlight' => '[data-tour="verif-list"]',
                'title'     => 'Pastikan semua LO tercentang',
                'text'      => 'Hanya LO yang “Form Bongkar”-nya sudah “Sudah Diisi” yang bisa dipilih. Ketuk kartu LO (atau kotak di kanan kartu) untuk mencentang, sampai semua LO tercentang. Bila daftar belum berubah, ketuk “Refresh”.',
                'hint'      => '👆 Pastikan semua LO tercentang',
                'done'      => '[data-tour="verif-list"]:not(:has(.verif-lo:not(.is-disabled):not(.is-selected))):has(.verif-lo.is-selected)',
            ],
            [
                'no'        => 2,
                'label'     => 'Verifikasi Order',
                'target'    => 'a.verif-method.is-on',
                'highlight' => '[data-tour="verif-methods"]',
                'title'     => 'Pilih metode verifikasi',
                'text'      => 'Ada dua metode. “Pindai Kode QR”: Anda memindai QR dari HP Petugas SPBU. “Kode Konfirmasi”: Anda mengetik kode 6 angka seperti kode OTP. Bila satu metode bermasalah, pakai metode yang lain.',
                'hint'      => '👆 Ketuk “Pindai Kode QR” atau “Kode Konfirmasi”',
                'done'      => null,
            ],
        ]];
    }

    if ($screen === 'amt_verifikasi_qr') {
        return ['journey' => 'verifikasi', 'subKey' => 'amt_verif_qr', 'steps' => [
            [
                'no'       => 3,
                'label'    => 'Pindai Kode QR',
                'target'   => '#vqrFrame',
                'title'    => 'Konfirmasi ke Petugas SPBU',
                'text'     => 'Minta Petugas SPBU membuka aplikasinya dan memastikan permintaan verifikasi (notifikasi terbaru) sudah terkirim. Lalu arahkan kamera ke kode QR di HP Petugas SPBU. Waktu pindai 2 menit, lihat hitungan mundur di bawah. Setelah Anda ketuk “Mengerti”, kode QR dipindai otomatis dalam ' . (int) (AMT_VERIF_QR_SCAN_TOUR_MS / 1000) . ' detik.',
                'hint'     => '👆 Ketuk “Mengerti” bila Petugas SPBU sudah siap',
                'button'   => 'Mengerti',
                'announce' => true,
                'skip'     => '.vqr.is-expired',
                'done'     => null,
            ],
        ]];
    }

    if ($screen === 'amt_verifikasi_kode') {
        return ['journey' => 'verifikasi', 'subKey' => 'amt_verif_kode', 'steps' => [
            [
                'no'        => 3,
                'label'     => 'Kode Konfirmasi',
                'target'    => '#vkodeBoxes',
                'highlight' => '.vkode-card',
                'title'     => 'Minta kode ke Petugas SPBU',
                'text'      => 'Minta Petugas SPBU membuka aplikasinya dan memastikan permintaan verifikasi (notifikasi terbaru) sudah terkirim. Petugas SPBU akan menyebutkan kode 6 angka, seperti kode OTP. Kode berlaku 3 menit.',
                'hint'      => '👆 Ketuk “Mengerti” bila kode sudah Anda terima',
                'button'    => 'Mengerti',
                'remember'  => true,
                'skip'      => '#vkodeExpired:not([hidden])',
                'done'      => null,
            ],
            [
                'no'        => null,
                'label'     => 'Kode Konfirmasi',
                'target'    => '#vkodeBoxes',
                'highlight' => '.vkode-card',
                'title'     => 'Ketik 6 angka kode',
                'text'      => 'Ketuk kotak pertama lalu ketik semua angka. Setelah kotak terakhir terisi, kode terkirim otomatis.'
                    . ($sim ? ' Mode simulasi: angka apa saja diterima.' : '')
                    . ' Bila kode kedaluwarsa, ketuk “Kirim ulang kode” atau pakai metode Pindai Kode QR.',
                'hint'      => '👆 Ketuk kotak angka, lalu ketik kode',
                'skip'      => '#vkodeExpired:not([hidden])',
                'done'      => null,
            ],
        ]];
    }

    if ($screen === 'amt_verifikasi_sukses') {
        $more = !amt_verif_is_done($ship);
        return ['journey' => 'verifikasi', 'subKey' => 'amt_verif_sukses', 'steps' => [
            [
                'no'       => 4,
                'label'    => 'Order Terverifikasi',
                'target'   => '.vok-card',
                'title'    => 'Verifikasi berhasil ✅',
                'text'     => 'Nomor LO pada kartu ini sudah terverifikasi.'
                    . ($more ? ' Masih ada LO yang belum diverifikasi: setelah “Oke”, ulangi langkah yang sama untuk LO tersebut.' : ''),
                'button'   => 'Mengerti',
                'remember' => true,
                'done'     => null,
            ],
            [
                'no'     => null,
                'label'  => 'Order Terverifikasi',
                'target' => '.vok-btn',
                'title'  => 'Ketuk “Oke”',
                'text'   => 'Ketuk “Oke” untuk kembali ke Aktifitas di SPBU.',
                'hint'   => '👆 Ketuk tombol biru “Oke”',
                'done'   => null,
            ],
        ]];
    }

    return $none;
}

function amt_tour_finish_pack(): array
{
    return ['journey' => 'rating', 'subKey' => 'amt_spbu_finish', 'steps' => [
        [
            'no'     => 5,
            'label'  => 'Selesai di SPBU',
            'target' => '#finOpen',
            'title'  => 'Ketuk “Selesai”',
            'text'   => 'Semua aktifitas di SPBU ini sudah hijau, jadi tombol “Selesai” sudah menyala. Ketuk untuk menandai serah terima BBM di SPBU ini sudah selesai.',
            'hint'   => '👆 Ketuk tombol biru “Selesai”',
            'done'   => '#finSheet.is-open',
        ],
        [
            'no'        => 6,
            'label'     => 'Selesai di SPBU',
            'target'    => '#finSend',
            'highlight' => '.amts-fin__card',
            'when'      => '#finSheet.is-open',
            'title'     => 'Ketuk “Kirim”',
            'text'      => 'Pop up ini menanyakan apakah Anda yakin menyelesaikan pengiriman. Data yang sudah dikirim tidak bisa diubah. Ketuk “Kirim” bila semua sudah benar, atau “Batal” untuk kembali.',
            'hint'      => '👆 Ketuk tombol biru “Kirim”',
            'done'      => null,
        ],
    ]];
}

function amt_tour_sj_steps(): array
{
    $none = ['journey' => 'suratjalan', 'subKey' => null, 'steps' => []];
    $s = amt_ship_current();
    if ($s === null || !amt_sj_ship_ready($s) || amt_sj_done($s['id'])) {
        return $none;
    }
    return ['journey' => 'suratjalan', 'subKey' => 'amt_sj', 'steps' => [
        [
            'no'        => 2,
            'label'     => 'Foto Surat Jalan',
            'target'    => '.sj-item:not(.has-photo) [data-act="take"]',
            'highlight' => '.sj-item:not(.has-photo) .sj-card',
            'title'     => 'Foto surat jalan tiap LO',
            'text'      => 'Foto surat jalan yang sudah dicap stempel atau ditandatangani pihak SPBU. Setiap Nomor LO punya kartu sendiri dan wajib difoto semuanya.',
            'list'      => [
                'Ketuk “Ambil Foto” pada kartu LO yang menyala.',
                'Arahkan kamera belakang ke surat jalan, pastikan tulisan jelas dan tidak blur.',
                'Ketuk “Foto”, lalu “Simpan Foto” di layar kamera.',
                'Ulangi untuk Nomor LO berikutnya sampai semua kartu berisi foto.',
            ],
            'hint'      => '👆 Ketuk “Ambil Foto” pada kartu yang menyala',
            'done'      => '#sjForm[data-complete="1"]',
            'ok'        => '✅ Semua surat jalan sudah difoto',
            'okText'    => 'Setiap kartu LO kini menampilkan hasil fotonya.',
            'hold'      => 1500,
        ],
        [
            'no'     => 3,
            'label'  => 'Foto Surat Jalan',
            'target' => '#sjSave',
            'title'  => 'Ketuk “Simpan Foto”',
            'text'   => 'Semua LO sudah punya foto, jadi tombol biru di bawah sudah menyala. Ketuk untuk menyimpan. Status Surat Jalan tiap LO akan berubah menjadi “Sudah Ditambahkan”.',
            'hint'   => '👆 Ketuk tombol biru “Simpan Foto”',
            'done'   => null,
        ],
    ]];
}

function amt_tour_rating_steps(): array
{
    $none = ['journey' => 'rating', 'subKey' => null, 'steps' => []];
    $s = amt_ship_current();
    if ($s === null || !amt_rating_ship_ready($s) || amt_rating_done($s['id'])) {
        return $none;
    }
    return ['journey' => 'rating', 'subKey' => 'amt_rating', 'steps' => [
        [
            'no'        => 2,
            'label'     => 'Rating Petugas SPBU',
            'target'    => '#rtOfficer',
            'title'     => 'Isi nama Petugas SPBU',
            'text'      => 'Ketik nama Petugas SPBU yang melayani pembongkaran BBM tadi. Kolom ini wajib diisi.',
            'hint'      => '👆 Ketuk kolom nama, lalu ketik',
            'done'      => '#rtForm[data-named="1"]',
            'settle'    => 1500,
            'ok'        => '✅ Nama petugas sudah terisi',
        ],
        [
            'no'        => 3,
            'label'     => 'Rating Petugas SPBU',
            'target'    => '.rt-card:not(.is-rated) .rt-stars',
            'highlight' => '.rt-card:not(.is-rated)',
            'title'     => 'Beri bintang untuk 5 penilaian',
            'text'      => 'Ketuk bintang pada tiap kartu: 1 bintang berarti sangat kurang, 5 bintang berarti luar biasa. Semua kartu wajib dinilai.',
            'list'      => [
                'Safety Petugas Bongkar: pembongkaran oleh petugas khusus dan memakai APD dengan benar.',
                'Sarfas: sarana dan fasilitas SPBU aman untuk pembongkaran.',
                'Komunikasi: SPBU transparan saat mengukur volume BBM bersama sebelum pembongkaran.',
                'Operasional: penjualan pada tangki yang sedang dibongkar dihentikan.',
                'Aspek Layanan: waktu tunggu bongkar.',
            ],
            'hint'      => '👆 Ketuk bintang pada kartu yang menyala',
            'done'      => '.rt-card.is-rated',
        ],
        [
            'no'        => 3,
            'label'     => 'Rating Petugas SPBU',
            'target'    => '#rtSend',
            'when'      => '#rtForm[data-rated="1"]',
            'nodim'     => true,
            'title'     => 'Lanjutkan mengisi bintang',
            'text'      => 'Bagus! Ketuk bintang pada kartu penilaian lain yang masih kosong sampai kelimanya terisi.',
            'hint'      => '👆 Isi bintang di kartu yang masih kosong',
            'done'      => '#rtForm[data-allrated="1"]',
            'ok'        => '✅ Kelima penilaian sudah terisi',
            'okText'    => 'Tinggal kirim penilaian Anda.',
            'hold'      => 1500,
        ],
        [
            'no'     => 4,
            'label'  => 'Rating Petugas SPBU',
            'target' => '#rtSend',
            'when'   => '#rtForm[data-allrated="1"]',
            'title'  => 'Ketuk “Kirim”',
            'text'   => 'Nama dan kelima bintang sudah terisi, jadi tombol “Kirim” menyala. Setelah terkirim, Anda kembali ke Aktifitas di SPBU dan tombol “Selesai” menyala.',
            'hint'   => '👆 Ketuk tombol biru “Kirim”',
            'done'   => null,
        ],
    ]];
}

function amt_tour_kendala_steps(): array
{
    $shot     = '.amtcam.is-open .amtcam-shot:not([hidden])';
    $estSkip  = '#kdForm[data-est="1"], #kdForm[data-bisa="tidak"]';
    return ['journey' => 'kendala', 'subKey' => 'amt_kendala', 'steps' => [
        [
            'no'        => 1,
            'target'    => '#kdJenisWrap',
            'title'     => 'Pilih jenis kendala',
            'text'      => 'Ketuk kolom “Jenis Kendala”, lalu pilih yang paling sesuai, misalnya Mogok, Ban Bocor/Pecah, atau Kecelakaan. Pilih “Lainnya” bila tidak ada yang cocok.',
            'hint'      => '👆 Ketuk kolom “Jenis Kendala”',
            'done'      => '#kdForm[data-jenis="1"]',
            'ok'        => '✅ Jenis kendala sudah dipilih',
        ],
        [
            'no'        => 2,
            'target'    => '#kdBisa',
            'highlight' => '#kdBisa',
            'title'     => 'Pilih estimasi kendala',
            'text'      => 'Pilih “Ya, Bisa Diprediksi” bila Anda bisa memperkirakan kapan kendala selesai, maka kolom estimasi waktu akan muncul. Pilih “Tidak Bisa Diprediksi” bila belum bisa diperkirakan, maka kolom estimasi waktu tidak muncul.',
            'hint'      => '👆 Pilih salah satu, lalu ketuk “Mengerti”',
            'button'    => 'Mengerti',
            'done'      => null,
        ],
        [
            'no'        => 3,
            'target'    => '#kdTime',
            'title'     => 'Isi estimasi waktu selesai',
            'text'      => 'Perkirakan berapa lama kendala selesai diperbaiki. Ketuk kolom “Estimasi Waktu Kendala Selesai” untuk membuka pilihan jam dan menit.',
            'hint'      => '👆 Ketuk kolom estimasi waktu',
            'done'      => '#kdModal.is-open, ' . $estSkip,
        ],
        [
            'no'        => 3,
            'over'      => true,
            'when'      => '#kdModal.is-open',
            'target'    => '#kdApply',
            'highlight' => '#kdWheels',
            'title'     => 'Atur jam dan menit',
            'text'      => 'Geser angka ke atas atau bawah (atau ketuk angka di atas / bawahnya) sampai sesuai perkiraan. Tombol “Terapkan” menyala setelah durasinya lebih dari 00 : 00.',
            'hint'      => '👆 Atur durasi, lalu ketuk “Terapkan”',
            'done'      => $estSkip,
            'ok'        => '✅ Estimasi waktu sudah diisi',
        ],
        [
            'no'        => 4,
            'target'    => '#kdPhotoBtn',
            'highlight' => '#kdPhoto',
            'title'     => 'Ambil foto bukti',
            'text'      => 'Ketuk kartu “Foto Bukti Kendala” (“Ambil Foto”) untuk membuka kamera, lalu arahkan ke bukti kendala, misalnya ban yang pecah atau kerusakan mesin.',
            'hint'      => '👆 Ketuk “Ambil Foto”',
            'done'      => '#kdForm[data-photo="1"], .amtcam.is-open',
        ],
        [
            'no'        => 4,
            'camera'    => true,
            'target'    => '.amtcam [data-act="shoot"]',
            'title'     => 'Ketuk “Foto”',
            'text'      => 'Arahkan kamera ke bukti kendala sampai terlihat jelas, lalu ketuk “Foto”. Ketuk “Kamera Depan” bila ingin berganti kamera.',
            'hint'      => '👆 Ketuk tombol “Foto”',
            'done'      => $shot . ', #kdForm[data-photo="1"]',
        ],
        [
            'no'        => 4,
            'camera'    => true,
            'target'    => '.amtcam [data-act="save"]',
            'title'     => 'Ketuk “Simpan Foto”',
            'text'      => 'Periksa hasil fotonya. Bila buktinya sudah jelas, ketuk “Simpan Foto”. Bila kurang bagus, ketuk “Ambil Ulang”.',
            'hint'      => '👆 Ketuk “Simpan Foto”',
            'done'      => '#kdForm[data-photo="1"]',
            'ok'        => '✅ Foto bukti sudah tersimpan',
        ],
        [
            'no'        => 5,
            'target'    => '#kdNote',
            'highlight' => '#kdNoteWrap',
            'title'     => 'Tulis justifikasi',
            'text'      => 'Ceritakan singkat apa yang terjadi: kapan, di mana, dan apa penyebabnya, supaya Terminal mudah memahami kendala Anda.',
            'hint'      => '👆 Ketuk kolom, lalu ketik keterangan',
            'done'      => '#kdForm[data-note="1"]',
            'settle'    => 1500,
            'ok'        => '✅ Justifikasi sudah terisi',
        ],
        [
            'no'        => 6,
            'target'    => '#kdSend',
            'when'      => '#kdForm[data-ready="1"]',
            'title'     => 'Ketuk “Kirim Laporan Kendala”',
            'text'      => 'Semua isian wajib sudah lengkap, jadi tombol “Kirim Laporan Kendala” menyala. Setelah terkirim, Anda kembali ke beranda dan menunggu konfirmasi dari Terminal.',
            'hint'      => '👆 Ketuk tombol biru “Kirim Laporan Kendala”',
            'done'      => null,
        ],
    ]];
}

function amt_tour_steps_for(string $screen, bool $running, int $checkins = 0): array
{
    if ($screen === amt_home_screen()) {
        if ($running && $checkins > 0) {
            $outPack = amt_out_tour_pack();
            if ($outPack !== null) {
                return $outPack;
            }
            if (amt_flow_get('pti_done') && amt_pti_is_done()) {
                return amt_tour_pti_done_pack($checkins);
            }
            $dcuSeen = amt_flow_get('dcu_seen');
            $steps = [[
                'no'       => null,
                'label'    => 'Mulai Ritase Berhasil',
                'target'   => null,
                'notice'   => true,
                'title'    => 'Lakukan DCU dulu 🩺',
                'text'     => 'Mulai Ritase berhasil. Sekarang lakukan DCU (cek kesehatan) terlebih dahulu. DCU dilakukan langsung di tempat, bukan lewat aplikasi ini. Menu PTI akan menyala setelah kartu ini ditutup.',
                'button'   => 'Mengerti',
                'autoHide' => AMT_DCU_VISIBLE_FOR,
                'announce' => true,
                'skip'     => '.amt-home[data-dcu-seen="1"]',
                'done'     => null,
            ]];
            if (!amt_pti_is_done()) {
                $steps[] = [
                    'no'     => 1,
                    'target' => '[data-tour="amt-menu-pti"]',
                    'when'   => 'a[data-tour="amt-menu-pti"]',
                    'delay'  => AMT_PTI_HINT_DELAY,
                    'title'  => 'Buka menu “PTI”',
                    'text'   => 'PTI adalah pemeriksaan mobil tangki sebelum berangkat. Menu PTI sekarang sudah menyala.',
                    'hint'   => '👆 Ketuk menu “PTI”',
                    'done'   => null,
                ];
            }
            return [
                'journey'    => count($steps) > 1 ? 'pti' : 'dcu',
                'startDelay' => $dcuSeen ? 0 : AMT_TOUR_DCU_DELAY_MS,
                'steps'      => $steps,
            ];
        }
        if ($running) {
            $afterPack = amt_out_tour_after_pack();
            if ($afterPack !== null) {
                return $afterPack;
            }
            return ['journey' => 'checkin', 'steps' => [[
                'no'     => 1,
                'target' => '[data-tour="amt-menu-checkin"]',
                'title'  => 'Absen masuk berhasil 🎉',
                'text'   => 'Jam Kerja sudah berjalan dan menu Mulai Ritase sekarang menyala. Ketuk Mulai Ritase saat Anda mendapat tugas pengiriman BBM; Jam Kerja Aktif baru mulai berjalan setelah itu, dijeda saat Selesai Ritase, dan lanjut lagi di Mulai Ritase berikutnya. PTI menyala setelah Mulai Ritase dan DCU; Selesai Ritase belum bisa dipakai.',
                'hint'   => '👆 Ketuk menu “Mulai Ritase”',
                'done'   => null,
            ]]];
        }
        $tourFinished = !empty(work_data()['ended_at']);
        $welcome = $tourFinished
            ? [
                'no'        => null,
                'label'     => 'Tutorial Selesai',
                'target'    => null,
                'notice'    => true,
                'restart'   => true,
                'title'     => 'Selamat! Tutorial selesai 🎉',
                'text'      => 'Selamat, Anda telah menyelesaikan sesi tutorial ini. Klik “Mulai” untuk mengulang proses dari awal TANPA mode tutorial, atau pilih “Sudah cukup sampai di sini”. Untuk menyalakan tutorial lagi kapan saja, ketuk ikon profil di pojok kanan atas.',
                'button'    => 'Mulai',
                'skipLabel' => 'Sudah cukup sampai di sini',
                'done'      => null,
            ]
            : [
                'no'     => null,
                'target' => null,
                'title'  => 'Selamat datang di OneFIS 👋',
                'text'   => 'Kita mulai dari absen masuk kerja. Ikuti saja kotak yang menyala, hanya 6 langkah singkat.',
                'button' => 'Mulai',
                'done'   => null,
            ];
        return ['journey' => 'masuk', 'steps' => [
            $welcome,
            [
                'no'     => 1,
                'target' => '[data-tour="amt-menu-start_end"]',
                'title'  => 'Buka menu “Mulai / Selesai Kerja”',
                'text'   => 'Setiap hari kerja diawali dengan absen. Menu yang abu-abu belum bisa dipakai sebelum Anda absen.',
                'hint'   => '👆 Ketuk menu “Mulai / Selesai Kerja”',
                'done'   => null,
            ],
        ]];
    }

    if ($screen === 'start_end') {
        if ($running && amt_out_after_checkout()) {
            return ['journey' => 'pulang', 'steps' => [[
                'no'        => 1,
                'target'    => '[data-tour="btn-end-work"]',
                'highlight' => '[data-tour="card-end-work"]',
                'title'  => 'Ketuk “Selesai Kerja”',
                'text'   => 'Ritase sudah ditutup, jadi waktunya mengakhiri jam kerja. Ketuk “Selesai Kerja”, lalu isi form-nya.',
                'hint'   => '👆 Ketuk tombol “Selesai Kerja”',
                'done'   => null,
            ]]];
        }
        if ($running) {
            return ['journey' => 'pulang', 'steps' => [[
                'no'        => 1,
                'target'    => '[data-tour="btn-end-work"]',
                'highlight' => '[data-tour="card-end-work"]',
                'title'  => 'Absen pulang: “Selesai Kerja”',
                'text'   => 'Saat jam kerja selesai, ketuk “Selesai Kerja”. Kalau belum waktunya pulang, ketuk “Mengerti” saja.',
                'hint'   => '👆 Ketuk “Selesai Kerja” saat sudah selesai bekerja',
                'button' => 'Mengerti',
                'done'   => null,
            ]]];
        }
        return ['journey' => 'masuk', 'steps' => [[
            'no'        => 2,
            'target'    => '[data-tour="btn-start-work"]',
            'highlight' => '[data-tour="card-start-work"]',
            'title'  => 'Ketuk “Mulai Kerja”',
            'text'   => 'Ini tombol absen masuk. Pastikan Anda sudah berada di area terminal.',
            'hint'   => '👆 Ketuk tombol hijau “Mulai Kerja”',
            'done'   => null,
        ]]];
    }

    if ($screen === 'start_work') {
        return ['journey' => 'masuk', 'steps' => amt_tour_form_steps('start')];
    }
    if ($screen === 'checkin') {
        return ['journey' => 'checkin', 'steps' => amt_tour_form_steps('checkin')];
    }
    if ($screen === 'end_work') {
        return ['journey' => 'pulang', 'steps' => amt_tour_form_steps('end')];
    }

    if ($screen === 'amt_shipments' || $screen === 'amt_shipment_detail') {
        $pack = amt_tour_ship_steps($screen);
        return ['journey' => $pack['journey'], 'steps' => $pack['steps']];
    }

    if ($screen === 'amt_spbu') {
        $pack = amt_tour_spbu_steps();
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    if ($screen === 'amt_checklist_lo' || $screen === 'amt_checklist') {
        $pack = $screen === 'amt_checklist_lo' ? amt_tour_pbk_lo_steps() : amt_tour_pbk_steps();
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    if (in_array($screen, AMT_VERIF_SCREENS, true)) {
        $pack = amt_tour_verif_steps($screen);
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    if ($screen === AMT_SJ_SCREEN) {
        $pack = amt_tour_sj_steps();
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    if ($screen === AMT_KENDALA_SCREEN) {
        return ['journey' => 'kendala', 'steps' => [], 'subKey' => null];
    }

    if ($screen === AMT_RATING_SCREEN) {
        $pack = amt_tour_rating_steps();
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    if ($screen === AMT_OUT_SCREEN) {
        return ['journey' => 'checkout', 'steps' => amt_out_tour_form_steps()];
    }

    if ($screen === 'amt_pti_hasil') {
        $pack = amt_tour_pti_hasil_steps();
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    if ($screen === 'amt_pti' || $screen === 'amt_pti_form') {
        $pack = amt_tour_pti_steps($screen);
        return ['journey' => $pack['journey'], 'steps' => $pack['steps'], 'subKey' => $pack['subKey']];
    }

    return ['journey' => 'masuk', 'steps' => []];
}

function amt_tour_config(string $screen): array
{
    $running  = work_is_running();
    $checkins = $running ? count(work_checkins()) : 0;
    $pack     = amt_tour_steps_for($screen, $running, $checkins);
    $subKey   = null;

    if ($running && $screen === amt_home_screen()) {
        $subKey = !empty($pack['subKey'])
            ? $pack['subKey']
            : ($checkins > 0 ? 'amt_home_dcu_' . $checkins : 'amt_home_running');
    } elseif ($running && $screen === 'start_end') {
        $subKey = 'start_end_running';
    } elseif (!empty($pack['subKey'])) {
        $subKey = $pack['subKey'];
    }

    return [
        'steps'        => $pack['steps'],
        'subKey'       => $subKey,
        'journey'      => AMT_TOUR_JOURNEYS[$pack['journey']] ?? null,
        'startDelay'   => (int) ($pack['startDelay'] ?? 0),
        'epoch'        => amt_tour_epoch(),
        'fresh'        => amt_tour_fresh(),
        'persist'      => !in_array($screen, ['start_work', 'checkin', 'checkout', 'end_work', 'start_end', 'amt_surat_jalan', 'amt_rating', 'amt_kendala'], true)
                          && !($screen === 'amt_spbu' && empty($pack['subKey'])),
        'screenOrder'  => AMT_TOUR_SCREEN_ORDER,
        'screenLabels' => AMT_TOUR_SCREEN_LABELS,
        'noFab'        => $screen === 'amt_performance',
    ];
}