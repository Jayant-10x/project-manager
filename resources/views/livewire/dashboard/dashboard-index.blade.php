<div>
    <!-- 1. Top KPI Row (5 Blocks) -->
    <div class="kpis">
        <x-kpi-card label="Total Receivable" :value="$m['scheduled']" sub="Across all projects" icon="cash"
                    :ismoney="true" color="#0b3d91" bg="#e8eefc" :href="route('schedule.index')"/>
        <x-kpi-card label="Received" :value="$m['received']" :sub="$m['collectionRate'] . '% collected'" icon="check"
                    :ismoney="true" color="#5aa236" bg="#eef7e8" :href="route('payments.index')"/>
        <x-kpi-card label="Due" :value="$m['due']" sub="Still to be collected" icon="clock" color="#e0a13a"
                    :ismoney="true" bg="#fdf3e2" :href="route('schedule.index')"/>
        <x-kpi-card label="Overdue" :value="$m['overdue']" :sub="$overdueCount . ' installments late'" icon="alert"
                    :ismoney="true" color="#d94b4b" bg="#fdeaea" :href="route('today.index')"/>
        <x-kpi-card label="This Month" :value="$m['thisMonth']" sub="Collection this month" icon="cal" color="#2e75b6"
                    :ismoney="true" bg="#e8f1fa" :href="route('payments.index')"/>
    </div>

    <!-- 2. Main Split Grid: Today's Work Mini-Triage & Project Health / Trend -->
    <div class="grid-2">
        <!-- Today's Work Quick List -->
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Today's Work</h3>
                    <div class="text-xs text-[#8894ac]">Check these first</div>
                </div>
                <div class="right">
                    <a href="{{ route('today.index') }}" wire:navigate class="btn btn-ghost btn-sm">
                        Full list
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($topOverdue->isEmpty() && $topFollowups->isEmpty() && $topLateProjects->isEmpty())
                    <div class="empty py-8 text-center text-gray-400">
                        <span class="flex items-center justify-center mb-2">
                            <svg viewBox="0 0 24 24" class="w-8 h-8 stroke-current fill-none">
                                <path d="M20 6L9 17l-5-5"/>
                            </svg>
                        </span>
                        <p class="font-medium text-gray-600">Everything is on track</p>
                        <div class="text-xs">Nothing pending for today</div>
                    </div>
                @else
                    <!-- Top Overdue Payments -->
                    @foreach($topOverdue as $t)
                        @php
                            $bal = max(0, (float)$t->amount - (float)$t->received - (float)$t->tds);
                            $dueDate = $t->due_date ? \Carbon\Carbon::parse($t->due_date) : null;
                            $daysLate = $dueDate ? abs(\Carbon\Carbon::parse($today)->diffInDays($dueDate, false)) : 0;
                        @endphp
                        <div class="mini-item clickable"
                             @click="Livewire.navigate('{{ route('projects.show', $t->project_id) }}')">
                            <span
                                style="width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:#fdeaea;color:#d94b4b;flex:0 0 30px">
                                <svg viewBox="0 0 24 24" class="w-4 h-4"><circle cx="12" cy="12" r="9"/><path
                                        d="M12 8v5M12 16.5v.5"/></svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="t">{{ $t->project?->customer?->company }} —
                                    <x-money :value="$bal"/>
                                </div>
                                <div class="s">{{ $t->label }} · due {{ $dueDate ? $dueDate->format('d M') : '—' }}
                                    · {{ $daysLate }} days late
                                </div>
                            </div>
                            <div class="r"><span class="badge b-red">Overdue</span></div>
                        </div>
                    @endforeach

                    <!-- Top Follow-ups -->
                    @foreach($topFollowups as $f)
                        @php $fupDate = $f->next_date ? \Carbon\Carbon::parse($f->next_date) : null; @endphp
                        <div class="mini-item clickable"
                             @click="Livewire.navigate('{{ route('customers.show', $f->customer_id) }}')">
                            <span
                                style="width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:#e8eefc;color:#0b3d91;flex:0 0 30px">
                                <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                        d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="t">{{ $f->customer?->company }}</div>
                                <div class="s">{{ $f->next_note ?: $f->type }}
                                    · {{ $fupDate ? $fupDate->format('d M') : '—' }}</div>
                            </div>
                            <div class="r"><span class="badge b-navy">{{ $f->type }}</span></div>
                        </div>
                    @endforeach

                    <!-- Top Delayed / At Risk Projects -->
                    @foreach($topLateProjects as $p)
                        <div class="mini-item clickable"
                             @click="Livewire.navigate('{{ route('projects.show', $p->id) }}')">
                            <span
                                style="width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:#fdf3e2;color:#e0a13a;flex:0 0 30px">
                                <svg viewBox="0 0 24 24" class="w-4 h-4"><circle cx="12" cy="12" r="9"/><path
                                        d="M12 7v5l3 2"/></svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="t">{{ $p->name }}</div>
                                <div class="s">{{ $p->customer?->company }} · {{ $p->delay_reason ?: $p->health }}</div>
                            </div>
                            <div class="r">
                                <x-health-badge :health="$p->health"/>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Project Health & Collection Trend -->
        <div>
            <!-- Project Health Breakdown -->
            <div class="card" style="margin-bottom:15px">
                <div class="card-head">
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Project Health</h3>
                        <div class="text-xs text-[#8894ac]">{{ $totalProjectsCount }} projects</div>
                    </div>
                </div>
                <div class="card-body">
                    <div style="display:flex;gap:14px;flex-wrap:wrap">
                        @php
                            $colors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                        @endphp
                        @foreach($healthCounts as $label => $cnt)
                            @php
                                $p = $totalProjectsCount > 0 ? round(($cnt / $totalProjectsCount) * 100) : 0;
                                $c = $colors[$label];
                            @endphp
                            <a href="{{ route('projects.index', ['health' => $label]) }}" wire:navigate
                               class="block p-2 rounded-xl transition hover:bg-gray-50 cursor-pointer"
                               style="flex:1;min-width:110px;text-decoration:none;color:inherit"
                               title="Filter {{ $label }} projects">
                                <div
                                    style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:5px">
                                    <i style="width:8px;height:8px;border-radius:50%;background:{{ $c }};display:inline-block"></i>{{ $label }}
                                </div>
                                <div style="display:flex;align-items:baseline;justify-content:space-between">
                                    <span style="font-size:20px;font-weight:750;letter-spacing:-.6px">{{ $cnt }}</span>
                                    <span style="font-size:11px;font-weight:600;color:var(--muted)">{{ $p }}%</span>
                                </div>
                                <div class="bar" style="height:5px;margin-top:6px">
                                    <i style="width:{{ $p }}%;background:{{ $c }}"></i>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- 6-Month Collection Trend Sparkline -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Collection Trend</h3>
                        <div class="text-xs text-[#8894ac]">Last 6 months</div>
                    </div>
                </div>
                <div class="card-body">
                    @php $maxVal = max(array_merge([1], array_column($trend, 'value'))); @endphp
                    <div class="spark-chart" style="display:flex;gap:6px;align-items:flex-end">
                        @foreach($trend as $t)
                            @php $barHeight = max(4, round(($t['value'] / $maxVal) * 100)); @endphp
                            <div class="spark-col">
                                <div class="spark-val" x-text="$store.currency.format({{ (float)$t['value'] }}, true)"></div>
                                <div class="spark-bar-wrap">
                                    <i style="height:{{ $barHeight }}%"
                                       :title="'{{ $t['label'] }}: ' + $store.currency.format({{ (float)$t['value'] }}, false)"></i>
                                </div>
                                <div class="spark-label">{{ $t['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 3. Upcoming & Overdue Installments -->
    <div class="grid-2e" style="margin-bottom:15px">
        <!-- Upcoming Payments -->
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Upcoming Payments</h3>
                    <div class="text-xs text-[#8894ac]">Next {{ $alertDays }} days</div>
                </div>
            </div>
            <div class="card-body">
                @forelse($upcomingTerms as $t)
                    @php
                        $bal = max(0, (float)$t->amount - (float)$t->received - (float)$t->tds);
                        $dueDate = $t->due_date ? \Carbon\Carbon::parse($t->due_date) : null;
                    @endphp
                    <div class="term clickable"
                         @click="Livewire.navigate('{{ route('projects.show', $t->project_id) }}')">
                        <div class="term-body">
                            <div class="term-title">{{ $t->project?->customer?->company }}</div>
                            <div class="term-meta">
                                <span>{{ $t->label }}</span>
                                <span>Due {{ $dueDate ? $dueDate->format('d M') : '—' }}</span>
                            </div>
                        </div>
                        <div class="term-amt">
                            <div class="a">
                                <x-money :value="$bal"/>
                            </div>
                            <div class="b">{{ $t->project?->code }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty py-6 text-center text-gray-400">
                        <span class="flex items-center justify-center mb-1">
                            <svg viewBox="0 0 24 24" class="w-8 h-8 stroke-current fill-none">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <path d="M16 2v4M8 2v4M3 10h18"/>
                            </svg>
                        </span>
                        <p class="text-xs font-semibold text-gray-500">Nothing due in the next few days</p>
                    </div>
                @endforelse
            </div>
        </div>

        <!-- Overdue Payments -->
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Overdue Payments</h3>
                    <div class="text-xs text-[#8894ac]">Needs immediate follow-up</div>
                </div>
            </div>
            <div class="card-body">
                @forelse($fullOverdueTerms as $t)
                    @php
                        $bal = max(0, (float)$t->amount - (float)$t->received - (float)$t->tds);
                        $dueDate = $t->due_date ? \Carbon\Carbon::parse($t->due_date) : null;
                        $daysLate = $dueDate ? abs(\Carbon\Carbon::parse($today)->diffInDays($dueDate, false)) : 0;
                    @endphp
                    <div class="term over clickable"
                         @click="Livewire.navigate('{{ route('projects.show', $t->project_id) }}')">
                        <div class="term-body">
                            <div class="term-title">{{ $t->project?->customer?->company }}</div>
                            <div class="term-meta">
                                <span>{{ $t->label }}</span>
                                <span>Due {{ $dueDate ? $dueDate->format('d M') : '—' }}</span>
                                <span style="color:#d94b4b;font-weight:600">{{ $daysLate }} days late</span>
                            </div>
                        </div>
                        <div class="term-amt">
                            <div class="a">
                                <x-money :value="$bal"/>
                            </div>
                            <div class="b">{{ $t->project?->code }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty py-6 text-center text-gray-400">
                        <span class="flex items-center justify-center mb-1">
                            <svg viewBox="0 0 24 24" class="w-8 h-8 stroke-current fill-none">
                                <circle cx="12" cy="12" r="9"/>
                                <path d="M12 8v5M12 16.5v.5"/>
                            </svg>
                        </span>
                        <p class="text-xs font-semibold text-gray-500">No overdue payments</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- 4. Sales-wise Performance & Operational Cards (AMC + Handover) -->
    <div class="grid-2e" style="margin-bottom:15px">
        <!-- Sales Performance Table -->
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Sales-wise performance</h3>
                    <div class="text-xs text-[#8894ac]">Revenue and collection by salesperson</div>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                    <tr>
                        <th>Salesperson</th>
                        <th>Customers</th>
                        <th>Projects</th>
                        <th class="num">Value</th>
                        <th class="num">Received</th>
                        <th>Collection</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($salesRows as $s)
                        <tr>
                            <td class="strong">{{ $s['name'] }}</td>
                            <td>{{ $s['customers'] }}</td>
                            <td>{{ $s['projects'] }}</td>
                            <td class="num">
                                <x-money :value="$s['value']"/>
                            </td>
                            <td class="num">
                                <x-money :value="$s['received']"/>
                            </td>
                            <td style="min-width:120px">
                                <div class="bar {{ $s['rate'] >= 80 ? 'green' : ($s['rate'] >= 40 ? 'amber' : '') }}">
                                    <i style="width:{{ $s['rate'] }}%"></i>
                                </div>
                                <span class="muted" style="font-size:10.8px">{{ $s['rate'] }}%</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty py-6 text-center text-gray-400">
                                    <p class="text-xs">No sales attribution data yet</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- AMC / Renewal & Handover Pending Widgets -->
        <div>
            <!-- AMC Renewal -->
            <div class="card" style="margin-bottom:15px">
                <div class="card-head">
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">AMC / Renewal</h3>
                        <div class="text-xs text-[#8894ac]">{{ $alertDays }} days before alert</div>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($amcProjects as $a)
                        @php
                            $amcEndDate = $a->amc_end ? \Carbon\Carbon::parse($a->amc_end) : null;
                            $diff = $amcEndDate ? \Carbon\Carbon::parse($today)->diffInDays($amcEndDate, false) : 0;
                        @endphp
                        <div class="mini-item clickable"
                             @click="Livewire.navigate('{{ route('projects.show', $a->id) }}')">
                            <span
                                style="width:30px;height:30px;border-radius:9px;display:grid;place-items:center;background:#eef7e8;color:#5aa236;flex:0 0 30px">
                                <svg viewBox="0 0 24 24" class="w-4 h-4"><path d="M21 12a9 9 0 11-3-6.7"/><path
                                        d="M21 3v6h-6"/></svg>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="t">{{ $a->customer?->company }}</div>
                                <div class="s">{{ $a->name }} · AMC
                                    ends {{ $amcEndDate ? $amcEndDate->format('d M') : '—' }}</div>
                            </div>
                            <div class="r">
                                <span class="badge {{ $diff < 0 ? 'b-red' : 'b-amber' }}">
                                    {{ $diff < 0 ? abs($diff) . ' days late' : ($diff === 0 ? 'today' : $diff . ' days') }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <div class="empty py-6 text-center text-gray-400">
                            <p class="text-xs">No renewals pending</p>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Handover Pending -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Handover Pending</h3>
                        <div class="text-xs text-[#8894ac]">Delivered but checklist incomplete</div>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($handoverProjects as $h)
                        <div class="mini-item clickable"
                             @click="Livewire.navigate('{{ route('projects.show', $h->id) }}')">
                            <svg viewBox="0 0 24 24" class="w-4 h-4 text-amber-600">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                <path d="M4 22v-7"/>
                            </svg>
                            <div style="margin-left:10px;flex:1;min-width:0">
                                <div class="t">{{ $h->name }}</div>
                                <div class="s">{{ $h->customer?->company }} · {{ $h->code }}</div>
                            </div>
                            <div class="r"><span class="badge b-amber">Pending</span></div>
                        </div>
                    @empty
                        <div class="empty py-6 text-center text-gray-400">
                            <p class="text-xs">All handovers complete</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- 5. Payment Ageing Horizontal Breakdown -->
    <div class="card">
        <div class="card-head">
            <div>
                <h3 class="font-bold text-sm text-[#0d1b34]">Payment Ageing</h3>
                <div class="text-xs text-[#8894ac]">How old the overdue amounts are</div>
            </div>
        </div>
        <div class="card-body">
            @php
                $maxAgeVal = max(array_merge([1], array_values($ageing)));
                $ageColors = ['0-30' => '#5aa236', '31-60' => '#2e75b6', '61-90' => '#e0a13a', '90+' => '#d94b4b'];
            @endphp
            <div style="display:flex;flex-direction:column;gap:13px">
                @foreach($ageing as $bracket => $amt)
                    @php
                        $barPct = round(($amt / $maxAgeVal) * 100, 1);
                        $col = $ageColors[$bracket];
                    @endphp
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:12.3px;margin-bottom:5px">
                            <span style="font-weight:600">{{ $bracket }} days</span>
                            <span class="muted" style="font-weight:650"><x-money :value="$amt"/></span>
                        </div>
                        <div class="bar" style="height:8px">
                            <i style="width:{{ $barPct }}%;background:{{ $col }}"></i>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
