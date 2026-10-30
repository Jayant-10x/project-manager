<x-modal name="customer-import-modal" title="Bulk Import Customers" sub="Paste from Excel or upload a CSV file"
         :wide="true">
    <form wire:submit="import">
        <div class="notice mb-4 p-3 bg-blue-50 text-blue-900 border border-blue-200 rounded-xl text-xs">
            Format (with header):<br>
            <code>company,contact,phone,email,city,state,gst_number,source</code><br>
            One customer per line. Duplicate company names are automatically skipped.
        </div>

        <div class="mb-3">
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Default Salesperson</label>
            <select wire:model="defaultSalesPersonId" class="i text-sm">
                <option value="">*** Select Salesperson ***</option>
                @foreach($salesPeople as $sp)
                    <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Paste CSV Data</label>
            <textarea wire:model="pastedData" class="i text-sm font-mono" style="min-height:140px"
                      placeholder="company,contact,phone,email,city,state,gst_number,source&#10;Acme Pvt Ltd,Rajesh,9876543210,raj@acme.in,Mumbai,Maharashtra,27AAAAA0000A1Z5,Referral"></textarea>
            @error('pastedData') <span class="text-xs text-red-500!">{{ $message }}</span> @enderror
        </div>

        <div class="mb-4">
            <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Or Upload CSV File</label>
            <input type="file" wire:model="csvFile" accept=".csv,.txt" class="i text-sm">
        </div>

        <div class="flex justify-end gap-3 mt-4">
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary min-w-[170px] justify-center" wire:loading.attr="disabled" wire:target="import">
                <span wire:loading.remove wire:target="import" class="inline-flex items-center justify-center gap-1.5">Import Customers</span>
                <span wire:loading.inline-flex wire:target="import" class="items-center justify-center gap-1.5">
                    <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Importing...</span>
                </span>
            </button>
        </div>
    </form>
</x-modal>
