{{-- Toast stack (Alpine.js).

    Show a toast from any Alpine scope:
        showToast('success', 'Berhasil', 'Data berhasil disimpan')
        showToast('error', 'Gagal', 'Terjadi kesalahan')
        showToast('warning', 'Perhatian', 'Stok hampir habis')
        showToast('info', 'Info', 'Event baru telah dibuat')
--}}
<div class="toast" x-data="toastStack" x-init="window.showToast = (type, title, message) => pushToast(type, title, message)" aria-live="polite" aria-atomic="true">
    <template x-for="toast in toasts" :key="toast.id">
        <div class="toast-item" :class="{
            'toast-success': toast.type === 'success',
            'toast-error': toast.type === 'error',
            'toast-warning': toast.type === 'warning',
            'toast-info': toast.type === 'info',
        }" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-x-4" x-transition:enter-end="opacity-100 translate-x-0" x-transition:leave="transition ease-in duration-300" x-transition:leave-start="opacity-100 translate-x-0" x-transition:leave-end="opacity-0 translate-x-4">
            <span class="mt-0.5 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-current/10">
                <svg x-show="toast.type === 'success'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                <svg x-show="toast.type === 'error'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                <svg x-show="toast.type === 'warning'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-1.5 6.75h3m-6.75 0a2.25 2.25 0 01-2.25-2.25V8.25L11.25 2.25H18a2.25 2.25 0 012.25 2.25v12a2.25 2.25 0 01-2.25 2.25H5.25z"/></svg>
                <svg x-show="toast.type === 'info'" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
            </span>
            <div class="flex-1">
                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100" x-text="toast.title"></p>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400" x-text="toast.message"></p>
            </div>
            <button type="button" class="shrink-0 rounded-lg p-1 text-slate-400 transition-colors hover:bg-slate-100 dark:text-slate-500 dark:hover:bg-slate-700/60" @click="toasts = toasts.filter(t => t !== toast)" aria-label="Dismiss notification">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </template>
</div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('toastStack', () => ({
            toasts: [],
            nextId: 0,
            pushToast(type, title, message) {
                const toast = { id: ++this.nextId, type, title, message };
                this.toasts.push(toast);
                setTimeout(() => { this.toasts = this.toasts.filter(t => t !== toast); }, 4000);
            },
        }));
    });
</script>
