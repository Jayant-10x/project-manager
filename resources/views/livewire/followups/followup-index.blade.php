<div>
    <!-- Page Header Summary & Log Action -->
    <div class="page-head mb-4">
        <span class="info text-sm text-[#8894ac]">
            {{ $totalCount }} {{ \Illuminate\Support\Str::plural('follow-up', $totalCount) }}
            @if($dueList->count() > 0)
                · <b style="color:#d94b4b">{{ $dueList->count() }} pending</b>
            @endif
        </span>
        <span class="spacer"></span>
        <button type="button" class="btn btn-primary btn-sm" @click="Livewire.dispatch('open-followup-modal')">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Log Follow-up
        </button>
    </div>

    <!-- Pending / Overdue Follow-ups Notice Banner -->
    @if($dueList->isNotEmpty())
        <div class="notice warn mb-4">
            <svg viewBox="0 0 24 24" class="w-4 h-4 flex-shrink-0 mt-0.5">
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 8v5M12 16.5v.5"/>
            </svg>
            <div class="text-xs">
                <b>{{ $dueList->count() }} follow-ups are pending.</b><br>
                @foreach($dueList->take(4) as $d)
                    <div>{{ $d->customer?->display_name }} — {{ $d->next_note ?: $d->summary }}
                        ({{ $d->next_date?->format('d M') }})
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Filterbar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="filterbar">
                <div class="gsearch" style="width:230px">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" placeholder="Summary, detail, customer...">
                </div>

                <select wire:model.live="customer_id" class="i" style="width:auto; max-width: 180px;">
                    <option value="">All customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="type" class="i" style="width:auto">
                    <option value="">All types</option>
                    <option value="Call">Call</option>
                    <option value="Email">Email</option>
                    <option value="Meeting">Meeting</option>
                    <option value="WhatsApp">WhatsApp</option>
                    <option value="Note">Note</option>
                </select>

                <div class="flex items-center gap-1.5 text-xs text-[#8894ac]">
                    <span>From</span>
                    <input type="date" wire:model.live="date_from" class="i" style="width:auto; padding: 4px 8px; font-size: 12px;">
                    <span>To</span>
                    <input type="date" wire:model.live="date_to" class="i" style="width:auto; padding: 4px 8px; font-size: 12px;">
                </div>

                @if($search !== '' || $type !== '' || $customer_id !== '' || $date_from !== '' || $date_to !== '')
                    <button type="button" class="btn btn-ghost btn-sm" wire:click="clearFilters">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Follow-ups History Card -->
    <div class="card">
        <div class="card-body p-0">
            @forelse($followups as $followup)
                @php
                    $kindColors = [
                        'Call'     => '#0b3d91',
                        'Email'    => '#2e75b6',
                        'Meeting'  => '#7a5af8',
                        'WhatsApp' => '#5aa236',
                        'Note'     => '#8894ac',
                    ];
                    $badgeColor = $kindColors[$followup->type] ?? '#8894ac';
                    $isOverdue = $followup->next_date && $followup->next_date->toDateString() < $today;
                @endphp
                <div
                    class="fu flex items-start gap-3 p-3.5 border-b last:border-b-0 border-[#eef2f9] hover:bg-slate-50 transition">
                    <span class="fu-ico flex-shrink-0" style="background: {{ $badgeColor }}1a; color: {{ $badgeColor }}">
                        @if($followup->type === 'Meeting')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle
                                    cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/></svg>
                        @elseif($followup->type === 'Email')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><rect x="2" y="4" width="20" height="16" rx="2"/><path
                                    d="M22 6l-10 7L2 6"/></svg>
                        @elseif($followup->type === 'WhatsApp')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        @elseif($followup->type === 'Note')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path
                                    d="M14 2v6h6"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 1.9.7 2.8a2 2 0 01-.5 2.1L8.1 9.9a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.4c.9.3 1.8.6 2.8.7a2 2 0 011.7 2z"/></svg>
                        @endif
                    </span>

                    <div style="flex:1;min-width:0">
                        <div class="fu-s font-semibold text-sm text-[#0d1b34]">{{ $followup->summary }}</div>
                        @if($followup->detail)
                            <div class="fu-t text-xs text-gray-600 mt-1 leading-relaxed">{{ $followup->detail }}</div>
                        @endif
                        <div class="fu-t text-xs text-[#8894ac] mt-1">
                            @if($followup->customer)
                                <a href="{{ route('customers.show', $followup->customer_id) }}" wire:navigate class="link">
                                    {{ $followup->customer->display_name }}
                                </a>
                            @endif
                            @if($followup->project)
                                · {{ $followup->project->code }}
                            @endif
                            · {{ $followup->type }} · {{ $followup->happened_on->format('d M H:i') }}
                            @if($followup->createdBy)
                                · {{ $followup->createdBy->name }}
                            @endif
                        </div>

                        @if($followup->next_date)
                            <div class="mt-2">
                                <span class="badge {{ $isOverdue ? 'b-red' : 'b-navy' }}">
                                    Next: {{ $followup->next_date->format('d M Y') }}
                                    @if($followup->next_note)
                                        — {{ $followup->next_note }}
                                    @endif
                                </span>
                            </div>
                        @endif
                    </div>

                    <button type="button"
                            class="mini-btn danger flex-shrink-0"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Remove Follow-up?',
                                message: 'Are you sure you want to remove this follow-up entry?',
                                confirmText: 'Remove Follow-up',
                                cancelText: 'Cancel',
                                danger: true,
                                wire: $wire,
                                action: 'deleteFollowup',
                                params: {{ $followup->id }}
                            })"
                            title="Remove follow-up">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path
                                d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                        </svg>
                    </button>
                </div>
            @empty
                <div class="empty">
                     <span class="flex items-center justify-center">
                    <svg viewBox="0 0 24 24">
                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                    </svg>
                     </span>
                    <p>No follow-ups yet</p>
                    <div class="sm">Talk to a customer and log it</div>
                </div>
            @endforelse
        </div>
        <div class="p-4 border-t border-[#e2e8f3]">{{ $followups->links() }}</div>
    </div>
</div>
