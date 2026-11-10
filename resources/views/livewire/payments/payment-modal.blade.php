<x-modal name="payment-modal" title="Record Payment Receipt" :sub="$projectSub ?: 'Log verified settlement to ledger'" :wide="true">
    <form wire:submit="save" novalidate>
        <div class="form-grid">
            <label class="f full">
                <span>Target Project<span class="text-red-500"> *</span></span>
                <select wire:model.live="project_id"
                        class="i @error('project_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Assigned Project ***</option>
                    @foreach($projects as $project)
                        <option value="{{ $project->id }}">{{ $project->code }} — {{ $project->name }} (due
                            ₹{{ number_format($project->money['due'], 0) }})
                        </option>
                    @endforeach
                </select>
                @error('project_id') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            @if($terms->isNotEmpty())
                <label class="f full">
                    <span>Against which installment</span>
                    <select wire:model.live="term_id"
                            class="i @error('term_id') border-red-500! bg-red-50/20! @enderror">
                        <option value="">*** No Installment (on account) ***</option>
                        @foreach($terms as $term)
                            @php $termBalance = max(0, $term->amount - $term->received - $term->tds); @endphp
                            <option value="{{ $term->id }}">
                                #{{ $term->seq }} {{ $term->label }} — ₹{{ number_format($termBalance, 2) }}
                                remaining ({{ ucfirst($term->trigger_type) }}{{ $term->milestone ? ' • Milestone: ' . $term->milestone->title : '' }})@if($term->due_date) [due {{ $term->due_date->format('d M') }}]@endif
                            </option>
                        @endforeach
                    </select>
                    @error('term_id') <span
                        class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                </label>
            @endif

            <label class="f">
                <span>Amount received (₹)<span class="text-red-500"> *</span></span>
                <input wire:model.blur="amount"
                       type="number" step="0.01" min="0.01"
                       placeholder="0.00"
                       class="i @error('amount') border-red-500! bg-red-50/20! @enderror">
                @error('amount') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>TDS deducted (₹)</span>
                <input wire:model.blur="tds"
                       type="number" step="0.01" min="0"
                       placeholder="0.00"
                       class="i @error('tds') border-red-500! bg-red-50/20! @enderror">
                @error('tds') <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                <div class="hint text-xs text-[#8894ac] mt-1">Enter this if the customer deducted TDS</div>
            </label>

            <label class="f">
                <span>Payment date<span class="text-red-500"> *</span></span>
                <input wire:model.blur="paid_on"
                       type="date"
                       class="i @error('paid_on') border-red-500! bg-red-50/20! @enderror">
                @error('paid_on') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Mode<span class="text-red-500"> *</span></span>
                <select wire:model.blur="mode"
                        class="i @error('mode') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Payment Mode ***</option>
                    <option value="NEFT">NEFT</option>
                    <option value="RTGS">RTGS</option>
                    <option value="UPI">UPI</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Cash">Cash</option>
                    <option value="Card">Card</option>
                    <option value="Other">Other</option>
                </select>
                @error('mode') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Reference (UTR / cheque no)</span>
                <input wire:model.blur="reference"
                       class="i @error('reference') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. UTR8891234001">
                @error('reference') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Bank</span>
                <input wire:model.blur="bank"
                       class="i @error('bank') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. HDFC Bank">
                @error('bank') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Note</span>
                <input wire:model.blur="note"
                       class="i @error('note') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Advance against PO 4412">
                @error('note') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Receipt Slip / Bank Advice (Smart Document Attachment)</span>
                <input type="file" wire:model="receipt_file"
                       class="i text-xs @error('receipt_file') border-red-500! bg-red-50/20! @enderror"
                       accept=".pdf,.png,.jpg,.jpeg,.webp,.doc,.docx">
                @error('receipt_file') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                <div class="hint text-xs text-[#8894ac] mt-1">Upload transfer proof, receipt slip, or bank advice (PDF, JPG, PNG, DOCX up to 10 MB). Automatically logged to Project Documents.</div>
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit" class="btn btn-green min-w-40 justify-center" wire:loading.attr="disabled" wire:target="save">
                <span wire:loading.remove wire:target="save" class="inline-flex items-center justify-center gap-1.5">
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    <span>Save payment</span>
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
