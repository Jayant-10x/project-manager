<div wire:ignore.self
     x-data="{
        show: false,
        title: 'Confirm Action',
        message: 'Are you sure you want to proceed?',
        confirmText: 'Confirm',
        cancelText: 'Cancel',
        type: 'danger', // 'danger' | 'warning' | 'info' | 'logout' | 'success'
        hasInput: false,
        inputPlaceholder: '',
        inputValue: '',
        inputRequired: false,
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
            this.hasInput = !!options.hasInput;
            this.inputPlaceholder = options.inputPlaceholder || '';
            this.inputValue = options.defaultInputValue || '';
            this.inputRequired = !!options.inputRequired;
            this.callback = typeof options.onConfirm === 'function' ? options.onConfirm : null;
            this.targetLivewire = options.wire || null;
            this.targetAction = options.action || null;
            const rawParams = options.params !== undefined ? options.params : options.args;
            this.targetParams = Array.isArray(rawParams) ? rawParams : (rawParams !== undefined ? [rawParams] : []);
            this.show = true;

            if (this.hasInput) {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.$refs.inputField?.focus();
                    }, 50);
                });
            } else {
                this.$nextTick(() => {
                    setTimeout(() => {
                        this.$refs.confirmBtn?.focus();
                    }, 50);
                });
            }
        },
        confirm() {
            if (this.hasInput && this.inputRequired && !this.inputValue.trim()) {
                return;
            }
            const cb = this.callback;
            const wire = this.targetLivewire;
            const action = this.targetAction;
            let params = [...this.targetParams];
            if (this.hasInput) {
                params.push(this.inputValue);
            }

            this.show = false;
            const currentInput = this.inputValue;
            this.callback = null;
            this.targetLivewire = null;
            this.targetAction = null;
            this.targetParams = [];
            this.hasInput = false;
            this.inputValue = '';
            window.ModalManager?.close('confirm-modal');
            setTimeout(() => {
                window._confirmModalActive = false;
            }, 300);

            if (cb) {
                cb(currentInput);
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
            this.hasInput = false;
            this.inputValue = '';
            window.ModalManager?.close('confirm-modal');
            setTimeout(() => {
                window._confirmModalActive = false;
            }, 300);
        }
    }"
     x-init="
        $watch('show', value => {
            if (value) {
                window.ModalManager?.open('confirm-modal');
            } else {
                window.ModalManager?.close('confirm-modal');
            }
        });
    "
     data-confirm-modal
     x-on:confirm-dialog.window="open($event.detail)"
     x-show="typeof show !== 'undefined' && show"
     x-on:keydown.escape.window="if (typeof show !== 'undefined' && show) { cancel(); $event.stopPropagation(); }"
     x-on:keydown.enter.window="if (typeof show !== 'undefined' && show && !hasInput) { confirm(); $event.stopPropagation(); }"
     style="display: none; background-color: rgba(8, 20, 42, 0.65); backdrop-filter: blur(8px); -webkit-backdrop-filter: blur(8px);"
     class="fixed inset-0 flex items-center justify-center p-4 sm:p-6 z-[9999] overflow-y-auto overscroll-contain"
     @click.self.stop="cancel()"
     x-transition:enter="ease-out duration-150"
     x-transition:enter-start="opacity-0"
     x-transition:enter-end="opacity-100"
     x-transition:leave="ease-in duration-100"
     x-transition:leave-start="opacity-100"
     x-transition:leave-end="opacity-0">

    <div
        class="bg-white rounded-2xl w-full max-w-md shadow-2xl overflow-hidden my-auto border border-[#e2e8f3] text-left transform"
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
                <!-- Icon Plate: Danger, Warning, Logout, Info, Success -->
                <div class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0 border"
                     :class="{
                         'bg-red-50 text-red-600 border-red-100': type === 'danger',
                         'bg-amber-50 text-amber-600 border-amber-100': type === 'warning',
                         'bg-purple-50 text-purple-600 border-purple-100': type === 'logout',
                         'bg-blue-50 text-[#0b3d91] border-blue-100': type === 'info',
                         'bg-emerald-50 text-emerald-600 border-emerald-100': type === 'success'
                     }"
                     :style="
                         type === 'danger' ? 'background-color: #fef2f2 !important; color: #dc2626 !important; border-color: #fee2e2 !important;' :
                         type === 'warning' ? 'background-color: #fffbeb !important; color: #d97706 !important; border-color: #fef3c7 !important;' :
                         type === 'success' ? 'background-color: #f0fdf4 !important; color: #16a34a !important; border-color: #dcfce7 !important;' :
                         type === 'logout' ? 'background-color: #f5f3ff !important; color: #7c3aed !important; border-color: #ede9fe !important;' :
                         'background-color: #eff6ff !important; color: #0b3d91 !important; border-color: #dbeafe !important;'
                     ">

                    <!-- Success Check Icon -->
                    <template x-if="type === 'success'">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 stroke-current fill-none stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>

                    <!-- Danger Trash/Cross Icon -->
                    <template x-if="type === 'danger'">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 stroke-current fill-none stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </template>

                    <!-- Warning Triangle Icon (Unsaved changes / caution) -->
                    <template x-if="type === 'warning'">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 stroke-current fill-none stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                        </svg>
                    </template>

                    <!-- Logout / Sign-out Icon -->
                    <template x-if="type === 'logout'">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 stroke-current fill-none stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </template>

                    <!-- Info Check/Bell Icon -->
                    <template x-if="type === 'info'">
                        <svg viewBox="0 0 24 24" class="w-6 h-6 stroke-current fill-none stroke-2">
                            <path stroke-linecap="round" stroke-linejoin="round"
                                  d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </template>
                </div>

                <div class="flex-1 min-w-0 pt-0.5">
                    <h3 class="text-base font-bold text-[#0d1b34] tracking-tight" x-text="title"></h3>
                    <p class="text-xs text-[#64748b] mt-1.5 leading-relaxed" x-text="message"></p>

                    <!-- Optional Text Input Block (e.g. Rejection reason or cancellation remark) -->
                    <template x-if="hasInput">
                        <div class="mt-3.5">
                            <textarea x-ref="inputField"
                                      x-model="inputValue"
                                      :placeholder="inputPlaceholder || 'Add remarks or reason (optional)...'"
                                      rows="3"
                                      @keydown.enter.ctrl="confirm()"
                                      class="w-full text-xs rounded-xl border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 p-2.5 text-slate-800 dark:text-slate-100 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-[#0b3d91]/20 focus:border-[#0b3d91] transition shadow-xs"></textarea>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Modal Footer Actions with Clean Spacing -->
        <div class="px-6 py-4 bg-[#f8fafc] border-t border-[#eef2f9] flex items-center justify-end gap-3">
            <button type="button"
                    @click.stop="cancel()"
                    class="btn btn-outline-secondary btn-sm px-4 py-2 text-xs font-semibold"
                    x-text="cancelText">
            </button>
            <button type="button"
                    x-ref="confirmBtn"
                    @click.stop="confirm()"
                    class="btn btn-sm px-4 py-2 text-xs font-semibold shadow-sm transition"
                    :class="{
                        'btn-danger': type === 'danger',
                        'btn-amber btn-warning': type === 'warning',
                        'btn-purple': type === 'logout',
                        'btn-primary btn-info': type === 'info',
                        'btn-green btn-success': type === 'success'
                    }"
                    :style="
                        type === 'danger' ? 'background: var(--red) !important; border-color: var(--red) !important; color: #fff !important; box-shadow: 0 4px 14px -4px rgba(217, 75, 75, .4);' :
                        type === 'warning' ? 'background: var(--amber) !important; border-color: var(--amber) !important; color: #fff !important; box-shadow: 0 4px 12px -4px rgba(224, 161, 58, .6);' :
                        type === 'success' ? 'background: var(--green) !important; border-color: var(--green) !important; color: #fff !important; box-shadow: 0 4px 14px -4px rgba(90, 162, 54, .5);' :
                        type === 'logout' ? 'background: #7c3aed !important; border-color: #7c3aed !important; color: #fff !important; box-shadow: 0 4px 14px -4px rgba(124, 58, 237, .5);' :
                        'background: var(--navy) !important; border-color: var(--navy) !important; color: #fff !important; box-shadow: 0 4px 14px -4px rgba(11, 61, 145, .5);'
                    "
                    x-text="confirmText">
            </button>
        </div>
    </div>
</div>
