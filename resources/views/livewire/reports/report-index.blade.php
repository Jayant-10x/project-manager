<div>
    <!-- Scope Bar & Role Header -->
    <div class="mb-6 flex items-center justify-between flex-wrap gap-4">
        <div>
            <h2 class="text-xl font-bold text-[#0d1b34] tracking-tight">
                {{ $role }} Analytics &amp; Reports
            </h2>
            <p class="text-xs text-[#8894ac] mt-0.5 flex items-center gap-2">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-[#0b3d91] border border-blue-200">
                    {{ $role }} Scope
                </span>
                <span>&middot;</span>
                <span>{{ now()->format('F Y') }} Reporting Period</span>
            </p>
        </div>
    </div>

    <!-- 1. Executive Analytics & KPI Summary Section Card -->
    <div class="card mb-6">
        <div class="card-head flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">
                        @switch($role)
                            @case('Admin') Executive Revenue &amp; Collection Analytics @break
                            @case('Manager') Team Revenue &amp; Collections Summary @break
                            @case('Coordinator') Portfolio Payments &amp; Delivery Progress @break
                            @case('TechPerson') Execution &amp; Milestone Delivery Summary @break
                            @case('Sales') Deals Realization &amp; Collections Summary @break
                        @endswitch
                    </h3>
                    <div class="text-xs text-[#8894ac]">Reconciled contract values, gross settled collections &amp; pending dues</div>
                </div>
            </div>
            <div class="text-xs text-slate-500 font-medium">
                Period: <span class="font-semibold text-slate-700">{{ now()->format('F Y') }}</span>
            </div>
        </div>
        <div class="card-body p-4 sm:p-5 bg-slate-50/40">
            @if($role === 'Admin')
                <div class="kpis mb-0">
                    <x-kpi-card label="Total Receivable" :value="$totalScheduled" sub="Portfolio contract value across organization" icon="cash"
                                :ismoney="true" color="#0b3d91" bg="#e8eefc" :href="route('schedule.index')"/>
                    <x-kpi-card label="Received" :value="$totalReceived"
                                :sub="$totalTds > 0
                                    ? (view('components.money', ['value' => $totalCash])->render() . ' Cash + ' . view('components.money', ['value' => $totalTds])->render() . ' TDS Credit (' . $collectionRate . '% settled)')
                                    : ('Realized Revenue · ' . $collectionRate . '% settled to date')"
                                icon="check" :ismoney="true" color="#5aa236" bg="#eef7e8" :href="route('payments.index')"/>
                    <x-kpi-card label="Due" :value="$totalDue" sub="Balance Due · Net pending collection" icon="clock"
                                color="#e0a13a" :ismoney="true" bg="#fdf3e2" :href="route('schedule.index')"/>
                    <x-kpi-card label="Overdue" :value="$overdueAmt" :sub="$overdueCount . ' ' . \Illuminate\Support\Str::plural('installment', $overdueCount) . ' late'" icon="alert"
                                :ismoney="true" color="#d94b4b" bg="#fdeaea" :href="route('today.index')"/>
                    <x-kpi-card label="TDS Deducted" :value="$totalTds" sub="Withheld TDS against invoices" icon="shield" color="#7a5af8"
                                :ismoney="true" bg="#f0ecff" :href="route('payments.index')"/>
                </div>
            @elseif($role === 'Manager')
                <div class="kpis mb-0">
                    <x-kpi-card label="Total Receivable" :value="$totalScheduled" sub="Team Bookings · Total team contract value" icon="cash"
                                :ismoney="true" color="#0b3d91" bg="#e8eefc" :href="route('schedule.index')"/>
                    <x-kpi-card label="Received" :value="$totalReceived"
                                :sub="$totalTds > 0
                                    ? (view('components.money', ['value' => $totalCash])->render() . ' Cash + ' . view('components.money', ['value' => $totalTds])->render() . ' TDS Credit (' . $collectionRate . '% settled)')
                                    : ('Team Realized · ' . $collectionRate . '% collected to date')"
                                icon="check"
                                :ismoney="true" color="#5aa236" bg="#eef7e8" :href="route('payments.index')"/>
                    <x-kpi-card label="Due" :value="$totalDue" sub="Balance Due · Net pending collection" icon="clock"
                                color="#e0a13a" :ismoney="true" bg="#fdf3e2" :href="route('schedule.index')"/>
                    <x-kpi-card label="Overdue" :value="$overdueAmt" :sub="'Team Overdue · ' . $overdueCount . ' installments late'" icon="alert"
                                :ismoney="true" color="#d94b4b" bg="#fdeaea" :href="route('today.index')"/>
                    <x-kpi-card label="Active Projects" :value="$projectsCount" sub="Managed by your team" icon="proj"
                                :ismoney="false" color="#2e75b6" bg="#e8f1fa" :href="route('projects.index')"/>
                </div>
            @elseif($role === 'Coordinator')
                <div class="kpis mb-0">
                    <x-kpi-card label="Total Receivable" :value="$totalScheduled" sub="Portfolio Value · Contract value on accounts" icon="cash"
                                :ismoney="true" color="#0b3d91" bg="#e8eefc" :href="route('schedule.index')"/>
                    <x-kpi-card label="Received" :value="$totalReceived"
                                :sub="$totalTds > 0
                                    ? (view('components.money', ['value' => $totalCash])->render() . ' Cash + ' . view('components.money', ['value' => $totalTds])->render() . ' TDS Credit (' . $collectionRate . '% settled)')
                                    : ('Realized Revenue · ' . $collectionRate . '% settled to date')"
                                icon="check" :ismoney="true" color="#5aa236" bg="#eef7e8" :href="route('payments.index')"/>
                    <x-kpi-card label="Due" :value="$totalDue" sub="Balance Due · Net pending collection" icon="clock"
                                color="#e0a13a" :ismoney="true" bg="#fdf3e2" :href="route('schedule.index')"/>
                    <x-kpi-card label="Overdue" :value="$overdueAmt" :sub="'Overdue on Projects · ' . $overdueCount . ' pending recovery'" icon="alert"
                                :ismoney="true" color="#d94b4b" bg="#fdeaea" :href="route('today.index')"/>
                    <x-kpi-card label="Active Projects" :value="$projectsCount"
                                :sub="'Assigned Projects · ' . ($coordinatorData['completedMilestones'] ?? 0) . ' / ' . ($coordinatorData['totalMilestones'] ?? 0) . ' done (' . ($coordinatorData['milestoneRate'] ?? 0) . '%)'"
                                icon="proj" :ismoney="false" color="#2e75b6" bg="#e8f1fa" :href="route('projects.index')"/>
                </div>
            @elseif($role === 'TechPerson')
                <div class="kpis mb-0">
                    <x-kpi-card label="Active Projects" :value="$projectsCount"
                                sub="My Active Projects · In progress"
                                icon="proj" :ismoney="false" color="#0b3d91" bg="#e8eefc" :href="route('projects.index', ['status' => 'In Progress'])"/>
                    <x-kpi-card label="Milestones Done"
                                :value="($coordinatorData['completedMilestones'] ?? 0) . ' / ' . ($coordinatorData['totalMilestones'] ?? 0)"
                                :sub="'Milestone Velocity · ' . ($coordinatorData['milestoneRate'] ?? 0) . '% completed'"
                                icon="check" :ismoney="false" color="#5aa236" bg="#eef7e8" :href="route('projects.index')"/>
                    <x-kpi-card label="On Track" :value="$healthDistribution['On Track']" sub="Projects on track" icon="check"
                                color="#5aa236" :ismoney="false" bg="#eef7e8" :href="route('projects.index', ['health' => 'On Track'])"/>
                    <x-kpi-card label="Delayed / At Risk" :value="$healthDistribution['Delayed'] + $healthDistribution['At Risk']"
                                sub="Projects needing attention" icon="alert"
                                :ismoney="false" color="#d94b4b" bg="#fdeaea" :href="route('projects.index', ['health' => 'Delayed'])"/>
                    <x-kpi-card label="Completed" :value="$healthDistribution['Completed']"
                                sub="Successfully delivered projects"
                                icon="shield" :ismoney="false" color="#2e75b6" bg="#e8f1fa" :href="route('projects.index', ['status' => 'Delivered'])"/>
                </div>
            @elseif($role === 'Sales')
                <div class="kpis mb-0">
                    <x-kpi-card label="Total Receivable" :value="$salesData['totalDealsValue']" :sub="'Total Closed Deals · ' . $salesData['dealsCount'] . ' deals closed'" icon="cash"
                                :ismoney="true" color="#0b3d91" bg="#e8eefc" :href="route('projects.index')"/>
                    <x-kpi-card label="Received" :value="$salesData['grossRealized']"
                                :sub="$salesData['tdsRealized'] > 0
                                    ? (view('components.money', ['value' => $salesData['cashRealized']])->render() . ' Cash + ' . view('components.money', ['value' => $salesData['tdsRealized']])->render() . ' TDS Credit (' . $salesData['realizationRate'] . '% settled)')
                                    : ('Cash Realized · ' . $salesData['realizationRate'] . '% realized from deals')"
                                icon="check" color="#5aa236"
                                :ismoney="true" bg="#eef7e8" :href="auth()->user()->hasPermission('payments', 'view') ? route('payments.index') : route('projects.index')"/>
                    <x-kpi-card label="Due" :value="$salesData['dueAmount']" sub="Balance Due · Pending collection on deals" icon="clock"
                                color="#e0a13a" :ismoney="true" bg="#fdf3e2"
                                :href="auth()->user()->hasPermission('schedules', 'view') ? route('schedule.index') : route('projects.index')"/>
                    <x-kpi-card label="Overdue" :value="$salesData['overdueAmount']" :sub="$salesData['overdueCount'] . ' late on customer accounts'" icon="alert"
                                :ismoney="true" color="#d94b4b" bg="#fdeaea"
                                :href="auth()->user()->hasPermission('today', 'view') ? route('today.index') : route('projects.index')"/>
                    @if($salesData['target'] > 0)
                        <x-kpi-card label="Target" :value="$salesData['target']" :sub="'Quota Target · ' . $salesData['targetProgress'] . '% quota achieved'" icon="target"
                                    :ismoney="true" color="#2e75b6" bg="#e8f1fa" :href="route('projects.index')"/>
                    @else
                        <x-kpi-card label="Customers" :value="$salesData['customersAcquired']" sub="Customer Accounts · Acquired customers" icon="cust" color="#2e75b6"
                                    :ismoney="false" bg="#e8f1fa" :href="route('customers.index')"/>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- 2. Role-Specific Charts & Reports Breakdown -->
    @if($role === 'Admin' || $role === 'Manager')
        <!-- Monthly Collection Trend & Payment Mode Breakdown -->
        <div class="grid-2e mb-6">
            <!-- Monthly Collection Sparkline -->
            <div class="card">
                <div class="card-head p-4 border-b border-[#eef2f9] flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Monthly Collections</h3>
                        <div class="text-xs text-[#8894ac]">Realized cash collections over past 6 months</div>
                    </div>
                </div>
                <div class="card-body">
                    @php $maxVal = max(array_merge([1], array_column($trend, 'value'))); @endphp
                    <div class="spark-chart" style="display:flex;gap:6px;align-items:flex-end">
                        @foreach($trend as $trendItem)
                            @php $barHeight = max(4, round(($trendItem['value'] / $maxVal) * 100)); @endphp
                            <div class="spark-col">
                                <div class="spark-val" x-text="$store.currency.format({{ (float)$trendItem['value'] }}, true)"></div>
                                <div class="spark-bar-wrap">
                                    <i style="height:{{ $barHeight }}%"
                                       :title="'{{ $trendItem['label'] }}: ' + $store.currency.format({{ (float)$trendItem['value'] }}, false)"></i>
                                </div>
                                <div class="spark-label">{{ $trendItem['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Payment Mode Horizontal Bars -->
            <div class="card">
                <div class="card-head p-4 border-b border-[#eef2f9] flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="6" width="20" height="12" rx="2"/><circle cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Payment Modes</h3>
                        <div class="text-xs text-[#8894ac]">Channel breakdown of cleared receipts</div>
                    </div>
                </div>
                <div class="card-body">
                    @php $maxMode = max(array_merge([1], array_column($modes, 'value'))); @endphp
                    @if(empty($modes))
                        <div class="empty py-6 text-center text-gray-400">
                            <p class="text-xs">No receipts logged yet</p>
                        </div>
                    @else
                        <div style="display:flex;flex-direction:column;gap:13px">
                            @foreach($modes as $modeItem)
                                @php $pct = round(($modeItem['value'] / $maxMode) * 100, 1); @endphp
                                <div>
                                    <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:4px">
                                        <span class="font-semibold text-slate-800">{{ $modeItem['label'] }}</span>
                                        <span class="font-bold text-slate-700" x-text="$store.currency.format({{ (float)$modeItem['value'] }})"></span>
                                    </div>
                                    <div class="bar" style="height:7px">
                                        <i style="width:{{ $pct }}%;background:#0b3d91"></i>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>

        @if($role === 'Manager' && $teamCoordinators->isNotEmpty())
            <!-- Coordinator Workload & Delivery Velocity Table -->
            <div class="card mb-6">
                <div class="card-head p-4 border-b border-[#eef2f9] flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-[#7a5af8] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Team Coordinator Velocity</h3>
                        <div class="text-xs text-[#8894ac]">Active project assignments and delivery distribution</div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Coordinator</th>
                                <th>Active Projects</th>
                                <th>On Track</th>
                                <th>Delayed / Risk</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($teamCoordinators as $coordinator)
                                <tr>
                                    <td class="font-semibold text-[#0d1b34]">{{ $coordinator->name }}</td>
                                    <td class="font-bold text-slate-800">{{ $coordinator->active_projects_count }}</td>
                                    <td class="font-semibold text-emerald-600">{{ $coordinator->on_track_projects_count }}</td>
                                    <td class="font-semibold {{ $coordinator->delayed_projects_count > 0 ? 'text-red-600' : 'text-slate-400' }}">
                                        {{ $coordinator->delayed_projects_count }}
                                    </td>
                                    <td>
                                        @if($coordinator->delayed_projects_count > 0)
                                            <span class="badge b-red">Attention Needed</span>
                                        @else
                                            <span class="badge b-green">Healthy</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Overdue Ageing Analysis -->
        <div class="card mb-6">
            <div class="card-head p-4 border-b border-[#eef2f9] flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-[#d94b4b] flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Overdue Payment Timeline</h3>
                    <div class="text-xs text-[#8894ac]">Payment Ageing Analysis &middot; Uncollected balance grouped by days overdue</div>
                </div>
            </div>
            <div class="card-body">
                @php
                    $colors = ['0-30' => '#5aa236', '31-60' => '#e0a13a', '61-90' => '#d94b4b', '90+' => '#991b1b'];
                    $totalAgeing = array_sum($ageing);
                @endphp
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    @foreach($ageing as $bucket => $amount)
                        @php
                            $pct = $totalAgeing > 0 ? round(($amount / $totalAgeing) * 100, 1) : 0;
                            $col = $colors[$bucket];
                        @endphp
                        <a href="{{ route('schedule.index', ['status' => 'Overdue']) }}" wire:navigate
                           class="p-3.5 rounded-xl border border-gray-100 bg-gray-50/70 hover:bg-gray-100/80 hover:border-gray-200 transition cursor-pointer block no-underline text-inherit"
                           title="Filter overdue installments in schedule">
                            <div class="text-xs font-semibold text-slate-600 mb-1 flex items-center justify-between">
                                <span>{{ $bucket }} Days</span>
                                <span class="font-bold" style="color:{{ $col }}">{{ $pct }}%</span>
                            </div>
                            <div class="text-lg font-bold text-slate-800" x-text="$store.currency.format({{ (float)$amount }})"></div>
                            <div class="bar mt-2" style="height:6px">
                                <i style="width:{{ $pct }}%;background:{{ $col }}"></i>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>

        @if(!empty($salesRows))
            <!-- Sales Attribution Leaderboard -->
            <div class="card mb-6">
                <div class="card-head p-4 border-b border-[#eef2f9] flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-amber-50 text-[#e0a13a] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="7"/><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Sales Attribution &amp; Performance</h3>
                        <div class="text-xs text-[#8894ac]">Booking value and collection realization by salesperson</div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Salesperson</th>
                                <th>Customers</th>
                                <th>Projects</th>
                                <th class="num">Contract Value</th>
                                <th class="num">Realized</th>
                                <th>Collection Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesRows as $sr)
                                <tr>
                                    <td class="font-semibold text-[#0d1b34]">{{ $sr['name'] }}</td>
                                    <td>{{ $sr['customers'] }}</td>
                                    <td>{{ $sr['projects'] }}</td>
                                    <td class="num font-semibold" x-text="$store.currency.format({{ (float)$sr['value'] }})"></td>
                                    <td class="num font-semibold text-emerald-700" x-text="$store.currency.format({{ (float)$sr['received'] }})"></td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-xs">{{ $sr['rate'] }}%</span>
                                            <div class="bar flex-1" style="height:5px">
                                                <i style="width:{{ $sr['rate'] }}%;background:#5aa236"></i>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Milestone Execution vs Payment Realization Variance Table -->
        <div class="card mb-6">
            <div class="card-head p-4 border-b border-[#eef2f9] flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Milestone Delivery vs Cash Realization Variance</h3>
                    <div class="text-xs text-[#8894ac]">Identifies delivery milestones completed ahead of payment collection</div>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Project</th>
                            <th>Milestones Done</th>
                            <th>Revenue Realized</th>
                            <th>Delivery/Payment Gap</th>
                            <th class="num">Balance Due</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($varianceRows as $varianceRow)
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $varianceRow['project']->id) }}" wire:navigate class="font-bold text-blue-900 hover:underline">
                                        {{ $varianceRow['project']->name }}
                                    </a>
                                    <div class="text-xs text-[#8894ac]">{{ $varianceRow['project']->customer?->display_name ?? '—' }}</div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs">{{ $varianceRow['msPct'] }}%</span>
                                        <div class="bar flex-1" style="height:5px">
                                            <i style="width:{{ $varianceRow['msPct'] }}%;background:#2e75b6"></i>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="flex items-center gap-2">
                                        <span class="font-bold text-xs">{{ $varianceRow['moneyPct'] }}%</span>
                                        <div class="bar flex-1" style="height:5px">
                                            <i style="width:{{ $varianceRow['moneyPct'] }}%;background:#5aa236"></i>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    @if($varianceRow['gap'] > 15)
                                        <span class="badge b-red">⚠️ +{{ $varianceRow['gap'] }}% Ahead</span>
                                    @elseif($varianceRow['gap'] < -15)
                                        <span class="badge b-amber">Payment Ahead</span>
                                    @else
                                        <span class="badge b-green">Synchronized</span>
                                    @endif
                                </td>
                                <td class="num font-semibold text-slate-800" x-text="$store.currency.format({{ (float)$varianceRow['due'] }})"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-400 text-xs">No project variance records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    @elseif($role === 'Coordinator' || $role === 'TechPerson')
        <!-- COORDINATOR & TECH PERSON VIEW -->
        <div class="grid-2 mb-6">
            <!-- Upcoming Deliveries (Next 60 Days) -->
            <div class="card">
                <div class="card-head p-4 border-b border-[#eef2f9] flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Upcoming Project Deliveries (Next 60 Days)</h3>
                        <div class="text-xs text-[#8894ac]">Target delivery deadlines on your accounts</div>
                    </div>
                </div>
                <div class="card-body">
                    @forelse($coordinatorData['upcomingDeliveries'] as $p)
                        @php
                            $del = $p->delivery_date ? \Carbon\Carbon::parse($p->delivery_date) : null;
                            $days = $del ? (int) round(\Carbon\Carbon::today()->startOfDay()->diffInDays($del->startOfDay(), false)) : 0;
                        @endphp
                        <div class="flex items-center justify-between p-3 rounded-xl border border-gray-100 hover:bg-gray-50/70 transition mb-2">
                            <div class="min-w-0 pr-3">
                                <a href="{{ route('projects.show', $p->id) }}" wire:navigate class="font-bold text-sm text-[#0d1b34] hover:underline">
                                    {{ $p->name }}
                                </a>
                                <div class="text-xs text-[#8894ac]">{{ $p->customer?->display_name ?? '—' }} &middot; Due {{ $del ? $del->format('d M Y') : '—' }}</div>
                            </div>
                            <span class="badge {{ $days <= 7 ? 'b-red' : 'b-amber' }}">
                                {{ $days == 0 ? 'Today' : ($days < 0 ? abs($days) . 'd late' : $days . 'd left') }}
                            </span>
                        </div>
                    @empty
                        <div class="empty py-6 text-center text-gray-400 text-xs">
                            No project delivery deadlines scheduled in next 60 days.
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Milestone Execution Variance Table -->
            <div class="card">
                <div class="card-head p-4 border-b border-[#eef2f9] flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-indigo-50 text-[#7a5af8] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 11 12 14 22 4"/><path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Milestone Delivery Execution</h3>
                        <div class="text-xs text-[#8894ac]">Milestones completed vs pending on assigned projects</div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Project</th>
                                <th>Milestones</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($varianceRows as $varianceRow)
                                <tr>
                                    <td>
                                        <a href="{{ route('projects.show', $varianceRow['project']->id) }}" wire:navigate class="font-bold text-xs text-blue-900 hover:underline">
                                            {{ $varianceRow['project']->name }}
                                        </a>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <span class="font-bold text-xs">{{ $varianceRow['msPct'] }}%</span>
                                            <div class="bar flex-1" style="height:5px">
                                                <i style="width:{{ $varianceRow['msPct'] }}%;background:#0b3d91"></i>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <x-health-badge :health="$varianceRow['health']"/>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center py-6 text-gray-400 text-xs">No assigned projects.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    @elseif($role === 'Sales')
        <!-- SALES VIEW -->
        <div class="card mb-6">
            <div class="card-head p-4 border-b border-[#eef2f9] flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Deals Pipeline &amp; Customer Realization</h3>
                    <div class="text-xs text-[#8894ac]">Closed customer accounts, payment collections and fulfillment health</div>
                </div>
            </div>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Deal / Project</th>
                            <th>Customer</th>
                            <th class="num">Contract Value</th>
                            <th class="num">Cash Cleared</th>
                            <th>Fulfillment Health</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($salesData['deals'] as $deal)
                            @php
                                $cleared = (float) $deal->payments->sum('amount');
                            @endphp
                            <tr>
                                <td>
                                    <a href="{{ route('projects.show', $deal->id) }}" wire:navigate class="font-bold text-blue-900 hover:underline">
                                        {{ $deal->name }}
                                    </a>
                                    <div class="text-xs text-[#8894ac]">{{ $deal->code }}</div>
                                </td>
                                <td class="font-semibold text-[#0d1b34]">{{ $deal->customer?->display_name ?? '—' }}</td>
                                <td class="num font-bold text-slate-800" x-text="$store.currency.format({{ (float)$deal->value }})"></td>
                                <td class="num font-semibold text-emerald-700" x-text="$store.currency.format({{ $cleared }})"></td>
                                <td>
                                    <x-health-badge :health="$deal->health"/>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-8 text-gray-400 text-xs">No deals originated yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
