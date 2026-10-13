<div>
    <!-- Header with Aggregated Financial Summary & Actions -->
    <div class="page-head">
        <span class="info">
            {{ $payments->total() }} receipt{{ $payments->total() === 1 ? '' : 's' }} · <b><x-money
                    :value="$totalAmount"/></b>
            @if($totalTds > 0)
                · TDS
                <x-money :value="$totalTds"/>
            @endif
        </span>
        <span class="spacer"></span>
        <button type="button" class="btn btn-ghost btn-sm" wire:click="exportCsv">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <path d="M7 10l5 5 5-5"/>
                <path d="M12 15V3"/>
            </svg>
            Export CSV
        </button>
        <button type="button" class="btn btn-green btn-sm"
                @click="Livewire.dispatch('open-payment-modal', { projectId: null, termId: null })">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Record payment
        </button>
    </div>

    <!-- Filterbar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="filterbar">
                <div class="gsearch" style="width:250px">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" placeholder="Receipt, customer, UTR...">
                </div>

                <select wire:model.live="mode" class="i" style="width:auto">
                    <option value="">All modes</option>
                    <option value="Cash">Cash</option>
                    <option value="UPI">UPI</option>
                    <option value="NEFT">NEFT</option>
                    <option value="RTGS">RTGS</option>
                    <option value="Cheque">Cheque</option>
                    <option value="Card">Card</option>
                    <option value="Other">Other</option>
                </select>

                <button type="button" class="btn btn-ghost btn-sm" wire:click="clearFilters">
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                    Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Receipts Data Table -->
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Receipt</th>
                    <th>Date</th>
                    <th>Customer</th>
                    <th>Project</th>
                    <th class="num">Amount</th>
                    <th class="num">TDS</th>
                    <th>Mode</th>
                    <th>Reference</th>
                    <th class="num">Balance</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td class="strong">{{ $p->code }}</td>
                        <td class="nowrap muted">{{ $p->paid_on?->format('d M') ?? '—' }}</td>
                        <td class="clickable"
                            @click="Livewire.navigate('{{ route('customers.show', $p->customer_id) }}')">
                            {{ $p->customer?->company ?? '—' }}
                        </td>
                        <td class="muted">
                            <a href="{{ route('projects.show', $p->project_id) }}" wire:navigate class="link">
                                {{ $p->project?->code }}
                            </a>
                            @if($p->term)
                                <div style="font-size:10.6px">{{ $p->term->label }}</div>
                            @endif
                        </td>
                        <td class="num font-bold text-[#3d7a24]">
                            <x-money :value="$p->amount"/>
                        </td>
                        <td class="num muted">{{ $p->tds > 0 ? number_format($p->tds, 2) : '—' }}</td>
                        <td><span class="pill">{{ $p->mode }}</span></td>
                        <td class="muted" style="font-size:11.2px">
                            {{ $p->reference ?: '—' }}
                            @if($p->bank)
                                <br>{{ $p->bank }}
                            @endif
                        </td>
                        <td class="num">
                            <x-money :value="$p->project?->money['due'] ?? 0"/>
                        </td>
                        <td>
                            <div class="row-actions">
                                <button type="button"
                                        class="mini-btn danger"
                                        wire:confirm="Remove payment entry? The receipt will be deleted and the installment balance will adjust."
                                        wire:click="deletePayment({{ $p->id }})"
                                        title="Delete receipt">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                        <path
                                            d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10">
                            <div class="empty">
                                <span class="flex items-center justify-center">
                                    <svg viewBox="0 0 24 24">
                                        <rect x="2" y="6" width="20" height="12" rx="2"/>
                                        <circle cx="12" cy="12" r="2.5"/>
                                        <path d="M6 12h.01M18 12h.01"/>
                                    </svg>
                                </span>
                                <p>No receipts yet</p>
                                <div class="sm">Recorded payments will appear here</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-[#e2e8f3]">{{ $payments->links() }}</div>
    </div>
</div>
