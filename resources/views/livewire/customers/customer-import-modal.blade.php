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
            <button type="submit" class="btn btn-primary">Import Customers</button>
        </div>
    </form>
</x-modal>
