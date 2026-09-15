import { CheckCircle2, UserCircle, Star, QrCode, FileText, Truck } from 'lucide-react';
import { useState } from 'react';

export const PrimaryButton = ({ onClick, children, disabled = false, className = '' }: any) => (
  <button 
    onClick={onClick} 
    disabled={disabled}
    className={`w-full py-3.5 rounded-xl font-semibold text-[15px] transition-all active:scale-[0.98]
      ${disabled ? 'bg-slate-200 text-slate-400' : 'bg-blue-600 text-white hover:bg-blue-700 shadow-md shadow-blue-200'} ${className}`}
  >
    {children}
  </button>
);

export function ShipmentScreen({ onNext }: { onNext: () => void }) {
  return (
    <div className="p-4 pt-8 pb-20 flex flex-col min-h-full">
      <div className="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 mb-4 relative overflow-hidden">
        <div className="absolute top-0 left-0 w-1.5 h-full bg-blue-500"></div>
        <div className="flex justify-between items-start mb-4 pl-2">
          <div>
            <p className="text-xs text-slate-500 font-medium mb-1">24/05/2026 10:00 AM</p>
            <p className="text-sm font-semibold text-slate-800">PERTALITE 5000 L</p>
          </div>
          <span className="px-2.5 py-1 bg-emerald-100 text-emerald-700 text-[10px] font-bold rounded-md uppercase tracking-wide">Tiba di Lokasi</span>
        </div>
        <div className="flex items-center gap-2 text-sm text-slate-600 bg-slate-50 p-3 rounded-lg border border-slate-100 ml-2">
          <Truck size={16} className="text-blue-500" />
          <span className="font-medium">B 1234 ABC</span>
        </div>
      </div>
      
      <div className="mt-auto pt-6">
        <PrimaryButton onClick={onNext}>Tiba di Lokasi</PrimaryButton>
      </div>
    </div>
  );
}

export function VerificationScreen({ onNext }: { onNext: () => void }) {
  return (
    <div className="p-4 pt-8 pb-8 flex flex-col min-h-full gap-4">
      <div className="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
        <div className="flex items-center gap-3 mb-4">
          <div className="w-12 h-12 bg-blue-50 rounded-full flex items-center justify-center text-blue-500">
            <Truck size={24} />
          </div>
          <div>
            <p className="text-[15px] font-bold text-slate-800">B 9170 SEJ</p>
            <p className="text-xs text-slate-500 font-medium">16 KL</p>
          </div>
        </div>
        <p className="text-xs font-semibold text-slate-700 mb-3">Apakah mobil tangki sesuai?</p>
        <div className="flex gap-2">
          <button className="flex-1 py-2.5 rounded-lg border border-red-500 text-red-500 text-sm font-medium hover:bg-red-50 transition-colors">Tidak sesuai</button>
          <button className="flex-1 py-2.5 rounded-lg border border-blue-600 bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100 transition-colors">Ya, sesuai</button>
        </div>
      </div>

      <div className="bg-white rounded-2xl p-4 shadow-sm border border-slate-100">
        <div className="flex items-center gap-3 mb-4">
          <div className="w-12 h-12 bg-slate-100 rounded-full flex items-center justify-center text-slate-500 overflow-hidden border border-slate-200">
            <img src="https://ui-avatars.com/api/?name=Farhan&background=0D8ABC&color=fff" alt="Avatar" className="w-full h-full object-cover" />
          </div>
          <div>
            <p className="text-[14px] font-bold text-slate-800">MOHAMMAD FARHAN</p>
            <p className="text-xs text-slate-500 font-medium mt-0.5">AMT 1</p>
          </div>
        </div>
        <p className="text-xs font-semibold text-slate-700 mb-3">Apakah AMT 1 sesuai?</p>
        <div className="flex gap-2">
          <button className="flex-1 py-2.5 rounded-lg border border-red-500 text-red-500 text-sm font-medium hover:bg-red-50 transition-colors">Tidak sesuai</button>
          <button className="flex-1 py-2.5 rounded-lg border border-blue-600 bg-blue-50 text-blue-700 text-sm font-medium hover:bg-blue-100 transition-colors">Ya, sesuai</button>
        </div>
      </div>

      <div className="mt-auto">
        <PrimaryButton onClick={onNext}>Kirim Verifikasi MT dan AMT</PrimaryButton>
      </div>
    </div>
  );
}

