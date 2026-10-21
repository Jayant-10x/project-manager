<div x-data="{
        show: false,
        title: 'Confirm Action',
        message: 'Are you sure you want to proceed?',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        type: 'danger', // 'danger' | 'warning' | 'info' | 'logout'
        callback: null,
        targetLivewire: null,
        targetAction: null,
        targetParams: [],
        open(options) {
            window._confirmModalActive = true;
            this.title = options.title || 'Confirm Action';
            this.message = options.message || 'Are you sure you want to proceed?';
            this.confirmText = options.confirmText || 'Confirm';
            this.cancelText = options.cancelText || 'Cancel';
            this.type = options.type || (options.danger !== false ? 'danger' : 'info');
            this.callback = typeof options.onConfirm === 'function' ? options.onConfirm : null;
            this.targetLivewire = options.wire || null;
            this.targetAction = options.action || null;
            this.targetParams = Array.isArray(options.params) ? options.params : (options.params !== undefined ? [options.params] : []);
            this.show = true;
        },
        confirm() {
            window._confirmModalActive = false;
            const cb = this.callback;
            const wire = this.targetLivewire;
            const action = this.targetAction;
            const params = this.targetParams;

            this.show = false;
            this.callback = null;
            this.targetLivewire = null;
            this.targetAction = null;
            this.targetParams = [];

            if (cb) {
                cb();
            } else if (wire && action) {
                wire.call(action, ...params);
            }
        },
        cancel() {
            this.show = false;
            this.callback = null;
            this.targetLivewire = null;
            this.targetAction = null;
            this.targetParams = [];
            setTimeout(() => {
                window._confirmModalActive = false;
            }, 100);
        }
    }"
    data-confirm-modal
    x-on:confirm-dialog.window="open($event.detail)"
    x-show="show"
    x-on:keydown.escape.window="if (show) { cancel(); $event.stopPropagation(); }"
    style="display: none; background-color: rgba(8, 20, 42, 0.65); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"
    class="fixed inset-0 flex items-center justify-center p-4 sm:p-6 z-[9999] overflow-y-auto"
    x-transition:enter="ease-out duration-150"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="ease-in duration-100"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0">

    <div class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden my-auto border border-[#e2e8f3] text-left transform"
         @click.outside="cancel()"
         x-transition:enter="ease-out duration-150"
         x-transition:enter-start="opacity-0 translate-y-3 scale-95"
         x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="ease-in duration-100"
         x-transition:leave-start="opacity-100 translate-y-0 scale-100"
         x-transition:leave-end="opacity-0 translate-y-3 scale-95">

        <!-- Modal Header / Content Block -->
        <div class="p-6">
            <div class="flex items-start gap-4">
                <!-- Icon Plate: Danger, Warning, Logout, Info -->
                <div class="w-12 h-12 rounded-xl flex items-center justify-center flex-shrink-0"
                     :class="{
                         'bg-red-50 text-red-600 border border-red-100': type === 'danger',
                         'bg-amber-50 text-amber-600 border border-amber-100': type === 'warning',
                         'bg-purple-50 text-purple-600 border border-purple-100': type === 'logout',
                         'bg-blue-50 text-[#0b3d91] border border-blue-100': type === 'info'
                     }">

                    <!-- Danger Trash/Cross Icon -->
                    <template x-if="type === 'danger'">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 stroke-current fill-none stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                    </template>

                    <!-- Warning Triangle Icon (Unsaved changes / caution) -->
                    <template x-if="type === 'warning'">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 stroke-current fill-none stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </template>

                    <!-- Logout / Sign-out Icon -->
                    <template x-if="type === 'logout'">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 stroke-current fill-none stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </template>

                    <!-- Info Check/Bell Icon -->
                    <template x-if="type === 'info'">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 stroke-current fill-none stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </template>
                </div>

                <div class="flex-1 min-w-0 pt-0.5">
                    <h3 class="text-base font-bold text-[#0d1b34] tracking-tight" x-text="title"></h3>
                    <p class="text-xs text-[#64748b] mt-1.5 leading-relaxed" x-text="message"></p>
                </div>
            </div>
        </div>

        <!-- Modal Footer Actions with Clean Spacing -->
        <div class="px-6 py-4 bg-[#f8fafc] border-t border-[#eef2f9] flex items-center justify-end gap-3">
            <button type="button"
                    @click="cancel()"
                    class="btn btn-ghost btn-sm px-4 py-2 text-xs font-semibold"
                    x-text="cancelText">
            </button>
            <button type="button"
                    @click="confirm()"
                    class="btn btn-sm px-4 py-2 text-xs font-semibold shadow-sm transition"
                    :class="{
                        'btn-danger !bg-red-600 !text-white hover:!bg-red-700': type === 'danger',
                        'btn-danger !bg-amber-600 !text-white hover:!bg-amber-700': type === 'warning',
                        'btn-primary !bg-purple-600 !text-white hover:!bg-purple-700': type === 'logout',
                        'btn-primary !bg-[#0b3d91] !text-white hover:!bg-[#082a63]': type === 'info'
                    }"
                    x-text="confirmText">
            </button>
        </div>
    </div>
</div>
