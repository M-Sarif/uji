import { useState } from 'react';
import { Clock, Plus, Truck, Map, CheckCircle2, Database, Users, Wrench, FileText } from 'lucide-react';
import { PrimaryButton } from './Screens';

export function DashboardScreen({ onNext }: { onNext: () => void }) {
  return (
    <div className="flex flex-col min-h-full bg-gradient-to-br from-slate-50 to-rose-50/20 p-4 gap-4 pb-8">
      {/* Menu Card */}
      <div className="bg-white p-5 rounded-[2rem] shadow-[0_2px_12px_-4px_rgba(0,0,0,0.05)] border border-slate-50 flex gap-6">
        <div className="flex flex-col items-center gap-2 cursor-pointer hover:opacity-80 transition-opacity flex-1" onClick={onNext}>
          <div className="w-[4.5rem] h-[4.5rem] bg-gradient-to-br from-blue-50 to-blue-100/50 rounded-2xl flex items-center justify-center border border-blue-100 shadow-sm overflow-hidden">
            <img src="/empty-delivery-truck.png" alt="Shipments" className="w-12 h-12 object-contain" onError={(e) => (e.currentTarget.style.display = 'none')} />
          </div>
          <span className="text-[12px] font-bold text-slate-700">Shipments</span>
        </div>
        <div className="flex flex-col items-center gap-2 flex-1 cursor-pointer hover:opacity-80 transition-opacity">
          <div className="w-[4.5rem] h-[4.5rem] bg-gradient-to-br from-rose-50 to-red-100/50 rounded-2xl flex items-center justify-center border border-rose-100 shadow-sm overflow-hidden">
            <img src="/fuel-barrel-CcalmtYK.png" alt="Claim Losses" className="w-12 h-12 object-contain" onError={(e) => (e.currentTarget.style.display = 'none')} />
          </div>
          <span className="text-[12px] font-bold text-slate-700 text-center leading-tight">Claim<br/>Losses</span>
        </div>
        <div className="flex-1"></div>
      </div>

      {/* Illustration Card */}
      <div className="bg-white p-5 rounded-[2.5rem] shadow-[0_2px_12px_-4px_rgba(0,0,0,0.05)] border border-slate-50 flex-1 flex flex-col">
         <div className="flex-1 w-full flex items-center justify-center relative min-h-[220px]">
            <img src="/Order.png" alt="Order Illustration" className="w-[90%] max-w-[280px] object-contain" onError={(e) => (e.currentTarget.style.display = 'none')} />
         </div>
         
         <button onClick={onNext} className="w-full py-4 mt-auto bg-white border-2 border-slate-200 rounded-xl flex items-center justify-center gap-2 text-slate-400 font-semibold text-[14px] hover:bg-slate-50 hover:text-slate-600 transition-colors">
            <FileText size={18} /> Lihat Detail Order
         </button>
      </div>
    </div>
  );
}

export function ShipmentsListScreen({ onNext }: { onNext: () => void }) {
  return (
    <div className="flex flex-col min-h-full bg-slate-50">
      <div className="bg-white p-4 border-b border-slate-100 shadow-sm flex gap-4">
         <button className="flex-1 pb-2 border-b-2 border-blue-600 text-blue-600 font-bold text-sm">Riwayat</button>
         <button className="flex-1 pb-2 border-b-2 border-transparent text-slate-400 font-semibold text-sm">Draft</button>
      </div>
      <div className="p-4 flex-1">
         <div className="bg-white rounded-xl p-4 border border-slate-100 shadow-sm mb-4 cursor-pointer hover:border-blue-300 transition-colors">
           <div className="flex justify-between items-start mb-3">
             <div>
               <p className="text-[10px] text-slate-400 font-bold uppercase tracking-wider mb-1">Pengiriman Aktif</p>
               <p className="text-sm font-bold text-slate-800">24/05/2026 10:00 AM</p>
             </div>
             <span className="px-2 py-1 bg-amber-100 text-amber-700 text-[10px] font-bold rounded-md uppercase">Diproses</span>
           </div>
           <div className="bg-slate-50 rounded-lg p-3 border border-slate-100 flex items-center gap-3">
             <Truck size={20} className="text-blue-500" />
             <div>
               <p className="text-xs font-semibold text-slate-700">PERTALITE 5000 L</p>
               <p className="text-[11px] text-slate-500">B 1234 ABC</p>
             </div>
           </div>
         </div>
      </div>
      <div className="p-4 bg-white border-t border-slate-100 mt-auto rounded-b-[2.5rem]">
         <PrimaryButton onClick={onNext}>Buat Order</PrimaryButton>
      </div>
    </div>
  );
}

