<x-modal name="project-milestone-modal"
         :title="($hasExistingMilestones ? 'Edit Milestones' : 'Add Milestones') . ' — ' . $projectCode"
         sub="Build a timeline so the project stays on schedule" :wide="true">
    <form wire:submit="save">
        <div class="space-y-3">
            @foreach($items as $index => $milestoneItem)
                <div wire:key="ms-item-{{ $index }}-{{ $milestoneItem['id'] ?? 'new' }}"
                     class="ms-row p-3 border rounded-xl {{ !empty($milestoneItem['locked']) ? 'bg-amber-50/40 border-amber-200/80' : 'border-[#eef2f9] bg-slate-50/50' }}">
                    <div class="d-flex flex-wrap px-3" style="width: 100%;">
                        <div class="flex items-center gap-3">
                        <span class="ms-dot {{ $milestoneItem['done'] ? 'done' : '' }} shrink-0">
                            {{ $milestoneItem['done'] ? '✓' : ($index + 1) }}
                        </span>
                            <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
                                <div class="md:col-span-2">
                                    <div class="flex items-center justify-between mb-1">
                                        <label class="text-[10px] font-bold text-gray-400 uppercase block">Milestone
                                            Name
                                            <span class="text-red-500"> *</span></label>
                                        @if(!empty($milestoneItem['locked']))
                                            <span class="text-[10px] text-amber-700 font-semibold"
                                                  title="{{ $milestoneItem['lockedReason'] ?? 'Connected to payment schedule' }}">
                                            (Locked to Schedule)
                                        </span>
                                        @endif
                                    </div>
                                    <input wire:model="items.{{ $index }}.name"
                                           class="i text-sm @error('items.'.$index.'.name') border-red-500! bg-red-50/20! @enderror {{ !empty($milestoneItem['locked']) ? 'bg-gray-100 cursor-not-allowed text-gray-600' : '' }}"
                                           placeholder="Milestone description"
                                           title="{{ !empty($milestoneItem['locked']) ? ($milestoneItem['lockedReason'] ?? 'Connected to payment schedule') : '' }}"
                                        {{ !empty($milestoneItem['locked']) ? 'disabled' : '' }}>
                                    @error('items.'.$index.'.name')
                                    <span class="text-red-500!">{{$message}}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Due
                                        Date</label>
                                    <input type="date" wire:model="items.{{ $index }}.dueDate"
                                           class="i text-sm @error('items.'.$index.'.dueDate') border-red-500! bg-red-50/20! @enderror">
                                    @error('items.'.$index.'.dueDate')
                                    <span class="text-red-500!">{{$message}}</span>
                                    @enderror
                                </div>
                                <div>
                                    <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Status <span
                                            class="text-red-500"> *</span></label>
                                    <select wire:model="items.{{ $index }}.status"
                                            class="i text-sm @error('items.'.$index.'.status') border-red-500! bg-red-50/20! @enderror">
                                        <option value="">*** Select Status ***</option>
                                        <option value="Pending">Pending</option>
                                        <option value="In Progress">In Progress</option>
                                        <option value="Done">Done</option>
                                        <option value="Blocked">Blocked</option>
                                    </select>
                                    @error('items.'.$index.'.status')
                                    <span class="text-red-500!">{{$message}}</span>
                                    @enderror
                                </div>
                            </div>
                            <div class="flex items-center gap-1.5 shrink-0 justify-it">
                                @if(!empty($milestoneItem['locked']))
                                    <span class="badge b-amber text-xs shrink-0 inline-flex items-center gap-1"
                                          title="{{ $milestoneItem['lockedReason'] ?? 'Milestone is locked because it is connected to a payment schedule' }}">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3 fill-none stroke-current stroke-2">
                                        <rect x="5" y="11" width="14" height="10" rx="2"/>
                                        <path d="M8 11V7a4 4 0 018 0v4"/>
                                    </svg>
                                    <span>Locked</span>
                                </span>
                                @elseif(count($items) > 1)
                                    <button type="button" class="btn btn-outline-danger btn-xs"
                                            wire:click="removeRow({{ $index }})"
                                            title="Remove milestone" aria-label="Remove milestone">
                                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                            <path
                                                d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                        </svg>
                                        <span>Remove</span>
                                    </button>
                                @endif
                            </div>
                        </div>

                        @if(!empty($milestoneItem['connectedTerms']))
                            <div
                                class="mt-2.5 pt-2 border-t border-amber-200/60 flex items-center justify-between text-xs flex-wrap gap-2 text-slate-700">
                                <div class="flex items-center gap-2 flex-wrap">
                                <span
                                    class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-900 px-2 py-0.5 rounded-md">
                                    <svg class="w-3.5 h-3.5 text-amber-700" viewBox="0 0 24 24" fill="none"
                                         stroke="currentColor" stroke-width="2">
                                        <rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22"
                                                                                               y2="10"/>
                                    </svg>
                                    Connected Scheduled Payment(s):
                                </span>
                                    @foreach($milestoneItem['connectedTerms'] as $connectedTerm)
                                        <span
                                            class="inline-flex items-center gap-1.5 text-xs bg-white border border-amber-200/90 px-2.5 py-1 rounded-md shadow-2xs">
                                        <span
                                            class="font-semibold text-slate-800">#{{ $connectedTerm['seq'] }} {{ $connectedTerm['label'] }}</span>
                                        <span class="text-slate-300">&middot;</span>
                                        <span class="font-bold text-slate-900"><x-money :value="$connectedTerm['amount']"/></span>
                                        <span class="text-slate-300">&middot;</span>
                                        @if(($connectedTerm['status'] ?? '') === 'Paid' || ($connectedTerm['due'] ?? 0) <= 0.01)
                                                <span
                                                    class="badge b-green text-[10px] py-0 px-1.5 font-bold">Paid</span>
                                            @elseif(($connectedTerm['received'] ?? 0) > 0)
                                                <span class="badge b-amber text-[10px] py-0 px-1.5 font-bold">
                                                Received: <x-money :value="$connectedTerm['received']"/> (Due: <x-money
                                                        :value="$connectedTerm['due']"/>)
                                            </span>
                                            @else
                                                <span class="badge b-navy text-[10px] py-0 px-1.5 font-bold">Due: <x-money
                                                        :value="$connectedTerm['due']"/></span>
                                            @endif
                                    </span>
                                    @endforeach
                                </div>
                                {{--<span class="text-[11px] text-amber-800/80 italic">
                                    Triggers payment upon milestone completion
                                </span>--}}
                            </div>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        @error('items')
        <div class="mt-2 text-xs text-red-500! font-medium">{{ $message }}</div>
        @enderror

        <div class="flex items-center gap-2 mt-3 flex-wrap">
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addRow">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add milestone
            </button>
            <button type="button" class="btn btn-ghost btn-sm text-blue-600 hover:text-blue-700"
                    wire:click="loadStandardTemplate('e-commerce')"
                    title="Load standard 5-stage agency milestone template">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                E Commerce Template
            </button>
            <button type="button" class="btn btn-ghost btn-sm text-blue-600 hover:text-blue-700"
                    wire:click="loadStandardTemplate('website-development')"
                    title="Load standard 5-stage agency milestone template">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Website Development Template
            </button>
            <button type="button" class="btn btn-ghost btn-sm text-blue-600 hover:text-blue-700"
                    wire:click="loadStandardTemplate('ads-management')"
                    title="Load standard 5-stage agency milestone template">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Ads Management Template
            </button>
            <button type="button" class="btn btn-ghost btn-sm text-blue-600 hover:text-blue-700"
                    wire:click="loadStandardTemplate('d2c-ads')"
                    title="Load standard 5-stage agency milestone template">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                D2C Ads Template
            </button>
            <button type="button" class="btn btn-ghost btn-sm text-blue-600 hover:text-blue-700"
                    wire:click="loadStandardTemplate('seo')"
                    title="Load standard 5-stage agency milestone template">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                SEO Template
            </button>
            <button type="button" class="btn btn-ghost btn-sm text-blue-600 hover:text-blue-700"
                    wire:click="loadStandardTemplate('video-editing')"
                    title="Load standard 5-stage agency milestone template">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Video Editing Template
            </button>
            <button type="button" class="btn btn-ghost btn-sm text-blue-600 hover:text-blue-700"
                    wire:click="loadStandardTemplate('software-tools-crm')"
                    title="Load standard 5-stage agency milestone template">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Software Tools / CRM Template
            </button>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit"
                    class="btn btn-primary min-w-40 justify-center"
                    wire:loading.attr="disabled"
                    wire:target="save">
                <span wire:loading.remove wire:target="save"
                      class="inline-flex items-center justify-center gap-1.5">{{ $hasExistingMilestones ? 'Save milestones' : 'Add milestones' }}</span>
                <span wire:loading.inline-flex wire:target="save" class="items-center justify-center gap-1.5">
                    <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Saving...</span>
                </span>
            </button>
        </div>
    </form>
</x-modal>