export function LoListScreen({ onNext }: { onNext: () => void }) {
  const [selected, setSelected] = useState(false);
  
  return (
    <div className="p-4 pt-6 pb-8 flex flex-col min-h-full">
      <p className="text-[15px] font-semibold text-slate-800 mb-1 px-1">Daftar LO</p>
      <p className="text-xs text-slate-500 mb-5 px-1">Pilih LO untuk mengisi checklist</p>
      
      <div 
        onClick={() => setSelected(true)}
        className={`bg-white rounded-2xl p-4 shadow-sm border-2 cursor-pointer transition-all ${selected ? 'border-blue-500 bg-blue-50/40' : 'border-slate-200'}`}
      >
        <div className="flex items-start gap-4">
          <div className={`w-5 h-5 rounded mt-0.5 flex items-center justify-center border transition-colors ${selected ? 'bg-blue-600 border-blue-600' : 'border-slate-300'}`}>
            {selected && <CheckCircle2 size={14} className="text-white" />}
          </div>
          <div className="flex-1 space-y-2.5">
             <div className="flex text-sm"><span className="w-24 text-slate-500 font-medium">Nomor LO</span><span className="font-semibold text-slate-700">: 8143805561</span></div>
             <div className="flex text-sm"><span className="w-24 text-slate-500 font-medium">Order</span><span className="font-semibold text-slate-700">: PERTALITE 5.000 L</span></div>
             <div className="flex text-sm"><span className="w-24 text-slate-500 font-medium">Status</span><span className={selected ? "text-emerald-600 font-bold" : "text-amber-500 font-bold"}>: {selected ? 'Telah Diisi' : 'Belum Diisi'}</span></div>
          </div>
        </div>
      </div>

      <div className="mt-auto pt-6 flex gap-3">
        <button className="flex-1 py-3.5 rounded-xl border-2 border-slate-200 text-slate-600 font-bold text-sm flex items-center justify-center gap-2 hover:bg-slate-50 transition-colors">
          <FileText size={16} /> Lihat Checklist
        </button>
        <div className="flex-1">
          <PrimaryButton onClick={onNext} disabled={!selected}>Mulai Checklist</PrimaryButton>
        </div>
      </div>
    </div>
  );
}

export function QrScreen({ onNext }: { onNext: () => void }) {
  return (
    <div className="p-4 pt-8 pb-8 flex flex-col min-h-full items-center">
      <div className="w-full bg-white rounded-2xl p-8 shadow-sm border border-slate-100 text-center mb-6">
         <h2 className="text-[15px] font-bold text-slate-800 mb-2">Kode QR Kamu Sudah Dibuat</h2>
         <p className="text-xs text-slate-500 mb-8 leading-relaxed">Scan QR Code ini oleh AMT untuk menyelesaikan proses verifikasi</p>
         
         <div className="w-48 h-48 mx-auto bg-white rounded-2xl flex items-center justify-center border-2 border-slate-100 shadow-sm mb-8">
            <QrCode size={120} strokeWidth={1} className="text-slate-800" />
         </div>
         
         <div className="relative py-4 border-t border-dashed border-slate-200">
           <p className="text-[11px] font-bold text-slate-400 uppercase tracking-widest mb-3">Atau gunakan kode</p>
           <div className="text-4xl font-black tracking-[0.3em] text-blue-600">
             131200
           </div>
         </div>
      </div>
      <div className="mt-auto w-full">
        <PrimaryButton onClick={onNext}>Selesai Verifikasi</PrimaryButton>
      </div>
    </div>
  );
}

