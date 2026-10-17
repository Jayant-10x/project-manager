<div>
    <!-- Top 4 KPIs -->
    <div class="kpis four mb-6">
        <x-kpi-card label="Overdue" :value="$totalOverdue" :sub="$overdue->count() . ' installments'" icon="alert"
                    color="#d94b4b" bg="#fdeaea" :ismoney="true"/>
        <x-kpi-card :label="'Due in ' . $alertDays . ' days'" :value="$totalSoon" :ismoney="true"
                    :sub="$soon->count() . ' installments'" icon="clock" color="#e0a13a" bg="#fdf3e2"/>
        <x-kpi-card label="Follow-ups" :value="$followups->count()" sub="Pending conversations" icon="chat"
                    color="#0b3d91" bg="#e8eefc"/>
        <x-kpi-card label="My Open Tasks" :value="$openTasks" sub="In your checklist" icon="list" color="#5aa236"
                    bg="#eef7e8"/>
    </div>

    <!-- Main Grid: Overdue Payments vs (Upcoming + Follow-ups) -->
    <div class="grid-2e mb-6">
        <!-- Overdue Payments Card -->
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Overdue Payments</h3>
                    <div class="text-xs text-[#8894ac]">{{ $overdue->count() }} installments ·
                        <x-money :value="$totalOverdue"/>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @forelse($overdue as $t)
                    @php
                        $bal = max(0, (float)$t->amount - (float)$t->received - (float)$t->tds);
                        $dueDate = $t->due_date ? \Carbon\Carbon::parse($t->due_date) : null;
                        $daysLate = $dueDate ? abs(\Carbon\Carbon::parse($today)->diffInDays($dueDate, false)) : 0;
                    @endphp
                    <div class="term over clickable"
                         @click="Livewire.navigate('{{ route('projects.show', $t->project_id) }}')">
                        <div class="term-no" style="background:#fdeaea;color:#d94b4b">{{ $t->seq }}</div>
                        <div class="term-body">
                            <div class="term-title">{{ $t->project?->customer?->company ?? '—' }}</div>
                            <div class="term-meta">
                                <span>{{ $t->label }}</span>
                                <span>{{ $t->project?->code }}</span>
                                <span style="color:#d94b4b;font-weight:650">{{ $daysLate }} days late</span>
                            </div>
                        </div>
                        <div class="term-amt flex flex-col items-end gap-1.5">
                            <div class="a">
                                <x-money :value="$bal"/>
                            </div>
                            <button type="button"
                                    class="btn btn-green btn-sm"
                                    @click.stop="Livewire.dispatch('open-payment-modal', { projectId: {{ $t->project_id }}, termId: {{ $t->id }} })">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                    <path d="M12 5v14M5 12h14"/>
                                </svg>
                                Payment
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="empty py-8 text-center text-gray-400">
                        <span class="flex items-center justify-center mb-2">
                            <svg viewBox="0 0 24 24" class="w-8 h-8 stroke-current fill-none">
                                <path d="M20 6L9 17l-5-5"/>
                            </svg>
                        </span>
                        <p class="font-medium text-gray-600">No overdue payments</p>
                        <div class="text-xs">All payments are on track</div>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Right Column: Upcoming Payments & Follow-up Calls -->
        <div>
            <!-- Upcoming Payments -->
            <div class="card" style="margin-bottom:15px">
                <div class="card-head">
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Upcoming payments</h3>
                        <div class="text-xs text-[#8894ac]">Next {{ $alertDays }} days</div>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($soon as $t)
                        @php
                            $bal = max(0, (float)$t->amount - (float)$t->received - (float)$t->tds);
                            $dueDate = $t->due_date ? \Carbon\Carbon::parse($t->due_date) : null;
                        @endphp
                        <div class="mini-item clickable"
                             @click="Livewire.navigate('{{ route('projects.show', $t->project_id) }}')">
                            <div class="flex-1 min-w-0">
                                <div class="t">{{ $t->project?->customer?->company }} —
                                    <x-money :value="$bal"/>
                                </div>
                                <div class="s">{{ $t->label }} · {{ $t->project?->code }}</div>
                            </div>
                            <div class="r">
                                <span class="badge b-amber">{{ $dueDate ? $dueDate->format('d M') : '—' }}</span>
                            </div>
                        </div>
                    @empty
                        <div class="empty py-6 text-center text-gray-400">
                            <p class="text-xs font-semibold text-gray-500">Nothing due in the next few days</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Follow-up Calls -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Follow-up calls</h3>
                        <div class="text-xs text-[#8894ac]">{{ $followups->count() }} pending</div>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($followups as $f)
                        @php
                            $fDate = $f->next_date ? \Carbon\Carbon::parse($f->next_date) : null;
                            $isOverdueFup = $fDate && $fDate->toDateString() < $today;
                        @endphp
                        <div class="mini-item clickable"
                             @click="Livewire.navigate('{{ route('customers.show', $f->customer_id) }}')">
                            <div class="flex-1 min-w-0">
                                <div class="t">{{ $f->customer?->company ?? '—' }}</div>
                                <div class="s">{{ $f->next_note ?: $f->summary }}</div>
                            </div>
                            <div class="r flex items-center gap-1.5">
                                <span class="badge {{ $isOverdueFup ? 'b-red' : 'b-navy' }}">
                                    {{ $fDate ? $fDate->format('d M') : '—' }}
                                </span>
                                <button type="button" class="mini-btn" title="Log Follow-up"
                                        @click.stop="Livewire.dispatch('open-followup-modal', { customerId: {{ $f->customer_id }}, projectId: {{ $f->project_id ?? 'null' }} })">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="empty py-6 text-center text-gray-400">
                            <p class="text-xs font-semibold text-gray-500">No pending follow-ups</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Bottom Full Width: Projects Needing Attention Table -->
    <div class="card">
        <div class="card-head">
            <div>
                <h3 class="font-bold text-sm text-[#0d1b34]">Projects Needing Attention</h3>
                <div class="text-xs text-[#8894ac]">At risk and delayed</div>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Project</th>
                    <th>Customer</th>
                    <th>Health</th>
                    <th>Delivery</th>
                    <th>Reason</th>
                    <th class="num">Due</th>
                </tr>
                </thead>
                <tbody>
                @forelse($lateProjects as $p)
                    @php $delDate = $p->delivery_date ? \Carbon\Carbon::parse($p->delivery_date) : null; @endphp
                    <tr class="clickable" @click="Livewire.navigate('{{ route('projects.show', $p->id) }}')">
                        <td>
                            <div class="strong">{{ $p->name }}</div>
                            <div class="muted" style="font-size:10.8px">{{ $p->code }}</div>
                        </td>
                        <td>{{ $p->customer?->company ?? '—' }}</td>
                        <td>
                            <x-health-badge :health="$p->health"/>
                        </td>
                        <td class="nowrap muted">{{ $delDate ? $delDate->format('d M') : '—' }}</td>
                        <td class="muted">{{ $p->delay_reason ?: '—' }}</td>
                        <td class="num font-bold text-[#e0a13a]">
                            <x-money :value="$p->money['due']"/>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6">
                            <div class="empty py-6 text-center text-gray-400">
                                <p class="text-xs font-semibold text-gray-500">All projects on track</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