export function OrderInfoScreen({ onNext }: { onNext: () => void }) {
  return (
    <div className="p-4 flex flex-col min-h-full pb-8">
       <h2 className="text-sm font-bold text-slate-800 mb-4">1. Isi Informasi Umum</h2>
       <div className="space-y-4 flex-1">
          <div>
            <label className="text-xs font-semibold text-slate-600 block mb-1">No SPBU</label>
            <div className="w-full bg-slate-100 border border-slate-200 rounded-lg p-3 text-[13px] text-slate-500 font-medium">3210829 - PT Lorem Ipsum</div>
          </div>
          <div>
            <label className="text-xs font-semibold text-slate-600 block mb-1">Ship-to Number</label>
            <div className="w-full bg-slate-100 border border-slate-200 rounded-lg p-3 text-[13px] text-slate-500 font-medium">1234567</div>
          </div>
          <div>
            <label className="text-xs font-semibold text-slate-600 block mb-1">Terminal Supply *</label>
            <div className="w-full bg-white border border-slate-200 rounded-lg p-3 text-[13px] text-slate-800 shadow-sm font-medium">Depot Plumpang</div>
          </div>
          <div>
            <label className="text-xs font-semibold text-slate-600 block mb-1.5">Jenis Order *</label>
            <div className="flex gap-3">
              <div className="flex-1 bg-blue-50 border-2 border-blue-500 rounded-lg p-2.5 flex items-center justify-center gap-2 text-blue-700 text-xs font-bold shadow-sm">
                <div className="w-3 h-3 rounded-full bg-blue-500 border-2 border-white shadow-sm ring-1 ring-blue-500"></div> Reguler
              </div>
              <div className="flex-1 bg-white border-2 border-slate-200 rounded-lg p-2.5 flex items-center justify-center gap-2 text-slate-500 text-xs font-bold hover:bg-slate-50 cursor-pointer">
                <div className="w-3 h-3 rounded-full border-2 border-slate-300"></div> Emergency
              </div>
            </div>
          </div>
          <div>
            <label className="text-xs font-semibold text-slate-600 block mb-1">Tanggal Pengiriman *</label>
            <div className="w-full bg-white border border-slate-200 rounded-lg p-3 text-[13px] text-slate-800 shadow-sm font-medium flex justify-between items-center">
              25/05/2026 <Clock size={16} className="text-slate-400" />
            </div>
          </div>
       </div>
       <div className="mt-8 pt-4 border-t border-slate-100 flex gap-3">
         <button className="px-4 py-3.5 rounded-xl border-2 border-slate-200 text-slate-600 font-bold text-sm bg-slate-50 hover:bg-slate-100 transition-colors">Draft</button>
         <PrimaryButton onClick={onNext} className="flex-1">Selanjutnya</PrimaryButton>
       </div>
    </div>
  );
}

