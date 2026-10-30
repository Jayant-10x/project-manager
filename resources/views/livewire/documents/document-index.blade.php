<div>
    <!-- Header with Document Count & Upload Trigger -->
    <div class="page-head">
        <span class="info">{{ $documents->total() }} document{{ $documents->total() === 1 ? '' : 's' }}</span>
        <span class="spacer"></span>
        <button type="button" class="btn btn-primary btn-sm"
                @click="Livewire.dispatch('open-document-upload-modal')">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M12 19V5M5 12l7-7 7 7"/>
            </svg>
            Upload document
        </button>
    </div>

    <!-- Filterbar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="filterbar">
                <div class="gsearch" style="width:250px">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" placeholder="Title, file, customer...">
                </div>

                <select wire:model.live="customer_id" class="i" style="width:auto; max-width: 180px;">
                    <option value="">All customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                    @endforeach
                </select>

                @if($projects->isNotEmpty())
                    <select wire:model.live="project_id" class="i" style="width:auto; max-width: 180px;">
                        <option value="">All projects</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->code ?? $p->project_code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                @endif

                <select wire:model.live="category" class="i" style="width:auto">
                    <option value="">All categories</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat }}">{{ $cat }}</option>
                    @endforeach
                </select>

                @if($search !== '' || $category !== '' || $customer_id !== '' || $project_id !== '')
                    <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearFilters">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Documents List -->
    <div class="card">
        <div class="card-body p-0">
            @forelse($documents as $doc)
                @php
                    $ext = strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION) ?: 'DOC');
                    $catColors = [
                        'Contract'       => '#0b3d91',
                        'KYC/GST'        => '#5aa236',
                        'Invoice'        => '#e0a13a',
                        'Proposal'       => '#7a5af8',
                        'Quotation'      => '#2e75b6',
                        'Delivery Proof' => '#5aa236',
                        'AMC'            => '#d94b4b',
                        'Other'          => '#8894ac',
                    ];
                    $col = $catColors[$doc->category] ?? '#8894ac';
                    $fileUrl = file_exists(public_path('uploads/' . $doc->stored_as))
                        ? asset('uploads/' . $doc->stored_as)
                        : asset('storage/' . $doc->stored_as);
                @endphp
                <div
                    class="doc flex items-center gap-3 p-3.5 border-b last:border-b-0 border-[#eef2f9] hover:bg-slate-50 transition">
                    <span class="doc-ico flex-shrink-0" style="background: {{ $col }}1a; color: {{ $col }}">
                        {{ substr($ext, 0, 4) }}
                    </span>

                    <div style="flex:1;min-width:0">
                        <div class="doc-n font-bold text-sm text-[#0d1b34]">{{ $doc->title }}</div>
                        <div class="doc-m text-xs text-[#8894ac] mt-0.5">
                            {{ $doc->file_name }} · {{ $doc->size_kb }} KB
                            @if($doc->customer)
                                · {{ $doc->customer->display_name }}
                            @endif
                            @if($doc->project)
                                / {{ $doc->project->code }}
                            @endif
                            @if($doc->uploadedBy)
                                · {{ $doc->uploadedBy->name }}
                            @endif
                            · {{ $doc->created_at->format('d M H:i') }}
                        </div>
                    </div>

                    <span class="pill flex-shrink-0">{{ $doc->category }}</span>

                    <div class="row-actions flex items-center justify-end gap-1.5 flex-shrink-0">
                        <button type="button"
                                class="btn btn-outline-primary btn-xs"
                                @click="Livewire.dispatch('open-document-preview', {
                                    data: {
                                        title: '{{ addslashes($doc->title) }}',
                                        name: '{{ addslashes($doc->file_name) }}',
                                        sizeKb: '{{ $doc->size_kb }}',
                                        category: '{{ $doc->category }}',
                                        url: '{{ $fileUrl }}',
                                        downloadUrl: '{{ route('documents.download', $doc->id, absolute: false) }}',
                                        mime: '{{ $doc->mime }}'
                                    }
                                })"
                                title="Preview document">
                            <svg viewBox="0 0 24 24" class="w-3 h-3">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>

                        <a class="btn btn-outline-secondary btn-xs" href="{{ route('documents.download', $doc->id, absolute: false) }}" download="{{ $doc->file_name }}"
                           title="Download document">
                            <svg viewBox="0 0 24 24" class="w-3 h-3">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                            </svg>
                        </a>

                        <button type="button"
                                class="btn btn-outline-danger btn-xs"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Remove Document?',
                                    message: 'Are you sure you want to remove {{ addslashes($doc->file_name) }}? The file will be permanently deleted.',
                                    confirmText: 'Remove Document',
                                    cancelText: 'Cancel',
                                    danger: true,
                                    wire: $wire,
                                    action: 'deleteDocument',
                                    params: {{ $doc->id }}
                                })"
                                title="Remove document">
                            <svg viewBox="0 0 24 24" class="w-3 h-3">
                                <path
                                    d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="empty">
                     <span class="flex items-center justify-center">
                        <svg viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            <path d="M14 2v6h6"/>
                        </svg>
                     </span>
                    <p>No documents yet</p>
                    <div class="sm">Upload a contract, KYC, invoice or delivery proof</div>
                </div>
            @endforelse
        </div>
        <div class="p-4 border-t border-[#e2e8f3]">{{ $documents->links() }}</div>
    </div>

    <!-- Modals -->
    <livewire:documents.document-upload-modal/>
    <livewire:documents.document-preview-modal/>
</div>
