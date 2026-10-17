<x-modal name="project-schedule-modal"
         :title="'Payment Schedule — ' . $projectCode"
         sub="Project value: ₹{{ number_format($projectValue, 2) }} · Fixed date, milestone linked or recurring"
         :wide="true">
    <form wire:submit="save" novalidate>
        <div class="notice mb-4 {{ abs($diff) < 0.01 ? '' : 'warn' }}">
            <svg viewBox="0 0 24 24" class="w-4 h-4">
                <path d="M12 8v5M12 16.5v.5"/>
            </svg>
            <div>
                Schedule total <b>
                    <x-money :value="$total"/>
                </b> · Project value <b>
                    <x-money :value="$projectValue"/>
                </b>
                @if(abs($diff) < 0.01)
                    — <span class="text-green-600 font-bold">Matched</span>
                @else
                    — <span class="text-amber-700 font-bold"><x-money :value="abs($diff)"/> {{ $diff > 0 ? 'short' : 'over' }}</span>
                @endif
            </div>
        </div>

        @error('terms')
        <span class="text-xs text-red-500! font-medium block mb-2">{{ $message }}</span>
        @enderror

        <div class="space-y-3">
            @foreach($terms as $i => $t)
                <div wire:key="term-row-{{ $i }}-{{ $t['id'] ?? 'new' }}"
                     class="term {{ $t['locked'] ? 'paid opacity-90' : '' }} p-3 bg-slate-50 border rounded-xl @error('terms.'.$i.'.*') border-red-300 @enderror">
                    <div class="flex items-start gap-2">
                        <div class="term-no mt-6 text-xs font-bold text-gray-500 w-5 text-center">{{ $i + 1 }}</div>

                        <div class="flex-1 grid grid-cols-1 md:grid-cols-5 gap-2">
                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Label<span
                                        class="text-red-500"> *</span></label>
                                <input wire:model.blur="terms.{{ $i }}.label"
                                       class="i text-sm @error('terms.'.$i.'.label') border-red-500! bg-red-50/20! @enderror"
                                       placeholder="e.g. Advance"
                                    {{ $t['locked'] ? 'disabled' : '' }}>
                                @error('terms.'.$i.'.label')
                                <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Amount (₹)
                                    <span class="text-red-500"> *</span></label>
                                <input type="number" step="0.01" min="0.01"
                                       wire:model.live.debounce.400ms="terms.{{ $i }}.amount"
                                       class="i text-sm @error('terms.'.$i.'.amount') border-red-500! bg-red-50/20! @enderror"
                                       placeholder="0.00"
                                    {{ $t['locked'] ? 'disabled' : '' }}>
                                @error('terms.'.$i.'.amount')
                                <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Trigger Mode
                                    <span class="text-red-500"> *</span></label>
                                <select wire:model.live="terms.{{ $i }}.trigger"
                                        class="i text-sm @error('terms.'.$i.'.trigger') border-red-500! bg-red-50/20! @enderror"
                                    {{ $t['locked'] ? 'disabled' : '' }}>
                                    <option value="fixed">Fixed date</option>
                                    <option value="milestone">Milestone linked</option>
                                    <option value="recurring">Recurring</option>
                                </select>
                                @error('terms.'.$i.'.trigger')
                                <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">
                                    Due Date {{ ($t['trigger'] ?? '') === 'fixed' ? '*' : '' }}
                                </label>
                                <input type="date"
                                       wire:model.blur="terms.{{ $i }}.dueDate"
                                       class="i text-sm @error('terms.'.$i.'.dueDate') border-red-500! bg-red-50/20! @enderror"
                                    {{ $t['locked'] ? 'disabled' : '' }}>
                                @error('terms.'.$i.'.dueDate')
                                <span class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                @if(($t['trigger'] ?? '') === 'milestone')
                                    <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Milestone
                                        <span class="text-red-500"> *</span></label>
                                    <select wire:model.blur="terms.{{ $i }}.milestoneId"
                                            class="i text-sm @error('terms.'.$i.'.milestoneId') border-red-500! bg-red-50/20! @enderror"
                                        {{ $t['locked'] ? 'disabled' : '' }}>
                                        <option value="">— Select Milestone —</option>
                                        @foreach($availableMilestones as $avm)
                                            <option value="{{ $avm['id'] }}">{{ $avm['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('terms.'.$i.'.milestoneId')
                                    <span
                                        class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                    @enderror
                                @else
                                    <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Grace
                                        Days</label>
                                    <input type="number" min="0" max="180"
                                           wire:model.blur="terms.{{ $i }}.graceDays"
                                           class="i text-sm @error('terms.'.$i.'.graceDays') border-red-500! bg-red-50/20! @enderror"
                                        {{ $t['locked'] ? 'disabled' : '' }}>
                                    @error('terms.'.$i.'.graceDays')
                                    <span
                                        class="text-[11px] text-red-500! font-medium mt-0.5 block">{{ $message }}</span>
                                    @enderror
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 flex items-center">
                            @if($t['locked'])
                                <span class="badge b-green text-xs"
                                      title="Installment has payments logged">Locked</span>
                            @else
                                <button type="button"
                                        class="mini-btn danger"
                                        wire:click="removeRow({{ $i }})"
                                        title="Remove installment">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                        <path
                                            d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                    </svg>
                                </button>
                            @endif
                        </div>
                    </div>

                    @error("terms.{$i}.locked")
                    <span class="text-xs text-red-500! font-medium mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            @endforeach
        </div>

        <div class="flex gap-2 mt-4">
            <button type="button" class="btn btn-ghost btn-sm" wire:click="addRow">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add installment
            </button>
            <button type="button" class="btn btn-ghost btn-sm" wire:click="autoSplit">
                Auto-split (30/40/30)
            </button>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="confirmClose()" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">Save schedule</button>
        </div>
    </form>
</x-modal>
