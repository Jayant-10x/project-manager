<x-modal name="customer-modal"
         :title="$customerId ? ($isRejected ? 'Edit & Resubmit Customer' : 'Edit Customer') : 'New Customer'"
         :sub="$customerId ? ($isRejected ? 'Update details to re-submit this customer for review' : 'Changes are saved immediately') : 'Onboard a customer and create a project'"
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
                <input wire:model.live.blur="alt_phone" placeholder="+91 ..."
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
                <span>State <span class="text-red-500">*</span></span>
                <select wire:model.live="state"
                        class="i @error('state') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select State ***</option>
                    @foreach(config('constants.states') as $id =>$state_name)
                        <option value="{{ $id }}">{{ $state_name }}</option>
                    @endforeach
                </select>
                @error('state')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>City</span>
                <input wire:model.live.blur="city" placeholder="e.g. Delhi"
                       class="i @error('city') border-red-500! bg-red-50/20! @enderror">
                @error('city')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>GSTIN</span>
                <input wire:model.live.blur="gst_number"
                       class="i @error('gst_number') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. 27AABCS1429P1ZV">
                @error('gst_number')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>PAN</span>
                <input wire:model.live.blur="pan_number" placeholder="e.g. AABCA1234C"
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

            @if(auth()->user()->role !== 'Sales')
                <label class="f">
                    <span>Onboarded by (sales person) <span class="text-red-500">*</span></span>
                    <select wire:model.live="sales_person_id"
                            class="i @error('sales_person_id') border-red-500! bg-red-50/20! @enderror">
                        <option value="">*** Select Sales Person ***</option>
                        @foreach($salesPeople as $sp)
                            <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                        @endforeach
                    </select>
                    @error('sales_person_id')
                    <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                    @enderror
                    <div class="hint">Attributed sales person for performance reporting</div>
                </label>
            @endif

            <label class="f">
                <span>Status <span class="text-red-500">*</span></span>
                <select wire:model.live="status"
                        class="i @error('status') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Status ***</option>
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
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary min-w-[150px] justify-center" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save" class="inline-flex items-center justify-center gap-1.5">{{ $customerId ? ($isRejected ? 'Update & Resubmit' : 'Save changes') : 'Create customer' }}</span>
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
