<div>
    <div class="mb-4">
        <button type="button"
                @click="window.history.length > 1 ? window.history.back() : Livewire.navigate('{{ route('projects.index') }}')"
                class="btn btn-ghost btn-sm inline-flex items-center gap-1.5"
                title="Go back">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back
        </button>
    </div>

    <!-- Health / Delivery Status Notice Banner -->
    <div class="notice {{ $project->health === 'Delayed' ? 'warn' : '' }}">
        <svg viewBox="0 0 24 24" class="w-4 h-4 flex-shrink-0 mt-0.5">
            @if($project->health === 'Delayed')
                <circle cx="12" cy="12" r="9"/>
                <path d="M12 8v5M12 16.5v.5"/>
            @else
                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                <path d="M4 22v-7"/>
            @endif
        </svg>
        <div class="text-xs">
            <b>{{ $project->health }}</b> · {{ $project->status }}
            @if($project->delay_reason)
                <br>Reason: {{ $project->delay_reason }}
            @endif
            @if($project->delivery_date)
                <br>Delivery target: {{ $project->delivery_date->format('d M Y') }}
                @if($daysLate > 0)
                    · <b style="color:#d94b4b">{{ $daysLate }} days late</b>
                @endif
            @endif
        </div>
    </div>

    <!-- Project Identity & Financial Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:14px">
                <div style="flex:1;min-width:240px">
                    <h2 style="font-size:19px;font-weight:700;letter-spacing:-.3px">{{ $project->name }}</h2>
                    <div class="muted" style="font-size:12.4px;margin-top:3px">
                        @if($project->customer)
                            <a href="{{ route('customers.show', $project->customer_id) }}" wire:navigate class="link">
                                {{ $project->customer->company }}
                            </a> ·
                        @endif
                        {{ $project->project_type ?: '—' }} · start {{ $project->start_date?->format('d M') ?? '—' }} ·
                        delivery {{ $project->delivery_date?->format('d M') ?? '—' }}
                        @if(auth()->user()->role === 'Admin')
                            · cost
                            <x-money :value="$project->cost"/>
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button type="button" class="btn btn-ghost btn-sm"
                            @click="$dispatch('open-milestone-modal', { projectId: {{ $project->id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 2l9 5-9 5-9-5 9-5zM3 12l9 5 9-5M3 17l9 5 9-5"/>
                        </svg>
                        Milestones
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm"
                            @click="$dispatch('open-schedule-modal', { projectId: {{ $project->id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                        Schedule
                    </button>
                    <button type="button" class="btn btn-green btn-sm"
                            @click="Livewire.dispatch('open-payment-modal', { projectId: {{ $project->id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Payment
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm"
                            @click="$dispatch('open-project-modal', { id: {{ $project->id }} })"
                            title="Edit project" aria-label="Edit project">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                        </svg>
                    </button>
                    @if(auth()->user()->role === 'Admin')
                        <button type="button" class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Delete Project?',
                                    message: 'Are you sure you want to permanently delete {{ addslashes($project->name) }} ({{ $project->code }}) and all associated milestones, payment schedules, and receipts?',
                                    confirmText: 'Delete Project',
                                    cancelText: 'Cancel',
                                    danger: true,
                                    wire: $wire,
                                    action: 'deleteProject'
                                })"
                                title="Delete project" aria-label="Delete project">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path
                                    d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            <div class="money-grid">
                <div class="money-cell cursor-pointer hover:bg-gray-50 transition rounded-xl p-1.5"
                     wire:click="$set('activeTab', 'milestones')" title="View milestones">
                    <div class="k">Project Value</div>
                    <div class="v">
                        <x-money :value="$project->value > 0 ? $project->value : $project->money['scheduled']"/>
                    </div>
                    @if($project->value > 0 && abs((float)$project->value - (float)$project->money['scheduled']) > 0.01)
                        <div class="text-[11px] text-[#8894ac] mt-0.5">
                            Sched: <x-money :value="$project->money['scheduled']"/>
                        </div>
                    @endif
                </div>
                <div class="money-cell rec cursor-pointer hover:bg-green-50/50 transition rounded-xl p-1.5"
                     wire:click="$set('activeTab', 'payments')" title="View receipts">
                    <div class="k">Received</div>
                    <div class="v text-[#5aa236]">
                        <x-money :value="$project->money['received']"/>
                    </div>
                </div>
                <div class="money-cell due cursor-pointer hover:bg-amber-50/50 transition rounded-xl p-1.5"
                     wire:click="$set('activeTab', 'schedule')" title="View payment schedule">
                    <div class="k">Due</div>
                    <div class="v text-[#e0a13a]">
                        <x-money :value="$project->money['due']"/>
                    </div>
                </div>
                <div class="money-cell over cursor-pointer hover:bg-red-50/50 transition rounded-xl p-1.5"
                     wire:click="$set('activeTab', 'schedule')" title="View overdue installments">
                    <div class="k">Overdue</div>
                    <div class="v text-[#d94b4b]">
                        <x-money :value="$project->money['overdue']"/>
                    </div>
                </div>
            </div>

            <div style="margin-top:14px">
                <div class="progress-row">
                    <span>Collection</span>
                    <b>{{ $r }}%</b>
                </div>
                <div class="bar {{ $r >= 80 ? 'green' : ($r >= 40 ? '' : 'amber') }}" style="height:8px">
                    <i style="width:{{ $r }}%"></i>
                </div>
            </div>

            @if($project->amc_required)
                <div class="divider"></div>
                <div class="money-grid" style="grid-template-columns:repeat(3,1fr)">
                    <div class="money-cell">
                        <div class="k">AMC Start</div>
                        <div class="v" style="font-size:14px">{{ $project->amc_start?->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div class="money-cell">
                        <div class="k">AMC End</div>
                        <div class="v" style="font-size:14px">{{ $project->amc_end?->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div class="money-cell">
                        <div class="k">AMC Amount</div>
                        <div class="v" style="font-size:14px">
                            <x-money :value="$project->amc_amount"/>
                        </div>
                    </div>
                </div>
            @endif

            <div style="margin-top:12px">
                @if($project->handover_done)
                    <span class="badge b-green">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5"><path d="M20 6L9 17l-5-5"/></svg> Handover complete
                    </span>
                @else
                    <span class="badge b-amber">Handover Pending</span>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabbed Project Body -->
    <div class="card mb-4">
        <div class="tabs">
            <button wire:click="$set('activeTab', 'overview')"
                    class="tab {{ $activeTab === 'overview' ? 'active' : '' }}">Overview
            </button>
            <button wire:click="$set('activeTab', 'milestones')"
                    class="tab {{ $activeTab === 'milestones' ? 'active' : '' }}">
                Milestones <span class="n">{{ $milestones->count() }}</span>
            </button>
            <button wire:click="$set('activeTab', 'schedule')"
                    class="tab {{ $activeTab === 'schedule' ? 'active' : '' }}">
                Payment schedule <span class="n">{{ $terms->count() }}</span>
            </button>
            <button wire:click="$set('activeTab', 'payments')"
                    class="tab {{ $activeTab === 'payments' ? 'active' : '' }}">
                Receipts <span class="n">{{ $payments->count() }}</span>
            </button>
            <button wire:click="$set('activeTab', 'documents')"
                    class="tab {{ $activeTab === 'documents' ? 'active' : '' }}">
                Documents <span class="n">{{ $documents->count() }}</span>
            </button>
        </div>

        <div class="card-body">
            <!-- 1. OVERVIEW -->
            @if($activeTab === 'overview')
                @if($project->description)
                    <div style="font-size:13.2px;color:var(--text2);line-height:1.65">{{ $project->description }}</div>
                    <div class="divider"></div>
                @endif

                <div class="grid-3" style="gap:14px">
                    <div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Status</div>
                            <div class="v font-semibold">{{ $project->status }}</div>
                        </div>
                        <div class="field">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Health</div>
                            <div class="v">
                                <x-health-badge :health="$project->health"/>
                            </div>
                        </div>
                    </div>
                    <div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Coordinator</div>
                            <div class="v font-semibold">{{ $project->coordinator?->name ?? '—' }}</div>
                        </div>
                        <div class="field">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Delay reason</div>
                            <div class="v font-semibold">{{ $project->delay_reason ?: '—' }}</div>
                        </div>
                    </div>
                    <div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Milestones done</div>
                            <div class="v font-semibold">{{ $milestones->where('done', true)->count() }}
                                / {{ $milestones->count() }}</div>
                        </div>
                        <div class="field">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Collection</div>
                            <div class="v font-semibold">{{ $r }}%</div>
                        </div>
                    </div>
                </div>

                @if($project->handover_notes)
                    <div class="divider"></div>
                    <div class="field">
                        <div class="k text-xs font-bold text-gray-400 uppercase">Handover notes</div>
                        <div class="v mt-0.5">{{ $project->handover_notes }}</div>
                    </div>
                @endif

                @if($project->notes)
                    <div class="field mt-3">
                        <div class="k text-xs font-bold text-gray-400 uppercase">Notes</div>
                        <div class="v mt-0.5">{{ $project->notes }}</div>
                    </div>
                @endif

                @if($milestones->isNotEmpty())
                    <div class="divider"></div>
                    <div class="card-head" style="padding:0 0 8px;border:none">
                        <div>
                            <h3>Timeline</h3>
                            <div class="sub">{{ $milestones->where('done', true)->count() }}
                                of {{ $milestones->count() }} complete
                            </div>
                        </div>
                    </div>
                    @foreach($milestones as $idx => $m)
                        <div class="ms-row flex items-start gap-3 py-2.5 border-b border-[#eef2f9]">
                            <span
                                class="ms-dot {{ $m->done ? 'done' : ($m->status === 'In Progress' ? 'prog' : ($m->status === 'Blocked' ? 'block' : '')) }}">
                                {{ $m->done ? '✓' : ($idx + 1) }}
                            </span>
                            <div class="ms-body flex-1 min-w-0">
                                <div
                                    class="ms-name font-bold text-sm {{ $m->done ? 'text-gray-400' : '' }}">{{ $m->name }}</div>
                                <div class="ms-meta text-xs text-[#8894ac] mt-0.5">
                                    {{ $m->due_date ? 'Due ' . $m->due_date->format('d M Y') : 'No date' }}
                                    @if($m->done && $m->done_on)
                                        · done {{ $m->done_on->format('d M') }}
                                    @endif
                                    · {{ $m->status }}
                                    @if(!$m->done && $m->due_date && $m->due_date->toDateString() < now()->toDateString())
                                        · <b style="color:#d94b4b">{{ abs(now()->diffInDays($m->due_date, false)) }}
                                            days late</b>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif

                <!-- 2. MILESTONES -->
            @elseif($activeTab === 'milestones')
                <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
                    <button type="button" class="btn btn-primary btn-sm"
                            @click="$dispatch('open-milestone-modal', { projectId: {{ $project->id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                        </svg>
                        Edit milestones
                    </button>
                </div>
                @forelse($milestones as $idx => $m)
                    <div wire:key="milestone-row-{{ $m->id }}" class="ms-row flex items-center justify-between p-3 border border-[#e2e8f3] rounded-lg mb-2">
                        <div class="flex items-center gap-3">
                            <input type="checkbox"
                                   wire:key="milestone-chk-{{ $m->id }}-{{ $m->done ? 'done' : 'open' }}"
                                   wire:click="toggleMilestone({{ $m->id }})"
                                   {{ $m->done ? 'checked' : '' }} class="h-4 w-4 rounded accent-[#0b3d91] cursor-pointer">
                            <div>
                                <div
                                    class="text-sm font-bold {{ $m->done ? 'line-through text-gray-400' : '' }}">{{ $m->name }}</div>
                                <div class="text-xs text-[#8894ac]">
                                    Due: {{ $m->due_date?->format('d M Y') ?? 'Flexible' }}</div>
                            </div>
                        </div>
                        <span
                            class="badge {{ $m->done ? 'b-green' : ($m->status === 'In Progress' ? 'b-navy' : ($m->status === 'Blocked' ? 'b-red' : 'b-amber')) }}">
                            {{ $m->status }}
                        </span>
                    </div>
                @empty
                    <div class="empty">
                         <span class="flex items-center justify-center">
                        <svg viewBox="0 0 24 24">
                            <path d="M12 2l9 5-9 5-9-5 9-5zM3 12l9 5 9-5M3 17l9 5 9-5"/>
                        </svg>
                         </span>
                        <p>No milestones yet</p>
                        <div class="sm">Build a timeline — from kickoff to delivery</div>
                    </div>
                @endforelse

                <!-- 3. SCHEDULE -->
            @elseif($activeTab === 'schedule')
                @php
                    $schedSum = $terms->sum('amount');
                    $paidSum = $terms->sum('received');
                @endphp
                <div style="display:flex;gap:10px;align-items:center;margin-bottom:12px;flex-wrap:wrap">
                    <span class="muted" style="font-size:12.4px">
                        {{ $terms->count() }} installments · <x-money :value="$schedSum"/> scheduled · <x-money
                            :value="$paidSum"/> received
                    </span>
                    <div style="margin-left:auto;display:flex;gap:8px">
                        <button type="button" class="btn btn-ghost btn-sm"
                                @click="$dispatch('open-recurring-modal', { projectId: {{ $project->id }} })">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M21 12a9 9 0 11-3-6.7M21 3v6h-6"/>
                            </svg>
                            Generate monthly
                        </button>
                        <button type="button" class="btn btn-primary btn-sm"
                                @click="$dispatch('open-schedule-modal', { projectId: {{ $project->id }} })">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                            </svg>
                            Edit schedule
                        </button>
                    </div>
                </div>

                @forelse($terms as $term)
                    @php $bal = max(0, $term->amount - $term->received - $term->tds); @endphp
                    <div
                        class="term {{ $term->status === 'Paid' ? 'paid' : ($term->status === 'Overdue' ? 'over' : '') }}">
                        <div class="term-no">{{ $term->seq }}</div>
                        <div class="term-body">
                            <div class="term-title">{{ $term->label }} ·
                                <x-money :value="$term->amount"/>
                            </div>
                            <div class="term-meta">
                                <span>{{ ucfirst($term->trigger_type) }}</span>
                                @if($term->due_date)
                                    <span>Due {{ $term->due_date->format('d M Y') }} @if($term->grace_days > 0)
                                            (+{{ $term->grace_days }}d grace)
                                        @endif</span>
                                @else
                                    <span class="muted">No date set</span>
                                @endif
                                @if($term->received > 0)
                                    <span style="color:#3d7a24">Received <x-money :value="$term->received"/></span>
                                @endif
                            </div>
                        </div>
                        <div class="term-amt">
                            <span
                                class="badge {{ $term->status === 'Paid' ? 'b-green' : ($term->status === 'Overdue' ? 'b-red' : ($term->status === 'Partial' ? 'b-amber' : 'b-grey')) }}">
                                {{ $term->status }}
                            </span>
                            <div class="b text-xs mt-1" style="{{ $bal <= 0 ? 'color:#3d7a24' : '' }}">
                                {{ $bal > 0 ? 'remaining' : 'paid' }}
                            </div>
                        </div>
                        <div style="display:flex;flex-direction:column;gap:4px">
                            @if($bal > 0)
                                <button type="button" class="mini-btn" @click="Livewire.dispatch('open-payment-modal', { projectId: {{ $project->id }}, termId: {{ $term->id }} })"
                                        title="Record payment">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                </button>
                            @endif
                            <button type="button" class="mini-btn danger"
                                    @click="$dispatch('confirm-dialog', {
                                        title: 'Remove Installment Term?',
                                        message: 'Are you sure you want to remove {{ addslashes($term->label) }} (₹{{ number_format($term->amount) }}) from the schedule?',
                                        confirmText: 'Remove Installment',
                                        cancelText: 'Cancel',
                                        danger: true,
                                        wire: $wire,
                                        action: 'deleteTerm',
                                        params: {{ $term->id }}
                                    })"
                                    title="Remove">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                    <path
                                        d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                @empty
                    <div class="empty">
                         <span class="flex items-center justify-center">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                            <path d="M16 2v4M8 2v4M3 10h18"/>
                        </svg>
                         </span>
                        <p>No schedule yet</p>
                        <div class="sm">Create a payment schedule — fixed, milestone or recurring</div>
                    </div>
                @endforelse

                <!-- 4. PAYMENTS -->
            @elseif($activeTab === 'payments')
                <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
                    <button type="button" class="btn btn-green btn-sm"
                            @click="Livewire.dispatch('open-payment-modal', { projectId: {{ $project->id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Record payment
                    </button>
                </div>
                @if($payments->isNotEmpty())
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Receipt</th>
                                <th>Date</th>
                                <th class="num">Amount</th>
                                <th class="num">TDS</th>
                                <th>Mode</th>
                                <th>Reference</th>
                                <th>Entered by</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($payments as $pay)
                                <tr>
                                    <td class="strong">{{ $pay->code }}</td>
                                    <td class="nowrap">{{ $pay->paid_on->format('d M Y') }}</td>
                                    <td class="num font-bold text-[#5aa236]">
                                        <x-money :value="$pay->amount"/>
                                    </td>
                                    <td class="num muted">
                                        @if($pay->tds > 0)
                                            <x-money :value="$pay->tds"/>
                                        @else
                                            —
                                        @endif
                                    </td>
                                    <td><span class="pill">{{ $pay->mode }}</span></td>
                                    <td class="muted"
                                        style="font-size:11.3px">{{ $pay->reference ?: '—' }} @if($pay->bank)
                                            <br>{{ $pay->bank }}
                                        @endif</td>
                                    <td class="muted nowrap">{{ $pay->enteredBy?->name ?? '—' }}</td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="mini-btn danger"
                                                    @click="$dispatch('confirm-dialog', {
                                                        title: 'Delete Payment Receipt?',
                                                        message: 'Remove this payment entry ({{ $pay->code ?: 'Receipt' }})? The receipt will be deleted and the installment balance adjusted.',
                                                        confirmText: 'Delete Payment',
                                                        cancelText: 'Cancel',
                                                        danger: true,
                                                        wire: $wire,
                                                        action: 'deletePayment',
                                                        params: {{ $pay->id }}
                                                    })"
                                                    title="Delete payment" aria-label="Delete payment">
                                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                                    <path
                                                        d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty">
                         <span class="flex items-center justify-center">
                        <svg viewBox="0 0 24 24">
                            <rect x="2" y="6" width="20" height="12" rx="2"/>
                            <circle cx="12" cy="12" r="2.5"/>
                        </svg>
                         </span>
                        <p>No receipts yet</p>
                        <div class="sm">Record a payment when it comes in</div>
                    </div>
                @endif

                <!-- 5. DOCUMENTS -->
            @elseif($activeTab === 'documents')
                <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
                    <button type="button" class="btn btn-primary btn-sm"
                            @click="Livewire.dispatch('open-document-upload-modal', { projectId: {{ $project->id }}, customerId: {{ $project->customer_id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 19V5M5 12l7-7 7 7"/>
                        </svg>
                        Upload document
                    </button>
                </div>
                @forelse($documents as $doc)
                    <div class="doc flex items-center justify-between p-3 border-b border-[#eef2f9]">
                        <span class="doc-ico">{{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION)) }}</span>
                        <div style="flex:1;min-width:0;margin-left:10px">
                            <div class="doc-n font-bold text-sm">{{ $doc->title }}</div>
                            <div class="doc-m text-xs text-[#8894ac]">{{ $doc->file_name }} · {{ $doc->size_kb }} KB
                                · {{ $doc->created_at->format('d M H:i') }}</div>
                        </div>
                        <span class="pill mr-2">{{ $doc->category }}</span>
                        @php
                            $fileUrl = file_exists(public_path('uploads/' . $doc->stored_as))
                                ? asset('uploads/' . $doc->stored_as)
                                : asset('storage/' . $doc->stored_as);
                        @endphp
                        <button type="button"
                                class="mini-btn mr-1"
                                @click="Livewire.dispatch('open-document-preview', {
                                    data: {
                                        title: '{{ addslashes($doc->title) }}',
                                        name: '{{ addslashes($doc->file_name) }}',
                                        sizeKb: '{{ $doc->size_kb }}',
                                        category: '{{ $doc->category }}',
                                        url: '{{ $fileUrl }}',
                                        downloadUrl: '{{ route('documents.download', $doc->id, absolute: false) }}',
                                        mime: '{{ $doc->mime }}'
                                    }
                                })"
                                title="Preview">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                        <a href="{{ route('documents.download', $doc->id, absolute: false) }}" download="{{ $doc->file_name }}"
                           class="mini-btn mr-1" title="Download">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                            </svg>
                        </a>
                        <button type="button" class="mini-btn danger"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Remove Document?',
                                    message: 'Are you sure you want to remove {{ addslashes($doc->file_name) }}? The file will be permanently deleted.',
                                    confirmText: 'Remove Document',
                                    cancelText: 'Cancel',
                                    danger: true,
                                    wire: $wire,
                                    action: 'deleteDocument',
                                    params: {{ $doc->id }}
                                })"
                                title="Remove">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path
                                    d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            </svg>
                        </button>
                    </div>
                @empty
                    <div class="empty">
                         <span class="flex items-center justify-center">
                        <svg viewBox="0 0 24 24">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                        </svg>
                         </span>
                        <p>No documents yet</p>
                        <div class="sm">Upload a contract, invoice or delivery proof</div>
                    </div>
                @endforelse
            @endif
        </div>
    </div>

    <!-- Modals for Schedule, Recurring & Milestones -->
    <livewire:projects.project-schedule-modal/>
    <livewire:projects.project-recurring-modal/>
    <livewire:projects.project-milestone-modal/>
    <livewire:documents.document-upload-modal/>
</div>
