<x-modal name="document-upload-modal"
         title="Upload Document"
         sub="Contract, KYC, invoice, proposal or delivery proof">
    <form wire:submit="save" novalidate>
        <div class="form-grid">
            <label class="f full">
                <span>Customer<span class="text-red-500"> *</span></span>
                <select wire:model.live="customer_id"
                        class="i @error('customer_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Customer ***</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                    @endforeach
                </select>
                @error('customer_id')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f full">
                <span>Project (optional)</span>
                <select wire:model.live="project_id"
                        class="i @error('project_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** No Project ***</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                    @endforeach
                </select>
                @error('project_id')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Category<span class="text-red-500"> *</span></span>
                <select wire:model.blur="category"
                        class="i @error('category') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Category ***</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>
                @error('category')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Title<span class="text-red-500"> *</span></span>
                <input wire:model.blur="title"
                       class="i @error('title') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Signed agreement">
                @error('title')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div x-data="documentUploadHandler()" class="mt-4">
            <label class="text-[11px] font-bold text-gray-500 uppercase block mb-1">
                File Attachment <span class="text-red-500"> *</span>
            </label>

            <!-- Hidden native file input with accept filter -->
            <input type="file"
                   x-ref="fileInput"
                   wire:model="file"
                   @change="handleFileSelect($event)"
                   accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.doc,.docx,.xls,.xlsx,.csv,.txt,.ppt,.pptx,.zip"
                   class="hidden"/>

            <!-- Drag & Drop Zone -->
            <div
                class="relative rounded-2xl border-2 border-dashed transition-all p-5 text-center cursor-pointer select-none @error('file') border-red-400! bg-red-50/30! @enderror"
                :class="{
                     'border-[#0b3d91] bg-blue-50/50 scale-[1.01]': isDropping,
                     'border-red-400 bg-red-50/30': fileError,
                     'border-[#cbd5e1] hover:border-[#0b3d91] hover:bg-slate-50/60 bg-white': !isDropping && !fileError
                 }"
                @dragover.prevent="isDropping = true"
                @dragleave.prevent="isDropping = false"
                @drop.prevent="handleDrop($event)"
                @click="$refs.fileInput.click()">

                <!-- State 1: Uploading in progress -->
                <div wire:loading wire:target="file" class="py-3">
                    <div
                        class="w-10 h-10 mx-auto rounded-full bg-blue-100 text-[#0b3d91] flex items-center justify-center mb-2 animate-pulse">
                        <svg viewBox="0 0 24 24" class="w-5 h-5 animate-spin">
                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3" fill="none"
                                    class="opacity-25"/>
                            <path fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                                  class="opacity-75"/>
                        </svg>
                    </div>
                    <p class="text-sm font-bold text-[#0b3d91]">Uploading attachment...</p>
                    <p class="text-xs text-[#8894ac] mt-1">Please wait while the file is processed</p>
                </div>

                <!-- State 2: File is selected / attached -->
                <div wire:loading.remove wire:target="file">
                    @if($file)
                        <div
                            class="py-1 flex items-center justify-between gap-3 text-left bg-blue-50/70 border border-blue-200/60 rounded-xl p-3"
                            @click.stop>
                            <div class="flex items-center gap-3 min-w-0">
                                <div
                                    class="w-10 h-10 rounded-lg bg-[#0b3d91] text-white flex items-center justify-center shrink-0">
                                    <svg viewBox="0 0 24 24" class="w-5 h-5 fill-none stroke-current stroke-2">
                                        <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                                        <polyline points="14 2 14 8 20 8"/>
                                    </svg>
                                </div>
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-[#0d1b34] truncate">
                                        {{ is_object($file) && method_exists($file, 'getClientOriginalName') ? $file->getClientOriginalName() : 'File attached' }}
                                    </div>
                                    <div class="text-xs text-[#8894ac]">
                                        {{ is_object($file) && method_exists($file, 'getSize') ? round($file->getSize() / 1024) . ' KB' : '' }}
                                        · <span class="text-emerald-600 font-semibold">Ready to upload</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <button type="button"
                                        @click.stop="$refs.fileInput.click()"
                                        class="text-xs font-semibold text-[#0b3d91] hover:underline px-2 py-1">
                                    Change
                                </button>
                                <button type="button"
                                        @click.stop="clearFile()"
                                        class="w-7 h-7 rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 flex items-center justify-center transition"
                                        title="Remove file">
                                    <svg viewBox="0 0 24 24" class="w-4 h-4 fill-none stroke-current stroke-2">
                                        <path d="M18 6L6 18M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @else
                        <!-- State 3: Empty Dropzone ready for input -->
                        <div class="py-2">
                            <div
                                class="w-12 h-12 mx-auto rounded-2xl bg-blue-50 text-[#0b3d91] flex items-center justify-center mb-3">
                                <svg viewBox="0 0 24 24" class="w-6 h-6 fill-none stroke-current stroke-2">
                                    <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                    <polyline points="17 8 12 3 7 8"/>
                                    <line x1="12" y1="3" x2="12" y2="15"/>
                                </svg>
                            </div>

                            <div class="text-sm text-[#0d1b34] font-bold">
                                <span>Drag & drop your file here, or</span>
                                <span class="text-[#0b3d91] underline decoration-[#0b3d91]/30 ml-1">browse</span>
                            </div>

                            <div class="text-[11.5px] text-[#8894ac] mt-1.5 space-y-0.5">
                                <div>Supported: <span class="font-semibold text-gray-600">PDF, DOC, DOCX, XLS, XLSX, CSV, TXT, PPT, Images, ZIP</span>
                                </div>
                                <div>Maximum file size: <span class="font-semibold text-gray-600">10 MB</span></div>
                            </div>
                        </div>
                    @endif

                    <!-- File error message displayed inside the dropzone -->
                    <div x-show="fileError"
                         x-cloak
                         class="mt-3 p-2.5 bg-red-100/70 border border-red-200 rounded-xl text-left flex items-start gap-2 text-xs text-red-700">
                        <svg viewBox="0 0 24 24"
                             class="w-4 h-4 text-red-500! shrink-0 mt-0.5 fill-none stroke-current stroke-2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span x-text="fileError" class="font-medium"></span>
                    </div>

                    @error('file')
                    <div
                        class="mt-3 p-2.5 bg-red-100/70 border border-red-200 rounded-xl text-left flex items-start gap-2 text-xs text-red-700">
                        <svg viewBox="0 0 24 24"
                             class="w-4 h-4 text-red-500! shrink-0 mt-0.5 fill-none stroke-current stroke-2">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                        <span class="font-medium">{{ $message }}</span>
                    </div>
                    @enderror
                </div>
            </div>
        </div>

        <script>
            window.documentUploadHandler = function () {
                return {
                    isDropping: false,
                    fileError: '',
                    fileName: '',
                    fileSize: '',
                    allowedExts: ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'txt', 'ppt', 'pptx', 'zip'],
                    maxBytes: 10 * 1024 * 1024,
                    validateFile(file) {
                        this.fileError = '';
                        if (!file) return false;

                        const nameParts = file.name.split('.');
                        const ext = nameParts.length > 1 ? nameParts.pop().toLowerCase() : '';
                        if (!this.allowedExts.includes(ext)) {
                            this.fileError = 'The file "' + file.name + '" is not allowed. Supported formats: PDF, DOC, XLS, CSV, TXT, PPT, Images, ZIP.';
                            return false;
                        }

                        if (file.size > this.maxBytes) {
                            const mb = (file.size / (1024 * 1024)).toFixed(1);
                            this.fileError = 'File size (' + mb + ' MB) exceeds the 10 MB limit.';
                            return false;
                        }

                        this.fileName = file.name;
                        this.fileSize = file.size > 1024 * 1024
                            ? (file.size / (1024 * 1024)).toFixed(1) + ' MB'
                            : Math.round(file.size / 1024) + ' KB';
                        return true;
                    },
                    handleDrop(e) {
                        this.isDropping = false;
                        const files = e.dataTransfer.files;
                        if (files.length > 0) {
                            const file = files[0];
                            if (this.validateFile(file)) {
                                this.$wire.upload('file', file, () => {
                                    this.fileError = '';
                                }, () => {
                                    this.fileError = 'Failed to upload attachment. Please check the file.';
                                });
                            }
                        }
                    },
                    handleFileSelect(e) {
                        const files = e.target.files;
                        if (files.length > 0) {
                            const file = files[0];
                            if (!this.validateFile(file)) {
                                e.target.value = '';
                            }
                        }
                    },
                    clearFile() {
                        this.fileName = '';
                        this.fileSize = '';
                        this.fileError = '';
                        if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                        this.$wire.set('file', null);
                    }
                };
            }
        </script>

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled">Upload</button>
        </div>
    </form>
</x-modal>
