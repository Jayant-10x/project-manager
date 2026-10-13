<x-modal name="document-preview-modal"
         :title="$title ?: 'Document Preview'"
         :sub="$name ? ($name . ' · ' . $sizeKb . ' KB · ' . $category) : null"
         :wide="true">
    <div class="preview rounded-xl overflow-hidden bg-[#0d1b34] flex items-center justify-center min-h-85">
        @if($isImage)
            <img src="{{ $url }}" alt="{{ $name }}" class="max-h-[70vh] w-auto object-contain">
        @elseif($isPdf)
            <iframe src="{{ $url }}" class="w-full h-[70vh] border-0 bg-white"></iframe>
        @else
            <div class="text-center p-10 text-[#c3cee5]">
                <svg viewBox="0 0 24 24" class="w-12 h-12 mx-auto mb-3 opacity-60">
                    <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                    <path d="M14 2v6h6"/>
                </svg>
                <div class="text-base font-semibold">This file type cannot be previewed directly</div>
                <div class="text-xs text-gray-400 mt-1">Download the file to view its contents</div>
            </div>
        @endif
    </div>

    <div class="flex gap-3 mt-4 justify-end">
        @if($url)
            <a class="btn btn-primary btn-sm" href="{{ $url }}" target="_blank" rel="noopener">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                    <circle cx="12" cy="12" r="3"/>
                </svg>
                Open in new tab
            </a>
            <a class="btn btn-ghost btn-sm" href="{{ $url }}" download>
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                </svg>
                Download
            </a>
        @endif
        <button type="button" @click="$dispatch('close-modal', 'document-preview-modal')" class="btn btn-ghost btn-sm">
            Close
        </button>
    </div>
</x-modal>