export function OrderProductScreen({ onNext }: { onNext: () => void }) {
  const [added, setAdded] = useState(false);
  return (
    <div className="p-4 flex flex-col min-h-full pb-8">
       <h2 className="text-sm font-bold text-slate-800 mb-4">2. Pilih Produk BBM</h2>
       
       {!added ? (
         <div className="flex-1 flex flex-col items-center justify-center text-center">
            <div className="w-16 h-16 bg-blue-50 rounded-full flex items-center justify-center text-blue-500 mb-4 shadow-sm border border-blue-100">
              <Plus size={32} />
            </div>
            <p className="text-sm font-bold text-slate-700 mb-1">Produk BBM masih kosong</p>
            <p className="text-[13px] text-slate-500 mb-6">Silakan tambah produk BBM terlebih dahulu</p>
            <button onClick={() => setAdded(true)} className="px-6 py-3.5 bg-white border-2 border-blue-600 text-blue-600 rounded-xl font-bold text-sm hover:bg-blue-50 transition-colors shadow-sm w-full">
              + Tambah Produk BBM
            </button>
         </div>
       ) : (
         <div className="flex-1 flex flex-col">
            <div className="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm mb-6">
               <p className="text-xs font-semibold text-slate-600 mb-2">Produk BBM *</p>
               <div className="w-full bg-slate-50 border border-slate-200 rounded-lg p-3 text-[14px] font-bold text-slate-800 mb-5">PERTALITE</div>
               
               <p className="text-xs font-semibold text-slate-600 mb-2">Stok Aktual Produk BBM</p>
               <div className="flex justify-between items-center text-[13px] bg-slate-50 p-3 rounded-lg mb-2 border border-slate-100">
                 <span className="text-slate-600 font-medium">Tank 1 (60.000L)</span>
                 <span className="font-bold text-slate-800">40.000 L</span>
               </div>
               <div className="flex justify-between items-center text-[13px] bg-blue-50 border border-blue-200 p-3 rounded-lg mb-6 shadow-sm">
                 <span className="text-blue-800 font-bold">Total Kapasitas</span>
                 <span className="font-bold text-blue-700 text-[14px]">60.000 L</span>
               </div>

               <p className="text-xs font-semibold text-slate-600 mb-2">Qty Rekomendasi Sistem</p>
               <div className="w-full bg-slate-100 border border-slate-200 rounded-lg p-3 text-[14px] font-bold text-slate-500 mb-6">60.000 Liter</div>

               <p className="text-xs font-semibold text-slate-600 mb-2">Qty Order *</p>
               <div className="w-full bg-white border-2 border-blue-500 focus-within:ring-4 ring-blue-100 rounded-lg p-3 text-[15px] font-bold text-slate-800 mb-2 flex justify-between items-center shadow-sm">
                 <span>8000</span> <span className="text-slate-400 font-medium text-xs">Liter</span>
               </div>
            </div>
            <div className="mt-auto">
              <PrimaryButton onClick={onNext}>Terapkan & Selanjutnya</PrimaryButton>
            </div>
         </div>
       )}
    </div>
  );
}

export function OrderReviewScreen({ onNext }: { onNext: () => void }) {
  return (
    <div className="p-4 flex flex-col min-h-full pb-8">
       <h2 className="text-sm font-bold text-slate-800 mb-4">3. Review Order</h2>
       
       <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-5">
         <div className="bg-slate-50 p-3.5 border-b border-slate-200">
           <p className="text-xs font-bold text-slate-700 uppercase tracking-wider">Informasi Umum</p>
         </div>
         <div className="p-4.5 space-y-3.5 text-[13px]">
           <div className="flex justify-between"><span className="text-slate-500 font-medium">No SPBU</span><span className="font-bold text-slate-800">3210829</span></div>
           <div className="flex justify-between"><span className="text-slate-500 font-medium">Ship-to</span><span className="font-bold text-slate-800">1234567</span></div>
           <div className="flex justify-between"><span className="text-slate-500 font-medium">Jenis Order</span><span className="font-bold text-blue-600">Reguler</span></div>
           <div className="flex justify-between"><span className="text-slate-500 font-medium">Tanggal</span><span className="font-bold text-slate-800">25/05/2026</span></div>
         </div>
       </div>

       <div className="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden mb-auto">
         <div className="bg-slate-50 p-3.5 border-b border-slate-200">
           <p className="text-xs font-bold text-slate-700 uppercase tracking-wider">Produk BBM</p>
         </div>
         <div className="p-4.5 flex items-center justify-between">
           <div>
             <p className="font-bold text-[15px] text-slate-800 mb-1">PERTALITE</p>
             <p className="text-[13px] text-slate-500 font-medium">Qty Order: <span className="font-bold text-slate-700">8000 L</span></p>
           </div>
           <div className="w-10 h-10 bg-emerald-50 rounded-full flex items-center justify-center text-emerald-500 border border-emerald-100 shadow-sm">
             <CheckCircle2 size={24} />
           </div>
         </div>
       </div>

       <div className="mt-8 pt-4 border-t border-slate-100 flex gap-3">
         <button className="px-4 py-3.5 rounded-xl border-2 border-slate-200 text-slate-600 font-bold text-sm bg-slate-50 hover:bg-slate-100 transition-colors">Draft</button>
         <PrimaryButton onClick={onNext} className="flex-1">Submit Order</PrimaryButton>
       </div>
    </div>
  );
}

