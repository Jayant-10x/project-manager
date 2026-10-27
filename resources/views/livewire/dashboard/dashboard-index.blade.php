<div>
    <!-- Top Welcome Header & Scope Bar -->
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#0d1b34] tracking-tight">
                    Welcome back, {{ $user->name }} 👋
                </h1>
                <p class="text-sm text-[#8894ac] mt-1 flex items-center gap-2 flex-wrap">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-[#0b3d91] border border-blue-200">
                        {{ $role }}
                    </span>
                    <span>&middot;</span>
                    <span>{{ now()->format('l, d F Y') }}</span>
                    <span>&middot;</span>
                    <span class="text-slate-500">
                        @switch($role)
                            @case('Admin') Company-wide Executive Governance @break
                            @case('Manager') Operations & Team Delivery Overview @break
                            @case('Coordinator') My Active Projects & Delivery Schedule @break
                            @case('TakePerson')
                            @case('Take Person') My Assigned Projects & Delivery Schedule @break
                            @case('Sales') My Deals Pipeline & Client Accounts @break
                        @endswitch
                    </span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                <a href="{{ route('calendar.index') }}" wire:navigate
                   class="btn btn-outline-primary btn-sm flex items-center gap-1.5 shadow-xs"
                   title="Open Datewise Payment Calendar">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span>Payment Calendar</span>
                </a>
                @if($user->hasPermission('today', 'view'))
                    <a href="{{ route('today.index') }}" wire:navigate
                       class="btn btn-outline-primary btn-sm flex items-center gap-1.5 shadow-xs"
                       title="Open Today's Work Queue">
                        <svg viewBox="0 0 24 24" class="w-4 h-4">
                            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                            <path d="M4 22v-7"/>
                        </svg>
                        <span>Today's Work</span>
                        <livewire:components.dynamic-counter type="today" :warn="true" :hideZero="true"/>
                    </a>
                @endif
                @if($role === 'Sales')
                    <button type="button"
                            class="btn btn-outline-primary btn-sm flex items-center gap-1.5 shadow-xs"
                            @click="$dispatch('open-customer-modal', { id: null })"
                            title="Add Customer">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        <span>Add Customer</span>
                    </button>
                    <button type="button"
                            class="btn btn-primary btn-sm flex items-center gap-1.5 shadow-xs"
                            @click="$dispatch('open-project-modal', { id: null })"
                            title="New Project">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        <span>New Project</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    @if($accessibleUsers->isNotEmpty())
        <div class="card p-3 mb-6 bg-slate-50/70 border border-slate-200/80 flex items-center justify-between flex-wrap gap-3">
            <div class="flex items-center gap-2.5 flex-wrap">
                <span class="text-xs font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                    </svg>
                    <span>Filter by Team Member:</span>
                </span>
                <select wire:model.live="selectedUserId" class="i text-xs py-1.5 px-3 rounded-lg border-slate-300 bg-white" style="width: auto; min-width: 220px;">
                    <option value="">Entire Organization / Team Portfolio</option>
                    @foreach($accessibleUsers as $au)
                        <option value="{{ $au->id }}">{{ $au->name }} ({{ $au->role }})</option>
                    @endforeach
                </select>
                @if($selectedUserId)
                    <button type="button" wire:click="$set('selectedUserId', null)" class="btn btn-outline-danger btn-sm text-xs py-1 px-2.5">
                        Reset Filter
                    </button>
                @endif
            </div>
            @if($selectedUserId && $activeViewingUser && $activeViewingUser->id !== $user->id)
                <div class="text-xs text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-200 font-medium flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                    <span>Filtered View: <strong>{{ $activeViewingUser->name }}</strong> ({{ $activeViewingUser->role }})</span>
                </div>
            @endif
        </div>
    @endif

    <!-- 1. Role-Tailored KPI Row -->
    @switch($role)
        @case('Admin')
            <!-- Admin Financial Summary KPIs (5 Blocks) -->
            <div class="kpis mb-6">
                <x-kpi-card label="Total Receivable" :value="$m['scheduled']" sub="All portfolio projects" icon="cash"
                            :ismoney="true" color="#0b3d91" bg="#e8eefc"
                            :href="$user->hasPermission('schedules', 'view') ? route('schedule.index') : null"/>
                <x-kpi-card label="Received" :value="$m['received']"
                            :sub="$m['tds'] > 0
                            ? ('Realized Revenue · ' . view('components.money', ['value' => $m['cash']])->render() . ' Cash + ' . view('components.money', ['value' => $m['tds']])->render() . ' TDS Credit (' . $m['collectionRate'] . '% settled)')
                            : ('Realized Revenue · ' . $m['collectionRate'] . '% settled to date')"
                            icon="check" :ismoney="true" color="#5aa236" bg="#eef7e8"
                            :href="$user->hasPermission('payments', 'view') ? route('payments.index') : null"/>
                <x-kpi-card label="Due" :value="$m['due']" sub="Balance Due · Net pending collection" icon="clock"
                            color="#e0a13a"
                            :ismoney="true" bg="#fdf3e2"
                            :href="$user->hasPermission('schedules', 'view') ? route('schedule.index') : null"/>
                <x-kpi-card label="Overdue" :value="$m['overdue']"
                            :sub="$overdueCount . ' ' . \Illuminate\Support\Str::plural('installment', $overdueCount) . ' late'"
                            icon="alert"
                            :ismoney="true" color="#d94b4b" bg="#fdeaea"
                            :href="$user->hasPermission('today', 'view') ? route('today.index') : null"/>
                <x-kpi-card label="This Month" :value="$m['thisMonth']" sub="Realized this calendar month" icon="cal"
                            color="#2e75b6"
                            :ismoney="true" bg="#e8f1fa"
                            :href="$user->hasPermission('payments', 'view') ? route('payments.index') : null"/>
            </div>
            <!-- ADMIN VIEW: Macro Portfolio Health & Cash Realization -->
            <div class="grid-2 mb-6">
                <!-- Project Health Breakdown -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Project Health</h3>
                            <div class="text-xs text-[#8894ac]">{{ $totalProjectsCount }} total projects across company
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;gap:14px;flex-wrap:wrap">
                            @php
                                $colors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                            @endphp
                            @foreach($healthCounts as $label => $cnt)
                                @php
                                    $percentage = $totalProjectsCount > 0 ? round(($cnt / $totalProjectsCount) * 100) : 0;
                                    $colorCode = $colors[$label];
                                @endphp
                                <a href="{{ route('projects.index', ['health' => $label]) }}" wire:navigate
                                   class="block p-3 rounded-xl transition hover:bg-gray-50 border border-gray-100 hover:border-gray-200 cursor-pointer"
                                   style="flex:1;min-width:110px;text-decoration:none;color:inherit"
                                   title="Filter {{ $label }} projects">
                                    <div
                                        style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:6px">
                                        <i style="width:8px;height:8px;border-radius:50%;background:{{ $colorCode }};display:inline-block"></i>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <div style="display:flex;align-items:baseline;justify-content:space-between">
                                        <span
                                            style="font-size:22px;font-weight:750;letter-spacing:-.6px">{{ $cnt }}</span>
                                        <span
                                            style="font-size:11px;font-weight:600;color:var(--muted)">{{ $percentage }}%</span>
                                    </div>
                                    <div class="bar" style="height:5px;margin-top:8px">
                                        <i style="width:{{ $percentage }}%;background:{{ $colorCode }}"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- 6-Month Realized Revenue Trend -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Collection Trend</h3>
                            <div class="text-xs text-[#8894ac]">Monthly revenue realized (Last 6 months)</div>
                        </div>
                    </div>
                    <div class="card-body">
                        @php $maxVal = max(array_merge([1], array_column($trend, 'value'))); @endphp
                        <div class="spark-chart" style="display:flex;gap:8px;align-items:flex-end">
                            @foreach($trend as $point)
                                @php $barHeight = max(4, round(($point['value'] / $maxVal) * 100)); @endphp
                                <div class="spark-col">
                                    <div class="spark-val"
                                         x-text="$store.currency.format({{ (float)$point['value'] }}, true)"></div>
                                    <div class="spark-bar-wrap">
                                        <i style="height:{{ $barHeight }}%"
                                           :title="'{{ $point['label'] }}: ' + $store.currency.format({{ (float)$point['value'] }}, false)"></i>
                                    </div>
                                    <div class="spark-label">{{ $point['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Attribution Performance Table -->
            @if(!empty($salesRows))
                <div class="card mb-6">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-amber-50 text-[#e0a13a] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="7"/>
                                <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Sales-wise Performance</h3>
                            <div class="text-xs text-[#8894ac]">Contract booking value and collection rate by
                                salesperson
                            </div>
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
                                <th class="num">Received</th>
                                <th>Collection Rate</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($salesRows as $salesRow)
                                <tr>
                                    <td class="strong">{{ $salesRow['name'] }}</td>
                                    <td>{{ $salesRow['customers'] }}</td>
                                    <td>{{ $salesRow['projects'] }}</td>
                                    <td class="num font-semibold">
                                        <x-money :value="$salesRow['value']"/>
                                    </td>
                                    <td class="num font-semibold text-emerald-700">
                                        <x-money :value="$salesRow['received']"/>
                                    </td>
                                    <td style="min-width:130px">
                                        <div
                                            class="bar {{ $salesRow['rate'] >= 80 ? 'green' : ($salesRow['rate'] >= 40 ? 'amber' : '') }}">
                                            <i style="width:{{ $salesRow['rate'] }}%"></i>
                                        </div>
                                        <span class="muted" style="font-size:10.8px">{{ $salesRow['rate'] }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Payment Ageing Breakdown -->
            <div class="card">
                <div class="card-head flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-[#d94b4b] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 14 14"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Overdue Payment Timeline</h3>
                        <div class="text-xs text-[#8894ac]">Breakdown of overdue payments by days late</div>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $ageing = $ageing ?? ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];
                        $maxAgeVal = max(array_merge([1], array_values($ageing)));
                        $ageColors = ['0-30' => '#5aa236', '31-60' => '#2e75b6', '61-90' => '#e0a13a', '90+' => '#d94b4b'];
                    @endphp
                    <div style="display:flex;flex-direction:column;gap:14px">
                        @foreach($ageing as $bracket => $amt)
                            @php
                                $barPct = round(($amt / $maxAgeVal) * 100, 1);
                                $col = $ageColors[$bracket];
                            @endphp
                            <div>
                                <div
                                    style="display:flex;justify-content:space-between;font-size:12.3px;margin-bottom:5px">
                                    <span style="font-weight:600">{{ $bracket }} days late</span>
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
            @break
        @case('Manager')
            <!-- Manager Operational Delivery & Revenue KPIs (5 Blocks) -->
            <div class="kpis mb-6">
                <x-kpi-card label="Total Receivable" :value="$m['scheduled']" sub="Team Bookings · Total contract value"
                            icon="cash"
                            :ismoney="true" color="#0b3d91" bg="#e8eefc"
                            :href="$user->hasPermission('schedules', 'view') ? route('schedule.index') : null"/>
                <x-kpi-card label="Received" :value="$m['received']"
                            :sub="$m['tds'] > 0
                            ? ('Team Collections · ' . view('components.money', ['value' => $m['cash']])->render() . ' Cash + ' . view('components.money', ['value' => $m['tds']])->render() . ' TDS Credit (' . $m['collectionRate'] . '% settled)')
                            : ('Team Collections · ' . $m['collectionRate'] . '% team realization')"
                            icon="check" :ismoney="true" color="#5aa236" bg="#eef7e8"
                            :href="$user->hasPermission('payments', 'view') ? route('payments.index') : null"/>
                <x-kpi-card label="Due" :value="$m['due']" sub="Balance Due · Net pending collection" icon="clock"
                            color="#e0a13a"
                            :ismoney="true" bg="#fdf3e2"
                            :href="$user->hasPermission('schedules', 'view') ? route('schedule.index') : null"/>
                <x-kpi-card label="Overdue" :value="$m['overdue']"
                            :sub="$overdueCount . ' ' . \Illuminate\Support\Str::plural('installment', $overdueCount) . ' late'"
                            icon="alert"
                            :ismoney="true" color="#d94b4b" bg="#fdeaea"
                            :href="$user->hasPermission('today', 'view') ? route('today.index') : null"/>
                <x-kpi-card label="Active Projects" :value="$totalProjectsCount" sub="Projects currently managed"
                            icon="proj" color="#2e75b6"
                            :ismoney="false" bg="#e8f1fa" :href="route('projects.index')"/>
            </div>
            <!-- MANAGER VIEW: Team Delivery & Coordinator Workload -->
            <div class="grid-2 mb-6">
                <!-- Team Project Health -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Team Project Health</h3>
                            <div class="text-xs text-[#8894ac]">{{ $totalProjectsCount }} projects managed by your team
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;gap:14px;flex-wrap:wrap">
                            @php
                                $colors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                            @endphp
                            @foreach($healthCounts as $label => $cnt)
                                @php
                                    $percentage = $totalProjectsCount > 0 ? round(($cnt / $totalProjectsCount) * 100) : 0;
                                    $colorCode = $colors[$label];
                                @endphp
                                <a href="{{ route('projects.index', ['health' => $label]) }}" wire:navigate
                                   class="block p-3 rounded-xl transition hover:bg-gray-50 border border-gray-100 hover:border-gray-200 cursor-pointer"
                                   style="flex:1;min-width:110px;text-decoration:none;color:inherit">
                                    <div
                                        style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:6px">
                                        <i style="width:8px;height:8px;border-radius:50%;background:{{ $colorCode }};display:inline-block"></i>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <div style="display:flex;align-items:baseline;justify-content:space-between">
                                        <span
                                            style="font-size:22px;font-weight:750;letter-spacing:-.6px">{{ $cnt }}</span>
                                        <span
                                            style="font-size:11px;font-weight:600;color:var(--muted)">{{ $percentage }}%</span>
                                    </div>
                                    <div class="bar" style="height:5px;margin-top:8px">
                                        <i style="width:{{ $percentage }}%;background:{{ $colorCode }}"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Team Collection Trend -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Team Revenue Trend</h3>
                            <div class="text-xs text-[#8894ac]">{{ $totalProjectsCount }} realized collections from team
                                accounts
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @php $maxVal = max(array_merge([1], array_column($trend, 'value'))); @endphp
                        <div class="spark-chart" style="display:flex;gap:8px;align-items:flex-end">
                            @foreach($trend as $point)
                                @php $barHeight = max(4, round(($point['value'] / $maxVal) * 100)); @endphp
                                <div class="spark-col">
                                    <div class="spark-val"
                                         x-text="$store.currency.format({{ (float)$point['value'] }}, true)"></div>
                                    <div class="spark-bar-wrap">
                                        <i style="height:{{ $barHeight }}%"
                                           :title="'{{ $point['label'] }}: ' + $store.currency.format({{ (float)$point['value'] }}, false)"></i>
                                    </div>
                                    <div class="spark-label">{{ $point['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coordinator Workload & Delivery Velocity -->
            <div class="card mb-6">
                <div class="card-head flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-lg bg-indigo-50 text-[#7a5af8] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Coordinator Workload &amp; Delivery Velocity</h3>
                        <div class="text-xs text-[#8894ac]">Active projects and health distribution by coordinator</div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Coordinator</th>
                            <th>Active Projects</th>
                            <th>On Track</th>
                            <th>Delayed / At Risk</th>
                            <th>Delivery Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($teamCoordinators as $coord)
                            <tr>
                                <td>
                                    <div class="font-semibold text-[#0d1b34]">{{ $coord->name }}</div>
                                    <div class="text-xs text-[#8894ac]">{{ $coord->email }}</div>
                                </td>
                                <td class="font-bold text-slate-800">{{ $coord->active_projects_count }}</td>
                                <td class="font-semibold text-emerald-600">{{ $coord->on_track_projects_count }}</td>
                                <td class="font-semibold {{ $coord->delayed_projects_count > 0 ? 'text-red-600' : 'text-slate-400' }}">
                                    {{ $coord->delayed_projects_count }}
                                </td>
                                <td>
                                    @if($coord->delayed_projects_count > 0)
                                        <span class="badge b-red">Attention Needed</span>
                                    @elseif($coord->active_projects_count > 0)
                                        <span class="badge b-green">Healthy</span>
                                    @else
                                        <span class="badge b-gray">Available</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-400 text-xs">
                                    No coordinators currently assigned to your team.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Team Sales Performance -->
            @if(!empty($salesRows))
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Team Sales Performance</h3>
                            <div class="text-xs text-[#8894ac]">Bookings and cash realization across supervised sales
                                team
                            </div>
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
                                <th class="num">Received</th>
                                <th>Collection</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($salesRows as $salesRow)
                                <tr>
                                    <td class="strong">{{ $salesRow['name'] }}</td>
                                    <td>{{ $salesRow['customers'] }}</td>
                                    <td>{{ $salesRow['projects'] }}</td>
                                    <td class="num font-semibold">
                                        <x-money :value="$salesRow['value']"/>
                                    </td>
                                    <td class="num font-semibold text-emerald-700">
                                        <x-money :value="$salesRow['received']"/>
                                    </td>
                                    <td style="min-width:130px">
                                        <div
                                            class="bar {{ $salesRow['rate'] >= 80 ? 'green' : ($salesRow['rate'] >= 40 ? 'amber' : '') }}">
                                            <i style="width:{{ $salesRow['rate'] }}%"></i>
                                        </div>
                                        <span class="muted" style="font-size:10.8px">{{ $salesRow['rate'] }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            @break
        @case('Coordinator')
        @case('TakePerson')
        @case('Take Person')
            <!-- Coordinator / TakePerson Project Delivery & Milestone Health KPIs (5 Blocks) -->
            <div class="kpis mb-6">
                <x-kpi-card label="Active Projects" :value="$coordinatorData['activeProjectsCount']"
                            sub="My Active Projects · In-progress" icon="proj"
                            :ismoney="false" color="#0b3d91" bg="#e8eefc"
                            :href="route('projects.index', ['status' => 'In Progress'])"/>
                <x-kpi-card label="Milestones" :value="$coordinatorData['completedMilestones']"
                            :sub="'Milestones Progress · ' . $coordinatorData['milestoneRate'] . '% completed (' . $coordinatorData['totalMilestones'] . ' total)'"
                            icon="check"
                            :ismoney="false" color="#5aa236" bg="#eef7e8" :href="route('schedule.index')"/>
                <x-kpi-card label="Total Receivable" :value="$m['scheduled']" sub="Assigned project value" icon="cash"
                            :ismoney="true" color="#2e75b6" bg="#e8f1fa" :href="route('projects.index')"/>
                <x-kpi-card label="Due" :value="$m['due']" sub="Net Pending Collection · On my projects" icon="clock"
                            color="#e0a13a"
                            :ismoney="true" bg="#fdf3e2" :href="route('schedule.index')"/>
                <x-kpi-card label="Overdue" :value="$m['overdue']" :sub="$overdueCount . ' late on my accounts'"
                            icon="alert"
                            :ismoney="true" color="#d94b4b" bg="#fdeaea" :href="route('today.index')"/>
            </div>
            <!-- COORDINATOR VIEW: Milestones & Project Deliveries -->
            <div class="grid-2 mb-6">
                <!-- My Projects Health -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">My Projects Health</h3>
                            <div class="text-xs text-[#8894ac]">{{ $totalProjectsCount }} projects assigned to you</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;gap:14px;flex-wrap:wrap">
                            @php
                                $colors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                            @endphp
                            @foreach($healthCounts as $label => $cnt)
                                @php
                                    $percentage = $totalProjectsCount > 0 ? round(($cnt / $totalProjectsCount) * 100) : 0;
                                    $colorCode = $colors[$label];
                                @endphp
                                <a href="{{ route('projects.index', ['health' => $label]) }}" wire:navigate
                                   class="block p-3 rounded-xl transition hover:bg-gray-50 border border-gray-100 hover:border-gray-200 cursor-pointer"
                                   style="flex:1;min-width:110px;text-decoration:none;color:inherit">
                                    <div
                                        style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:6px">
                                        <i style="width:8px;height:8px;border-radius:50%;background:{{ $colorCode }};display:inline-block"></i>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <div style="display:flex;align-items:baseline;justify-content:space-between">
                                        <span
                                            style="font-size:22px;font-weight:750;letter-spacing:-.6px">{{ $cnt }}</span>
                                        <span
                                            style="font-size:11px;font-weight:600;color:var(--muted)">{{ $percentage }}%</span>
                                    </div>
                                    <div class="bar" style="height:5px;margin-top:8px">
                                        <i style="width:{{ $percentage }}%;background:{{ $colorCode }}"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Upcoming Project Deliveries (Next 30 Days) -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 14 14"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Upcoming Deliveries</h3>
                            <div class="text-xs text-[#8894ac]">Projects scheduled for completion in next 30 days</div>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($coordinatorData['upcomingDeliveries'] as $proj)
                            @php
                                $delDate = $proj->delivery_date ? \Carbon\Carbon::parse($proj->delivery_date) : null;
                                $daysLeft = $delDate ? (int) round(\Carbon\Carbon::today()->startOfDay()->diffInDays($delDate->startOfDay(), false)) : 0;
                            @endphp
                            <div
                                class="mini-item clickable flex items-center justify-between p-2.5 rounded-xl border border-gray-100 hover:bg-gray-50/70 transition mb-2"
                                @click="Livewire.navigate('{{ route('projects.show', $proj->id) }}')">
                                <div class="flex-1 min-w-0 pr-3">
                                    <div class="font-semibold text-sm text-[#0d1b34]">{{ $proj->name }}</div>
                                    <div class="text-xs text-[#8894ac] flex items-center gap-1.5 mt-0.5">
                                        <span>{{ $proj->customer?->display_name ?? '—' }}</span>
                                        <span>&middot;</span>
                                        <span>Due {{ $delDate ? $delDate->format('d M') : '—' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                <span class="badge {{ $daysLeft <= 7 ? 'b-red' : 'b-amber' }}">
                                    {{ $daysLeft == 0 ? 'Today' : ($daysLeft < 0 ? abs($daysLeft) . 'd late' : $daysLeft . 'd left') }}
                                </span>
                                    <x-health-badge :health="$proj->health"/>
                                </div>
                            </div>
                        @empty
                            <div class="empty py-6 text-center text-gray-400">
                            <span class="flex items-center justify-center mb-1 text-emerald-500">
                                <svg viewBox="0 0 24 24" class="w-7 h-7 stroke-current fill-none"><path
                                        d="M20 6L9 17l-5-5"/></svg>
                            </span>
                                <p class="text-xs font-semibold text-gray-500">No project deliveries due in next 30
                                    days</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- 6-Month Realized Revenue Trend -->
            <div class="card">
                <div class="card-head flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                            <polyline points="17 6 23 6 23 12"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Project Cash Flow Trend</h3>
                        <div class="text-xs text-[#8894ac]">Collections recorded on your assigned accounts</div>
                    </div>
                </div>
                <div class="card-body">
                    @php $maxVal = max(array_merge([1], array_column($trend, 'value'))); @endphp
                    <div class="spark-chart" style="display:flex;gap:8px;align-items:flex-end">
                        @foreach($trend as $point)
                            @php $barHeight = max(4, round(($point['value'] / $maxVal) * 100)); @endphp
                            <div class="spark-col">
                                <div class="spark-val"
                                     x-text="$store.currency.format({{ (float)$point['value'] }}, true)"></div>
                                <div class="spark-bar-wrap">
                                    <i style="height:{{ $barHeight }}%"
                                       :title="'{{ $point['label'] }}: ' + $store.currency.format({{ (float)$point['value'] }}, false)"></i>
                                </div>
                                <div class="spark-label">{{ $point['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @break
        @case('Sales')
            <!-- Sales Deal Bookings & Performance KPIs (5 Blocks) -->
            <div class="kpis mb-6">
                <x-kpi-card label="Total Receivable" :value="$salesData['totalDealsValue']"
                            :sub="'Total Closed Bookings · ' . $salesData['dealsCount'] . ' deals closed'" icon="cash"
                            :ismoney="true" color="#0b3d91" bg="#e8eefc" :href="route('projects.index')"/>
                @if($salesData['target'] > 0)
                    <x-kpi-card label="Target" :value="$salesData['target']"
                                :sub="$salesData['targetProgress'] . '% quota achieved'" icon="target"
                                :ismoney="true" color="#5aa236" bg="#eef7e8" :href="route('projects.index')"/>
                @else
                    <x-kpi-card label="Avg Deal Value" :value="$salesData['avgDealSize']" sub="Per closed project"
                                icon="chart"
                                :ismoney="true" color="#5aa236" bg="#eef7e8" :href="route('projects.index')"/>
                @endif
                <x-kpi-card label="Received" :value="$salesData['grossRealized']"
                            :sub="$salesData['tdsRealized'] > 0
                            ? ('Cash Inflow Realized · ' . view('components.money', ['value' => $salesData['cashRealized']])->render() . ' Cash + ' . view('components.money', ['value' => $salesData['tdsRealized']])->render() . ' TDS Credit (' . $salesData['realizationRate'] . '% settled)')
                            : ('Cash Inflow Realized · ' . $salesData['realizationRate'] . '% cash collected')"
                            icon="cash" color="#2e75b6"
                            :ismoney="true" bg="#e8f1fa"
                            :href="auth()->user()->hasPermission('payments', 'view') ? route('payments.index') : route('projects.index')"/>
                <x-kpi-card label="Customers" :value="$salesData['customersAcquired']" sub="Client accounts acquired"
                            icon="cust" color="#e0a13a"
                            :ismoney="false" bg="#fdf3e2" :href="route('customers.index')"/>
                <x-kpi-card label="Projects" :value="$totalProjectsCount" sub="Active client projects" icon="proj"
                            color="#0b3d91"
                            :ismoney="false" bg="#e8eefc" :href="route('projects.index')"/>
            </div>
            <!-- SALES VIEW: Deal Origination, Quota Progress & Client Projects -->
            <div class="grid-2 mb-6">
                <!-- Client Projects Fulfillment Status -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Client Projects Fulfillment</h3>
                            <div class="text-xs text-[#8894ac]">Execution status of projects sold by you</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;gap:14px;flex-wrap:wrap">
                            @php
                                $colors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                            @endphp
                            @foreach($healthCounts as $label => $cnt)
                                @php
                                    $percentage = $totalProjectsCount > 0 ? round(($cnt / $totalProjectsCount) * 100) : 0;
                                    $colorCode = $colors[$label];
                                @endphp
                                <a href="{{ route('projects.index', ['health' => $label]) }}" wire:navigate
                                   class="block p-3 rounded-xl transition hover:bg-gray-50 border border-gray-100 hover:border-gray-200 cursor-pointer"
                                   style="flex:1;min-width:110px;text-decoration:none;color:inherit">
                                    <div
                                        style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:6px">
                                        <i style="width:8px;height:8px;border-radius:50%;background:{{ $colorCode }};display:inline-block"></i>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <div style="display:flex;align-items:baseline;justify-content:space-between">
                                        <span
                                            style="font-size:22px;font-weight:750;letter-spacing:-.6px">{{ $cnt }}</span>
                                        <span
                                            style="font-size:11px;font-weight:600;color:var(--muted)">{{ $percentage }}%</span>
                                    </div>
                                    <div class="bar" style="height:5px;margin-top:8px">
                                        <i style="width:{{ $percentage }}%;background:{{ $colorCode }}"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Recent Closed Deals & Accounts -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 00-3-3.87"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Recent Closed Deals</h3>
                            <div class="text-xs text-[#8894ac]">Latest client accounts originated</div>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($salesData['recentDeals'] as $deal)
                            <div
                                class="mini-item clickable flex items-center justify-between p-2.5 rounded-xl border border-gray-100 hover:bg-gray-50/70 transition mb-2"
                                @click="Livewire.navigate('{{ route('projects.show', $deal->id) }}')">
                                <div class="flex-1 min-w-0 pr-3">
                                    <div class="font-semibold text-sm text-[#0d1b34]">{{ $deal->name }}</div>
                                    <div class="text-xs text-[#8894ac] flex items-center gap-1.5 mt-0.5">
                                        <span>{{ $deal->customer?->display_name ?? '—' }}</span>
                                        <span>&middot;</span>
                                        <span class="font-semibold text-emerald-700"><x-money
                                                :value="$deal->value"/></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 flex-shrink-0">
                                    <x-health-badge :health="$deal->health"/>
                                </div>
                            </div>
                        @empty
                            <div class="empty py-6 text-center text-gray-400">
                                <p class="text-xs font-semibold text-gray-500">No deals originated yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            @if($salesData['target'] > 0)
                <!-- Quota Progress Card for Sales -->
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Sales Quota Target</h3>
                            <div class="text-xs text-[#8894ac]">Closed revenue vs assigned sales quota</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="flex items-baseline justify-between mb-2">
                            <div class="font-bold text-lg text-[#0d1b34]">
                                <x-money :value="$salesData['totalDealsValue']"/>
                                <span class="text-sm font-normal text-[#8894ac]">booked of</span>
                                <x-money :value="$salesData['target']"/>
                                <span class="text-sm font-normal text-[#8894ac]">quota</span>
                            </div>
                            <div
                                class="font-bold text-base {{ $salesData['targetProgress'] >= 80 ? 'text-emerald-600' : 'text-blue-600' }}">
                                {{ $salesData['targetProgress'] }}% Achieved
                            </div>
                        </div>
                        <div class="bar" style="height:10px">
                            <i style="width:{{ $salesData['targetProgress'] }}%;background:{{ $salesData['targetProgress'] >= 80 ? '#5aa236' : '#0b3d91' }}"></i>
                        </div>
                    </div>
                </div>
            @endif
            @break
    @endswitch
</div>
