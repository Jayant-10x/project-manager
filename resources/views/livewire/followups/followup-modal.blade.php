<x-modal name="followup-modal" title="Log Follow-up" sub="What was discussed and what comes next">
    <form wire:submit="save" novalidate>
        <div class="form-grid">
            <label class="f full">
                <span>Customer <span class="text-red-500">*</span></span>
                <select wire:model.live="customer_id"
                        class="i @error('customer_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">— Select Customer —</option>
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
                <span>Project (optional)</span>
                <select wire:model.blur="project_id"
                        class="i @error('project_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">— none —</option>
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
            <button type="submit" class="btn btn-primary">Save follow-up</button>
        </div>
    </form>
</x-modal>
