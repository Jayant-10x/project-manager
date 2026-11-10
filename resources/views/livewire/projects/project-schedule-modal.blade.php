<x-modal name="project-schedule-modal"
         :title="'Payment Schedule — ' . $projectCode"
         sub="Project value: ₹{{ number_format($projectValue, 2) }} · Fixed date, milestone linked or recurring"
         :wide="true">
    <form wire:submit="save" novalidate>
        <div class="notice mb-4 {{ abs($diff) < 0.01 ? '' : ($diff < -0.01 ? '' : 'warn') }}">
            <svg viewBox="0 0 24 24" class="w-4 h-4">
                @if(abs($diff) < 0.01)
                    <polyline points="20 6 9 17 4 12"/>
                @elseif($diff < -0.01)
                    <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                @else
                    <path d="M12 8v5M12 16.5v.5"/>
                @endif
            </svg>
            <div class="flex-1 flex items-center justify-between gap-3 flex-wrap">
                <div>
                    <div>
                        Schedule total <b><x-money :value="$total"/></b> &middot; Project value <b><x-money :value="$projectValue"/></b>
                        @if(($totalReceived ?? 0) > 0)
                            &middot; Received <b class="text-emerald-700 font-semibold"><x-money :value="$totalReceived"/></b>
                            &middot; Pending Due <b class="text-amber-800 font-semibold"><x-money :value="$totalPending"/></b>
                        @endif
                    </div>
                    @if($diff < -0.01)
                        <div class="text-[11px] text-[#5a6a85] mt-0.5">
                            Includes <x-money :value="abs($diff)"/> in additional / recurring installments beyond the project value
                        </div>
                    @elseif($diff > 0.01)
                        <div class="text-[11px] text-[#96660f] mt-0.5">
                            <x-money :value="abs($diff)"/> of project value is not yet allocated to installments
                        </div>
                    @endif
                </div>
                <div>
                    @if(abs($diff) < 0.01)
                        <span class="badge b-green">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Matched (100% scheduled)
                        </span>
                    @elseif($diff < -0.01)
                        <span class="badge b-navy">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                            <x-money :value="abs($diff)"/> Extra / Recurring Overage
                        </span>
                    @else
                        <span class="badge b-amber">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="9"/><path d="M12 8v5M12 16.5v.5"/></svg>
                            <x-money :value="abs($diff)"/> Unscheduled Balance
                        </span>
                    @endif
                </div>
            </div>
        </div>

        @error('terms')
        <span class="text-xs text-red-500! font-medium block mb-2">{{ $message }}</span>
        @enderror

        @php
            $nonRecurringTerms = [];
            $recurringTermsList = [];
            foreach ($terms as $termIndex => $termItem) {
                if (($termItem['trigger'] ?? '') === 'recurring') {
                    $recurringTermsList[$termIndex] = $termItem;
                } else {
                    $nonRecurringTerms[$termIndex] = $termItem;
                }
            }
            $nonRecurringSubtotal = (float) array_sum(array_map(fn($termItem) => is_numeric($termItem['amount'] ?? null) ? (float)$termItem['amount'] : 0, $nonRecurringTerms));
            $recurringSubtotal = (float) array_sum(array_map(fn($termItem) => is_numeric($termItem['amount'] ?? null) ? (float)$termItem['amount'] : 0, $recurringTermsList));
            $hasLockedNonRecurringTerms = ! empty(array_filter($terms, fn ($termItem) => ! empty($termItem['locked']) && ($termItem['trigger'] ?? '') !== 'recurring'));
            $hasRecurringTerms = ! empty(array_filter($terms, fn ($termItem) => ($termItem['trigger'] ?? '') === 'recurring'));
        @endphp

        <div x-data="{ openNonRecurring: true, openRecurring: true }" class="space-y-4">
            {{-- Non-Recurring Section Collapse --}}
            <div class="border border-slate-200/90 rounded-xl overflow-hidden bg-white shadow-2xs">
                <div class="w-full flex items-center justify-between px-4 py-2.5 bg-slate-100/90 border-b border-slate-200/80 select-none">
                    <button type="button"
                            @click="openNonRecurring = !openNonRecurring"
                            class="flex items-center gap-2.5 text-left cursor-pointer group flex-1">
                        <svg class="w-4 h-4 text-slate-500 transition-transform duration-200"
                             :class="{ 'rotate-180': !openNonRecurring }"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span class="font-semibold text-xs uppercase tracking-wider text-slate-700 group-hover:text-slate-900">
                            Non-Recurring Installments
                        </span>
                        <span class="badge b-navy text-xs">
                            {{ count($nonRecurringTerms) }} {{ count($nonRecurringTerms) === 1 ? 'installment' : 'installments' }}
                        </span>
                    </button>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-600">
                            Subtotal: <b class="text-slate-900 font-semibold"><x-money :value="$nonRecurringSubtotal"/></b>
                        </span>
                    </div>
                </div>

                <div x-show="openNonRecurring" class="p-3.5 space-y-3 bg-slate-50/50">
                    @forelse($nonRecurringTerms as $termIndex => $termItem)
                        <div wire:key="term-row-{{ $termIndex }}-{{ $termItem['id'] ?? 'new' }}"
                             class="term flex-wrap {{ $termItem['locked'] ? 'paid opacity-90' : '' }} p-3 bg-white border border-slate-200/80 rounded-xl @error('terms.'.$termIndex.'.*') border-red-300 @enderror">
                            <div class="flex items-start gap-2">
                                <div class="term-no mt-6 text-xs font-bold text-gray-500 w-6 text-center">#{{ $loop->iteration }}</div>

                                <div class="flex-1 grid grid-cols-1 md:grid-cols-5 gap-2">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Label<span
                                                class="text-red-500"> *</span></label>
                                        <input wire:model.blur="terms.{{ $termIndex }}.label"
                                               class="i text-sm @error('terms.'.$termIndex.'.label') border-red-500! bg-red-50/20! @enderror"
                                               placeholder="e.g. Advance"
                                            {{ $termItem['locked'] ? 'disabled' : '' }}>
                                        @error('terms.'.$termIndex.'.label')
                                        <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Amount (₹)
                                            <span class="text-red-500"> *</span></label>
                                        <input type="number" step="0.01" min="0.01"
                                               wire:model.live.debounce.400ms="terms.{{ $termIndex }}.amount"
                                               class="i text-sm @error('terms.'.$termIndex.'.amount') border-red-500! bg-red-50/20! @enderror"
                                               placeholder="0.00"
                                            {{ $termItem['locked'] ? 'disabled' : '' }}>
                                        @error('terms.'.$termIndex.'.amount')
                                        <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Trigger Mode
                                            <span class="text-red-500"> *</span></label>
                                        <select wire:model.live="terms.{{ $termIndex }}.trigger"
                                                class="i text-sm @error('terms.'.$termIndex.'.trigger') border-red-500! bg-red-50/20! @enderror"
                                            {{ $termItem['locked'] ? 'disabled' : '' }}>
                                            <option value="">*** Select Trigger Mode ***</option>
                                            <option value="fixed">Fixed date</option>
                                            <option value="milestone">Milestone linked</option>
                                            <option value="recurring">Recurring</option>
                                        </select>
                                        @error('terms.'.$termIndex.'.trigger')
                                        <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">
                                            Due Date <span class="text-red-500"> {{ ($termItem['trigger'] ?? '') === 'fixed' ? '*' : '' }}</span>
                                        </label>
                                        <input type="date"
                                               wire:model.blur="terms.{{ $termIndex }}.dueDate"
                                               class="i text-sm @error('terms.'.$termIndex.'.dueDate') border-red-500! bg-red-50/20! @enderror"
                                            {{ $termItem['locked'] ? 'disabled' : '' }}>
                                        @error('terms.'.$termIndex.'.dueDate')
                                        <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        @if(($termItem['trigger'] ?? '') === 'milestone')
                                            <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Milestone
                                                <span class="text-red-500"> *</span></label>
                                            <select wire:model.blur="terms.{{ $termIndex }}.milestoneId"
                                                    class="i text-sm @error('terms.'.$termIndex.'.milestoneId') border-red-500! bg-red-50/20! @enderror"
                                                {{ $termItem['locked'] ? 'disabled' : '' }}>
                                                <option value="">*** Select Milestone ***</option>
                                                @foreach($availableMilestones as $milestoneOption)
                                                    <option value="{{ $milestoneOption['id'] }}">{{ $milestoneOption['name'] }}</option>
                                                @endforeach
                                            </select>
                                            @error('terms.'.$termIndex.'.milestoneId')
                                            <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                            @enderror
                                        @else
                                            <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Grace Days</label>
                                            <input type="number" min="0" max="180"
                                                   wire:model.blur="terms.{{ $termIndex }}.graceDays"
                                                   class="i text-sm @error('terms.'.$termIndex.'.graceDays') border-red-500! bg-red-50/20! @enderror"
                                                {{ $termItem['locked'] ? 'disabled' : '' }}>
                                            @error('terms.'.$termIndex.'.graceDays')
                                            <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                            @enderror
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center">
                                    @if($termItem['locked'])
                                        <span class="badge b-green text-xs"
                                              title="Installment has payments logged">
                                            <svg viewBox="0 0 24 24" class="w-3 h-3 fill-none stroke-current stroke-2">
                                                <rect x="5" y="11" width="14" height="10" rx="2"/>
                                                <path d="M8 11V7a4 4 0 018 0v4"/>
                                            </svg>
                                        </span>
                                    @elseif(count($terms) > 1)
                                        <button type="button"
                                                class="btn btn-outline-danger btn-xs"
                                                wire:click="removeRow({{ $termIndex }})"
                                                title="Remove installment">
                                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                                <path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if(!empty($termItem['locked']))
                                <div class="mt-2.5 pt-2 border-t border-slate-200/80 flex items-center justify-between text-xs flex-wrap gap-2 text-slate-700">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200/90 px-2 py-0.5 rounded-md shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                                            </svg>
                                            Locked Installment
                                        </span>
                                        <span class="text-slate-300">&middot;</span>
                                        <span class="text-[11px] text-slate-600">
                                            Total: <b class="text-slate-900 font-semibold"><x-money :value="$termItem['amount']"/></b>
                                        </span>
                                        <span class="text-slate-300">&middot;</span>
                                        <span class="text-[11px] text-slate-600">
                                            Received: <b class="text-emerald-700 font-semibold"><x-money :value="$termItem['received'] ?? 0"/></b>
                                        </span>
                                        @if(!empty($termItem['tds']) && $termItem['tds'] > 0)
                                            <span class="text-slate-300">&middot;</span>
                                            <span class="text-[11px] text-slate-600">
                                                TDS: <b class="font-semibold text-slate-900"><x-money :value="$termItem['tds']"/></b>
                                            </span>
                                        @endif
                                        <span class="text-slate-300">&middot;</span>
                                        @if(($termItem['due'] ?? 0) > 0.01)
                                            <span class="text-[11px] text-amber-800">
                                                Pending Due: <b class="font-semibold text-amber-900"><x-money :value="$termItem['due']"/></b>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700">
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                Fully Collected
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if(($termItem['status'] ?? '') === 'Paid' || ($termItem['due'] ?? 0) <= 0.01)
                                            <span class="badge b-green text-xs font-semibold">Paid</span>
                                        @elseif(($termItem['received'] ?? 0) > 0)
                                            <span class="badge b-amber text-xs font-semibold">Paid in partial ({{ round((($termItem['received']+$termItem['tds'] ?? 0) / max(0.01, $termItem['amount'])) * 100) }}%)</span>
                                        @else
                                            <span class="badge b-navy text-xs font-semibold">{{ $termItem['status'] ?? 'Pending' }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @error("terms.{$termIndex}.locked")
                            <span class="text-xs text-red-500! font-medium mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-slate-500 italic bg-white rounded-lg border border-dashed border-slate-200">
                            No non-recurring installments. Click "Add installment" or use the split buttons below.
                        </div>
                    @endforelse
                        <div class="flex gap-2 mt-4 flex-wrap items-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addRow">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                Add installment
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="split5050" title="{{ $hasLockedNonRecurringTerms ? 'Preserve locked non-recurring & recurring payments and split remaining balance 50-50' : 'Split project value 50-50 (recurring payments preserved)' }}">
                                50-50 Split{{ $hasLockedNonRecurringTerms ? ' (Remaining)' : '' }}
                            </button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="autoSplit" title="{{ $hasLockedNonRecurringTerms ? 'Preserve locked non-recurring & recurring payments and split remaining balance 30/40/30' : 'Split project value 30/40/30 (recurring payments preserved)' }}">
                                Auto-split (30/40/30){{ $hasLockedNonRecurringTerms ? ' (Remaining)' : '' }}
                            </button>
                            @if($hasLockedNonRecurringTerms || $hasRecurringTerms)
                                <span class="text-[11px] text-gray-500 italic">
                    ({{ $hasLockedNonRecurringTerms ? 'Locked non-recurring and ' : '' }}recurring payments are safely preserved; only uncollected non-recurring balance is split)
                </span>
                            @endif
                        </div>
                </div>
            </div>

            {{-- Recurring Section Collapse --}}
            <div class="border border-slate-200/90 rounded-xl overflow-hidden bg-white shadow-2xs">
                <div class="w-full flex items-center justify-between px-4 py-2.5 bg-slate-100/90 border-b border-slate-200/80 select-none">
                    <button type="button"
                            @click="openRecurring = !openRecurring"
                            class="flex items-center gap-2.5 text-left cursor-pointer group flex-1">
                        <svg class="w-4 h-4 text-slate-500 transition-transform duration-200"
                             :class="{ 'rotate-180': !openRecurring }"
                             fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                        <span class="font-semibold text-xs uppercase tracking-wider text-slate-700 group-hover:text-slate-900">
                            Recurring Payments
                        </span>
                        <span class="badge b-navy text-xs">
                            {{ count($recurringTermsList) }} {{ count($recurringTermsList) === 1 ? 'payment' : 'payments' }}
                        </span>
                    </button>
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-600">
                            Subtotal: <b class="text-slate-900 font-semibold"><x-money :value="$recurringSubtotal"/></b>
                        </span>
                    </div>
                </div>

                <div x-show="openRecurring" class="p-3.5 space-y-3 bg-slate-50/50">
                    @forelse($recurringTermsList as $termIndex => $termItem)
                        <div wire:key="term-row-{{ $termIndex }}-{{ $termItem['id'] ?? 'new' }}"
                             class="term flex-wrap {{ $termItem['locked'] ? 'paid opacity-90' : '' }} p-3 bg-white border border-slate-200/80 rounded-xl @error('terms.'.$termIndex.'.*') border-red-300 @enderror">
                            <div class="flex items-start gap-2">
                                <div class="term-no mt-6 text-xs font-bold text-gray-500 w-6 text-center">#{{ $loop->iteration }}</div>

                                <div class="flex-1 grid grid-cols-1 md:grid-cols-5 gap-2">
                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Label<span
                                                class="text-red-500"> *</span></label>
                                        <input wire:model.blur="terms.{{ $termIndex }}.label"
                                               class="i text-sm @error('terms.'.$termIndex.'.label') border-red-500! bg-red-50/20! @enderror"
                                               placeholder="e.g. Monthly retainer"
                                            {{ $termItem['locked'] ? 'disabled' : '' }}>
                                        @error('terms.'.$termIndex.'.label')
                                        <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Amount (₹)
                                            <span class="text-red-500"> *</span></label>
                                        <input type="number" step="0.01" min="0.01"
                                               wire:model.live.debounce.400ms="terms.{{ $termIndex }}.amount"
                                               class="i text-sm @error('terms.'.$termIndex.'.amount') border-red-500! bg-red-50/20! @enderror"
                                               placeholder="0.00"
                                            {{ $termItem['locked'] ? 'disabled' : '' }}>
                                        @error('terms.'.$termIndex.'.amount')
                                        <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                        @enderror
                                        @if(!empty($termItem['locked']))
                                            <div class="flex items-center gap-1.5 mt-1 text-[11px] font-semibold flex-wrap">
                                                <span class="text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-1.5 py-0.5 rounded shadow-2xs">
                                                    Rec: <x-money :value="$termItem['received'] ?? 0"/>
                                                </span>
                                                @if(($termItem['due'] ?? 0) > 0.01)
                                                    <span class="text-amber-800 bg-amber-50 border border-amber-200/80 px-1.5 py-0.5 rounded shadow-2xs">
                                                        Due: <x-money :value="$termItem['due'] ?? 0"/>
                                                    </span>
                                                @else
                                                    <span class="text-emerald-700 bg-emerald-50 border border-emerald-200/80 px-1.5 py-0.5 rounded shadow-2xs">
                                                        Paid in full
                                                    </span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Trigger Mode
                                            <span class="text-red-500"> *</span></label>
                                        <select wire:model.live="terms.{{ $termIndex }}.trigger"
                                                class="i text-sm @error('terms.'.$termIndex.'.trigger') border-red-500! bg-red-50/20! @enderror"
                                            {{ $termItem['locked'] ? 'disabled' : '' }}>
                                            <option value="">*** Select Trigger Mode ***</option>
                                            <option value="fixed">Fixed date</option>
                                            <option value="milestone">Milestone linked</option>
                                            <option value="recurring">Recurring</option>
                                        </select>
                                        @error('terms.'.$termIndex.'.trigger')
                                        <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">
                                            Due Date <span class="text-red-500"> {{ ($termItem['trigger'] ?? '') === 'fixed' ? '*' : '' }}</span>
                                        </label>
                                        <input type="date"
                                               wire:model.blur="terms.{{ $termIndex }}.dueDate"
                                               class="i text-sm @error('terms.'.$termIndex.'.dueDate') border-red-500! bg-red-50/20! @enderror"
                                            {{ $termItem['locked'] ? 'disabled' : '' }}>
                                        @error('terms.'.$termIndex.'.dueDate')
                                        <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div>
                                        @if(($termItem['trigger'] ?? '') === 'milestone')
                                            <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Milestone
                                                <span class="text-red-500"> *</span></label>
                                            <select wire:model.blur="terms.{{ $termIndex }}.milestoneId"
                                                    class="i text-sm @error('terms.'.$termIndex.'.milestoneId') border-red-500! bg-red-50/20! @enderror"
                                                {{ $termItem['locked'] ? 'disabled' : '' }}>
                                                <option value="">*** Select Milestone ***</option>
                                                @foreach($availableMilestones as $milestoneOption)
                                                    <option value="{{ $milestoneOption['id'] }}">{{ $milestoneOption['name'] }}</option>
                                                @endforeach
                                            </select>
                                            @error('terms.'.$termIndex.'.milestoneId')
                                            <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                            @enderror
                                        @else
                                            <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Grace Days</label>
                                            <input type="number" min="0" max="180"
                                                   wire:model.blur="terms.{{ $termIndex }}.graceDays"
                                                   class="i text-sm @error('terms.'.$termIndex.'.graceDays') border-red-500! bg-red-50/20! @enderror"
                                                {{ $termItem['locked'] ? 'disabled' : '' }}>
                                            @error('terms.'.$termIndex.'.graceDays')
                                            <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                            @enderror
                                        @endif
                                    </div>
                                </div>

                                <div class="mt-6 flex items-center">
                                    @if($termItem['locked'])
                                        <span class="badge b-green text-xs"
                                              title="Installment has payments logged">
                                            <svg viewBox="0 0 24 24" class="w-3 h-3 fill-none stroke-current stroke-2">
                                                <rect x="5" y="11" width="14" height="10" rx="2"/>
                                                <path d="M8 11V7a4 4 0 018 0v4"/>
                                            </svg>
                                        </span>
                                    @elseif(count($terms) > 1)
                                        <button type="button"
                                                class="btn btn-outline-danger btn-xs"
                                                wire:click="removeRow({{ $termIndex }})"
                                                title="Remove installment">
                                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                                <path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            @if(!empty($termItem['locked']))
                                <div class="mt-2.5 pt-2 border-t border-slate-200/80 flex items-center justify-between text-xs flex-wrap gap-2 text-slate-700">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-800 bg-emerald-50 border border-emerald-200/90 px-2 py-0.5 rounded-md shadow-2xs">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                                            </svg>
                                            Locked Installment
                                        </span>
                                        <span class="text-slate-300">&middot;</span>
                                        <span class="text-[11px] text-slate-600">
                                            Total: <b class="text-slate-900 font-semibold"><x-money :value="$termItem['amount']"/></b>
                                        </span>
                                        <span class="text-slate-300">&middot;</span>
                                        <span class="text-[11px] text-slate-600">
                                            Received: <b class="text-emerald-700 font-semibold"><x-money :value="$termItem['received'] ?? 0"/></b>
                                        </span>
                                        @if(!empty($termItem['tds']) && $termItem['tds'] > 0)
                                            <span class="text-slate-300">&middot;</span>
                                            <span class="text-[11px] text-slate-600">
                                                TDS: <b class="font-semibold text-slate-900"><x-money :value="$termItem['tds']"/></b>
                                            </span>
                                        @endif
                                        <span class="text-slate-300">&middot;</span>
                                        @if(($termItem['due'] ?? 0) > 0.01)
                                            <span class="text-[11px] text-amber-800">
                                                Pending Due: <b class="font-semibold text-amber-900"><x-money :value="$termItem['due']"/></b>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-700">
                                                <svg class="w-3 h-3 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                                                Fully Collected
                                            </span>
                                        @endif
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if(($termItem['status'] ?? '') === 'Paid' || ($termItem['due'] ?? 0) <= 0.01)
                                            <span class="badge b-green text-xs font-semibold">Paid</span>
                                        @elseif(($termItem['received'] ?? 0) > 0)
                                            <span class="badge b-amber text-xs font-semibold">Paid in partial ({{ round((($termItem['received']+$termItem['tds'] ?? 0) / max(0.01, $termItem['amount'])) * 100) }}%)</span>
                                        @else
                                            <span class="badge b-navy text-xs font-semibold">{{ $termItem['status'] ?? 'Pending' }}</span>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            @error("terms.{$termIndex}.locked")
                            <span class="text-xs text-red-500! font-medium mt-1 block">{{ $message }}</span>
                            @enderror
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-slate-500 italic bg-white rounded-lg border border-dashed border-slate-200">
                            No recurring payments scheduled. Click "Add recurring installment" to add monthly retainers or recurring fees.
                        </div>
                    @endforelse

                        <div class="flex gap-2 mt-4 flex-wrap items-center">
                            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addRecurringRow">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                Add recurring installment
                            </button>
                        </div>
                </div>
            </div>
        </div>

        @error('terms')
            <div class="mt-2 text-xs text-red-500! font-medium">{{ $message }}</div>
        @enderror

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary min-w-40 justify-center" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save" class="inline-flex items-center justify-center gap-1.5">Save schedule</span>
                <span wire:loading.inline-flex wire:target="save" class="items-center justify-center gap-1.5">
                    <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Saving...</span>
                </span>
            </button>
        </div>
    </form>
</x-modal>
