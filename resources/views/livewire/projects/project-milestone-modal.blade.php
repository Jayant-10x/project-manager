<x-modal name="project-milestone-modal" :title="'Milestones — ' . $projectCode"
         sub="Build a timeline so the project stays on schedule" :wide="true">
    <form wire:submit="save">
        <div class="space-y-3">
            @foreach($items as $i => $m)
                <div wire:key="ms-item-{{ $i }}-{{ $m['id'] ?? 'new' }}" class="ms-row flex items-center gap-3 p-2.5 border border-[#eef2f9] rounded-xl bg-slate-50/50">
                    <span class="ms-dot {{ $m['done'] ? 'done' : '' }} shrink-0">
                        {{ $m['done'] ? '✓' : ($i + 1) }}
                    </span>
                    <div class="flex-1 grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
                        <div class="md:col-span-2">
                            <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Milestone Name
                                *</label>
                            <input wire:model="items.{{ $i }}.name" class="i text-sm"
                                   placeholder="Milestone description">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Due Date</label>
                            <input type="date" wire:model="items.{{ $i }}.dueDate" class="i text-sm">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-gray-400 uppercase block mb-1">Status</label>
                            <select wire:model="items.{{ $i }}.status" class="i text-sm">
                                <option value="Pending">Pending</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Done">Done</option>
                                <option value="Blocked">Blocked</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button type="button" class="mini-btn danger" wire:click="removeRow({{ $i }})"
                                title="Remove milestone" aria-label="Remove milestone">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path
                                    d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <button type="button" class="btn btn-ghost btn-sm mt-3" wire:click="addRow">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add milestone
        </button>

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="$dispatch('close-modal')" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">Save milestones</button>
        </div>
    </form>
</x-modal>
