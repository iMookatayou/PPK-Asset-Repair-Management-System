<div x-data="{
    open: false,
    title: '',
    message: '',
    confirmText: 'ตกลง',
    cancelText: 'ยกเลิก',
    variant: 'primary',
    resolve: null,
    
    show(options) {
        this.title = options.title || 'ยืนยันการทำรายการ';
        this.message = options.message || '';
        this.confirmText = options.confirmText || 'ตกลง';
        this.cancelText = options.cancelText || 'ยกเลิก';
        this.variant = options.variant || 'primary';
        this.open = true;
        return new Promise((resolve) => {
            this.resolve = resolve;
        });
    },
    
    confirm() {
        this.open = false;
        if (this.resolve) this.resolve(true);
    },
    
    cancel() {
        this.open = false;
        if (this.resolve) this.resolve(false);
    }
}" 
x-init="window.ConfirmDialog = $data"
x-show="open" 
class="fixed inset-0 z-[100000] flex items-center justify-center p-4 bg-slate-900/40 backdrop-blur-sm"
x-cloak
style="display: none;"
>
    <script>
        window.Confirm = {
            show(options) {
                if (window.ConfirmDialog) {
                    return window.ConfirmDialog.show(options);
                }
                // Fallback to native confirm if component not ready
                console.warn('ConfirmDialog not ready, falling back to native confirm');
                return Promise.resolve(confirm(options.message || 'ยืนยัน?'));
            }
        };
    </script>
    <div 
        x-show="open"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="w-full max-w-md bg-white rounded-2xl overflow-hidden border border-slate-200"
        @click.away="cancel()"
        @keydown.escape.window="cancel()"
    >
        <div class="p-6">
            <div class="flex items-start gap-4 mb-2">
                <div 
                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full"
                    :class="{
                        'bg-blue-100 text-blue-600': variant === 'primary',
                        'bg-rose-100 text-rose-600': variant === 'danger',
                        'bg-amber-100 text-amber-600': variant === 'warning',
                        'bg-emerald-100 text-emerald-600': variant === 'success'
                    }"
                >
                    <template x-if="variant === 'primary'"><span class="material-symbols-outlined text-[28px]">help</span></template>
                    <template x-if="variant === 'danger'"><span class="material-symbols-outlined text-[28px]">report</span></template>
                    <template x-if="variant === 'warning'"><span class="material-symbols-outlined text-[28px]">warning</span></template>
                    <template x-if="variant === 'success'"><span class="material-symbols-outlined text-[28px]">check_circle</span></template>
                </div>
                <div class="pt-1">
                    <h3 class="text-lg font-bold text-slate-900 leading-tight" x-text="title"></h3>
                    <div class="mt-2 text-[14.5px] text-slate-600 leading-relaxed" x-text="message"></div>
                </div>
            </div>
        </div>
        
        <div class="bg-slate-50/80 px-6 py-4 flex flex-col-reverse sm:flex-row sm:justify-end gap-3">
            <button 
                @click="cancel()"
                type="button" 
                class="inline-flex justify-center rounded-xl px-4 py-2.5 text-[14px] font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-200 transition-all duration-200"
                x-text="cancelText"
            ></button>
            <button 
                @click="confirm()"
                type="button" 
                class="inline-flex justify-center rounded-xl px-6 py-2.5 text-[14px] font-semibold text-white transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2"
                :class="{
                    'bg-[#0F2D5C] hover:bg-[#133A73] focus:ring-[#0F2D5C]/50': variant === 'primary',
                    'bg-rose-600 hover:bg-rose-700 focus:ring-rose-500/50': variant === 'danger',
                    'bg-amber-500 hover:bg-amber-600 focus:ring-amber-500/50': variant === 'warning',
                    'bg-emerald-600 hover:bg-emerald-700 focus:ring-emerald-500/50': variant === 'success'
                }"
                x-text="confirmText"
            ></button>
        </div>
    </div>
</div>
