<x-modal name="project-recurring-modal"
         :title="'Monthly Recurring — ' . $projectCode"
         sub="Generate recurring installments on the same calendar day each month">
    <form wire:submit="generate" novalidate>
        <div class="form-grid">
            <label class="f">
                <span>Monthly amount (₹)<span class="text-red-500"> *</span></span>
                <input wire:model.blur="amount"
                       type="number" step="0.01" min="0.01"
                       class="i @error('amount') border-red-500! bg-red-50/20! @enderror"
                       placeholder="0.00">
                @error('amount')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Number of months<span class="text-red-500"> *</span></span>
                <input wire:model.blur="months"
                       type="number" min="1" max="60"
                       class="i @error('months') border-red-500! bg-red-50/20! @enderror">
                @error('months')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>First due date<span class="text-red-500"> *</span></span>
                <input wire:model.blur="startDate"
                       type="date"
                       class="i @error('startDate') border-red-500! bg-red-50/20! @enderror">
                @error('startDate')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Installment label<span class="text-red-500"> *</span></span>
                <input wire:model.blur="label"
                       class="i @error('label') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Monthly retainer">
                @error('label')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div class="hint mt-2 text-xs text-[#8894ac]">
            Each installment will include the month name (e.g. "Monthly retainer — Nov 2026").
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary min-w-48 justify-center" wire:loading.attr="disabled" wire:target="generate">
                <span wire:loading.remove wire:target="generate" class="inline-flex items-center justify-center gap-1.5">Generate Schedule</span>
                <span wire:loading.inline-flex wire:target="generate" class="items-center justify-center gap-1.5">
                    <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Generating...</span>
                </span>
            </button>
        </div>
    </form>
</x-modal>
