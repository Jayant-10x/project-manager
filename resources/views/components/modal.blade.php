@props(['name', 'show' => false, 'focusable' => false, 'title' => null, 'sub' => null, 'wide' => false, 'size' => null])

@php
    $maxWidthClass = match($size) {
        'sm' => 'max-w-xl',
        'md' => 'max-w-2xl',
        'lg' => 'max-w-4xl',
        'xl', 'wide' => 'max-w-5xl',
        '2xl', 'full' => 'max-w-6xl',
        default => ($wide ? 'max-w-5xl' : 'max-w-3xl')
    };
@endphp

<div wire:ignore.self
     x-data="{
        show: @js($show),
        isDirty: false,
        confirmClose() {
            if (window._confirmModalActive) return;
            if (this.isDirty) {
                window.dispatchEvent(new CustomEvent('confirm-dialog', {
                    detail: {
                        title: 'Discard Unsaved Changes?',
                        message: 'You have unsaved changes in this form. Are you sure you want to discard them?',
                        confirmText: 'Discard Changes',
                        cancelText: 'Keep Editing',
                        type: 'warning',
                        danger: true,
                        onConfirm: () => {
                            this.forceClose();
                        }
                    }
                }));
            } else {
                this.forceClose();
            }
        },
        forceClose() {
            this.show = false;
            this.isDirty = false;
            window.ModalManager?.close('{{ $name }}');
        }
     }"
     x-init="
        $watch('show', value => {
            if (value) {
                window.ModalManager?.open('{{ $name }}');
            } else {
                window.ModalManager?.close('{{ $name }}');
            }
        });
     "
     x-show="typeof show !== 'undefined' && show"
     x-on:open-modal.window="
        const d = $event.detail;
        if (d === '{{ $name }}' || d?.name === '{{ $name }}' || (Array.isArray(d) && d[0] === '{{ $name }}') || d?.[0] === '{{ $name }}') {
            if (typeof show !== 'undefined') {
                show = true;
            } else if (this._x_dataStack && this._x_dataStack[0]) {
                this._x_dataStack[0].show = true;
            }
            if (typeof isDirty !== 'undefined') {
                isDirty = false;
            } else if (this._x_dataStack && this._x_dataStack[0]) {
                this._x_dataStack[0].isDirty = false;
            }
            window.ModalManager?.open('{{ $name }}');
        }
     "
     x-on:close-modal.window="
        const d = $event.detail;
        if (!d || d === '{{ $name }}' || d?.name === '{{ $name }}' || (Array.isArray(d) && d[0] === '{{ $name }}') || d?.[0] === '{{ $name }}') {
            if (typeof forceClose === 'function') {
                forceClose();
            } else if (this._x_dataStack && typeof this._x_dataStack[0]?.forceClose === 'function') {
                this._x_dataStack[0].forceClose();
            }
        }
     "
     x-on:close.stop="if (typeof forceClose === 'function') { forceClose(); } else if (this._x_dataStack && typeof this._x_dataStack[0]?.forceClose === 'function') { this._x_dataStack[0].forceClose(); }"
     x-on:keydown.escape.window="if (typeof show !== 'undefined' && show && !window._confirmModalActive) confirmClose()"
     data-modal="{{ $name }}"
     @click.self="confirmClose()"
     style="display: none; background-color: rgba(8, 20, 42, 0.60); backdrop-filter: blur(4px); -webkit-backdrop-filter: blur(4px);"
     class="overlay fixed inset-0 flex items-start justify-center p-6 z-[120] overflow-y-auto overscroll-contain">

    <div
        class="modal bg-white rounded-2xl w-full {{ $maxWidthClass }} shadow-2xl overflow-hidden my-auto"
        @click.outside="if (typeof show !== 'undefined' && show && !window._confirmModalActive && !$event.target.closest('[data-confirm-modal]')) confirmClose()"
        @input="isDirty = true"
        @change="isDirty = true">

        <div class="modal-head flex items-center justify-between p-5 border-b border-[#eef2f9]">
            <div>
                @if($title)
                    <h3 class="text-base font-bold text-[#0d1b34]">{{ $title }}</h3>
                    @if($sub)
                        <p class="text-xs text-[#8894ac] mt-0.5">{{ $sub }}</p>
                    @endif
                @endif
            </div>
            <button type="button" @click="confirmClose()"
                    class="x ml-auto"
                    title="Close modal" aria-label="Close modal">
                ✕
            </button>
        </div>

        <div class="modal-body p-6 max-h-[82vh] overflow-y-auto overscroll-contain">
            {{ $slot }}
        </div>
    </div>
</div>
