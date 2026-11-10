<x-modal name="milestone-chase-modal"
         title="Smart Follow-up — Collection Chase"
         :sub="$projectCode ? ($projectCode . ' · ' . $milestoneName) : 'Log collection notes and schedule next chase date'"
         :wide="true">
    <form wire:submit="save" novalidate>
        <div class="space-y-4">
            <div class="p-3 bg-amber-50/60 border border-amber-200/70 rounded-xl text-xs text-amber-900 flex items-start gap-2">
                <svg viewBox="0 0 24 24" class="w-4 h-4 text-amber-600 shrink-0 mt-0.5">
                    <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                </svg>
                <div>
                    <b>Smart Follow-up:</b> Log discussion notes with the customer's finance/accounts team and schedule the next chase reminder for milestone <b>{{ $milestoneName }}</b>.
                </div>
            </div>

            <label class="f full">
                <span>Collection Chase Notes<span class="text-red-500"> *</span></span>
                <textarea wire:model.blur="chase_notes" rows="4"
                          class="i text-sm @error('chase_notes') border-red-500! bg-red-50/20! @enderror"
                          placeholder="e.g. Spoke to customer accounts manager. Milestone approved, payment release scheduled for Friday via NEFT."></textarea>
                @error('chase_notes') <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Next Chase Date (Optional)</span>
                <input type="date" wire:model.blur="next_chase_date"
                       class="i text-sm @error('next_chase_date') border-red-500! bg-red-50/20! @enderror">
                @error('next_chase_date') <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                <div class="hint text-xs text-[#8894ac] mt-1">Set a reminder date for your next follow-up with the customer.</div>
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary min-w-44 justify-center" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save" class="inline-flex items-center justify-center gap-1.5">
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                        <path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    <span>Save Chase Note</span>
                </span>
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
