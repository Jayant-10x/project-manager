<div>
    <!-- 1. Top 4 KPI Blocks matching viewReports() -->
    <div class="kpis four mb-6">
        <x-kpi-card label="Collection rate" :value="$collectionRate . '%'"
                    :sub="view('components.money', ['value' => $totalReceived])->render() . ' of ' . view('components.money', ['value' => $totalScheduled])->render()"
                    icon="chart" color="#5aa236" bg="#eef7e8" :href="route('schedule.index')"/>
        <x-kpi-card label="Overdue" :value="$overdueAmt" :sub="$overdueCount . ' installments'" icon="alert"
                    :ismoney="true" color="#d94b4b" bg="#fdeaea" :href="route('today.index')"/>
        <x-kpi-card label="TDS deducted" :value="$totalTds" sub="Received against invoices" icon="cash" color="#7a5af8"
                    :ismoney="true" bg="#f0ecff" :href="route('payments.index')"/>
        <x-kpi-card label="Average project value" :value="$avgProjectValue" :sub="$projectsCount . ' ' . \Illuminate\Support\Str::plural('project', $projectsCount)"
                    :ismoney="true" icon="cash" color="#0b3d91" bg="#e8eefc" :href="route('projects.index')"/>
    </div>

    <!-- 2. Split Grid: Monthly Collection Trend & Payment Mode Breakdown -->
    <div class="grid-2e mb-6">
        <!-- Monthly Collection Sparkline -->
        <div class="card">
            <div class="card-head p-4 border-b border-[#eef2f9]">
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Monthly collection</h3>
                    <div class="text-xs text-[#8894ac]">Last 6 months</div>
                </div>
            </div>
            <div class="card-body">
                @php $maxVal = max(array_merge([1], array_column($trend, 'value'))); @endphp
                <div class="spark">
                    @foreach($trend as $t)
                        @php $barHeight = max(4, round(($t['value'] / $maxVal) * 100)); @endphp
                        <i style="height: {{ $barHeight }}%"
                           :title="'{{ $t['label'] }}: ' + $store.currency.format({{ (float)$t['value'] }}, false)"></i>
                    @endforeach
                </div>
                <div class="flex gap-2 mt-2">
                    @foreach($trend as $t)
                        <div class="flex-1 text-center text-xs text-gray-500 font-bold">{{ $t['label'] }}</div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Payment Mode Horizontal Bars -->
        <div class="card">
            <div class="card-head p-4 border-b border-[#eef2f9]">
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Payment mode</h3>
                    <div class="text-xs text-[#8894ac]">How the money came in</div>
                </div>
            </div>
            <div class="card-body">
                @php $maxMode = max(array_merge([1], array_column($modes, 'value'))); @endphp
                @if(empty($modes))
                    <div class="empty py-6 text-center">
                        <p class="text-xs text-[#8894ac]">No receipts logged yet</p>
                    </div>
                @else
                    <div style="display:flex;flex-direction:column;gap:13px">
                        @foreach($modes as $m)
                            @php $pct = round(($m['value'] / $maxMode) * 100, 1); @endphp
                            <div>
                                <div
                                    style="display:flex;justify-content:space-between;font-size:12.3px;margin-bottom:5px">
                                    <span style="font-weight:600">{{ $m['label'] }}</span>
                                    <span class="muted" style="font-weight:650"><x-money :value="$m['value']"/></span>
                                </div>
                                <div class="bar" style="height:8px">
                                    <i style="width:{{ $pct }}%;background:#0b3d91"></i>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- 3. Split Grid: Payment Ageing & Project Health Breakdown -->
    <div class="grid-2e mb-6">
        <!-- Payment Ageing Horizontal Bars -->
        <div class="card">
            <div class="card-head p-4 border-b border-[#eef2f9]">
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Payment Ageing</h3>
                    <div class="text-xs text-[#8894ac]">How old the overdue amounts are</div>
                </div>
            </div>
            <div class="card-body">
                @php
                    $maxAge = max(array_merge([1], array_values($ageing)));
                    $ageColors = ['0-30' => '#5aa236', '31-60' => '#2e75b6', '61-90' => '#e0a13a', '90+' => '#d94b4b'];
                @endphp
                <div style="display:flex;flex-direction:column;gap:13px">
                    @foreach($ageing as $bracket => $amt)
                        @php
                            $pct = round(($amt / $maxAge) * 100, 1);
                            $col = $ageColors[$bracket];
                        @endphp
                        <div>
                            <div style="display:flex;justify-content:space-between;font-size:12.3px;margin-bottom:5px">
                                <span style="font-weight:600">{{ $bracket }} days</span>
                                <span class="muted" style="font-weight:650"><x-money :value="$amt"/></span>
                            </div>
                            <div class="bar" style="height:8px">
                                <i style="width:{{ $pct }}%;background:{{ $col }}"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Project Health Horizontal Distribution -->
        <div class="card">
            <div class="card-head p-4 border-b border-[#eef2f9]">
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Project Health</h3>
                    <div class="text-xs text-[#8894ac]">All projects</div>
                </div>
            </div>
            <div class="card-body">
                @php
                    $maxHealth = max(array_merge([1], array_values($healthDistribution)));
                    $hColors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                @endphp
                <div style="display:flex;flex-direction:column;gap:13px">
                    @foreach($healthDistribution as $hLabel => $hCount)
                        @php
                            $pct = round(($hCount / $maxHealth) * 100, 1);
                            $col = $hColors[$hLabel];
                        @endphp
                        <div>
                            <div style="display:flex;justify-content:space-between;font-size:12.3px;margin-bottom:5px">
                                <span style="font-weight:600">{{ $hLabel }}</span>
                                <span class="muted" style="font-weight:650">{{ $hCount }}</span>
                            </div>
                            <div class="bar" style="height:8px">
                                <i style="width:{{ $pct }}%;background:{{ $col }}"></i>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    <!-- 4. Bottom Table: Milestone vs Collection Variance Analysis -->
    <div class="card">
        <div class="card-head p-4 border-b border-[#eef2f9]">
            <div>
                <h3 class="font-bold text-sm text-[#0d1b34]">Milestone vs Collection</h3>
                <div class="text-xs text-[#8894ac]">Where work is ahead but payment is behind — watch these</div>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Project</th>
                    <th>Customer</th>
                    <th>Milestones</th>
                    <th>Collected</th>
                    <th>Gap</th>
                    <th class="num">Due</th>
                    <th>Health</th>
                </tr>
                </thead>
                <tbody>
                @forelse($varianceRows as $row)
                    @php
                        $p = $row['project'];
                        $gap = $row['gap'];
                        $gapBadge = $gap > 30 ? 'b-red' : ($gap > 10 ? 'b-amber' : 'b-green');
                    @endphp
                    <tr class="clickable" @click="Livewire.navigate('{{ route('projects.show', $p->id) }}')">
                        <td>
                            <div class="strong">{{ $p->name }}</div>
                            <div class="muted" style="font-size:10.8px">{{ $p->code }}</div>
                        </td>
                        <td class="muted">{{ $p->customer?->company ?? '—' }}</td>
                        <td style="min-width:110px">
                            <div class="bar green">
                                <i style="width:{{ $row['msPct'] }}%"></i>
                            </div>
                            <span class="muted" style="font-size:10.8px">{{ $row['msPct'] }}%</span>
                        </td>
                        <td style="min-width:110px">
                            <div class="bar">
                                <i style="width:{{ $row['moneyPct'] }}%"></i>
                            </div>
                            <span class="muted" style="font-size:10.8px">{{ $row['moneyPct'] }}%</span>
                        </td>
                        <td>
                            <span class="badge {{ $gapBadge }}">
                                {{ $gap > 0 ? '+' : '' }}{{ $gap }}%
                            </span>
                        </td>
                        <td class="num font-bold text-[#e0a13a]">
                            <x-money :value="$row['due']"/>
                        </td>
                        <td>
                            <x-health-badge :health="$row['health']"/>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty py-8 text-center text-gray-400">
                                <span class="flex items-center justify-center mb-2">
                                    <svg viewBox="0 0 24 24" class="w-10 h-10 stroke-current fill-none">
                                        <path d="M3 3v18h18"/>
                                        <path d="M7 15l4-4 3 3 5-6"/>
                                    </svg>
                                </span>
                                <p class="font-medium text-gray-600">No data yet</p>
                                <div class="text-xs">Create projects and the analysis will appear here</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
