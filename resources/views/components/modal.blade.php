@props(['name', 'show' => false, 'focusable' => false, 'title' => null, 'sub' => null, 'wide' => false])

<div x-data="{
        show: @js($show),
        isDirty: false,
        confirmClose() {
            if (this.isDirty) {
                if (confirm('You have unsaved changes. Are you sure you want to discard them?')) {
                    this.forceClose();
                }
            } else {
                this.forceClose();
            }
        },
        forceClose() {
            this.show = false;
            this.isDirty = false;
        }
     }"
     x-show="show"
     x-on:open-modal.window="
        const d = $event.detail;
        if (d === '{{ $name }}' || d?.name === '{{ $name }}' || (Array.isArray(d) && d[0] === '{{ $name }}') || d?.[0] === '{{ $name }}') {
            show = true;
            isDirty = false;
        }
     "
     x-on:close-modal.window="
        const d = $event.detail;
        if (!d || d === '{{ $name }}' || d?.name === '{{ $name }}' || (Array.isArray(d) && d[0] === '{{ $name }}') || d?.[0] === '{{ $name }}') {
            forceClose();
        }
     "
     x-on:close.stop="forceClose()"
     x-on:keydown.escape.window="if (show) confirmClose()"
     style="display: none;"
     class="overlay fixed inset-0 bg-[#08142a]/60 backdrop-blur-sm flex items-start justify-center p-6 z-50 overflow-y-auto">

    <div
        class="modal bg-white rounded-2xl w-full {{ $wide ? 'max-w-4xl' : 'max-w-2xl' }} shadow-2xl overflow-hidden my-auto"
        @click.outside="confirmClose()"
        @input="isDirty = true"
        @change="isDirty = true">

        @if($title)
        <div class="modal-head flex items-center justify-between p-5 border-b border-[#eef2f9]">
            <div>
                <h3 class="text-base font-bold text-[#0d1b34]">{{ $title }}</h3>
                @if($sub)
                    <p class="text-xs text-[#8894ac] mt-0.5">{{ $sub }}</p>
                @endif
            </div>
            <button type="button" @click="confirmClose()"
                    class="w-9 h-9 flex items-center justify-center rounded-lg text-[#8894ac] hover:text-[#0d1b34] hover:bg-slate-100 text-lg font-bold transition"
                    title="Close modal" aria-label="Close modal">
                ✕
            </button>
        </div>
        @endif

        <div class="modal-body p-6 max-h-[75vh] overflow-y-auto">
            {{ $slot }}
        </div>
    </div>
</div>
