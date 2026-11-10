<script>
    if (typeof window.toastManager !== 'function') {
        window.toastManager = function () {
            return {
                toasts: [],
                counter: 0,
                add(detail) {
                    const payload = Array.isArray(detail) ? detail[0] : (detail || {});
                    const msg = payload.msg || payload.message || (typeof payload === 'string' ? payload : '');
                    if (!msg) return;
                    const type = payload.type || payload.kind || 'ok';
                    const id = ++this.counter;
                    const toast = { id, msg, type };
                    this.toasts.push(toast);

                    setTimeout(() => {
                        this.remove(id);
                    }, payload.duration || 4500);
                },
                remove(id) {
                    this.toasts = this.toasts.filter(t => t.id !== id);
                }
            };
        };
    }

    if (window.Alpine) {
        window.Alpine.data('toastManager', window.toastManager);
    } else {
        document.addEventListener('alpine:init', () => {
            window.Alpine.data('toastManager', window.toastManager);
        });
    }
</script>

<div x-data="toastManager()"
     x-on:toast.window="add($event.detail)"
     class="toast-wrap"
     style="z-index: 9999;"
     aria-live="polite">
    <template x-for="item in toasts" :key="item.id">
        <div class="toast"
             :class="{
                 'ok': item.type === 'ok' || item.type === 'success',
                 'err': item.type === 'err' || item.type === 'error' || item.type === 'danger',
                 'warning': item.type === 'warning' || item.type === 'warn'
             }"
             role="alert"
             @click="remove(item.id)">
            <!-- Success Icon -->
            <template x-if="item.type === 'ok' || item.type === 'success'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M9 12l2 2 4-4"/>
                </svg>
            </template>

            <!-- Error Icon -->
            <template x-if="item.type === 'err' || item.type === 'error' || item.type === 'danger'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v5M12 16.5v.5"/>
                </svg>
            </template>

            <!-- Warning Icon -->
            <template x-if="item.type === 'warning' || item.type === 'warn'">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    <line x1="12" y1="9" x2="12" y2="13"/>
                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                </svg>
            </template>

            <!-- Text Content -->
            <div style="flex: 1; word-break: break-word; line-height: 1.45;" x-text="item.msg"></div>

            <!-- Close Action -->
            <button type="button"
                    @click.stop="remove(item.id)"
                    class="opacity-60 hover:opacity-100 transition text-current ml-2 -mr-1 shrink-0"
                    aria-label="Dismiss toast">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-none stroke-current stroke-2">
                    <path d="M18 6L6 18M6 6l12 12"/>
                </svg>
            </button>
        </div>
    </template>
</div>
