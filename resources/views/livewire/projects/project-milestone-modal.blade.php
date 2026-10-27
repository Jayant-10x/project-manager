<x-modal name="project-milestone-modal"
         :title="($hasExistingMilestones ? 'Edit Milestones' : 'Add Milestones') . ' — ' . $projectCode"
         sub="Build a timeline so the project stays on schedule" :wide="true">
    <form wire:submit="save">
        <div class="space-y-3">
            @foreach($items as $i => $m)
                <div wire:key="ms-item-{{ $i }}-{{ $m['id'] ?? 'new' }}"
                     class="ms-row flex items-center gap-3 p-2.5 border border-[#eef2f9] rounded-xl bg-slate-50/50">
                    <span class="ms-dot {{ $m['done'] ? 'done' : '' }} shrink-0">
                        {{ $m['done'] ? '✓' : ($i + 1) }}
                    </span>
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Milestone Name
                                <span class="text-red-500"> *</span></label>
                            <input wire:model="items.{{ $i }}.name" class="i text-sm @error('items.'.$i.'.name') border-red-500! bg-red-50/20! @enderror"
                                   placeholder="Milestone description">
                            @error('items.'.$i.'.name')
                            <span class="text-red-500!">{{$message}}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Due Date</label>
                            <input type="date" wire:model="items.{{ $i }}.dueDate" class="i text-sm @error('items.'.$i.'.dueDate') border-red-500! bg-red-50/20! @enderror">
                            @error('items.'.$i.'.dueDate')
                            <span class="text-red-500!">{{$message}}</span>
                            @enderror
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Status <span
                                    class="text-red-500"> *</span></label>
                            <select wire:model="items.{{ $i }}.status"
                                    class="i text-sm @error('items.'.$i.'.status') border-red-500! bg-red-50/20! @enderror">
                                <option value="">*** Select Status ***</option>
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Done">Done</option>
                                <option value="Blocked">Blocked</option>
                            </select>
                            @error('items.'.$i.'.status')
                            <span class="text-red-500!">{{$message}}</span>
                            @enderror
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" class="btn btn-outline-danger btn-xs" wire:click="removeRow({{ $i }})"
                                title="Remove milestone" aria-label="Remove milestone">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path
                                    d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            </svg>
                            <span>Remove</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="flex items-center gap-2 mt-3">
            <button type="button" class="btn btn-outline-primary btn-sm" wire:click="addRow">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add milestone
            </button>
            <button type="button" class="btn btn-ghost btn-sm text-blue-600 hover:text-blue-700" wire:click="loadStandardTemplate"
                    title="Load standard 5-stage agency milestone template">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Load standard template (5 stages)
            </button>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit"
                    class="btn btn-primary">{{ $hasExistingMilestones ? 'Save milestones' : 'Add milestones' }}</button>
        </div>
    </form>
</x-modal>
