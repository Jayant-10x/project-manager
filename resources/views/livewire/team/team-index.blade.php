<div>
    <!-- Top Hierarchy Banner & Team Stats -->
    <div class="card p-5 mb-6 bg-gradient-to-r from-blue-50/70 via-indigo-50/50 to-white border border-blue-100 rounded-2xl">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div class="flex items-center gap-3.5">
                @if($currentUser->avatar_url)
                    <img src="{{ $currentUser->avatar_url }}" alt="{{ $currentUser->name }}" class="w-12 h-12 rounded-2xl object-cover shadow-sm shrink-0 border border-blue-200">
                @else
                    <div class="w-12 h-12 rounded-2xl bg-[#0b3d91] text-white flex items-center justify-center font-bold text-base shadow-sm shrink-0">
                        {{ strtoupper(substr($currentUser->name, 0, 2)) }}
                    </div>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <h2 class="text-base font-bold text-[#0d1b34]">{{ $currentUser->name }}</h2>
                        <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-[#0b3d91]">
                            {{ $currentUser->role }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Your team directory &middot; <strong>{{ $stats['total'] }}</strong> member(s) reporting to you
                    </p>
                </div>
            </div>

            <!-- Quick Team Chips -->
            <div class="flex items-center gap-2.5 flex-wrap">
                <div class="px-3 py-1.5 rounded-xl bg-white border border-gray-200 text-xs shadow-2xs">
                    <span class="text-gray-400">Total Team:</span>
                    <strong class="text-gray-800 ml-1">{{ $stats['total'] }}</strong>
                </div>
                @if($currentUser->role === 'Manager')
                    <div class="px-3 py-1.5 rounded-xl bg-white border border-amber-200 text-xs shadow-2xs">
                        <span class="text-amber-600 font-medium">Coordinators:</span>
                        <strong class="text-amber-800 ml-1">{{ $stats['coordinators'] }}</strong>
                    </div>
                @endif
                <div class="px-3 py-1.5 rounded-xl bg-white border border-emerald-200 text-xs shadow-2xs">
                    <span class="text-emerald-600 font-medium">Sales Reps:</span>
                    <strong class="text-emerald-800 ml-1">{{ $stats['sales'] }}</strong>
                </div>
            </div>
        </div>

        @if(!empty($pendingResetRequests) && $pendingResetRequests->isNotEmpty())
            <div class="mt-4 pt-4 border-t border-blue-100/80">
                <div class="p-3.5 bg-amber-50/80 rounded-xl border border-amber-200/90">
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            <span class="text-xs font-bold text-amber-900 uppercase tracking-wide">Team Password Reset Requests</span>
                        </div>
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-200 text-amber-800">
                            {{ $pendingResetRequests->count() }} pending
                        </span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-2.5 mt-2">
                        @foreach($pendingResetRequests as $resetReq)
                            <div class="p-2.5 bg-white rounded-lg border border-amber-200/80 flex items-center justify-between gap-2 shadow-2xs">
                                <div class="min-w-0">
                                    <div class="text-xs font-bold text-slate-800 truncate">{{ $resetReq->user?->name ?? 'User' }}</div>
                                    <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                        <span>{{ $resetReq->user?->role }}</span>
                                        <span>&middot;</span>
                                        <span>{{ $resetReq->created_at?->diffForHumans() }}</span>
                                    </div>
                                </div>
                                <button type="button"
                                        @click="$dispatch('confirm-dialog', {
                                            title: 'Generate Temporary Password?',
                                            message: 'Are you sure you want to generate a new temporary password for {{ addslashes($resetReq->user?->name ?? 'this member') }}? Their existing password will be replaced immediately.',
                                            confirmText: 'Generate & Resolve',
                                            cancelText: 'Cancel',
                                            danger: false,
                                            wire: $wire,
                                            action: 'resetUserPassword',
                                            params: [{{ $resetReq->user_id }}],
                                            args: [{{ $resetReq->user_id }}]
                                        })"
                                        class="btn btn-sm btn-primary py-1 px-2.5 text-xs font-semibold shrink-0">
                                    Reset Password
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Filter Toolbar -->
    <div class="card p-4 mb-6">
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3">
            <div class="filterbar flex-wrap">
                <!-- Search Input with fixed width -->
                <div class="gsearch" style="width:260px">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" placeholder="Search by name, email, city...">
                </div>

                <!-- Role Filter (Only show relevant roles for reporting team) -->
                @if($currentUser->role === 'Manager')
                    <select wire:model.live="roleFilter" class="i" style="width:auto">
                        <option value="">All Roles</option>
                        <option value="Coordinator">Coordinator</option>
                        <option value="TechPerson">Tech Person</option>
                        <option value="Sales">Sales</option>
                    </select>
                @endif

                @if($search !== '' || $roleFilter !== '')
                    <button type="button"
                            wire:click="$set('search', ''); $set('roleFilter', '');"
                            class="btn btn-outline-danger btn-sm">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                        Clear filters
                    </button>
                @endif
            </div>

            <div class="text-xs text-gray-400 italic">
                Read-only directory &middot; Contact Admin to modify credentials
            </div>
        </div>
    </div>

    <!-- Team Members Grid -->
    @if($members->isEmpty())
        <div class="card p-12 text-center bg-white rounded-2xl border border-gray-100">
            <div class="w-14 h-14 rounded-2xl bg-blue-50 text-[#0b3d91] mx-auto flex items-center justify-center mb-3">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <h3 class="font-bold text-[#0d1b34] text-base mb-1">No team members assigned</h3>
            <p class="text-xs text-gray-500 max-w-sm mx-auto">
                No team members are currently assigned to report to you. Contact your Administrator to assign coordinators or sales team members to your team.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
            @foreach($members as $member)
                @php
                    $roleColors = [
                        'Coordinator' => ['badge' => 'bg-amber-50 text-amber-700 border-amber-200', 'avatar' => '#d97706'],
                        'TechPerson' => ['badge' => 'bg-cyan-50 text-cyan-700 border-cyan-200', 'avatar' => '#0891b2'],
                        'Sales' => ['badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'avatar' => '#059669'],
                        'Manager' => ['badge' => 'bg-purple-50 text-purple-700 border-purple-200', 'avatar' => '#7e22ce'],
                    ];
                    $cfg = $roleColors[$member->role] ?? ['badge' => 'bg-gray-50 text-gray-700 border-gray-200', 'avatar' => '#0b3d91'];
                @endphp

                <div class="card p-5 flex flex-col justify-between transition-all duration-200 hover:shadow-md border border-gray-100 rounded-2xl bg-white relative cursor-pointer"
                     wire:click="viewMember({{ $member->id }})">
                    <div>
                        <!-- Member Header -->
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                @if($member->avatar_url)
                                    <img src="{{ $member->avatar_url }}" alt="{{ $member->name }}" class="w-11 h-11 rounded-xl object-cover shrink-0 shadow-2xs border border-gray-200">
                                @else
                                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-xs"
                                         style="background:{{ $cfg['avatar'] }}">
                                        {{ strtoupper(substr($member->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <h4 class="font-bold text-[#0d1b34] text-sm truncate leading-tight" title="{{ $member->name }}">
                                        {{ $member->name }}
                                    </h4>
                                    <div class="text-xs text-gray-500 truncate mt-0.5">
                                        {{ $member->designation ?: $member->role }}
                                    </div>
                                </div>
                            </div>

                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold shrink-0 {{ $member->status === 'Active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $member->status === 'Active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                {{ $member->status }}
                            </span>
                        </div>

                        <!-- Role & Supervisor Line -->
                        <div class="flex items-center gap-2 flex-wrap mb-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $cfg['badge'] }}">
                                {{ $member->role }}
                            </span>

                            @if($member->reportingTo)
                                <span class="text-[11px] text-gray-500 inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M7 11l5-5 5 5M12 6v12"/>
                                    </svg>
                                    Reports to <strong>{{ $member->reporting_to === $currentUser->id ? 'You' : $member->reportingTo->name }}</strong>
                                </span>
                            @endif
                        </div>

                        <!-- Details Rows -->
                        <div class="space-y-1.5 text-xs text-gray-600 mb-4 bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                            <div class="flex items-center gap-2 truncate">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/>
                                </svg>
                                <a href="mailto:{{ $member->email }}" @click.stop class="truncate hover:text-[#0b3d91] transition">{{ $member->email }}</a>
                            </div>

                            @if($member->phone)
                                <div class="flex items-center gap-2 truncate">
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                                    </svg>
                                    <a href="tel:{{ $member->phone }}" @click.stop class="truncate hover:text-[#0b3d91] transition">{{ $member->phone }}</a>
                                </div>
                            @endif

                            @if($member->city)
                                <div class="flex items-center gap-2 truncate">
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="truncate">{{ $member->city }}</span>
                                </div>
                            @endif

                            @if($member->role === 'Sales')
                                @php
                                    $target = (float) ($member->target ?? 0.0);
                                    $achieved = (float) ($member->total_sales_value ?? 0.0);
                                    $progress = $target > 0 ? round(($achieved / $target) * 100, 1) : 0;
                                    $barWidth = min(100, max(0, $progress));
                                    $status = $member->target_status;
                                @endphp
                                <div class="pt-2 border-t border-gray-200/60 mt-1.5">
                                    <div class="flex items-center justify-between text-xs mb-1">
                                        <span class="font-semibold text-gray-700 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>
                                            </svg>
                                            Target Progress
                                        </span>
                                        @if($target > 0)
                                            <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-bold border {{ $status['badge'] }}">
                                                <span>{{ $progress }}%</span>
                                                <span class="opacity-60">·</span>
                                                <span>{{ $status['label'] }}</span>
                                            </span>
                                        @else
                                            <span class="text-[10px] text-gray-400">Not set</span>
                                        @endif
                                    </div>

                                    @if($target > 0)
                                        <div class="w-full bg-gray-200/80 rounded-full h-1.5 overflow-hidden mb-1">
                                            <div class="h-full rounded-full transition-all duration-300 {{ $progress >= 100 ? 'bg-emerald-500' : ($progress >= 50 ? 'bg-blue-600' : 'bg-amber-500') }}"
                                                 style="width: {{ $barWidth }}%"></div>
                                        </div>
                                        <div class="flex items-center justify-between text-[11px] text-gray-500">
                                            <span><x-money :value="$achieved"/></span>
                                            <span class="text-gray-400">/ <x-money :value="$target"/></span>
                                        </div>
                                    @else
                                        <div class="text-[11px] text-gray-400 italic">No monthly target assigned</div>
                                    @endif
                                </div>
                            @elseif($member->subordinates_count > 0)
                                <div class="flex items-center justify-between pt-1 border-t border-gray-200/60 mt-1.5">
                                    <span class="text-gray-500 font-medium">Direct Team:</span>
                                    <strong class="text-indigo-700 font-semibold">{{ $member->subordinates_count }} member(s)</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Card Action (View Only) -->
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-end">
                        <button type="button"
                                @click.stop
                                wire:click="viewMember({{ $member->id }})"
                                class="btn btn-outline-primary btn-xs w-full justify-center">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
                            </svg>
                            <span>View Member Profile</span>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>

        <div>
            {{ $members->links() }}
        </div>
    @endif

    <!-- Team Member View Modal -->
    @if($viewingMember)
        <div class="overlay fixed inset-0 flex items-start justify-center p-4 sm:p-6 z-[120] overflow-y-auto overscroll-contain"
             style="background: rgba(8, 20, 42, 0.60); backdrop-filter: blur(4px);"
             wire:keydown.escape.window="closeViewModal"
             x-data
             x-init="window.ModalManager?.open('team-view-modal')"
             x-destroy="window.ModalManager?.close('team-view-modal')">
            <!-- Modal Panel -->
            <div class="modal bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden my-auto border border-gray-100"
                 @click.outside="$wire.closeViewModal(); window.ModalManager?.close('team-view-modal');">
                <div class="bg-gradient-to-r from-[#0b3d91] to-[#1e5bbd] px-6 py-5 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @if($viewingMember->avatar_url)
                            <img src="{{ $viewingMember->avatar_url }}" alt="{{ $viewingMember->name }}" class="w-12 h-12 rounded-xl object-cover border border-white/40 shadow-xs">
                        @else
                            <div class="w-12 h-12 rounded-xl bg-white/20 text-white flex items-center justify-center font-bold text-base shadow-xs">
                                {{ strtoupper(substr($viewingMember->name, 0, 2)) }}
                            </div>
                        @endif
                        <div>
                            <h3 class="font-bold text-lg leading-tight">{{ $viewingMember->name }}</h3>
                            <div class="text-xs text-blue-100 mt-0.5">{{ $viewingMember->designation ?: $viewingMember->role }} &middot; {{ $viewingMember->role }}</div>
                        </div>
                    </div>
                    <button type="button" @click="$wire.closeViewModal(); window.ModalManager?.close('team-view-modal');" class="text-white/80 hover:text-white transition p-1" title="Close">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <div>
                            <div class="text-xs text-gray-500">Status</div>
                            <div class="font-semibold text-sm {{ $viewingMember->status === 'Active' ? 'text-emerald-700' : 'text-gray-600' }}">
                                {{ $viewingMember->status }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500">Team Role</div>
                            <div class="font-semibold text-sm text-[#0b3d91]">{{ $viewingMember->role }}</div>
                        </div>
                    </div>

                    <div>
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Contact Details</h5>
                        <div class="grid grid-cols-2 gap-3 text-xs bg-gray-50/70 p-3.5 rounded-xl border border-gray-100">
                            <div>
                                <div class="text-gray-400 font-medium">Email</div>
                                <a href="mailto:{{ $viewingMember->email }}" class="text-gray-800 font-medium hover:text-[#0b3d91] break-all">{{ $viewingMember->email }}</a>
                            </div>
                            <div>
                                <div class="text-gray-400 font-medium">Phone</div>
                                <div class="text-gray-800 font-medium">{{ $viewingMember->phone ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 font-medium">City</div>
                                <div class="text-gray-800 font-medium">{{ $viewingMember->city ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 font-medium">Joined Date</div>
                                <div class="text-gray-800 font-medium">{{ $viewingMember->created_at ? $viewingMember->created_at->format('d M Y') : '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Hierarchy Position</h5>
                        <div class="bg-gray-50/70 p-3.5 rounded-xl border border-gray-100 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 font-medium">Reports Directly To:</span>
                                <strong class="text-gray-800">{{ $viewingMember->reporting_to === $currentUser->id ? 'You' : ($viewingMember->reportingTo ? $viewingMember->reportingTo->name . ' (' . $viewingMember->reportingTo->role . ')' : 'Top-Level') }}</strong>
                            </div>

                            @if($viewingMember->role === 'Sales')
                                @php
                                    $target = (float) ($viewingMember->target ?? 0.0);
                                    $achieved = (float) ($viewingMember->total_sales_value ?? 0.0);
                                    $progress = $target > 0 ? round(($achieved / $target) * 100, 1) : 0;
                                    $barWidth = min(100, max(0, $progress));
                                    $status = $viewingMember->target_status;
                                @endphp
                                <div class="pt-3 border-t border-gray-200/70">
                                    <div class="flex items-center justify-between mb-2">
                                        <span class="font-bold text-gray-700 flex items-center gap-1.5 uppercase text-[11px] tracking-wider">
                                            <svg class="w-3.5 h-3.5 text-emerald-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/>
                                            </svg>
                                            Target & Performance
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold border {{ $status['badge'] }}">
                                            {{ $status['label'] }} ({{ $progress }}%)
                                        </span>
                                    </div>

                                    @if($target > 0)
                                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden mb-2">
                                            <div class="h-full rounded-full transition-all duration-500 {{ $progress >= 100 ? 'bg-emerald-500' : ($progress >= 50 ? 'bg-blue-600' : 'bg-amber-500') }}"
                                                 style="width: {{ $barWidth }}%"></div>
                                        </div>

                                        <div class="grid grid-cols-3 gap-2 text-center bg-white p-2.5 rounded-lg border border-gray-200/80 mb-2">
                                            <div>
                                                <div class="text-[10px] uppercase text-gray-400 font-semibold">Target</div>
                                                <div class="font-bold text-gray-800 text-xs mt-0.5"><x-money :value="$target"/></div>
                                            </div>
                                            <div>
                                                <div class="text-[10px] uppercase text-emerald-700 font-semibold">Booked</div>
                                                <div class="font-bold text-emerald-700 text-xs mt-0.5"><x-money :value="$achieved"/></div>
                                            </div>
                                            <div>
                                                <div class="text-[10px] uppercase text-blue-700 font-semibold">Deals</div>
                                                <div class="font-bold text-blue-700 text-xs mt-0.5">{{ $viewingMember->sales_projects_count }}</div>
                                            </div>
                                        </div>

                                        <div class="flex items-center justify-between text-[11px] text-gray-500">
                                            <span>Remaining to Goal:</span>
                                            @if($achieved < $target)
                                                <strong class="text-amber-700 font-semibold"><x-money :value="$target - $achieved"/></strong>
                                            @else
                                                <strong class="text-emerald-700 font-semibold">Goal Exceeded by <x-money :value="$achieved - $target"/></strong>
                                            @endif
                                        </div>
                                    @else
                                        <div class="text-xs text-gray-400 italic bg-white p-2.5 rounded-lg border border-gray-100">
                                            No monthly sales target configured for this representative.
                                        </div>
                                    @endif
                                </div>
                            @elseif($viewingMember->isTechPerson())
                                <div class="flex items-center justify-between pt-2 border-t border-gray-200/60">
                                    <span class="text-gray-500 font-medium">Assigned Projects (Tech Person):</span>
                                    <strong class="text-cyan-700 font-bold">{{ $viewingMember->tech_projects_count }} projects</strong>
                                </div>
                            @elseif($viewingMember->subordinates_count > 0)
                                <div class="flex items-center justify-between pt-2 border-t border-gray-200/60">
                                    <span class="text-gray-500 font-medium">Direct Subordinates:</span>
                                    <strong class="text-indigo-700 font-bold">{{ $viewingMember->subordinates_count }} members</strong>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-6 py-3 flex items-center justify-between border-t border-gray-100">
                    <button type="button"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Generate Temporary Password?',
                                message: 'Are you sure you want to generate a new temporary password for {{ addslashes($viewingMember->name) }}? Their existing password will be replaced immediately.',
                                confirmText: 'Generate & Resolve',
                                cancelText: 'Cancel',
                                danger: false,
                                wire: $wire,
                                action: 'resetUserPassword',
                                params: [{{ $viewingMember->id }}],
                                args: [{{ $viewingMember->id }}]
                            })"
                            class="btn btn-outline-danger btn-sm">
                        Reset Password
                    </button>
                    <button type="button"
                            wire:click="closeViewModal"
                            class="btn btn-outline-secondary btn-sm">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Temporary Credentials Share Modal for Team -->
    <x-modal name="temp-credentials-modal" title="Temporary Password Generated" sub="Share these temporary credentials with the team member" :wide="false">
        @if($tempCredentials)
            <div x-data="{
                copiedPass: false,
                copiedAll: false,
                copyPassword() {
                    navigator.clipboard.writeText('{{ $tempCredentials['password'] }}');
                    this.copiedPass = true;
                    setTimeout(() => this.copiedPass = false, 2000);
                },
                copyAll() {
                    navigator.clipboard.writeText(`{{ addslashes($tempCredentials['fullText']) }}`);
                    this.copiedAll = true;
                    setTimeout(() => this.copiedAll = false, 2000);
                }
            }" class="space-y-4">
                <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-700 leading-relaxed">
                    Temporary credentials generated for <strong>{{ $tempCredentials['name'] }}</strong> ({{ $tempCredentials['email'] }}).
                </div>

                <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 space-y-3 text-xs">
                    <div>
                        <span class="text-slate-400 block font-semibold text-[11px] uppercase tracking-wider mb-1">Generated Temporary Password</span>
                        <div class="flex items-center gap-2">
                            <span class="font-mono text-base font-bold text-[#0b3d91] bg-white px-3 py-1.5 rounded-lg border border-slate-300 select-all tracking-wider flex-1">
                                {{ $tempCredentials['password'] }}
                            </span>
                            <button type="button"
                                    @click="copyPassword()"
                                    class="btn btn-sm btn-primary inline-flex items-center gap-1.5">
                                <span x-show="!copiedPass">Copy Password</span>
                                <span x-show="copiedPass" style="display:none;" class="text-emerald-300 font-bold">Copied!</span>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="pt-3 border-t border-slate-200 flex justify-between items-center">
                    <button type="button"
                            @click="copyAll()"
                            class="btn btn-outline-secondary btn-sm inline-flex items-center gap-1.5">
                        <span x-show="!copiedAll">Copy All Details</span>
                        <span x-show="copiedAll" style="display:none;" class="text-emerald-600 font-bold">All Details Copied!</span>
                    </button>
                    <button type="button"
                            wire:click="closeTempCredentialsModal"
                            class="btn btn-secondary btn-sm">
                        Done
                    </button>
                </div>
            </div>
        @endif
    </x-modal>
</div>
