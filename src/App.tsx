/**
 * @license
 * SPDX-License-Identifier: Apache-2.0
 */

import { useState } from 'react';
import { ScreenState } from './types';
import { ShipmentScreen, VerificationScreen, LoListScreen, QrScreen, RatingScreen, DoneScreen } from './components/Screens';
import { DashboardScreen, ShipmentsListScreen, OrderInfoScreen, OrderProductScreen, OrderReviewScreen, TrackOrderScreen } from './components/OrderScreens';
import ChecklistWizard from './components/ChecklistWizard';
import { Battery, Signal, Wifi, ChevronLeft, Info, Bell } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

export default function App() {
  const [screen, setScreen] = useState<ScreenState>('dashboard');

  const getTutorialText = () => {
    switch (screen) {
      case 'dashboard': return "1. Ini adalah halaman utama OneFIS. Klik menu 'Shipments' atau 'Lihat Detail Order' untuk melanjutkan.";
      case 'shipments_list': return "2. Pada halaman Shipments pilih menu Buat Order untuk memesan BBM.";
      case 'create_order_info': return "3. Isi Informasi Umum seperti Jenis Order, Tanggal, dan Shift. Klik 'Selanjutnya'.";
      case 'create_order_product': return "4. Tambahkan Produk BBM, cek Stok Aktual, dan isi Qty Order (misal 8000L). Klik 'Terapkan'.";
      case 'create_order_review': return "5. Cek kembali Ringkasan Order Anda, lalu klik 'Submit Order'.";
      case 'track_order': return "6. Pantau order di tab Pengiriman. Setelah itu, klik tab 'Aktifitas' untuk melihat detail.";
      case 'shipment': return "7. Setelah mobil tangki tiba, klik 'Tiba di Lokasi' untuk verifikasi AMT.";
      case 'verification': return "8. Verifikasi kesesuaian data Mobil Tangki & AMT. Klik 'Kirim Verifikasi'.";
      case 'lo_list': return "9. Pilih LO yang akan dibongkar, lalu klik 'Mulai Checklist'.";
      case 'checklist': return "10. Ikuti 15 langkah checklist Pra-Pembongkaran sesuai kondisi lapangan (termasuk IJKBOUT/Flow Meter).";
      case 'qr_code': return "11. Tunjukkan QR Code / Kode Konfirmasi ini kepada AMT untuk diselesaikan.";
      case 'rating': return "12. Berikan penilaian mendetail (Safety, Sarfas, dll) untuk pelayanan AMT. Klik Selesai.";
      case 'done': return "Selesai! Seluruh proses dari Order BBM hingga Pembongkaran berhasil dicatat.";
      default: return "";
    }
  };

  const getHeaderTitle = () => {
    switch (screen) {
      case 'dashboard': return "";
      case 'shipments_list': return "Shipments";
      case 'create_order_info': return "Buat Order";
      case 'create_order_product': return "Buat Order";
      case 'create_order_review': return "Buat Order";
      case 'track_order': return "Detail Order";
      case 'shipment': return "Detail Order";
      case 'verification': return "Tiba di Lokasi";
      case 'lo_list': return "Checklist Pra-Pembongkaran";
      case 'checklist': return "Lihat Checklist Pra Bongkar";
      case 'qr_code': return "Permintaan Verifikasi";
      case 'rating': return "Rating AMT";
      case 'done': return "Pengiriman Selesai";
      default: return "";
    }
  };

  return (
    <div className="flex items-center justify-center min-h-screen bg-slate-900 p-4 font-sans text-slate-800">
      
      {/* Mobile Frame */}
      <div className="w-full max-w-[400px] h-[850px] max-h-[92vh] bg-slate-50 shadow-2xl relative overflow-hidden flex flex-col sm:rounded-[3rem] sm:border-[14px] border-slate-950">
        
        {/* Fake StatusBar */}
        <div className="h-9 w-full flex justify-between items-center px-6 text-xs font-semibold pt-3 shrink-0 bg-white z-20">
          <span>10:58</span>
          {/* Dynamic Island fake */}
          <div className="absolute left-1/2 -translate-x-1/2 top-2.5 w-28 h-7 bg-black rounded-full"></div>
          <div className="flex gap-1.5 items-center">
            <Signal size={14} />
            <Wifi size={14} />
            <Battery size={16} />
          </div>
        </div>

        {/* Header */}
        {screen === 'dashboard' ? (
          <div className="h-16 bg-white flex items-center justify-between px-5 shadow-[0_2px_10px_-4px_rgba(0,0,0,0.1)] shrink-0 z-10 relative">
             <div className="flex items-center">
               <img src="/logo-onefis.svg" alt="OneFIS" className="h-8 object-contain" onError={(e) => (e.currentTarget.style.display = 'none')} />
             </div>
             <div className="flex items-center gap-4">
               <Bell size={22} className="text-slate-700" />
               <div className="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white font-bold text-[14px] shadow-sm">6</div>
             </div>
          </div>
        ) : (
          <div className="h-14 bg-white flex items-center px-4 shadow-sm shrink-0 z-10 border-b border-slate-100 relative">
            <button className="p-2 -ml-2 text-slate-600 hover:bg-slate-100 rounded-full transition-colors" onClick={() => screen === 'shipments_list' ? setScreen('dashboard') : null}>
              <ChevronLeft size={24} />
            </button>
            <h1 className="ml-1 text-[16px] font-bold text-slate-800 truncate">{getHeaderTitle()}</h1>
          </div>
        )}

        {/* Tutorial Overlay (Floating) */}
        <AnimatePresence mode="wait">
          <motion.div 
            key={screen}
            initial={{ opacity: 0, y: -10 }}
            animate={{ opacity: 1, y: 0 }}
            exit={{ opacity: 0, y: -10 }}
            transition={{ delay: 0.2 }}
            className={`absolute ${screen === 'dashboard' ? 'top-32' : 'top-28'} left-4 right-4 bg-blue-600 text-white p-4 rounded-2xl shadow-xl z-50 border border-blue-500 flex gap-3`}
          >
            <Info size={20} className="shrink-0 mt-0.5 text-blue-200" />
            <p className="text-[13px] font-medium leading-relaxed">{getTutorialText()}</p>
            {/* Tooltip triangle */}
            <div className="absolute -bottom-2 left-10 w-4 h-4 bg-blue-600 rotate-45 border-b border-r border-blue-500"></div>
          </motion.div>
        </AnimatePresence>

        {/* Main Content Area */}
        <div className="flex-1 overflow-y-auto overflow-x-hidden relative bg-slate-50/50 scroll-smooth">
          <AnimatePresence mode="wait">
            <motion.div
              key={screen}
              initial={{ opacity: 0, x: 20 }}
              animate={{ opacity: 1, x: 0 }}
              exit={{ opacity: 0, x: -20 }}
              transition={{ duration: 0.2, ease: "easeInOut" }}
              className="min-h-full flex flex-col"
            >
              {screen === 'dashboard' && <DashboardScreen onNext={() => setScreen('shipments_list')} />}
              {screen === 'shipments_list' && <ShipmentsListScreen onNext={() => setScreen('create_order_info')} />}
              {screen === 'create_order_info' && <OrderInfoScreen onNext={() => setScreen('create_order_product')} />}
              {screen === 'create_order_product' && <OrderProductScreen onNext={() => setScreen('create_order_review')} />}
              {screen === 'create_order_review' && <OrderReviewScreen onNext={() => setScreen('track_order')} />}
              {screen === 'track_order' && <TrackOrderScreen onNext={() => setScreen('shipment')} />}
              {screen === 'shipment' && <ShipmentScreen onNext={() => setScreen('verification')} />}
              {screen === 'verification' && <VerificationScreen onNext={() => setScreen('lo_list')} />}
              {screen === 'lo_list' && <LoListScreen onNext={() => setScreen('checklist')} />}
              {screen === 'checklist' && <ChecklistWizard onComplete={() => setScreen('qr_code')} />}
              {screen === 'qr_code' && <QrScreen onNext={() => setScreen('rating')} />}
              {screen === 'rating' && <RatingScreen onNext={() => setScreen('done')} />}
              {screen === 'done' && <DoneScreen onReset={() => setScreen('dashboard')} />}
            </motion.div>
          </AnimatePresence>
        </div>
      </div>
      
    </div>
  );
}
