<div>
    <div class="page-head">
        <span class="info">
            @if(auth()->user()->role === 'Sales')
                {{ $projects->total() }} project{{ $projects->total() === 1 ? '' : 's' }} originated
            @else
                {{ $projects->total() }} project{{ $projects->total() === 1 ? '' : 's' }} · due <b><x-money
                        :value="$totalDue"/></b>
                @if($totalOverdue > 0)
                    · <b style="color:#d94b4b"><x-money :value="$totalOverdue"/> overdue</b>
                @endif
            @endif
        </span>
        <span class="spacer"></span>
        <button type="button" class="btn btn-primary btn-sm"
                @click="Livewire.dispatch('open-project-modal', { id: null })">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M12 5v14M5 12h14"/>
            </svg>
            New Project
        </button>
    </div>

    <!-- Filterbar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="filterbar">
                <div class="gsearch" style="width:230px">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" placeholder="Project, customer, code...">
                </div>

                <select wire:model.live="customer_id" class="i" style="width:auto; max-width: 180px;">
                    <option value="">All customers</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->display_name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="health" class="i" style="width:auto">
                    <option value="">Any health</option>
                    <option value="On Track">On Track</option>
                    <option value="At Risk">At Risk</option>
                    <option value="Delayed">Delayed</option>
                    <option value="Completed">Completed</option>
                </select>

                <select wire:model.live="status" class="i" style="width:auto">
                    <option value="">Any status</option>
                    <option value="Onboarding">Onboarding</option>
                    <option value="In Progress">In Progress</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>

                @if($coordinators->isNotEmpty())
                    <select wire:model.live="coordinator_id" class="i" style="width:auto; max-width: 160px;">
                        <option value="">All coordinators</option>
                        @foreach($coordinators as $coord)
                            <option value="{{ $coord->id }}">{{ $coord->name }}</option>
                        @endforeach
                    </select>
                @endif

                @if($salesPeople->isNotEmpty())
                    <select wire:model.live="sales_person_id" class="i" style="width:auto; max-width: 160px;">
                        <option value="">All salespeople</option>
                        @foreach($salesPeople as $sp)
                            <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                        @endforeach
                    </select>
                @endif

                @if($projectTypes->isNotEmpty())
                    <select wire:model.live="project_type" class="i" style="width:auto">
                        <option value="">All types</option>
                        @foreach($projectTypes as $pType)
                            <option value="{{ $pType }}">{{ $pType }}</option>
                        @endforeach
                    </select>
                @endif

                @if($search !== '' || $health !== '' || $status !== '' || $customer_id !== '' || $coordinator_id !== '' || $sales_person_id !== '' || $project_type !== '')
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

    <!-- Data Table -->
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Project</th>
                    <th>Customer</th>
                    <th>Coordinator</th>
                    <th>Health</th>
                    @if(auth()->user()->role !== 'Sales')
                        <th class="num">Value</th>
                        <th class="num">Received</th>
                        <th class="num">Due</th>
                    @endif
                    <th>Delivery</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($projects as $project)
                    @php
                        $msCount = $project->milestones->count();
                        $doneMsCount = $project->milestones->where('done', true)->count();
                    @endphp
                    <tr class="clickable" @click="Livewire.navigate('{{ route('projects.show', $project) }}')">
                        <td>
                            <div class="strong">{{ $project->name }}</div>
                            <div class="muted" style="font-size:10.9px">
                                {{ $project->code }}
                                @if($project->project_type)
                                    · {{ $project->project_type }}
                                @endif
                                @if($msCount > 0)
                                    · {{ $doneMsCount }}/{{ $msCount }} milestones
                                @endif
                            </div>
                        </td>
                        <td>{{ $project->customer?->display_name ?? '—' }}</td>
                        <td class="muted">{{ $project->coordinator?->name ?? '—' }}</td>
                        <td>
                            <x-health-badge :health="$project->health"/>
                        </td>
                        @if(auth()->user()->role !== 'Sales')
                            <td class="num">
                                <x-money :value="$project->money['scheduled']"/>
                            </td>
                            <td class="num" style="color:#3d7a24">
                                <x-money :value="$project->money['received']"/>
                            </td>
                            <td class="num font-bold"
                                style="color:{{ $project->money['overdue'] > 0 ? '#d94b4b' : ($project->money['due'] > 0 ? '#96660f' : 'inherit') }}">
                                <x-money :value="$project->money['due']"/>
                            </td>
                        @endif
                        <td class="nowrap muted">{{ $project->delivery_date?->format('d M') ?? '—' }}</td>
                        <td>
                            <div class="row-actions flex items-center justify-end gap-1.5">
                                <a href="{{ route('projects.show', $project) }}" wire:navigate class="btn btn-outline-primary btn-xs"
                                   title="View Project" @click.stop>
                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                    <span>View</span>
                                </a>
                                <button type="button" class="btn btn-outline-secondary btn-xs" title="Edit Project"
                                        @click.stop="Livewire.dispatch('open-project-modal', { id: {{ $project->id }} })">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                        <path d="M12 20h9"/>
                                        <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                    </svg>
                                    <span>Edit</span>
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="empty">
                                 <span class="flex items-center justify-center">
                                <svg viewBox="0 0 24 24">
                                    <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                                </svg>
                                 </span>
                                <p>No projects found</p>
                                <div class="sm">Change the filters or create a new project</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-[#e2e8f3]">{{ $projects->links() }}</div>
    </div>
</div>
