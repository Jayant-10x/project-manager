<div x-data="{}"
    @if(auth()->user()->hasPermission('payments', 'add'))
     x-on:prompt-milestone-payment.window="
        const d = $event.detail?.[0] || $event.detail;
        if (!d) return;
        window.dispatchEvent(new CustomEvent('confirm-dialog', {
            detail: {
                title: 'Milestone Completed',
                message: 'Milestone \'' + (d.milestoneName || 'Milestone') + '\' marked as done. Would you like to record payment for installment #' + d.termSeq + ' (' + d.termLabel + ') now?',
                confirmText: 'Record Payment Now',
                cancelText: 'Later',
                type: 'info',
                onConfirm: () => {
                    Livewire.dispatch('open-payment-modal', { projectId: d.projectId, termId: d.termId });
                }
            }
        }));
     "
    @endif
>
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

    @if($project->isPendingApproval())
        <div class="mb-4 p-4 rounded-xl border border-amber-200 bg-amber-50/80 flex flex-col sm:flex-row sm:items-center justify-between gap-3 shadow-xs">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-amber-100 text-amber-700 flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 fill-none stroke-current stroke-2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M12 6v6l4 2"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-amber-900">Pending Approval</h4>
                    <p class="text-xs text-amber-700 mt-0.5">
                        This project was submitted by <strong>{{ $project->salesPerson?->name ?? 'Sales' }}</strong> and is awaiting approval.
                    </p>
                </div>
            </div>
            @if($project->canBeApprovedBy(auth()->user()))
                <div class="flex items-center gap-2 shrink-0">
                    <button type="button"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Approve Project?',
                                message: 'Are you sure you want to approve project \'{{ addslashes($project->name) }}\'? This will mark the project as approved.',
                                confirmText: 'Approve Project',
                                cancelText: 'Cancel',
                                type: 'success',
                                wire: $wire,
                                action: 'approveProject'
                            })"
                            wire:loading.attr="disabled"
                            class="btn btn-sm btn-outline-green inline-flex items-center gap-1.5 font-medium">
                        <svg viewBox="0 0 24 24" class="w-4 h-4 fill-none stroke-current stroke-2"><path d="M20 6L9 17l-5-5"/></svg>
                        <span>Approve Project</span>
                    </button>
                    <button type="button"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Reject Project?',
                                message: 'Please provide a reason for rejecting project \'{{ addslashes($project->name) }}\':',
                                confirmText: 'Reject Project',
                                cancelText: 'Cancel',
                                type: 'danger',
                                hasInput: true,
                                inputPlaceholder: 'Enter rejection reason (optional)...',
                                onConfirm: (reason) => {
                                    $wire.rejectProject(reason);
                                }
                            })"
                            wire:loading.attr="disabled"
                            class="btn btn-sm btn-outline-danger inline-flex items-center gap-1.5 font-medium">
                        <svg viewBox="0 0 24 24" class="w-4 h-4 fill-none stroke-current stroke-2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                        <span>Reject Project</span>
                    </button>
                </div>
            @endif
        </div>
    @elseif($project->isRejected())
        <div class="mb-4 p-4 rounded-xl border border-rose-200 bg-rose-50/80 flex items-center justify-between gap-3 shadow-xs">
            <div class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-700 flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" class="w-5 h-5 fill-none stroke-current stroke-2">
                        <circle cx="12" cy="12" r="10"/>
                        <path d="M15 9l-6 6M9 9l6 6"/>
                    </svg>
                </div>
                <div>
                    <h4 class="text-sm font-semibold text-rose-900">Project Rejected</h4>
                    <p class="text-xs text-rose-700 mt-0.5">
                        This project was rejected by <strong>{{ $project->approvedBy?->name ?? 'Management / Supervisor' }}</strong>.
                        @if($project->rejection_reason)
                            <span class="block mt-0.5 font-medium">Reason: {{ $project->rejection_reason }}</span>
                        @endif
                    </p>
                    <p class="text-[11px] text-rose-600 mt-1">
                        You can update the project details to re-submit for review and approval.
                    </p>
                </div>
            </div>
            @if(auth()->user()->hasPermission('projects', 'edit'))
                <button type="button"
                        class="btn btn-sm btn-primary inline-flex items-center gap-1.5 shrink-0"
                        @click="Livewire.dispatch('open-project-modal', { id: {{ $project->id }} })">
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                        <path d="M12 20h9"/>
                        <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                    </svg>
                    <span>Edit & Resubmit</span>
                </button>
            @endif
        </div>
    @endif

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
                    · <b style="color:#d94b4b">{{ (int) round($daysLate) }} days late</b>
                @endif
            @endif
        </div>
    </div>

    <!-- Project Identity & Financial Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap;margin-bottom:14px">
                <div style="flex:1;min-width:240px">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h2 style="font-size:19px;font-weight:700;letter-spacing:-.3px">{{ $project->name }}</h2>
                        @if($project->isPendingApproval())
                            <span class="badge bg-amber-50 text-amber-700 border border-amber-300">Pending Approval</span>
                        @elseif($project->isRejected())
                            <span class="badge bg-rose-50 text-rose-700 border border-rose-300">Rejected</span>
                        @endif
                    </div>
                    <div class="muted" style="font-size:12.4px;margin-top:3px">
                        @if($project->customer)
                            @if(auth()->user()->isTechPerson() || !auth()->user()->hasPermission('customers', 'view'))
                                <span class="font-medium text-gray-700 dark:text-gray-300">{{ $project->customer->display_name }}</span> ·
                            @else
                                <a href="{{ route('customers.show', $project->customer_id) }}" wire:navigate class="link">
                                    {{ $project->customer->display_name }}
                                </a> ·
                            @endif
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
                    <button type="button" class="btn btn-outline-primary btn-sm"
                            @click="$dispatch('open-milestone-modal', { projectId: {{ $project->id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            @if($milestones->isEmpty())
                                <path d="M12 5v14M5 12h14"/>
                            @else
                                <path d="M12 2l9 5-9 5-9-5 9-5zM3 12l9 5 9-5M3 17l9 5 9-5"/>
                            @endif
                        </svg>
                        <span>{{ $milestones->isEmpty() ? 'Add Milestone' : 'Edit Milestone' }}</span>
                    </button>
                    @if(auth()->user()->hasPermission('schedules', 'view'))
                        <button type="button" class="btn btn-outline-primary btn-sm"
                                @click="$dispatch('open-schedule-modal', { projectId: {{ $project->id }} })">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <rect x="3" y="4" width="18" height="18" rx="2"/>
                                <path d="M16 2v4M8 2v4M3 10h18"/>
                            </svg>
                            Schedule
                        </button>
                    @endif
                    @if(auth()->user()->hasPermission('payments', 'add'))
                        <button type="button" class="btn btn-green btn-sm"
                                @click="Livewire.dispatch('open-payment-modal', { projectId: {{ $project->id }} })">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Record Payment
                        </button>
                    @endif
                    @if(auth()->user()->hasPermission('projects', 'edit'))
                        <button type="button" class="btn btn-outline-secondary btn-sm"
                                @click="Livewire.dispatch('open-project-modal', { id: {{ $project->id }} })"
                                title="Edit project" aria-label="Edit project">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                            </svg>
                        </button>
                    @endif
                    @if(auth()->user()->hasPermission('projects', 'delete'))
                        <button type="button" class="btn btn-outline-danger btn-sm"
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

            @if(auth()->user()->hasPermission('payments', 'view') || auth()->user()->hasPermission('schedules', 'view'))
                <div class="money-grid">
                    <div class="money-cell cursor-pointer hover:bg-gray-50 transition rounded-xl p-1.5"
                         wire:click="$set('activeTab', 'milestones')" title="View milestones">
                        <div class="k">Total Receivable</div>
                        <div class="v">
                            <x-money :value="$project->value > 0 ? $project->value : $project->money['scheduled']"/>
                        </div>
                        @if($project->value > 0 && abs((float)$project->value - (float)$project->money['scheduled']) > 0.01)
                            <div class="text-[11px] text-[#8894ac] mt-0.5">
                                Sched:
                                <x-money :value="$project->money['scheduled']"/>
                            </div>
                        @endif
                    </div>
                    <div class="money-cell rec cursor-pointer hover:bg-green-50/50 transition rounded-xl p-1.5"
                         wire:click="$set('activeTab', 'payments')" title="View receipts">
                        <div class="k">Received</div>
                        <div class="v text-[#5aa236]">
                            <x-money :value="$project->money['received']"/>
                        </div>
                        @if($project->money['tds'] > 0)
                            <div class="text-[11px] text-[#8894ac] mt-0.5">
                                <x-money :value="$project->money['cash']"/> Cash + <x-money :value="$project->money['tds']"/> TDS ({{ $r }}% settled)
                            </div>
                        @else
                            <div class="text-[11px] text-[#8894ac] mt-0.5">
                                {{ $r }}% settled
                            </div>
                        @endif
                    </div>
                    <div class="money-cell due cursor-pointer hover:bg-amber-50/50 transition rounded-xl p-1.5"
                         wire:click="$set('activeTab', 'schedule')" title="View payment schedule">
                        <div class="k">Due</div>
                        <div class="v text-[#e0a13a]">
                            <x-money :value="$project->money['due']"/>
                        </div>
                        <div class="text-[11px] text-[#8894ac] mt-0.5">
                            Net pending collection
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
            @endif

            @if($project->amc_required)
                <div class="divider"></div>
                <div class="money-grid"
                     style="grid-template-columns:repeat({{ auth()->user()->hasPermission('payments', 'view') ? 3 : 2 }},1fr)">
                    <div class="money-cell">
                        <div class="k">AMC Start</div>
                        <div class="v" style="font-size:14px">{{ $project->amc_start?->format('d M Y') ?? '—' }}</div>
                    </div>
                    <div class="money-cell">
                        <div class="k">AMC End</div>
                        <div class="v" style="font-size:14px">{{ $project->amc_end?->format('d M Y') ?? '—' }}</div>
                    </div>
                    @if(auth()->user()->hasPermission('payments', 'view'))
                        <div class="money-cell">
                            <div class="k">AMC Amount</div>
                            <div class="v" style="font-size:14px">
                                <x-money :value="$project->amc_amount"/>
                            </div>
                        </div>
                    @endif
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
            @if(auth()->user()->hasPermission('schedules', 'view'))
                <button wire:click="$set('activeTab', 'schedule')"
                        class="tab {{ $activeTab === 'schedule' ? 'active' : '' }}">
                    Payment schedule <span class="n">{{ $terms->count() }}</span>
                </button>
            @endif
            @if(auth()->user()->hasPermission('payments', 'view'))
                <button wire:click="$set('activeTab', 'payments')"
                        class="tab {{ $activeTab === 'payments' ? 'active' : '' }}">
                    Receipts <span class="n">{{ $payments->count() }}</span>
                </button>
            @endif
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
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Tech Person</div>
                            <div class="v font-semibold">{{ $project->techPerson?->name ?? '—' }}</div>
                        </div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Salesperson</div>
                            <div class="v font-semibold">{{ $project->salesPerson?->name ?? '—' }}</div>
                        </div>
                        @if($project->health === 'Delayed' || $project->status === 'Delayed' || $project->delay_reason)
                            <div class="field">
                                <div class="k text-xs font-bold text-gray-400 uppercase">Delay reason</div>
                                <div class="v font-semibold">{{ $project->delay_reason ?: '—' }}</div>
                            </div>
                        @endif
                    </div>
                    <div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Milestones done</div>
                            <div class="v font-semibold">{{ $milestones->where('done', true)->count() }}
                                / {{ $milestones->count() }}</div>
                        </div>
                        @if(auth()->user()->hasPermission('payments', 'view'))
                            <div class="field">
                                <div class="k text-xs font-bold text-gray-400 uppercase">Collection</div>
                                <div class="v font-semibold">{{ $r }}%</div>
                            </div>
                        @endif
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
                    @foreach($milestones as $idx => $milestone)
                        <div class="ms-row flex items-start gap-3 py-2.5 border-b border-[#eef2f9]">
                            <span
                                class="ms-dot {{ $milestone->done ? 'done' : ($milestone->status === 'In Progress' ? 'prog' : ($milestone->status === 'Blocked' ? 'block' : '')) }}">
                                {{ $milestone->done ? '✓' : ($idx + 1) }}
                            </span>
                            <div class="ms-body flex-1 min-w-0">
                                <div class="flex items-center justify-between gap-2">
                                    <div
                                        class="ms-name font-bold text-sm {{ $milestone->done ? 'text-gray-400' : '' }}">{{ $milestone->name }}</div>
                                    @if(auth()->user()->hasPermission('payments', 'view'))
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-xs text-[10.5px] py-0.5 px-1.5"
                                                @click="Livewire.dispatch('open-chase-modal', { milestoneId: {{ $milestone->id }} })"
                                                title="Log collection chase note">
                                            <svg viewBox="0 0 24 24" class="w-2.5 h-2.5 mr-0.5 inline"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                                            Chase
                                        </button>
                                    @endif
                                </div>
                                <div class="ms-meta text-xs text-[#8894ac] mt-0.5">
                                    {{ $milestone->due_date ? 'Due ' . $milestone->due_date->format('d M Y') : 'No date' }}
                                    @if($milestone->done && $milestone->done_on)
                                        · done {{ $milestone->done_on->format('d M') }}
                                    @endif
                                    · {{ $milestone->status }}
                                    @if(!$milestone->done && $milestone->due_date && $milestone->due_date->toDateString() < now()->toDateString())
                                        · <b style="color:#d94b4b">{{ (int) round(abs(now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($milestone->due_date)->startOfDay(), false))) }} days late</b>
                                    @endif
                                </div>
                                @if($milestone->chase_notes && auth()->user()->hasPermission('payments', 'view'))
                                    <div class="text-[11px] text-amber-800 bg-amber-50/70 border border-amber-200/60 rounded p-1.5 mt-1.5">
                                        <span class="font-semibold text-amber-900">Chase:</span> {{ $milestone->chase_notes }}
                                        @if($milestone->next_chase_date)
                                            <span class="ml-1 text-amber-700 font-medium">(Next: {{ $milestone->next_chase_date->format('d M Y') }})</span>
                                        @endif
                                    </div>
                                @endif
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
                            @if($milestones->isEmpty())
                                <path d="M12 5v14M5 12h14"/>
                            @else
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                            @endif
                        </svg>
                        <span>{{ $milestones->isEmpty() ? 'Add Milestone' : 'Edit Milestone' }}</span>
                    </button>
                </div>
                @forelse($milestones as $idx => $milestone)
                    <div wire:key="milestone-row-{{ $milestone->id }}"
                         class="ms-row p-3 border border-[#e2e8f3] rounded-lg mb-2">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <input type="checkbox"
                                       wire:key="milestone-chk-{{ $milestone->id }}-{{ $milestone->done ? 'done' : 'open' }}"
                                       wire:click="toggleMilestone({{ $milestone->id }})"
                                       {{ $milestone->done ? 'checked' : '' }} class="h-4 w-4 rounded accent-[#0b3d91] cursor-pointer">
                                <div>
                                    <div
                                        class="text-sm font-bold {{ $milestone->done ? 'line-through text-gray-400' : '' }}">{{ $milestone->name }}</div>
                                    <div class="text-xs text-[#8894ac]">
                                        Due: {{ $milestone->due_date?->format('d M Y') ?? 'Flexible' }}</div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span
                                    class="badge {{ $milestone->done ? 'b-green' : ($milestone->status === 'In Progress' ? 'b-navy' : ($milestone->status === 'Blocked' ? 'b-red' : 'b-amber')) }}">
                                    {{ $milestone->status }}
                                </span>
                                @if(auth()->user()->hasPermission('payments', 'view'))
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-xs whitespace-nowrap"
                                            @click="Livewire.dispatch('open-chase-modal', { milestoneId: {{ $milestone->id }} })"
                                            title="Log collection chase note">
                                        <svg viewBox="0 0 24 24" class="w-3 h-3 mr-1 inline"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                                        Chase Note
                                    </button>
                                @endif
                            </div>
                        </div>
                        @if($milestone->chase_notes && auth()->user()->hasPermission('payments', 'view'))
                            <div class="text-xs text-amber-800 bg-amber-50/70 border border-amber-200/60 rounded-md p-2 mt-2">
                                <span class="font-semibold text-amber-900">Collection Chase:</span> {{ $milestone->chase_notes }}
                                @if($milestone->next_chase_date)
                                    <span class="ml-1 text-amber-700 font-medium">· Next chase: {{ $milestone->next_chase_date->format('d M Y') }}</span>
                                @endif
                            </div>
                        @endif
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
            @elseif(auth()->user()->hasPermission('schedules', 'view') && $activeTab === 'schedule')
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
                        @if(auth()->user()->hasPermission('schedules', 'add'))
                            <button type="button" class="btn btn-outline-secondary btn-sm"
                                    @click="$dispatch('open-recurring-modal', { projectId: {{ $project->id }} })">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                    <path d="M21 12a9 9 0 11-3-6.7M21 3v6h-6"/>
                                </svg>
                                Generate monthly
                            </button>
                        @endif
                        @if(auth()->user()->hasPermission('schedules', 'edit'))
                            <button type="button" class="btn btn-primary btn-sm"
                                    @click="$dispatch('open-schedule-modal', { projectId: {{ $project->id }} })">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                    <path d="M12 20h9"/>
                                    <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                </svg>
                                Edit schedule
                            </button>
                        @endif
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
                                class="badge {{ status_badge_class($term->status) }}">
                                {{ $term->status }}
                            </span>
                            <div class="b text-xs mt-1" style="{{ $bal <= 0 ? 'color:#3d7a24' : '' }}">
                                {{ $bal > 0 ? 'remaining' : 'paid' }}
                            </div>
                        </div>
                        <div style="display:flex;align-items:center;gap:6px">
                            @if($bal > 0 && auth()->user()->hasPermission('payments', 'add'))
                                <button type="button" class="btn btn-outline-green btn-xs whitespace-nowrap"
                                        @click="Livewire.dispatch('open-payment-modal', { projectId: {{ $project->id }}, termId: {{ $term->id }} })"
                                        title="Record payment for this installment">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3 mr-1 inline" fill="none"
                                         stroke="currentColor" stroke-width="2">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                    Record Payment
                                </button>
                            @endif
                            @if(auth()->user()->hasPermission('schedules', 'delete'))
                                <button type="button" class="btn btn-outline-danger btn-xs"
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
                                    <span>Remove</span>
                                </button>
                            @endif
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
            @elseif(auth()->user()->hasPermission('payments', 'view') && $activeTab === 'payments')
                @if(auth()->user()->hasPermission('payments', 'add'))
                    <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
                        <button type="button" class="btn btn-green btn-sm"
                                @click="Livewire.dispatch('open-payment-modal', { projectId: {{ $project->id }} })">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Record payment
                        </button>
                    </div>
                @endif
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
                                        <div class="row-actions flex items-center gap-1.5">
                                            @if($pay->document_id)
                                                <a href="{{ route('documents.download', $pay->document_id) }}"
                                                   class="btn btn-outline-secondary btn-xs"
                                                   title="Download receipt slip">
                                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                                        <path d="M7 10l5 5 5-5"/>
                                                        <path d="M12 15V3"/>
                                                    </svg>
                                                </a>
                                            @elseif($pay->attachment)
                                                <a href="{{ asset('storage/' . $pay->attachment) }}" target="_blank"
                                                   class="btn btn-outline-secondary btn-xs"
                                                   title="View receipt slip">
                                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                                        <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                                                        <path d="M7 10l5 5 5-5"/>
                                                        <path d="M12 15V3"/>
                                                    </svg>
                                                </a>
                                            @endif
                                            <button type="button" class="btn btn-outline-danger btn-xs"
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
                                                <span>Delete</span>
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
                            $fileUrl = route('documents.preview', $doc->id, absolute: false);
                        @endphp
                        <button type="button"
                                class="btn btn-outline-primary btn-xs mr-1"
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
                        <a href="{{ route('documents.download', $doc->id, absolute: false) }}"
                           download="{{ $doc->file_name }}"
                           class="btn btn-outline-secondary btn-xs mr-1" title="Download">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                            </svg>
                        </a>
                        <button type="button" class="btn btn-outline-danger btn-xs"
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
