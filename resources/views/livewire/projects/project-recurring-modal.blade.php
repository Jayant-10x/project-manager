<x-modal name="project-recurring-modal"
         :title="'Monthly Recurring — ' . $projectCode"
         sub="Generate recurring installments on the same calendar day each month">
    <form wire:submit="generate" novalidate>
        <div class="form-grid">
            <label class="f">
                <span>Monthly amount (₹) *</span>
                <input wire:model.blur="amount"
                       type="number" step="0.01" min="0.01"
                       class="i @error('amount') !border-red-500 !bg-red-50/20 @enderror"
                       placeholder="0.00">
                @error('amount')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Number of months *</span>
                <input wire:model.blur="months"
                       type="number" min="1" max="60"
                       class="i @error('months') !border-red-500 !bg-red-50/20 @enderror">
                @error('months')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>First due date *</span>
                <input wire:model.blur="startDate"
                       type="date"
                       class="i @error('startDate') !border-red-500 !bg-red-50/20 @enderror">
                @error('startDate')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Installment label *</span>
                <input wire:model.blur="label"
                       class="i @error('label') !border-red-500 !bg-red-50/20 @enderror"
                       placeholder="e.g. Monthly retainer">
                @error('label')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div class="hint mt-2 text-xs text-[#8894ac]">
            Each installment will include the month name (e.g. "Monthly retainer — Nov 2026").
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="confirmClose()" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">Generate Schedule</button>
        </div>
    </form>
</x-modal>
