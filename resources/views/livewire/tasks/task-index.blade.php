<div>
    <!-- Interactive Metric Summary Section Card -->
    <div class="card mb-6">
        <div class="card-head flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"></path>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Task Execution &amp; Touchpoints</h3>
                    <div class="text-xs text-[#8894ac]">Personal execution checklist, priority deadlines &amp; scheduled customer calls</div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="info text-xs font-semibold text-[#8894ac] bg-slate-100 px-2.5 py-1 rounded-md">
                    {{ $openCount }} pending · {{ $doneCount }} done
                </span>
            </div>
        </div>
        <div class="card-body p-4 sm:p-5 bg-slate-50/40">
            <div class="kpis mb-0">
                <x-kpi-card label="Pending Tasks" :value="$openCount" sub="Assigned to you · Incomplete" icon="list"
                            color="#0b3d91" bg="#e8eefc"
                            wire:click="$set('filterStatus', '{{ $filterStatus === 'open' ? '' : 'open' }}')"
                            :class="$filterStatus === 'open' ? 'ring-2 ring-[#0b3d91] bg-blue-50/50' : ''"/>
                <x-kpi-card label="Completed" :value="$doneCount" sub="Checklist completed" icon="check"
                            color="#5aa236" bg="#eef7e8"
                            wire:click="$set('filterStatus', '{{ $filterStatus === 'done' ? '' : 'done' }}')"
                            :class="$filterStatus === 'done' ? 'ring-2 ring-[#5aa236] bg-green-50/50' : ''"/>
                <x-kpi-card label="Urgent / High" :value="$urgentCount" sub="Priority attention" icon="alert"
                            color="#e0a13a" bg="#fdf3e2"
                            wire:click="$set('filterPriority', '{{ $filterPriority === 'Urgent' ? '' : 'Urgent' }}')"
                            :class="$filterPriority === 'Urgent' ? 'ring-2 ring-[#e0a13a] bg-amber-50/50' : ''"/>
                <x-kpi-card label="Overdue Tasks" :value="$overdueCount" sub="Past due date" icon="alert"
                            color="#d94b4b" bg="#fdeaea"
                            wire:click="$set('filterStatus', '{{ $filterStatus === 'overdue' ? '' : 'overdue' }}')"
                            :class="$filterStatus === 'overdue' ? 'ring-2 ring-[#d94b4b] bg-red-50/50' : ''"/>
                <x-kpi-card label="Follow-up Calls" :value="$followups->count()" sub="Scheduled calls & touchpoints" icon="chat"
                            color="#7a5af8" bg="#f0ecff"
                            href="#followup-calls-section"/>
            </div>
        </div>
    </div>

    <!-- Header with Live Count Summary & Add Task Action -->
    <div class="page-head mb-4 flex items-center justify-between">
        <span class="info text-sm text-[#8894ac]">
            {{ $openCount }} pending · {{ $doneCount }} done
        </span>
        <button type="button" class="btn btn-primary btn-sm" @click="$dispatch('open-modal', 'add-task-modal')">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            Add Task
        </button>
    </div>

    <!-- Task Creation Modal -->
    <x-modal name="add-task-modal" title="Add New Task" sub="Personal execution checklist and scheduled follow-up">
        <form wire:submit="addTask" novalidate class="space-y-4">
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase block mb-1">What needs to be done<span
                        class="text-red-500"> *</span></label>
                <input wire:model.blur="title"
                       class="i text-sm @error('title') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Send payment reminder to Blueleaf">
                @error('title')
                <span class="text-[11px] text-red-500! font-medium mt-1 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Project (optional)</label>
                    <select wire:model.live="project_id"
                            class="i text-sm @error('project_id') border-red-500! bg-red-50/20! @enderror">
                        <option value="">*** None ***</option>
                        @foreach($projects as $projectOption)
                            <option value="{{ $projectOption->id }}">{{ $projectOption->code }} — {{ $projectOption->name }}</option>
                        @endforeach
                    </select>
                    @error('project_id')
                    <span class="text-[11px] text-red-500! font-medium mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Priority<span
                            class="text-red-500"> *</span></label>
                    <select wire:model.live="priority"
                            class="i text-sm @error('priority') border-red-500! bg-red-50/20! @enderror">
                        <option value="Low">Low</option>
                        <option value="Medium">Medium</option>
                        <option value="High">High</option>
                        <option value="Urgent">Urgent</option>
                    </select>
                    @error('priority')
                    <span class="text-[11px] text-red-500! font-medium mt-1 block">{{ $message }}</span>
                    @enderror
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Due Date</label>
                    <input wire:model.blur="due_date"
                           type="date"
                           class="i text-sm @error('due_date') border-red-500! bg-red-50/20! @enderror">
                    @error('due_date')
                    <span class="text-[11px] text-red-500! font-medium mt-1 block">{{ $message }}</span>
                    @enderror
                </div>

                @if(auth()->user()->role !== 'Sales')
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Assign to</label>
                        <select wire:model.live="assigned_to"
                                class="i text-sm @error('assigned_to') border-red-500! bg-red-50/20! @enderror">
                            @foreach($users as $assigneeOption)
                                <option value="{{ $assigneeOption->id }}">{{ $assigneeOption->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                        <span class="text-[11px] text-red-500! font-medium mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
                <button type="button" @click="confirmClose()" class="btn btn-ghost">Cancel</button>
                <button type="submit" class="btn btn-primary min-w-[140px] justify-center" wire:loading.attr="disabled" wire:target="addTask">
                    <span wire:loading.remove wire:target="addTask" class="inline-flex items-center justify-center gap-1.5">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        <span>Save Task</span>
                    </span>
                    <span wire:loading.inline-flex wire:target="addTask" class="items-center justify-center gap-1.5">
                        <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Saving...</span>
                    </span>
                </button>
            </div>
        </form>
    </x-modal>

    <!-- Filterbar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="filterbar">
                <div class="gsearch" style="width:230px">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="filterSearch" placeholder="Task, customer, project...">
                </div>

                <select wire:model.live="filterStatus" class="i" style="width:auto">
                    <option value="">All statuses</option>
                    <option value="open">Pending only</option>
                    <option value="done">Completed only</option>
                </select>

                <select wire:model.live="filterPriority" class="i" style="width:auto">
                    <option value="">All priorities</option>
                    <option value="Urgent">Urgent</option>
                    <option value="High">High</option>
                    <option value="Medium">Medium</option>
                    <option value="Low">Low</option>
                </select>

                @if($projects->isNotEmpty())
                    <select wire:model.live="filterProjectId" class="i" style="width:auto; max-width: 180px;">
                        <option value="">All projects</option>
                        @foreach($projects as $proj)
                            <option value="{{ $proj->id }}">{{ $proj->code }} — {{ $proj->name }}</option>
                        @endforeach
                    </select>
                @endif

                @if(auth()->user()->role === 'Admin' && $users->isNotEmpty())
                    <select wire:model.live="filterAssignedTo" class="i" style="width:auto; max-width: 160px;">
                        <option value="">All assignees</option>
                        @foreach($users as $userOption)
                            <option value="{{ $userOption->id }}">{{ $userOption->name }}</option>
                        @endforeach
                    </select>
                @endif

                @if($filterSearch !== '' || $filterStatus !== '' || $filterPriority !== '' || $filterProjectId !== '' || $filterAssignedTo !== '')
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

    <!-- Task Execution List -->
    <div class="card p-4">
        <div class="card-body p-0">
            @forelse($tasks as $task)
                @php
                    $isDone = (bool) $task->done;
                    $isLate = !$isDone && $task->due_date && $task->due_date->toDateString() < $today;
                    $daysLate = $isLate ? (int) round(abs(\Carbon\Carbon::parse($today)->startOfDay()->diffInDays(\Carbon\Carbon::parse($task->due_date)->startOfDay(), false))) : 0;
                    $custName = $task->project?->customer?->display_name ?? ($task->customer?->display_name ?? '');
                    $priorityClass = match($task->priority) {
                        'Urgent' => 'b-red',
                        'High'   => 'b-amber',
                        'Medium' => 'b-navy',
                        default  => 'b-grey',
                    };
                @endphp
                <div wire:key="task-item-{{ $task->id }}"
                     class="mini-item flex items-center gap-3 p-3 border-b last:border-b-0 hover:bg-slate-50 transition">
                    <input type="checkbox"
                           wire:key="task-chk-{{ $task->id }}-{{ $isDone ? 'done' : 'open' }}"
                           wire:click="toggleDone({{ $task->id }})"
                           {{ $isDone ? 'checked' : '' }}
                           class="w-4 h-4 rounded accent-[#0b3d91] cursor-pointer">

                    <div class="flex-1 min-w-0">
                        <div
                            class="t text-sm font-semibold {{ $isDone ? 'line-through text-gray-400' : 'text-[#0d1b34]' }}">
                            {{ $task->title }}
                        </div>
                        <div class="s text-xs text-[#8894ac] mt-0.5">
                            @if($task->project)
                                <span>{{ $task->project->code }} · </span>
                            @endif
                            @if($custName)
                                <span>{{ $custName }} · </span>
                            @endif
                            @if($task->due_date)
                                @if($isLate)
                                    <b style="color:#d94b4b">
                                        due {{ $task->due_date->format('d M') }} ({{ $daysLate }} days late)
                                    </b>
                                @else
                                    <span>due {{ $task->due_date->format('d M') }}</span>
                                @endif
                            @else
                                <span>no date</span>
                            @endif
                            @if($task->assignedTo)
                                <span>· {{ $task->assignedTo->name }}</span>
                            @endif
                        </div>
                    </div>

                    <span class="badge {{ $priorityClass }} text-xs">{{ $task->priority }}</span>

                    <button type="button"
                            class="btn btn-outline-danger btn-xs"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Remove Task?',
                                message: 'Are you sure you want to remove this task ({{ addslashes($task->title) }}) from your list?',
                                confirmText: 'Remove Task',
                                cancelText: 'Cancel',
                                danger: true,
                                wire: $wire,
                                action: 'deleteTask',
                                params: {{ $task->id }}
                            })"
                            title="Remove task">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path
                                d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                        </svg>
                        <span>Remove</span>
                    </button>
                </div>
            @empty
                <div class="empty py-8 text-center text-gray-400">
                    <svg viewBox="0 0 24 24" class="w-10 h-10 mx-auto mb-2 stroke-current fill-none">
                        <path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>
                    </svg>
                    <p class="font-medium text-gray-600">No tasks</p>
                    <div class="text-xs">Create your own checklist above</div>
                </div>
            @endforelse
        </div>
    </div>

    <!-- Upcoming Milestone Chases for Coordinators & Delivery Management -->
    @if(isset($milestoneChases) && $milestoneChases->isNotEmpty())
        <div class="card p-4 mt-6" id="milestone-chases-section">
            <div class="card-head pb-3 mb-3 border-b border-[#eef2f9] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-purple-50 text-[#7a5af8] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Upcoming Milestone Chases</h3>
                        <div class="text-xs text-[#8894ac]">{{ $milestoneChases->count() }} milestones requiring deliverable chase</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('schedule.index') }}" wire:navigate class="text-xs font-semibold text-[#7a5af8] hover:underline flex items-center gap-1">
                        <span>All Schedules</span>
                        <svg viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
            <div class="space-y-3">
                @foreach($milestoneChases as $mc)
                    @php
                        $mcDate = $mc->next_chase_date ? \Carbon\Carbon::parse($mc->next_chase_date) : null;
                        $isOverdueChase = $mcDate && $mcDate->toDateString() < $today;
                    @endphp
                    <div class="p-3 rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/50 transition">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold bg-purple-50 text-purple-700 border border-purple-200">
                                    Milestone Chase
                                </span>
                                <a href="{{ route('projects.show', $mc->project_id) }}" wire:navigate class="font-semibold text-sm text-[#0d1b34] hover:text-[#0b3d91] hover:underline">
                                    {{ $mc->project?->name ?? '—' }} ({{ $mc->project?->code }})
                                </a>
                                @if($mc->project?->customer)
                                    <span class="text-xs text-[#8894ac]">&middot; {{ $mc->project->customer->display_name }}</span>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <span class="badge {{ $isOverdueChase ? 'b-red' : 'b-navy' }}">
                                    Chase: {{ $mcDate ? $mcDate->format('d M') : '—' }}
                                </span>
                                <button type="button" class="btn btn-outline-primary btn-xs whitespace-nowrap"
                                        @click="$dispatch('open-milestone-chase-modal', { milestoneId: {{ $mc->id }} })">
                                    Update Chase
                                </button>
                            </div>
                        </div>
                        <div class="font-semibold text-xs text-slate-800 mt-1.5">
                            {{ $mc->name }}
                        </div>
                        @if($mc->chase_notes)
                            <div class="mt-1 text-xs text-slate-600 bg-slate-50 rounded p-2 border border-slate-200/60 leading-relaxed">
                                <span class="font-semibold text-slate-700">Latest Chase Note:</span> {{ $mc->chase_notes }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Follow-up Calls Scheduled -->
    @if($followups->isNotEmpty())
        <div class="card p-4 mt-6" id="followup-calls-section">
            <div class="card-head pb-3 mb-3 border-b border-[#eef2f9] flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Follow-up calls</h3>
                        <div class="text-xs text-[#8894ac]">{{ $followups->count() }} scheduled conversations</div>
                    </div>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('followups.index') }}" wire:navigate class="text-xs font-semibold text-[#0b3d91] hover:underline flex items-center gap-1">
                        <span>All Follow-ups</span>
                        <svg viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                    </a>
                </div>
            </div>
            <div class="space-y-3">
                @foreach($followups as $f)
                    @php
                        $fDate = $f->next_date ? \Carbon\Carbon::parse($f->next_date) : null;
                        $isOverdueFup = $fDate && $fDate->toDateString() < $today;
                        $message = $f->detail ?: $f->summary;
                    @endphp
                    <div class="p-3 rounded-xl border border-gray-100 hover:border-gray-200 hover:bg-gray-50/50 transition">
                        <div class="flex items-start justify-between gap-2">
                            <div class="flex items-center gap-2 flex-wrap">
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-semibold {{ $f->type === 'Call' ? 'bg-blue-50 text-blue-700 border border-blue-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                    @if($f->type === 'Call')
                                        <svg viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 16.9v3a2 2 0 01-2.2 2 19.8 19.8 0 01-8.6-3.1 19.5 19.5 0 01-6-6A19.8 19.8 0 012.1 4.2 2 2 0 014.1 2h3a2 2 0 012 1.7c.1 1 .4 1.9.7 2.8a2 2 0 01-.5 2.1L8.1 9.9a16 16 0 006 6l1.3-1.3a2 2 0 012.1-.4c.9.3 1.8.6 2.8.7a2 2 0 011.7 2z"/></svg>
                                    @else
                                        <svg viewBox="0 0 24 24" class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                                    @endif
                                    {{ $f->type }}
                                </span>
                                <a href="{{ route('customers.show', $f->customer_id) }}" wire:navigate class="font-semibold text-sm text-[#0d1b34] hover:text-[#0b3d91] hover:underline">
                                    {{ $f->customer?->display_name ?? '—' }}
                                </a>
                                @if($f->project)
                                    <a href="{{ route('projects.show', $f->project_id) }}" wire:navigate class="text-xs text-[#8894ac] hover:text-[#0b3d91] hover:underline">
                                        &middot; {{ $f->project->code }}
                                    </a>
                                @endif
                                @if($f->customer?->phone)
                                    <a href="tel:{{ $f->customer->phone }}" class="text-xs text-blue-600 hover:underline flex items-center gap-1 font-medium" title="Call {{ $f->customer->phone }}">
                                        <svg viewBox="0 0 24 24" class="w-3 h-3 fill-none stroke-current stroke-2">
                                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                                        </svg>
                                        <span>{{ $f->customer->phone }}</span>
                                    </a>
                                @endif
                            </div>
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <span class="badge {{ $isOverdueFup ? 'b-red' : 'b-navy' }}">
                                    {{ $fDate ? $fDate->format('d M') : '—' }}
                                </span>
                                <button type="button" class="btn btn-outline-primary btn-xs whitespace-nowrap inline-flex items-center gap-1" title="Log Follow-up"
                                        @click.stop="Livewire.dispatch('open-followup-modal', { customerId: {{ $f->customer_id }}, projectId: {{ $f->project_id ?? 'null' }} })">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                        <path d="M12 5v14M5 12h14"/>
                                    </svg>
                                    <span>Follow-up</span>
                                </button>
                            </div>
                        </div>

                        @if($f->summary && (!$f->detail || $f->summary !== $f->detail))
                            <div class="font-semibold text-xs text-slate-800 mt-2">
                                {{ $f->summary }}
                            </div>
                        @endif

                        @if($f->detail)
                            <div class="mt-1.5 text-xs text-slate-700 bg-slate-50 rounded-lg p-2.5 border border-slate-200/80 leading-relaxed font-normal whitespace-pre-line">
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
                @endforeach
            </div>
        </div>
    @endif
</div>
