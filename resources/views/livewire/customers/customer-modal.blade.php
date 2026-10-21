<x-modal name="customer-modal"
         :title="$customerId ? 'Edit Customer' : 'New Customer'"
         :sub="$customerId ? 'Changes are saved immediately' : 'Onboard a customer and create a project'"
         :wide="true">
    <form wire:submit="save">
        <div class="form-grid">
            <label class="f full">
                <span>Company name</span>
                <input wire:model.live.blur="company"
                       class="i @error('company') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Sunrise Mart Pvt Ltd">
                @error('company')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Contact person <span class="text-red-500">*</span></span>
                <input wire:model.live.blur="contact_name"
                       class="i @error('contact_name') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Rakesh Gupta">
                @error('contact_name')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Phone <span class="text-red-500">*</span></span>
                <input wire:model.live.blur="phone"
                       class="i @error('phone') border-red-500! bg-red-50/20! @enderror"
                       placeholder="+91 ...">
                @error('phone')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Alternate phone</span>
                <input wire:model.live.blur="alt_phone"
                       class="i @error('alt_phone') border-red-500! bg-red-50/20! @enderror">
                @error('alt_phone')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Email</span>
                <input wire:model.live.blur="email" type="text"
                       class="i @error('email') border-red-500! bg-red-50/20! @enderror"
                       placeholder="name@company.in">
                @error('email')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>City</span>
                <input wire:model.live.blur="city"
                       class="i @error('city') border-red-500! bg-red-50/20! @enderror">
                @error('city')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>State <span class="text-red-500">*</span></span>
                <select wire:model.live="state"
                        class="i @error('state') border-red-500! bg-red-50/20! @enderror">
                    <option value="">— select State —</option>
                    @foreach(config('constants.states') as $id =>$state_name)
                        <option value="{{ $id }}">{{ $state_name }}</option>
                    @endforeach
                </select>
                @error('state')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>GSTIN</span>
                <input wire:model.live.blur="gst_number"
                       class="i @error('gst_number') border-red-500! bg-red-50/20! @enderror"
                       placeholder="27AABCS1429P1ZV">
                @error('gst_number')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>PAN</span>
                <input wire:model.live.blur="pan_number"
                       class="i @error('pan_number') border-red-500! bg-red-50/20! @enderror">
                @error('pan_number')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Source</span>
                <input wire:model.live.blur="source"
                       class="i @error('source') border-red-500! bg-red-50/20! @enderror"
                       placeholder="Referral / Website / Cold call">
                @error('source')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Industry</span>
                <input wire:model.live.blur="industry"
                       class="i @error('industry') border-red-500! bg-red-50/20! @enderror"
                       placeholder="Retail / FMCG / IT">
                @error('industry')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Onboarded by (salesperson) <span class="text-red-500">*</span></span>
                <select wire:model.live="sales_person_id"
                        class="i @error('sales_person_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">— select —</option>
                    @foreach($salesPeople as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                    @endforeach
                </select>
                @error('sales_person_id')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
                <div class="hint">Attributed salesperson for performance reporting</div>
            </label>

            <label class="f">
                <span>Status <span class="text-red-500">*</span></span>
                <select wire:model.live="status"
                        class="i @error('status') border-red-500! bg-red-50/20! @enderror">
                    <option value="Active">Active</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Completed">Completed</option>
                    <option value="Churned">Churned</option>
                </select>
                @error('status')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f full">
                <span>Address</span>
                <textarea wire:model.live.blur="address"
                          class="i @error('address') border-red-500! bg-red-50/20! @enderror"
                          style="min-height:56px"></textarea>
                @error('address')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f full">
                <span>Billing name &amp; address</span>
                <textarea wire:model.live.blur="billing_address"
                          class="i @error('billing_address') border-red-500! bg-red-50/20! @enderror"
                          style="min-height:56px"></textarea>
                @error('billing_address')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f full">
                <span>Internal notes (not visible to customer)</span>
                <textarea wire:model.live.blur="internal_notes"
                          class="i @error('internal_notes') border-red-500! bg-red-50/20! @enderror"
                          style="min-height:56px"></textarea>
                @error('internal_notes')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="confirmClose()" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">
                {{ $customerId ? 'Save changes' : 'Create customer' }}
            </button>
        </div>
    </form>
</x-modal>
