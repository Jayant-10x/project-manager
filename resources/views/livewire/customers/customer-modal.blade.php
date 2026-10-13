<x-modal name="customer-modal"
         :title="$customerId ? 'Edit Customer' : 'New Customer'"
         :sub="$customerId ? 'Changes are saved immediately' : 'Onboard a customer and create a project'"
         :wide="true">
    <form wire:submit="save">
        <div class="form-grid">
            <label class="f full">
                <span>Company name *</span>
                <input wire:model="company" class="i @error('company') border-red-700! @enderror"
                       placeholder="e.g. Sunrise Mart Pvt Ltd">
                @error('company') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Contact person</span>
                <input wire:model="contact_name"
                       class="i @error('contact_name') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Rakesh Gupta">
                @error('contact_name') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Phone</span>
                <input wire:model="phone" class="i @error('phone') border-red-500! bg-red-50/20! @enderror"
                       placeholder="+91 ...">
                @error('phone') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Alternate phone</span>
                <input wire:model="alt_phone"
                       class="i @error('alt_phone') border-red-500! bg-red-50/20! @enderror">
                @error('alt_phone') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Email</span>
                <input wire:model="email" type="email"
                       class="i @error('email') border-red-500! bg-red-50/20! @enderror"
                       placeholder="name@company.in">
                @error('email') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>City</span>
                <input wire:model="city" class="i @error('city') border-red-500! bg-red-50/20! @enderror">
                @error('city') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>State</span>
                <input wire:model="state" class="i @error('state') border-red-500! bg-red-50/20! @enderror">
                @error('state') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>GSTIN</span>
                <input wire:model="gstin" class="i @error('gstin') border-red-500! bg-red-50/20! @enderror"
                       placeholder="27AABCS1429P1ZV">
                @error('gstin') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>PAN</span>
                <input wire:model="pan" class="i @error('pan') border-red-500! bg-red-50/20! @enderror">
                @error('pan') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Source</span>
                <input wire:model="source" class="i @error('source') border-red-500! bg-red-50/20! @enderror"
                       placeholder="Referral / Website / Cold call">
                @error('source') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Industry</span>
                <input wire:model="industry" class="i @error('industry') border-red-500! bg-red-50/20! @enderror"
                       placeholder="Retail / FMCG / IT">
                @error('industry') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Onboarded by (salesperson)</span>
                <select wire:model="sales_person_id"
                        class="i @error('sales_person_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">— select —</option>
                    @foreach($salesPeople as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                    @endforeach
                </select>
                @error('sales_person_id') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                <div class="hint">Attributed salesperson for performance reporting</div>
            </label>

            <label class="f">
                <span>Status</span>
                <select wire:model="status" class="i @error('status') border-red-500! bg-red-50/20! @enderror">
                    <option value="Active">Active</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Completed">Completed</option>
                    <option value="Churned">Churned</option>
                </select>
                @error('status') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Address</span>
                <textarea wire:model="address"
                          class="i @error('address') border-red-500! bg-red-50/20! @enderror"
                          style="min-height:56px"></textarea>
                @error('address') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Billing name &amp; address</span>
                <textarea wire:model="billing_addr"
                          class="i @error('billing_addr') border-red-500! bg-red-50/20! @enderror"
                          style="min-height:56px"></textarea>
                @error('billing_addr') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Internal notes (not visible to customer)</span>
                <textarea wire:model="internal_notes"
                          class="i @error('internal_notes') border-red-500! bg-red-50/20! @enderror"
                          style="min-height:56px"></textarea>
                @error('internal_notes') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="$dispatch('close-modal')" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">
                {{ $customerId ? 'Save changes' : 'Create customer' }}
            </button>
        </div>
    </form>
</x-modal>
