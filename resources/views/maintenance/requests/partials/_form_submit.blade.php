<div class="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-6 mt-6 border-t border-slate-200">
    <a href="{{ route('maintenance.requests.index') }}"
        class="inline-flex items-center justify-center gap-1.5 h-11 sm:h-9 px-6 sm:px-3 rounded border border-slate-200 bg-white
            text-[14px] sm:text-[13px] font-bold text-slate-700 hover:bg-slate-50 transition-all">
        <span class="material-symbols-outlined text-[18px] sm:text-[17px]">close</span>
        ยกเลิก
    </a>
    <button type="submit" form="main-form"
        class="inline-flex items-center justify-center overflow-hidden rounded bg-emerald-600 text-[14px] sm:text-[13px] font-bold text-white hover:bg-emerald-700 focus:outline-none focus:ring-2 focus:ring-emerald-200 transition-all active:scale-95 group h-11 sm:h-auto">
        <span
            class="hidden sm:flex px-2.5 py-2 bg-black/10 items-center justify-center text-white/90 group-hover:text-white border-r border-white/10 h-full">
            <span class="material-symbols-outlined text-[17px]">{{ $isEdit ? 'save' : 'send' }}</span>
        </span>
        <span class="px-6 py-2 leading-none flex items-center gap-2">
            <span class="sm:hidden material-symbols-outlined text-[18px]">{{ $isEdit ? 'save' : 'send' }}</span>
            {{ $isEdit ? 'บันทึกการแก้ไข' : 'ส่งใบแจ้งซ่อมบำรุง' }}
        </span>
    </button>
</div>
