<x-modal name="sales-modal"
         :title="$salesId ? 'Edit Salesperson' : 'Add Salesperson'"
         sub="Sales team registry for onboarding attribution and quota tracking">
    <form wire:submit="save" novalidate>
        <div class="form-grid">
            <label class="f full">
                <span>Name *</span>
                <input wire:model.blur="name"
                       class="i @error('name') !border-red-500 !bg-red-50/20 @enderror"
                       placeholder="e.g. Rahul Sharma"
                       maxlength="80">
                @error('name')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Phone</span>
                <input wire:model.blur="phone"
                       class="i @error('phone') !border-red-500 !bg-red-50/20 @enderror"
                       placeholder="+91 ..."
                       maxlength="30">
                @error('phone')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Email</span>
                <input wire:model.blur="email"
                       type="email"
                       class="i @error('email') !border-red-500 !bg-red-50/20 @enderror"
                       placeholder="rahul@the10xagency.in"
                       maxlength="160">
                @error('email')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Monthly target (₹) *</span>
                <input wire:model.blur="target"
                       type="number"
                       step="0.01"
                       min="0"
                       class="i @error('target') !border-red-500 !bg-red-50/20 @enderror"
                       placeholder="0.00">
                @error('target')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Status *</span>
                <select wire:model.blur="status"
                        class="i @error('status') !border-red-500 !bg-red-50/20 @enderror">
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
                @error('status')
                <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="confirmClose()" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">{{ $salesId ? 'Save changes' : 'Add salesperson' }}</button>
        </div>
    </form>
</x-modal>
