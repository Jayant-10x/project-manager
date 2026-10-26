<div>
    <div class="page-head">
        <span class="info">
            @if(auth()->user()->role === 'Sales')
                {{ $customers->total() }} customer {{ \Illuminate\Support\Str::plural('account', $customers->total()) }} acquired
            @else
                {{ $customers->total() }} customer{{ $customers->total() === 1 ? '' : 's' }} · total due <b><x-money
                        :value="$totalDue"/></b>
            @endif
        </span>
        <span class="spacer"></span>
        {{-- [Temporarily hidden - Export & Import buttons]
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
        --}}
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

                <select wire:model.live="state" class="i" style="width:auto">
                    <option value="">All states</option>
                    @foreach($states as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
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

                @if($search !== '' || $sales !== '' || $state !== '' || $pay !== '' || $status !== '')
                    <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearFilters">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                        Clear
                    </button>
                @endif
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
                @forelse($customers as $customer)
                    @php
                        $scheduledAmount = (float) $customer->projects->sum(fn ($proj) => $proj->money['scheduled']);
                        $receivedAmount = (float) $customer->projects->sum(fn ($proj) => $proj->money['received']);
                        $dueAmount = (float) $customer->projects->sum(fn ($proj) => $proj->money['due']);
                        $overdueAmount = (float) $customer->projects->sum(fn ($proj) => $proj->money['overdue']);
                        $collectionRate = $scheduledAmount > 0 ? min(100, round(($receivedAmount / $scheduledAmount) * 100)) : 0;
                        $paymentClass = $overdueAmount > 0 ? 'b-red' : ($dueAmount > 0 ? 'b-amber' : ($scheduledAmount > 0 ? 'b-green' : 'b-grey'));
                        $paymentLabel = $overdueAmount > 0 ? 'Overdue' : ($dueAmount > 0 ? 'Pending' : ($scheduledAmount > 0 ? 'Cleared' : '—'));
                    @endphp
                    <tr class="clickable" onclick="window.location='{{ route('customers.show', $customer) }}'">
                        <td>
                            <div
                                class="strong">{{ $customer->display_name }}</div>
                            <div class="muted" style="font-size:10.9px">
                                {{ $customer->code }}
                                @if($customer->company && $customer->contact_name)
                                    · {{ $customer->contact_name }}
                                @endif
                                @if($customer->city)
                                    · {{ $customer->city }}
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($customer->salesPerson)
                                <span class="pill">{{ $customer->salesPerson->name }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>{{ $customer->projects->count() }}</td>
                        <td class="num">
                            <x-money :value="$scheduledAmount"/>
                        </td>
                        <td class="num" style="color:#3d7a24">
                            <x-money :value="$receivedAmount"/>
                        </td>
                        <td class="num font-bold" style="color:{{ $dueAmount > 0 ? '#96660f' : 'inherit' }}">
                            <x-money :value="$dueAmount"/>
                        </td>
                        <td style="min-width:120px">
                            <div
                                style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                                <span class="badge dot {{ $paymentClass }}">{{ $paymentLabel }}</span>
                                <span class="text-xs text-muted font-semibold"
                                      style="font-size:11px;color:var(--muted);font-weight:600">{{ $collectionRate }}%</span>
                            </div>
                            <div class="bar" style="height:5px">
                                <i style="width:{{ $collectionRate }}%"></i>
                            </div>
                        </td>
                        <td>
                            <span
                                class="badge {{ $customer->status === 'Active' ? 'b-green' : ($customer->status === 'Churned' ? 'b-red' : 'b-grey') }}">
                                {{ $customer->status }}
                            </span>
                        </td>
                        <td>
                            <div class="row-actions flex items-center justify-end gap-1.5">
                                <a href="{{ route('customers.show', $customer) }}" wire:navigate
                                   class="btn btn-outline-primary btn-xs"
                                   title="View Customer Profile">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <span>View</span>
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-xs" title="Edit Customer"
                                        onclick="event.stopPropagation();"
                                        @click="Livewire.dispatch('open-customer-modal', { id: {{ $customer->id }} })">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                        <path d="M12 20h9"/>
                                        <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                    </svg>
                                    <span>Edit</span>
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

    {{-- [Temporarily hidden - Bulk Import Modal]
    <livewire:customers.customer-import-modal/>
    --}}
</div>
