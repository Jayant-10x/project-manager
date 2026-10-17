<div>
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
                        <option value="">— none —</option>
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
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

                @if(auth()->user()->role === 'Admin')
                    <div>
                        <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Assign to</label>
                        <select wire:model.live="assigned_to"
                                class="i text-sm @error('assigned_to') border-red-500! bg-red-50/20! @enderror">
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('assigned_to')
                        <span class="text-[11px] text-red-500! font-medium mt-1 block">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
                <button type="button" @click="$dispatch('close-modal', 'add-task-modal')" class="btn btn-ghost">Cancel
                </button>
                <button type="submit" class="btn btn-primary">
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    Save Task
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
                        @foreach($projects as $p)
                            <option value="{{ $p->id }}">{{ $p->code }} — {{ $p->name }}</option>
                        @endforeach
                    </select>
                @endif

                @if(auth()->user()->role === 'Admin' && $users->isNotEmpty())
                    <select wire:model.live="filterAssignedTo" class="i" style="width:auto; max-width: 160px;">
                        <option value="">All assignees</option>
                        @foreach($users as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                @endif

                @if($filterSearch !== '' || $filterStatus !== '' || $filterPriority !== '' || $filterProjectId !== '' || $filterAssignedTo !== '')
                    <button type="button" class="btn btn-ghost btn-sm" wire:click="clearFilters">
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
            @forelse($tasks as $t)
                @php
                    $isDone = (bool) $t->done;
                    $isLate = !$isDone && $t->due_date && $t->due_date->toDateString() < $today;
                    $daysLate = $isLate ? abs(\Carbon\Carbon::parse($today)->diffInDays($t->due_date, false)) : 0;
                    $custName = $t->project?->customer?->company ?? ($t->customer?->company ?? '');
                    $priorityClass = match($t->priority) {
                        'Urgent' => 'b-red',
                        'High'   => 'b-amber',
                        'Medium' => 'b-navy',
                        default  => 'b-grey',
                    };
                @endphp
                <div wire:key="task-item-{{ $t->id }}"
                     class="mini-item flex items-center gap-3 p-3 border-b last:border-b-0 hover:bg-slate-50 transition">
                    <input type="checkbox"
                           wire:key="task-chk-{{ $t->id }}-{{ $isDone ? 'done' : 'open' }}"
                           wire:click="toggleDone({{ $t->id }})"
                           {{ $isDone ? 'checked' : '' }}
                           class="w-4 h-4 rounded accent-[#0b3d91] cursor-pointer">

                    <div class="flex-1 min-w-0">
                        <div
                            class="t text-sm font-semibold {{ $isDone ? 'line-through text-gray-400' : 'text-[#0d1b34]' }}">
                            {{ $t->title }}
                        </div>
                        <div class="s text-xs text-[#8894ac] mt-0.5">
                            @if($t->project)
                                <span>{{ $t->project->code }} · </span>
                            @endif
                            @if($custName)
                                <span>{{ $custName }} · </span>
                            @endif
                            @if($t->due_date)
                                @if($isLate)
                                    <b style="color:#d94b4b">
                                        due {{ $t->due_date->format('d M') }} ({{ $daysLate }} days late)
                                    </b>
                                @else
                                    <span>due {{ $t->due_date->format('d M') }}</span>
                                @endif
                            @else
                                <span>no date</span>
                            @endif
                            @if($t->assignedTo)
                                <span>· {{ $t->assignedTo->name }}</span>
                            @endif
                        </div>
                    </div>

                    <span class="badge {{ $priorityClass }} text-xs">{{ $t->priority }}</span>

                    <button type="button"
                            class="mini-btn danger"
                            wire:confirm="Remove this task from your list?"
                            wire:click="deleteTask({{ $t->id }})"
                            title="Remove task">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path
                                d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                        </svg>
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
</div>
