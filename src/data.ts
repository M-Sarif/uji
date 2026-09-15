import { ChecklistStep } from './types';

export const CHECKLIST_STEPS: ChecklistStep[] = [
  { step: 1, text: "Pastikan tersedianya volume ruang kosong dalam tangki.", type: "photo" },
  { step: 2, text: "Tempatkan mobil tangki pada posisi pembongkaran yang benar.", type: "action" },
  { step: 3, text: "Tarik rem tangan, matikan mesin & aktifkan safety switch. Biarkan kunci kendaraan tetap terpasang di tempatnya. Pasang ganjal ban mobil tangki.", type: "action" },
  { step: 4, text: "Turunkan alat pemadam api dan tempatkan pada posisi yang aman dan mudah terjangkau.", type: "action" },
  { step: 5, text: "Pasang kabel arde dan yakinkan terpasang dengan benar.", type: "action" },
  { step: 6, text: "Periksa kesesuaian SPP yaitu produk, nomor segel (periksa keutuhan segel bawah dan atas) nopol Mobil Tangki, dan nama AMT.", type: "form_spp" },
  { step: 7, text: "Isi form data LO berdasarkan Metode Pengukuran (IJKBOUT/Flow Meter).", type: "form_ukur" },
  { step: 8, text: "Pemeriksaan Sampel BBM & view Test Report.", type: "action" },
  { step: 9, text: "Pasang selang bongkar pada inlet pipa tangki (filling point), pastikan kesesuaian tangki penerima dengan produk yang akan dibongkar, kemudian pada outlet mobil tangki (gunakan quick coupling).", type: "action" },
  { step: 10, text: "Lakukan pembongkaran dengan membuka kerangan sedikit demi sedikit. Pastikan tidak ada kebocoran pada selang maupun sambungan/coupling.", type: "action" },
  { step: 11, text: "Selesai melakukan bongkar, pastikan: - Muatan BBM di mobil tangki benar-benar telah habis dan lakukan pengukuran volume BBM di dalam tangki penerima. - Pastikan manhole atas tertutup sempurna.", type: "photo" },
  { step: 12, text: "Tutup kerangan, lepas selang bongkar dimulai dari mobil tangki dan tutup kembali lubang pengisian dari mobil tangki serta dipastikan tidak ada genangan BBM.", type: "photo" },
  { step: 13, text: "Lepas kabel arde, kembalikan alat pemadam ke tempat semula dan Pastikan segel bekas dibawa kembali dan diserahkan ke Terminal.", type: "photo" },
  { step: 14, text: "Selesaikan proses administrasi dan dokumen wajib ditandatangani bersama.", type: "photo" },
  { step: 15, text: "Konfirmasi Status LO", type: "konfirmasi_lo" },
];
