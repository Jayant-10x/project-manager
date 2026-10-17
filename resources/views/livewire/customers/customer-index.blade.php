<div>
    <div class="page-head">
        <span class="info">
            {{ $customers->total() }} customer{{ $customers->total() === 1 ? '' : 's' }} · total due <b><x-money
                    :value="$totalDue"/></b>
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
        <button type="button" class="btn btn-ghost btn-sm" @click="$dispatch('open-modal', 'customer-import-modal')">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <path d="M7 10l5-5 5 5"/>
                <path d="M12 5v12"/>
            </svg>
            Bulk Import
        </button>
        <button type="button" class="btn btn-primary btn-sm"
                @click="Livewire.dispatch('open-customer-modal', { id: null })">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            New Customer
        </button>
    </div>

    <!-- Filterbar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="filterbar">
                <div class="gsearch" style="width:240px">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" placeholder="Company, contact, phone, GST...">
                </div>

                <select wire:model.live="sales" class="i" style="width:auto">
                    <option value="">All salespeople</option>
                    @foreach($salesPeople as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="city" class="i" style="width:auto">
                    <option value="">All cities</option>
                    @foreach($cities as $ct)
                        <option value="{{ $ct }}">{{ $ct }}</option>
                    @endforeach
                </select>

                <select wire:model.live="pay" class="i" style="width:auto">
                    <option value="">Any payment status</option>
                    <option value="due">Payment pending</option>
                    <option value="over">Overdue</option>
                    <option value="clear">Fully paid</option>
                </select>

                <select wire:model.live="status" class="i" style="width:auto">
                    <option value="">Any status</option>
                    <option value="Active">Active</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Completed">Completed</option>
                    <option value="Churned">Churned</option>
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

    <!-- Data Table -->
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Customer</th>
                    <th>Onboarded By</th>
                    <th>Projects</th>
                    <th class="num">Value</th>
                    <th class="num">Received</th>
                    <th class="num">Due</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($customers as $c)
                    @php
                        $sched = (float) $c->projects->sum(fn($p) => $p->money['scheduled']);
                        $rec = (float) $c->projects->sum(fn($p) => $p->money['received']);
                        $due = (float) $c->projects->sum(fn($p) => $p->money['due']);
                        $overdue = (float) $c->projects->sum(fn($p) => $p->money['overdue']);
                        $r = $sched > 0 ? min(100, round(($rec / $sched) * 100)) : 0;
                        $payCls = $overdue > 0 ? 'b-red' : ($due > 0 ? 'b-amber' : ($sched > 0 ? 'b-green' : 'b-grey'));
                        $payLbl = $overdue > 0 ? 'Overdue' : ($due > 0 ? 'Pending' : ($sched > 0 ? 'Cleared' : '—'));
                    @endphp
                    <tr class="clickable" onclick="window.location='{{ route('customers.show', $c) }}'">
                        <td>
                            <div class="strong">{{ $c->company }}</div>
                            <div class="muted" style="font-size:10.9px">
                                {{ $c->code }}
                                @if($c->contact_name)
                                    · {{ $c->contact_name }}
                                @endif
                                @if($c->city)
                                    · {{ $c->city }}
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($c->salesPerson)
                                <span class="pill">{{ $c->salesPerson->name }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>{{ $c->projects->count() }}</td>
                        <td class="num">
                            <x-money :value="$sched"/>
                        </td>
                        <td class="num" style="color:#3d7a24">
                            <x-money :value="$rec"/>
                        </td>
                        <td class="num font-bold" style="color:{{ $due > 0 ? '#96660f' : 'inherit' }}">
                            <x-money :value="$due"/>
                        </td>
                        <td style="min-width:120px">
                            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                                <span class="badge dot {{ $payCls }}">{{ $payLbl }}</span>
                                <span class="text-xs text-muted font-semibold" style="font-size:11px;color:var(--muted);font-weight:600">{{ $r }}%</span>
                            </div>
                            <div class="bar" style="height:5px">
                                <i style="width:{{ $r }}%"></i>
                            </div>
                        </td>
                        <td>
                            <span
                                class="badge {{ $c->status === 'Active' ? 'b-green' : ($c->status === 'Churned' ? 'b-red' : 'b-grey') }}">
                                {{ $c->status }}
                            </span>
                        </td>
                        <td>
                            <div class="row-actions">
                                <a href="{{ route('customers.show', $c) }}" wire:navigate class="mini-btn inline-grid"
                                   title="View">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                <button type="button" class="mini-btn" title="Edit"
                                        onclick="event.stopPropagation();"
                                        @click="Livewire.dispatch('open-customer-modal', { id: {{ $c->id }} })">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                        <path d="M12 20h9"/>
                                        <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                    </svg>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty">
                                 <span class="flex items-center justify-center">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                    </svg>
                                 </span>
                                <p>No customers found</p>
                                <div class="sm">Add a customer or change the filters</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-[#e2e8f3]">{{ $customers->links() }}</div>
    </div>

    <!-- Livewire Bulk Import Modal Component -->
    <livewire:customers.customer-import-modal/>
</div>
