<x-modal name="payment-modal" title="Record Payment Receipt" :sub="$projectSub ?: 'Log verified settlement to ledger'">
    <form wire:submit="save" novalidate>
        <div class="form-grid">
            <label class="f full">
                <span>Target Project *</span>
                <select wire:model.live="project_id"
                        class="i @error('project_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">— Select Assigned Project —</option>
                    @foreach($projects as $p)
                        <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }} (due
                            ₹{{ number_format($p->money['due'], 0) }})
                        </option>
                    @endforeach
                </select>
                @error('project_id') <span
                    class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            @if($terms->isNotEmpty())
                <label class="f full">
                    <span>Against which installment</span>
                    <select wire:model.live="term_id"
                            class="i @error('term_id') border-red-500! bg-red-50/20! @enderror">
                        <option value="">— no installment (on account) —</option>
                        @foreach($terms as $t)
                            @php $bal = max(0, $t->amount - $t->received - $t->tds); @endphp
                            <option value="{{ $t->id }}">
                                #{{ $t->seq }} {{ $t->label }} — ₹{{ number_format($bal, 2) }}
                                remaining @if($t->due_date)
                                    (due {{ $t->due_date->format('d M') }})
                                @endif
                            </option>
                        @endforeach
                    </select>
                    @error('term_id') <span
                        class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
                </label>
            @endif

            <label class="f">
                <span>Amount received (₹) *</span>
                <input wire:model.blur="amount"
                       type="number" step="0.01" min="0.01"
                       class="i @error('amount') border-red-500! bg-red-50/20! @enderror">
                @error('amount') <span
                    class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>TDS deducted (₹)</span>
                <input wire:model.blur="tds"
                       type="number" step="0.01" min="0"
                       class="i @error('tds') border-red-500! bg-red-50/20! @enderror">
                @error('tds') <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
                <div class="hint text-xs text-[#8894ac] mt-1">Enter this if the customer deducted TDS</div>
            </label>

            <label class="f">
                <span>Payment date *</span>
                <input wire:model.blur="paid_on"
                       type="date"
                       class="i @error('paid_on') border-red-500! bg-red-50/20! @enderror">
                @error('paid_on') <span
                    class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Mode *</span>
                <select wire:model.blur="mode"
                        class="i @error('mode') border-red-500! bg-red-50/20! @enderror">
                    <option value="NEFT">NEFT</option>
                    <option value="RTGS">RTGS</option>
                    <option value="UPI">UPI</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Cash">Cash</option>
                    <option value="Card">Card</option>
                    <option value="Other">Other</option>
                </select>
                @error('mode') <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Reference (UTR / cheque no)</span>
                <input wire:model.blur="reference"
                       class="i @error('reference') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. UTR8891234001">
                @error('reference') <span
                    class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Bank</span>
                <input wire:model.blur="bank"
                       class="i @error('bank') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. HDFC Bank">
                @error('bank') <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Note</span>
                <input wire:model.blur="note"
                       class="i @error('note') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Advance against PO 4412">
                @error('note') <span class="text-xs text-red-500 mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="confirmClose()" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-green">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
                Save payment
            </button>
        </div>
    </form>
</x-modal>
