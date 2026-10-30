<x-modal name="followup-modal" title="Log Follow-up" sub="What was discussed and what comes next">
    <form wire:submit="save" novalidate>
        <div class="form-grid">
            <label class="f full">
                <span>Customer <span class="text-red-500">*</span></span>
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

            <label class="f">
                <span>Channel <span class="text-red-500">*</span></span>
                <select wire:model.blur="type"
                        class="i @error('type') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Channel ***</option>
                    <option value="Call">Call</option>
                    <option value="Email">Email</option>
                    <option value="Meeting">Meeting</option>
                    <option value="WhatsApp">WhatsApp</option>
                    <option value="Note">Note</option>
                </select>
                @error('type')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Interaction Date</span>
                <input wire:model.blur="happened_on"
                       type="date"
                       class="i @error('happened_on') border-red-500! bg-red-50/20! @enderror">
                @error('happened_on')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Project</span>
                <select wire:model.blur="project_id"
                        class="i @error('project_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** None ***</option>
                    @foreach($availableProjects as $p)
                        <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                    @endforeach
                </select>
                @error('project_id')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f full">
                <span>Summary <span class="text-red-500">*</span></span>
                <input wire:model.blur="summary"
                        class="i @error('summary') border-red-500! bg-red-50/20! @enderror"
                        placeholder="e.g. Discussed payment clearance and deliverables">
                @error('summary')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f full">
                <span>Detail</span>
                <textarea wire:model.blur="detail"
                          class="i @error('detail') border-red-500! bg-red-50/20! @enderror"
                          style="min-height:70px"
                          placeholder="Additional conversation details..."></textarea>
                @error('detail')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Next follow-up date</span>
                <input wire:model.blur="next_date"
                       type="date"
                       class="i @error('next_date') border-red-500! bg-red-50/20! @enderror">
                @error('next_date')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>What to do next</span>
                <input wire:model.blur="next_note"
                       class="i @error('next_note') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Send revised contract draft">
                @error('next_note')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary min-w-[150px] justify-center" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save" class="inline-flex items-center justify-center gap-1.5">Save follow-up</span>
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
