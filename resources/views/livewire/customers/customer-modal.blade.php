<x-modal name="customer-modal"
         :title="$customerId ? 'Edit Customer' : 'New Customer'"
         :sub="$customerId ? 'Changes are saved immediately' : 'Onboard a customer and create a project'"
         :wide="true">
    <form wire:submit="save">
        @if ($errors->any())
            <div class="mb-4 rounded-md bg-red-50 p-3 text-xs text-red-700 border border-red-200">
                <span class="font-semibold">Please resolve the following errors:</span>
                <ul class="mt-1 list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="form-grid">
            <label class="f full">
                <span>Company name *</span>
                <input wire:model="company" class="i" placeholder="e.g. Sunrise Mart Pvt Ltd">
                @error('company') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Contact person</span>
                <input wire:model="contact_name" class="i" placeholder="e.g. Rakesh Gupta">
                @error('contact_name') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Phone</span>
                <input wire:model="phone" class="i" placeholder="+91 ...">
                @error('phone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Alternate phone</span>
                <input wire:model="alt_phone" class="i">
                @error('alt_phone') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Email</span>
                <input wire:model="email" type="email" class="i" placeholder="name@company.in">
                @error('email') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>City</span>
                <input wire:model="city" class="i">
                @error('city') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>State</span>
                <input wire:model="state" class="i">
                @error('state') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>GSTIN</span>
                <input wire:model="gstin" class="i" placeholder="27AABCS1429P1ZV">
                @error('gstin') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>PAN</span>
                <input wire:model="pan" class="i">
                @error('pan') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Source</span>
                <input wire:model="source" class="i" placeholder="Referral / Website / Cold call">
                @error('source') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Industry</span>
                <input wire:model="industry" class="i" placeholder="Retail / FMCG / IT">
                @error('industry') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Onboarded by (salesperson)</span>
                <select wire:model="sales_person_id" class="i">
                    <option value="">— select —</option>
                    @foreach($salesPeople as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                    @endforeach
                </select>
                @error('sales_person_id') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                <div class="hint">Attributed salesperson for performance reporting</div>
            </label>

            <label class="f">
                <span>Status</span>
                <select wire:model="status" class="i">
                    <option value="Active">Active</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Completed">Completed</option>
                    <option value="Churned">Churned</option>
                </select>
                @error('status') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Address</span>
                <textarea wire:model="address" class="i" style="min-height:56px"></textarea>
                @error('address') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Billing name &amp; address</span>
                <textarea wire:model="billing_addr" class="i" style="min-height:56px"></textarea>
                @error('billing_addr') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Internal notes (not visible to customer)</span>
                <textarea wire:model="internal_notes" class="i" style="min-height:56px"></textarea>
                @error('internal_notes') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
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