export function TrackOrderScreen({ onNext }: { onNext: () => void }) {
  return (
    <div className="flex flex-col min-h-full bg-slate-50">
      <div className="bg-white px-4 pt-2 border-b border-slate-100 shadow-sm flex gap-4 shrink-0">
         <button onClick={onNext} className="flex-1 pb-3 border-b-2 border-transparent text-slate-400 font-semibold text-sm hover:text-slate-600 transition-colors">Aktifitas</button>
         <button className="flex-1 pb-3 border-b-2 border-blue-600 text-blue-600 font-bold text-sm">Pengiriman</button>
      </div>
      
      <div className="p-4 flex-1">
         <div className="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm relative overflow-hidden">
            {/* Timeline Line */}
            <div className="absolute left-[2.35rem] top-10 bottom-12 w-0.5 bg-slate-200"></div>
            <div className="absolute left-[2.35rem] top-10 h-[55%] w-0.5 bg-emerald-500"></div>

            <div className="space-y-8 relative z-10">
               <div className="flex gap-4">
                 <div className="w-8 h-8 shrink-0 rounded-full bg-emerald-500 text-white flex items-center justify-center border-[3px] border-white shadow-sm mt-0.5 ring-1 ring-slate-100">
                   <CheckCircle2 size={16} />
                 </div>
                 <div>
                   <p className="text-[14px] font-bold text-slate-800">LO Sedang Dijadwalkan</p>
                   <p className="text-[12px] text-slate-500 font-medium">Minggu, 24/05/2026 10:00 AM</p>
                 </div>
               </div>

               <div className="flex gap-4">
                 <div className="w-8 h-8 shrink-0 rounded-full bg-emerald-500 text-white flex items-center justify-center border-[3px] border-white shadow-sm mt-0.5 ring-1 ring-slate-100">
                   <CheckCircle2 size={16} />
                 </div>
                 <div>
                   <p className="text-[14px] font-bold text-slate-800">Menyiapkan BBM di Depot</p>
                   <p className="text-[12px] text-slate-500 font-medium">Senin, 25/05/2026 08:00 AM</p>
                 </div>
               </div>

               <div className="flex gap-4">
                 <div className="w-8 h-8 shrink-0 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center border-[3px] border-white shadow-sm mt-0.5 ring-1 ring-blue-200">
                   <Truck size={14} />
                 </div>
                 <div>
                   <p className="text-[14px] font-bold text-blue-700 mb-0.5">Order Sedang Dikirim</p>
                   <p className="text-[12px] text-slate-500 font-medium mb-3">Senin, 25/05/2026 09:00 AM</p>
                   <button className="bg-blue-50 border border-blue-200 px-3 py-2 rounded-lg flex items-center gap-2 hover:bg-blue-100 transition-colors shadow-sm active:scale-95">
                     <Map size={14} className="text-blue-600" />
                     <span className="text-[12px] font-bold text-blue-700">Lacak Pengiriman</span>
                   </button>
                 </div>
               </div>

               <div className="flex gap-4 opacity-40">
                 <div className="w-8 h-8 shrink-0 rounded-full bg-slate-200 border-[3px] border-white shadow-sm mt-0.5 ring-1 ring-slate-200"></div>
                 <div>
                   <p className="text-[14px] font-bold text-slate-600">Tiba di Lokasi</p>
                   <p className="text-[12px] text-slate-500 font-medium">Menunggu kedatangan...</p>
                 </div>
               </div>
            </div>
         </div>
      </div>
    </div>
  );
}
