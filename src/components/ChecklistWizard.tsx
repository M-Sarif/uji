import { useState } from 'react';
import { CHECKLIST_STEPS } from '../data';
import { Camera, AlertCircle } from 'lucide-react';
import { motion, AnimatePresence } from 'motion/react';

export default function ChecklistWizard({ onComplete }: { onComplete: () => void }) {
  const [currentStep, setCurrentStep] = useState(1);
  const stepData = CHECKLIST_STEPS[currentStep - 1];

  const handleNext = () => {
    if (currentStep < 15) {
      setCurrentStep(s => s + 1);
    } else {
      onComplete();
    }
  };
  
  const handlePrev = () => {
    if (currentStep > 1) {
      setCurrentStep(s => s - 1);
    }
  };

  const renderContent = () => {
    switch (stepData.type) {
      case 'action':
        return (
          <div className="bg-slate-50/50 rounded-xl p-4 border border-slate-100">
            <p className="text-xs text-slate-400 mb-4 font-medium">Tugas AMT dan diverifikasi oleh SPBU</p>
            <div className="flex gap-3">
              <button className="flex-1 py-3 rounded-xl border border-red-200 text-red-600 bg-white font-semibold text-[13px] hover:bg-red-50 transition-colors shadow-sm">Tidak Dilakukan</button>
              <button className="flex-1 py-3 rounded-xl border border-blue-600 bg-blue-50 text-blue-700 font-semibold text-[13px] hover:bg-blue-100 transition-colors shadow-sm">Ya, dilakukan</button>
            </div>
          </div>
        );
      case 'photo':
        return (
          <div className="bg-slate-50/50 rounded-xl p-4 border border-slate-100">
             <p className="text-xs text-slate-400 mb-4 font-medium">Foto pelaksanaan tugas Anda sebagai bukti.</p>
             <div className="w-full h-48 bg-slate-100 rounded-xl flex flex-col items-center justify-center border-2 border-dashed border-slate-300 text-slate-400 cursor-pointer hover:bg-slate-200 transition-colors shadow-sm">
                <Camera size={36} className="mb-3 text-slate-400" />
                <span className="text-sm font-semibold text-slate-500">Ambil Foto Bukti</span>
             </div>
          </div>
        );
      case 'form_spp':
        return (
          <div className="space-y-4">
             <div className="mb-2">
               <p className="text-sm font-bold text-slate-800">Produk</p>
               <p className="text-[11px] text-slate-500 mt-1">Isi kesesuaian produk yang telah diterima pihak SPBU.</p>
             </div>
             
             <div className="border-2 border-emerald-500 rounded-xl p-4 bg-emerald-50 relative shadow-sm">
                <p className="text-[11px] text-slate-500 font-medium mb-1 tracking-wide">8143805561</p>
                <p className="font-bold text-[15px] text-slate-800 mb-3">PERTALITE 5.000 L</p>
                <span className="inline-block px-3 py-1 bg-emerald-200 text-emerald-800 text-[10px] font-black rounded-md uppercase tracking-wider">Sudah Terisi</span>
             </div>
             
             <div className="border-2 border-slate-200 rounded-xl p-4 bg-white cursor-pointer hover:border-amber-400 transition-colors shadow-sm">
                <p className="text-[11px] text-slate-500 font-medium mb-1 tracking-wide">8143574365</p>
                <p className="font-bold text-[15px] text-slate-800 mb-3">PERTALITE 5.000 L</p>
                <span className="inline-block px-3 py-1 bg-amber-100 text-amber-700 text-[10px] font-black rounded-md uppercase tracking-wider">Belum Terisi</span>
             </div>
          </div>
        );
      case 'form_ukur':
        return (
          <div className="bg-blue-50/80 rounded-xl p-4 border border-blue-100 flex items-start gap-3 shadow-sm">
            <AlertCircle className="text-blue-500 shrink-0 mt-0.5" size={20} />
            <div>
              <p className="text-sm font-bold text-blue-900 mb-1.5">Pilih Metode Pengukuran</p>
              <p className="text-xs text-blue-700/80 leading-relaxed mb-4 font-medium">Pilih antara IJKBOUT atau Flow Meter untuk mengisi form data LO.</p>
              <div className="flex gap-2.5">
                <button className="flex-1 py-2 bg-white border border-blue-200 rounded-lg text-[13px] font-bold text-blue-700 shadow-sm hover:bg-blue-50">IJKBOUT</button>
                <button className="flex-1 py-2 bg-white border border-blue-200 rounded-lg text-[13px] font-bold text-blue-700 shadow-sm hover:bg-blue-50">Flow Meter</button>
              </div>
            </div>
          </div>
        );
      case 'konfirmasi_lo':
        return (
          <div className="space-y-4">
            <p className="text-[13px] text-slate-500 font-medium">Tentukan status bongkar untuk LO yang dipilih</p>
            <div className="border border-slate-200 rounded-xl p-5 bg-white shadow-sm">
              <div className="flex text-[13px] mb-2"><span className="w-20 text-slate-400 font-medium">No. LO</span><span className="font-semibold text-slate-700">: 8143805561</span></div>
              <div className="flex text-[13px] mb-6"><span className="w-20 text-slate-400 font-medium">Order</span><span className="font-semibold text-slate-700">: PERTALITE 5.000 L</span></div>
              <div className="flex gap-3">
                 <button className="flex-1 py-3 border border-slate-200 rounded-xl text-[13px] font-bold text-slate-600 bg-slate-50 hover:bg-slate-100 transition-colors shadow-sm">Tidak Jadi</button>
                 <button className="flex-1 py-3 border border-blue-600 bg-blue-50 text-blue-700 rounded-xl text-[13px] font-bold hover:bg-blue-100 transition-colors shadow-sm">Sudah Dibongkar</button>
              </div>
            </div>
          </div>
        );
    }
  };

  return (
    <div className="pt-6 pb-28 flex flex-col min-h-full">
      {/* Progress Bar */}
      <div className="px-5 mb-6">
        <div className="flex items-center gap-1.5">
          {Array.from({ length: 15 }).map((_, i) => (
            <div key={i} className={`h-1.5 flex-1 rounded-full transition-colors ${i < currentStep ? 'bg-emerald-500' : 'bg-slate-200'}`} />
          ))}
          <span className="text-[11px] font-black text-slate-400 ml-2 tracking-wider w-10 text-right">{String(currentStep).padStart(2, '0')}/15</span>
        </div>
      </div>

      {/* Content Area */}
      <div className="flex-1 px-4 relative">
        <AnimatePresence mode="wait">
          <motion.div
            key={currentStep}
            initial={{ opacity: 0, x: 15 }}
            animate={{ opacity: 1, x: 0 }}
            exit={{ opacity: 0, x: -15 }}
            transition={{ duration: 0.2, ease: "easeOut" }}
            className="bg-white rounded-[1.25rem] p-6 shadow-sm border border-slate-100"
          >
            <h2 className="text-[16px] font-bold text-slate-800 leading-snug mb-6">
              {stepData.text} <span className="text-red-500 ml-0.5">*</span>
            </h2>
            {renderContent()}
          </motion.div>
        </AnimatePresence>
      </div>

      {/* Bottom Navigation */}
      <div className="absolute bottom-0 left-0 right-0 p-4 bg-white/90 backdrop-blur-md border-t border-slate-100 flex gap-3 z-30 rounded-b-[2.5rem]">
        <button 
          onClick={handlePrev}
          disabled={currentStep === 1}
          className={`px-5 py-3.5 rounded-xl font-bold text-[14px] border transition-colors ${currentStep === 1 ? 'border-slate-100 text-slate-300 bg-slate-50' : 'border-slate-200 text-slate-600 hover:bg-slate-50 bg-white shadow-sm'}`}
        >
          Sebelumnya
        </button>
        <button 
          onClick={handleNext}
          className="flex-1 py-3.5 rounded-xl font-bold text-[15px] bg-blue-600 text-white hover:bg-blue-700 shadow-md shadow-blue-200 transition-all active:scale-[0.98]"
        >
          {currentStep === 15 ? 'Kirim Checklist' : 'Selanjutnya'}
        </button>
      </div>
    </div>
  );
}
