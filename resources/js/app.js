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

if (window.Alpine) {
    window.Alpine.data('toastManager', window.toastManager);
} else {
    document.addEventListener('alpine:init', () => {
        window.Alpine.data('toastManager', window.toastManager);
    });
}
