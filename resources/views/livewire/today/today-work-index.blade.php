<div>
    <!-- Top 4 KPIs -->
    <div class="kpis four mb-6">
        <x-kpi-card label="Overdue" :value="$totalOverdue"
                    :sub="$overdue->count() . ' ' . \Illuminate\Support\Str::plural('installment', $overdue->count())"
                    icon="alert"
                    color="#d94b4b" bg="#fdeaea" :ismoney="true"
                    :href="route('schedule.index', ['status' => 'Overdue'])"/>
        <x-kpi-card :label="'Due in ' . $alertDays . ' days'" :value="$totalSoon" :ismoney="true"
                    :sub="$soon->count() . ' ' . \Illuminate\Support\Str::plural('installment', $soon->count())"
                    icon="clock" color="#e0a13a" bg="#fdf3e2"
                    :href="route('schedule.index', ['status' => 'Upcoming'])"/>
        <x-kpi-card label="Follow-ups" :value="$followups->count()" sub="Pending conversations" icon="chat"
                    color="#0b3d91" bg="#e8eefc" :href="route('followups.index')"/>
        <x-kpi-card label="Tasks" :value="$openTasks" sub="Open Tasks · In your checklist" icon="list" color="#5aa236"
                    bg="#eef7e8" :href="route('tasks.index')"/>
    </div>

    <!-- Main Grid: Overdue Payments vs (Upcoming + Follow-ups) -->
    <div class="grid-2e mb-6">
        <!-- Overdue Payments Card -->
        <div class="card">
            <div class="card-head flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-rose-50 text-[#d94b4b] flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16.5v.5"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Overdue Payments</h3>
                    <div
                        class="text-xs text-[#8894ac]">{{ $overdue->count() }} {{ \Illuminate\Support\Str::plural('installment', $overdue->count()) }}
                        ·
                        <x-money :value="$totalOverdue"/>
                    </div>
                </div>
            </div>
            <div class="card-body">
                @forelse($overdue as $t)
                    @php
                        $bal = max(0, (float)$t->amount - (float)$t->received - (float)$t->tds);
                        $dueDate = $t->due_date ? \Carbon\Carbon::parse($t->due_date) : null;
                        $daysLate = $dueDate ? (int) round(abs(\Carbon\Carbon::parse($today)->startOfDay()->diffInDays($dueDate->startOfDay(), false))) : 0;
                    @endphp
                    <div class="term over clickable"
                         @click="Livewire.navigate('{{ route('projects.show', $t->project_id) }}')">
                        <div class="term-no" style="background:#fdeaea;color:#d94b4b">{{ $t->seq }}</div>
                        <div class="term-body">
                            <div class="term-title">{{ $t->project?->customer?->display_name ?? '—' }}</div>
                            <div class="term-meta">
                                <span>{{ $t->label }}</span>
                                <span>{{ $t->project?->code }}</span>
                                <span style="color:#d94b4b;font-weight:650">{{ $daysLate }} days late</span>
                            </div>
                            @if($t->milestone && $t->milestone->chase_notes)
                                <div
                                    class="mt-1.5 text-xs text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 inline-flex items-center gap-1.5 max-w-md">
                                    <span class="font-semibold text-amber-900">Chase Note:</span>
                                    <span class="truncate">{{ $t->milestone->chase_notes }}</span>
                                    @if($t->milestone->next_chase_date)
                                        <span
                                            class="text-amber-700 ml-1">· Next: {{ \Carbon\Carbon::parse($t->milestone->next_chase_date)->format('d M') }}</span>
                                    @endif
                                </div>
                            @endif
                        </div>
                        <div class="term-amt flex flex-col items-end gap-1.5">
                            <div class="a">
                                <x-money :value="$bal"/>
                            </div>
                            <div class="flex items-center gap-1.5">
                                <button type="button"
                                        class="btn btn-outline-primary btn-xs whitespace-nowrap"
                                        title="Log Chase Note"
                                        @click.stop="Livewire.dispatch('open-chase-modal', { milestoneId: {{ $t->milestone_id ?: 'null' }}, termId: {{ $t->id }} })">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                                    </svg>
                                    Log Chase Note
                                </button>
                                <button type="button"
                                        class="btn btn-outline-green btn-xs whitespace-nowrap"
                                        title="Record Payment"
                                        @click.stop="Livewire.dispatch('open-payment-modal', { projectId: {{ $t->project_id }}, termId: {{ $t->id }} })">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                    + Payment
                                </button>
                            </div>
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
                <div class="card-head flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-lg bg-amber-50 text-[#e0a13a] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="9"/>
                            <path d="M12 7v5l3 2"/>
                        </svg>
                    </div>
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
                        <div
                            class="mini-item clickable flex items-center justify-between gap-3 p-3 rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/50 transition mb-2"
                            @click="Livewire.navigate('{{ route('projects.show', $t->project_id) }}')">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center justify-between mb-1">
                                    <span
                                        class="font-semibold text-sm text-[#0d1b34]">{{ $t->project?->customer?->display_name ?? '—' }}</span>
                                    <span class="badge b-amber">{{ $dueDate ? $dueDate->format('d M') : '—' }}</span>
                                </div>
                                <div class="text-xs text-[#8894ac] flex items-center gap-2">
                                    <span>{{ $t->label }}</span>
                                    <span>&middot;</span>
                                    <span>{{ $t->project?->code }}</span>
                                    <span>&middot;</span>
                                    <span class="font-semibold text-[#0d1b34]"><x-money :value="$bal"/></span>
                                </div>
                                @if($t->milestone && $t->milestone->chase_notes)
                                    <div
                                        class="mt-1 text-xs text-amber-800 bg-amber-50 px-2 py-0.5 rounded border border-amber-200 inline-flex items-center gap-1 max-w-sm truncate">
                                        <span>💬</span>
                                        <span class="truncate">{{ $t->milestone->chase_notes }}</span>
                                    </div>
                                @endif
                            </div>
                            <div class="r flex items-center gap-1.5 flex-shrink-0">
                                <button type="button"
                                        class="btn btn-outline-primary btn-xs whitespace-nowrap"
                                        title="Log Chase Note"
                                        @click.stop="Livewire.dispatch('open-chase-modal', { milestoneId: {{ $t->milestone_id ?: 'null' }}, termId: {{ $t->id }} })">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                        <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                                    </svg>
                                    Log Chase Note
                                </button>
                                <button type="button"
                                        class="btn btn-outline-green btn-xs whitespace-nowrap"
                                        title="Record Payment"
                                        @click.stop="Livewire.dispatch('open-payment-modal', { projectId: {{ $t->project_id }}, termId: {{ $t->id }} })">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                    + Payment
                                </button>
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
            <div class="card" id="followups-card">
                <div class="card-head flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Follow-up calls</h3>
                            <div class="text-xs text-[#8894ac]">{{ $followups->count() }} pending</div>
                        </div>
                    </div>
                    <a href="{{ route('followups.index') }}" wire:navigate
                       class="text-xs font-semibold text-[#0b3d91] hover:underline flex items-center gap-1">
                        <span>View all</span>
                        <svg viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </a>
                </div>
                <div class="card-body">
                    @forelse($followups as $f)
                        @php
                            $fDate = $f->next_date ? \Carbon\Carbon::parse($f->next_date) : null;
                            $isOverdueFup = $fDate && $fDate->toDateString() < $today;
                            $message = $f->detail ?: $f->summary;
                        @endphp
                        <div
                            class="p-3 rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/50 transition mb-2.5">
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold {{ $f->type === 'Call' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        @if($f->type === 'Call')
                                            <svg viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor"
                                                 stroke-width="2"><path
                                                    d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 1.9.7 2.8a2 2 0 01-.5 2.1L8.1 9.9a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.4c.9.3 1.8.6 2.8.7a2 2 0 011.7 2z"/></svg>
                                        @else
                                            <svg viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor"
                                                 stroke-width="2"><path
                                                    d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                                        @endif
                                        {{ $f->type }}
                                    </span>
                                    <a href="{{ route('customers.show', $f->customer_id) }}" wire:navigate
                                       class="font-semibold text-sm text-[#0d1b34] hover:text-[#0b3d91] hover:underline">
                                        {{ $f->customer?->display_name ?? '—' }}
                                    </a>
                                    @if($f->project)
                                        <a href="{{ route('projects.show', $f->project_id) }}" wire:navigate
                                           class="text-xs text-[#8894ac] hover:text-[#0b3d91] hover:underline">
                                            &middot; {{ $f->project->code }}
                                        </a>
                                    @endif
                                    @if($f->customer?->phone)
                                        <a href="tel:{{ $f->customer->phone }}"
                                           class="text-xs text-blue-600 hover:underline flex items-center gap-1 font-medium"
                                           title="Call {{ $f->customer->phone }}">
                                            <span>📞</span>
                                            <span>{{ $f->customer->phone }}</span>
                                        </a>
                                    @endif
                                </div>
                                <div class="flex items-center gap-1.5 flex-shrink-0">
                                    <span class="badge {{ $isOverdueFup ? 'b-red' : 'b-navy' }}">
                                        {{ $fDate ? $fDate->format('d M') : '—' }}
                                    </span>
                                    <button type="button" class="btn btn-outline-primary btn-xs whitespace-nowrap"
                                            title="Log Follow-up"
                                            @click.stop="Livewire.dispatch('open-followup-modal', { customerId: {{ $f->customer_id }}, projectId: {{ $f->project_id ?? 'null' }} })">
                                        <svg viewBox="0 0 24 24" class="w-3 h-3">
                                            <path d="M12 5v14M5 12h14"/>
                                        </svg>
                                        Follow-up
                                    </button>
                                </div>
                            </div>

                            @if($f->summary && (!$f->detail || $f->summary !== $f->detail))
                                <div class="font-semibold text-xs text-slate-800 mt-2">
                                    {{ $f->summary }}
                                </div>
                            @endif

                            @if($f->detail)
                                <div
                                    class="mt-1.5 text-xs text-slate-700 bg-slate-50 rounded-lg p-2.5 border border-slate-200/80 leading-relaxed font-normal whitespace-pre-line">
                                    <span class="font-semibold text-slate-800 block mb-0.5">Discussion / Message:</span>
                                    {{ $f->detail }}
                                </div>
                            @endif

                            @if($f->next_note)
                                <div class="mt-1.5 text-xs text-blue-800 flex items-center gap-1.5 font-medium">
                                    <span class="text-blue-600 font-bold">➜ Next:</span>
                                    <span>{{ $f->next_note }}</span>
                                </div>
                            @endif
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
                        <td>{{ $p->customer?->display_name ?? '—' }}</td>
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
