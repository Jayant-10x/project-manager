<x-modal name="document-upload-modal"
         title="Upload Document"
         sub="Contract, KYC, invoice, proposal or delivery proof">
    <form wire:submit="upload" novalidate>
        <div class="form-grid">
            <label class="f full">
                <span>Customer *</span>
                <select wire:model.live="customer_id"
                        class="i @error('customer_id') !border-red-500 !bg-red-50/20 @enderror">
                    <option value="">— Select Customer —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->company }}</option>
                    @endforeach
                </select>
                @error('customer_id')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f full">
                <span>Project (optional)</span>
                <select wire:model.live="project_id"
                        class="i @error('project_id') !border-red-500 !bg-red-50/20 @enderror">
                    <option value="">— no project —</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                    @endforeach
                </select>
                @error('project_id')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Category *</span>
                <select wire:model.blur="category"
                        class="i @error('category') !border-red-500 !bg-red-50/20 @enderror">
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
                @error('category')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Title *</span>
                <input wire:model.blur="title"
                       class="i @error('title') !border-red-500 !bg-red-50/20 @enderror"
                       placeholder="e.g. Signed agreement">
                @error('title')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div class="mt-4">
            <label class="text-[11px] font-bold text-gray-400 uppercase block mb-1">File Attachment (Max 10 MB)
                *</label>
            <input type="file"
                   wire:model="file"
                   class="i text-sm @error('file') !border-red-500 !bg-red-50/20 @enderror">
            @error('file')
            <span class="text-xs text-red-500 font-medium block mt-1">{{ $message }}</span>
            @enderror
        </div>

        <div wire:loading wire:target="file" class="text-xs text-[#0b3d91] font-semibold mt-2">
            Scanning and uploading attachment...
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="confirmClose()" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Upload</button>
        </div>
    </form>
</x-modal>
