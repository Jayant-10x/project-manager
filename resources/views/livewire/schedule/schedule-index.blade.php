<div>
    <!-- Page Header Summary & Export Action -->
    <div class="page-head mb-4">
        <span class="info text-sm text-[#8894ac]">
            {{ $terms->count() }} installment{{ $terms->count() === 1 ? '' : 's' }} across {{ $totalProjectsCount }} project{{ $totalProjectsCount === 1 ? '' : 's' }}
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
    </div>

    <!-- 4 KPI Blocks -->
    <div class="kpis four mb-6">
        <x-kpi-card label="Upcoming" :value="$upcomingSum" :sub="$upcomingCount . ' installments'" icon="cal"
                    :ismoney="true" color="#0b3d91" bg="#e8eefc"/>
        <x-kpi-card label="Overdue" :value="$overdueSum" :sub="$overdueCount . ' installments'" icon="alert"
                    :ismoney="true" color="#d94b4b" bg="#fdeaea"/>
        <x-kpi-card label="Collected" :value="$collectedSum" :sub="$paidCount . ' fully paid'" icon="check"
                    :ismoney="true" color="#5aa236" bg="#eef7e8"/>
        <x-kpi-card label="Total scheduled" :value="$totalScheduledSum" sub="All active terms" icon="cash"
                    color="#7a5af8"
                    :ismoney="true" bg="#f0ecff"/>
    </div>

    <!-- Schedule Data Table -->
    <div class="card">
        <div class="card-head p-4 border-b border-[#eef2f9]">
            <div>
                <h3 class="font-bold text-sm text-[#0d1b34]">Complete Schedule</h3>
                <div class="text-xs text-[#8894ac]">Sorted by due date, most urgent first</div>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Due</th>
                    <th>Customer</th>
                    <th>Project</th>
                    <th>Installment</th>
                    <th>Trigger</th>
                    <th class="num">Amount</th>
                    <th class="num">Balance</th>
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($terms as $t)
                    @php
                        $bal = max(0, (float)$t->amount - (float)$t->received - (float)$t->tds);
                        $isLate = $t->status === 'Overdue';
                        $dueDate = $t->due_date ? \Carbon\Carbon::parse($t->due_date) : null;
                        $daysLate = ($isLate && $dueDate) ? abs(\Carbon\Carbon::parse($today)->diffInDays($dueDate, false)) : 0;
                        $triggerLabel = match ($t->trigger_type) {
                            'milestone' => 'Milestone linked',
                            'recurring' => 'Recurring monthly',
                            default     => 'Fixed date',
                        };
                        $badgeCls = match ($t->status) {
                            'Paid'    => 'b-green',
                            'Overdue' => 'b-red',
                            'Partial' => 'b-amber',
                            default   => 'b-grey',
                        };
                    @endphp
                    <tr>
                        <td class="nowrap" style="{{ $isLate ? 'color:#d94b4b;font-weight:650' : '' }}">
                            {{ $dueDate ? $dueDate->format('d M') : '—' }}
                            @if($isLate && $daysLate > 0)
                                <div style="font-size:10.5px">{{ $daysLate }} days late</div>
                            @endif
                        </td>
                        <td class="clickable"
                            @click="Livewire.navigate('{{ $t->project?->customer_id ? route('customers.show', $t->project->customer_id) : '#' }}')">
                            {{ $t->project?->customer?->company ?? '—' }}
                        </td>
                        <td class="muted">
                            <a href="{{ route('projects.show', $t->project_id) }}" wire:navigate class="link">
                                {{ $t->project?->code }}
                            </a>
                        </td>
                        <td>{{ $t->label }}</td>
                        <td><span class="pill">{{ $triggerLabel }}</span></td>
                        <td class="num">
                            <x-money :value="$t->amount"/>
                        </td>
                        <td class="num font-bold">
                            <x-money :value="$bal"/>
                        </td>
                        <td><span class="badge dot {{ $badgeCls }}">{{ $t->status }}</span></td>
                        <td>
                            @if($bal > 0)
                                <button type="button"
                                        class="mini-btn"
                                        @click="Livewire.dispatch('open-payment-modal', { projectId: {{ $t->project_id }}, termId: {{ $t->id }} })"
                                        title="Record payment">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty py-8 text-center text-gray-400">
                                <span class="flex items-center justify-center mb-2">
                                    <svg viewBox="0 0 24 24" class="w-10 h-10 stroke-current fill-none">
                                        <rect x="3" y="4" width="18" height="18" rx="2"/>
                                        <path d="M16 2v4M8 2v4M3 10h18"/>
                                    </svg>
                                </span>
                                <p class="font-medium text-gray-600">No installments yet</p>
                                <div class="text-xs">Create a payment schedule in the project</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