export function RatingScreen({ onNext }: { onNext: () => void }) {
  const [ratings, setRatings] = useState<Record<string, number>>({});
  
  const categories = [
    { id: 'safety', title: 'Safety AMT', desc: 'Apakah AMT menggunakan seragam dan APD dengan benar?' },
    { id: 'sarfas', title: 'Sarfas', desc: 'Apakah mobil tangki safety untuk proses pembongkaran?' },
    { id: 'komunikasi', title: 'Komunikasi', desc: 'Apakah AMT melakukan kroscek & menginformasikan produk?' },
    { id: 'operasional', title: 'Operasional', desc: 'Apakah AMT standby di lingkungan SPBU saat bongkar?' },
    { id: 'layanan', title: 'Aspek Layanan', desc: 'Ketepatan volume BBM' },
  ];

  const allRated = categories.every(c => (ratings[c.id] || 0) > 0);

  return (
    <div className="p-4 pt-6 pb-24 flex flex-col min-h-full">
       <div className="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 mb-4">
          <div className="flex items-center gap-3">
             <div className="w-10 h-10 bg-slate-100 rounded-full flex items-center justify-center overflow-hidden">
                <img src="https://ui-avatars.com/api/?name=Farhan&background=0D8ABC&color=fff" alt="Avatar" />
             </div>
             <div>
                <h2 className="text-[14px] font-bold text-slate-800">MOHAMMAD FARHAN</h2>
                <p className="text-[11px] text-slate-500 font-medium">AMT 1</p>
             </div>
          </div>
       </div>

       <div className="space-y-3">
         {categories.map(cat => (
            <div key={cat.id} className="bg-white rounded-xl p-4 shadow-sm border border-slate-100">
               <p className="text-sm font-bold text-slate-800 text-center mb-1">{cat.title}</p>
               <p className="text-[11px] text-slate-500 text-center mb-3 leading-relaxed px-2">{cat.desc}</p>
               <div className="flex justify-center gap-2">
                  {[1,2,3,4,5].map(star => (
                    <button key={star} onClick={() => setRatings(prev => ({...prev, [cat.id]: star}))} className="transition-transform active:scale-90">
                      <Star size={28} className={`transition-colors ${star <= (ratings[cat.id] || 0) ? "fill-yellow-400 text-yellow-400" : "text-slate-200 fill-slate-50"}`} />
                    </button>
                  ))}
               </div>
            </div>
         ))}
         <div className="bg-white rounded-xl p-4 shadow-sm border border-slate-100 mb-8">
           <p className="text-sm font-bold text-slate-800 text-center mb-3">Ulasan (Opsional)</p>
           <textarea className="w-full bg-slate-50 border border-slate-200 rounded-lg p-3 text-xs focus:ring-2 focus:ring-blue-100 focus:border-blue-400 outline-none transition-all" rows={3} placeholder="Contoh: Pelayanan sangat memuaskan"></textarea>
         </div>
       </div>
       
       <div className="absolute bottom-0 left-0 right-0 p-4 bg-white/90 backdrop-blur-md border-t border-slate-100 z-10 rounded-b-[2.5rem]">
         <PrimaryButton onClick={onNext} disabled={!allRated}>Kirim Penilaian</PrimaryButton>
       </div>
    </div>
  );
}

export function DoneScreen({ onReset }: { onReset: () => void }) {
  return (
    <div className="p-4 pt-32 pb-8 flex flex-col min-h-full items-center text-center">
      <div className="w-24 h-24 bg-emerald-100 rounded-full flex items-center justify-center text-emerald-500 mb-6 shadow-sm">
        <CheckCircle2 size={48} />
      </div>
      <h1 className="text-2xl font-bold text-slate-800 mb-3 tracking-tight">Pengiriman Selesai</h1>
      <p className="text-sm text-slate-500 mb-8 max-w-[260px] leading-relaxed">Order BBM telah berhasil diserahterimakan ke SPBU Anda dengan aman.</p>
      
      <div className="mt-auto w-full">
        <PrimaryButton onClick={onReset}>Kembali ke Beranda</PrimaryButton>
      </div>
    </div>
  );
}
