<div>
    <!-- Top Stats Bar -->
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3 mb-6">
        <div class="card p-3.5 flex items-center justify-between cursor-pointer transition hover:border-blue-400"
             wire:click="$set('roleFilter', '')"
             style="{{ $roleFilter === '' ? 'border-color:#0b3d91;background:#f5f8ff' : '' }}">
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Users</div>
                <div class="text-xl font-bold text-[#0d1b34] mt-0.5">{{ $stats['total'] }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-blue-50 text-[#0b3d91] flex items-center justify-center font-bold text-xs">
                ALL
            </div>
        </div>

        <div class="card p-3.5 flex items-center justify-between cursor-pointer transition hover:border-indigo-400"
             wire:click="$set('roleFilter', 'Admin')"
             style="{{ $roleFilter === 'Admin' ? 'border-color:#4338ca;background:#f5f5ff' : '' }}">
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Admins</div>
                <div class="text-xl font-bold text-indigo-700 mt-0.5">{{ $stats['admins'] }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-indigo-50 text-indigo-700 flex items-center justify-center font-bold text-xs">
                ADM
            </div>
        </div>

        <div class="card p-3.5 flex items-center justify-between cursor-pointer transition hover:border-purple-400"
             wire:click="$set('roleFilter', 'Manager')"
             style="{{ $roleFilter === 'Manager' ? 'border-color:#7e22ce;background:#faf5ff' : '' }}">
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Managers</div>
                <div class="text-xl font-bold text-purple-700 mt-0.5">{{ $stats['managers'] }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-purple-50 text-purple-700 flex items-center justify-center font-bold text-xs">
                MGR
            </div>
        </div>

        <div class="card p-3.5 flex items-center justify-between cursor-pointer transition hover:border-amber-400"
             wire:click="$set('roleFilter', 'Coordinator')"
             style="{{ $roleFilter === 'Coordinator' ? 'border-color:#d97706;background:#fffbeb' : '' }}">
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Coordinators</div>
                <div class="text-xl font-bold text-amber-700 mt-0.5">{{ $stats['coordinators'] }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-700 flex items-center justify-center font-bold text-xs">
                CRD
            </div>
        </div>

        <div class="card p-3.5 flex items-center justify-between cursor-pointer transition hover:border-emerald-400"
             wire:click="$set('roleFilter', 'Sales')"
             style="{{ $roleFilter === 'Sales' ? 'border-color:#059669;background:#f0fdf4' : '' }}">
            <div>
                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Sales Reps</div>
                <div class="text-xl font-bold text-emerald-700 mt-0.5">{{ $stats['sales'] }}</div>
            </div>
            <div class="w-9 h-9 rounded-xl bg-emerald-50 text-emerald-700 flex items-center justify-center font-bold text-xs">
                SLS
            </div>
        </div>
    </div>

    <!-- Filter & Action Toolbar -->
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

                <!-- Role Filter -->
                <select wire:model.live="roleFilter" class="i" style="width:auto">
                    <option value="">All Roles</option>
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Coordinator">Coordinator</option>
                    <option value="Sales">Sales</option>
                </select>

                <!-- Status Filter -->
                <select wire:model.live="statusFilter" class="i" style="width:auto">
                    <option value="">All Statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>

                @if($search !== '' || $roleFilter !== '' || $statusFilter !== '')
                    <button type="button"
                            wire:click="$set('search', ''); $set('roleFilter', ''); $set('statusFilter', '');"
                            class="btn btn-outline-danger btn-sm">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                        Clear filters
                    </button>
                @endif
            </div>

            <!-- Add User Button -->
            @can('users.add')
                <button type="button"
                        @click="Livewire.dispatch('open-user-modal', { id: null })"
                        class="btn btn-primary inline-flex items-center justify-center gap-1.5 whitespace-nowrap">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <span>Add New User</span>
                </button>
            @endcan
        </div>
    </div>

    <!-- User Card Grid -->
    @if($users->isEmpty())
        <div class="card p-12 text-center bg-white rounded-2xl border border-gray-100">
            <div class="w-14 h-14 rounded-2xl bg-gray-50 text-gray-400 mx-auto flex items-center justify-center mb-3">
                <svg class="w-7 h-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/>
                </svg>
            </div>
            <h3 class="font-bold text-[#0d1b34] text-base mb-1">No users found</h3>
            <p class="text-xs text-gray-500 max-w-sm mx-auto">
                No users match your search criteria. Try modifying your filters or add a new user.
            </p>
        </div>
    @else
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5 mb-6">
            @foreach($users as $user)
                @php
                    $roleColors = [
                        'Admin' => ['badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'avatar' => '#4338ca'],
                        'Manager' => ['badge' => 'bg-purple-50 text-purple-700 border-purple-200', 'avatar' => '#7e22ce'],
                        'Coordinator' => ['badge' => 'bg-amber-50 text-amber-700 border-amber-200', 'avatar' => '#d97706'],
                        'Sales' => ['badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'avatar' => '#059669'],
                    ];
                    $cfg = $roleColors[$user->role] ?? ['badge' => 'bg-gray-50 text-gray-700 border-gray-200', 'avatar' => '#0b3d91'];
                @endphp

                <div class="card p-5 flex flex-col justify-between transition-all duration-200 hover:shadow-md border border-gray-100 rounded-2xl bg-white relative cursor-pointer"
                     wire:click="viewUser({{ $user->id }})">
                    <!-- Top Info -->
                    <div>
                        <div class="flex items-start justify-between gap-3 mb-3">
                            <div class="flex items-center gap-3 min-w-0">
                                @if($user->avatar_url)
                                    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}" class="w-11 h-11 rounded-xl object-cover shrink-0 shadow-2xs border border-gray-200">
                                @else
                                    <div class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-xs"
                                         style="background:{{ $cfg['avatar'] }}">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                @endif
                                <div class="min-w-0">
                                    <h4 class="font-bold text-[#0d1b34] text-sm truncate leading-tight" title="{{ $user->name }}">
                                        {{ $user->name }}
                                    </h4>
                                    <div class="text-xs text-gray-500 truncate mt-0.5">
                                        {{ $user->designation ?: $user->role }}
                                    </div>
                                </div>
                            </div>

                            <!-- Status Pill (Quick Toggle with Confirmation) -->
                            @can('users.edit')
                                <button type="button"
                                        @click.stop="$dispatch('confirm-dialog', {
                                            title: '{{ $user->status === 'Active' ? 'Deactivate User Account?' : 'Activate User Account?' }}',
                                            message: '{{ $user->status === 'Active' ? 'Are you sure you want to deactivate ' . addslashes($user->name) . '? They will not be able to log in.' : 'Are you sure you want to activate ' . addslashes($user->name) . '?' }}',
                                            confirmText: '{{ $user->status === 'Active' ? 'Deactivate' : 'Activate' }}',
                                            danger: {{ $user->status === 'Active' ? 'true' : 'false' }},
                                            onConfirm: () => $wire.toggleUserStatus({{ $user->id }})
                                        })"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] font-semibold shrink-0 transition {{ $user->status === 'Active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100' : 'bg-gray-100 text-gray-600 border border-gray-200 hover:bg-gray-200' }}"
                                        title="Click to toggle account status">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'Active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                    <span>{{ $user->status }}</span>
                                    <svg class="w-3 h-3 text-gray-400 opacity-70 ml-0.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"/>
                                    </svg>
                                </button>
                            @else
                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[11px] font-semibold shrink-0 {{ $user->status === 'Active' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-gray-100 text-gray-600 border border-gray-200' }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $user->status === 'Active' ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                    {{ $user->status }}
                                </span>
                            @endcan
                        </div>

                        <!-- Role & Hierarchy Line -->
                        <div class="flex items-center gap-2 flex-wrap mb-4">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-[11px] font-semibold border {{ $cfg['badge'] }}">
                                {{ $user->role }}
                            </span>

                            @if($user->reportingTo)
                                <span class="text-[11px] text-gray-500 inline-flex items-center gap-1" title="Reports to supervisor">
                                    <svg class="w-3 h-3 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M7 11l5-5 5 5M12 6v12"/>
                                    </svg>
                                    Reports to <strong class="text-gray-700 font-medium">{{ $user->reportingTo->name }}</strong>
                                </span>
                            @else
                                <span class="text-[11px] text-gray-400">Top-level Supervisor</span>
                            @endif
                        </div>

                        <!-- Details Rows -->
                        <div class="space-y-1.5 text-xs text-gray-600 mb-4 bg-gray-50/70 p-3 rounded-xl border border-gray-100">
                            <div class="flex items-center gap-2 truncate">
                                <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><path d="M22 6l-10 7L2 6"/>
                                </svg>
                                <a href="mailto:{{ $user->email }}" @click.stop class="truncate hover:text-[#0b3d91] transition">{{ $user->email }}</a>
                            </div>

                            @if($user->phone)
                                <div class="flex items-center gap-2 truncate">
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M22 16.92v3a2 2 0 01-2.18 2 19.79 19.79 0 01-8.63-3.07 19.5 19.5 0 01-6-6 19.79 19.79 0 01-3.07-8.67A2 2 0 014.11 2h3a2 2 0 012 1.72 12.84 12.84 0 00.7 2.81 2 2 0 01-.45 2.11L8.09 9.91a16 16 0 006 6l1.27-1.27a2 2 0 012.11-.45 12.84 12.84 0 002.81.7A2 2 0 0122 16.92z"/>
                                    </svg>
                                    <a href="tel:{{ $user->phone }}" @click.stop class="truncate hover:text-[#0b3d91] transition">{{ $user->phone }}</a>
                                </div>
                            @endif

                            @if($user->city)
                                <div class="flex items-center gap-2 truncate">
                                    <svg class="w-3.5 h-3.5 text-gray-400 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0118 0z"/><circle cx="12" cy="10" r="3"/>
                                    </svg>
                                    <span class="truncate">{{ $user->city }}</span>
                                </div>
                            @endif

                            @if($user->role === 'Sales')
                                @php
                                    $target = (float) ($user->target ?? 0.0);
                                    $achieved = (float) ($user->total_sales_value ?? 0.0);
                                    $progress = $target > 0 ? round(($achieved / $target) * 100, 1) : 0;
                                    $barWidth = min(100, max(0, $progress));
                                    $status = $user->target_status;
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
                            @elseif($user->subordinates_count > 0)
                                <div class="flex items-center justify-between pt-1 border-t border-gray-200/60 mt-1.5">
                                    <span class="text-gray-500 font-medium">Direct Team:</span>
                                    <strong class="text-indigo-700 font-semibold">{{ $user->subordinates_count }} member(s)</strong>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Card Actions -->
                    <div class="pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                        <button type="button"
                                @click.stop
                                wire:click="viewUser({{ $user->id }})"
                                class="btn btn-outline-primary btn-xs flex-1 justify-center">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>
                            </svg>
                            <span>View</span>
                        </button>

                        @can('users.edit')
                            <button type="button"
                                    @click.stop="Livewire.dispatch('open-user-modal', { id: {{ $user->id }} })"
                                    class="btn btn-outline-secondary btn-xs flex-1 justify-center">
                                <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                    <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                                </svg>
                                <span>Edit</span>
                            </button>
                        @endcan

                        @can('users.delete')
                            @if(auth()->id() !== $user->id)
                                <button type="button"
                                        class="btn btn-outline-danger btn-xs"
                                        title="Delete user"
                                        @click.stop="$dispatch('confirm-dialog', {
                                            title: 'Delete User Account?',
                                            message: 'Are you sure you want to permanently delete {{ addslashes($user->name) }} ({{ $user->role }})? This action cannot be undone.',
                                            confirmText: 'Delete User',
                                            danger: true,
                                            onConfirm: () => $wire.deleteUser({{ $user->id }})
                                        })">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/>
                                    </svg>
                                    <span>Delete</span>
                                </button>
                            @endif
                        @endcan
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div>
            {{ $users->links() }}
        </div>
    @endif

    <!-- User Detail View Modal -->
    @if($viewingUser)
        <div class="overlay fixed inset-0 flex items-start justify-center p-4 sm:p-6 z-[120] overflow-y-auto overscroll-contain"
             style="background: rgba(8, 20, 42, 0.60); backdrop-filter: blur(4px);"
             wire:keydown.escape.window="closeViewModal"
             x-data
             x-init="window.ModalManager?.open('user-view-modal')"
             x-destroy="window.ModalManager?.close('user-view-modal')">
            <!-- Modal Panel -->
            <div class="modal bg-white rounded-2xl w-full max-w-lg shadow-2xl overflow-hidden my-auto border border-gray-100"
                 @click.outside="$wire.closeViewModal(); window.ModalManager?.close('user-view-modal');">
                <div class="bg-gradient-to-r from-[#0b3d91] to-[#1e5bbd] px-6 py-5 text-white flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        @if($viewingUser->avatar_url)
                            <img src="{{ $viewingUser->avatar_url }}" alt="{{ $viewingUser->name }}" class="w-12 h-12 rounded-xl object-cover border border-white/40 shadow-xs">
                        @else
                            <div class="w-12 h-12 rounded-xl bg-white/20 text-white flex items-center justify-center font-bold text-base shadow-xs backdrop-blur-xs">
                                {{ strtoupper(substr($viewingUser->name, 0, 2)) }}
                            </div>
                        @endif
                        <div>
                            <h3 class="font-bold text-lg leading-tight">{{ $viewingUser->name }}</h3>
                            <div class="text-xs text-blue-100 mt-0.5">{{ $viewingUser->designation ?: $viewingUser->role }} &middot; {{ $viewingUser->role }}</div>
                        </div>
                    </div>
                    <button type="button" @click="$wire.closeViewModal(); window.ModalManager?.close('user-view-modal');" class="text-white/80 hover:text-white transition p-1" title="Close">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Account Status Strip -->
                    <div class="flex items-center justify-between p-3 rounded-xl bg-gray-50 border border-gray-100">
                        <div>
                            <div class="text-xs text-gray-500">Account Status</div>
                            <div class="font-semibold text-sm {{ $viewingUser->status === 'Active' ? 'text-emerald-700' : 'text-gray-600' }}">
                                {{ $viewingUser->status }}
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-xs text-gray-500">System Role</div>
                            <div class="font-semibold text-sm text-[#0b3d91]">{{ $viewingUser->role }}</div>
                        </div>
                    </div>

                    <!-- Contact Details -->
                    <div>
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Contact Details</h5>
                        <div class="grid grid-cols-2 gap-3 text-xs bg-gray-50/70 p-3.5 rounded-xl border border-gray-100">
                            <div>
                                <div class="text-gray-400 font-medium">Email</div>
                                <a href="mailto:{{ $viewingUser->email }}" class="text-gray-800 font-medium hover:text-[#0b3d91] break-all">{{ $viewingUser->email }}</a>
                            </div>
                            <div>
                                <div class="text-gray-400 font-medium">Phone</div>
                                <div class="text-gray-800 font-medium">{{ $viewingUser->phone ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 font-medium">City</div>
                                <div class="text-gray-800 font-medium">{{ $viewingUser->city ?: '—' }}</div>
                            </div>
                            <div>
                                <div class="text-gray-400 font-medium">Member Since</div>
                                <div class="text-gray-800 font-medium">{{ $viewingUser->created_at ? $viewingUser->created_at->format('d M Y') : '—' }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Hierarchy & Team -->
                    <div>
                        <h5 class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">Hierarchy & Reporting</h5>
                        <div class="bg-gray-50/70 p-3.5 rounded-xl border border-gray-100 space-y-2 text-xs">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 font-medium">Reports Directly To:</span>
                                <strong class="text-gray-800">{{ $viewingUser->reportingTo ? $viewingUser->reportingTo->name . ' (' . $viewingUser->reportingTo->role . ')' : 'Top-Level Management' }}</strong>
                            </div>

                            @if($viewingUser->role === 'Sales')
                                @php
                                    $target = (float) ($viewingUser->target ?? 0.0);
                                    $achieved = (float) ($viewingUser->total_sales_value ?? 0.0);
                                    $progress = $target > 0 ? round(($achieved / $target) * 100, 1) : 0;
                                    $barWidth = min(100, max(0, $progress));
                                    $status = $viewingUser->target_status;
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
                                                <div class="font-bold text-blue-700 text-xs mt-0.5">{{ $viewingUser->sales_projects_count }}</div>
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
                            @elseif(in_array($viewingUser->role, ['Manager', 'Coordinator']))
                                <div class="pt-2 border-t border-gray-200/60">
                                    <div class="flex items-center justify-between mb-1.5">
                                        <span class="text-gray-500 font-medium">Direct Team Members:</span>
                                        <strong class="text-indigo-700">{{ $viewingUser->subordinates->count() }} members</strong>
                                    </div>
                                    @if($viewingUser->subordinates->isNotEmpty())
                                        <div class="flex flex-wrap gap-1.5 mt-2">
                                            @foreach($viewingUser->subordinates as $sub)
                                                <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[11px] bg-white border border-gray-200 text-gray-700 font-medium">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-indigo-500"></span>
                                                    {{ $sub->name }} ({{ $sub->role }})
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="bg-gray-50 px-6 py-3.5 flex items-center justify-between gap-2 border-t border-gray-100">
                    <div>
                        @if(auth()->user()->hasPermission('users', 'edit'))
                            <button type="button"
                                    @click="$dispatch('confirm-dialog', {
                                        title: 'Generate Temporary Password?',
                                        message: 'Are you sure you want to generate a new temporary password for {{ addslashes($viewingUser->name) }}? Their existing password will be replaced immediately.',
                                        confirmText: 'Generate & Resolve',
                                        cancelText: 'Cancel',
                                        danger: false,
                                        wire: $wire,
                                        action: 'resetUserPassword',
                                        params: [{{ $viewingUser->id }}],
                                        args: [{{ $viewingUser->id }}]
                                    })"
                                    class="btn btn-outline-danger btn-sm">
                                Reset Password
                            </button>
                        @endif
                    </div>
                    <div class="flex items-center gap-2">
                        @if(auth()->user()->hasPermission('users', 'edit'))
                            <button type="button"
                                    wire:click="closeViewModal"
                                    @click="Livewire.dispatch('open-user-modal', { id: {{ $viewingUser->id }} })"
                                    class="btn btn-primary btn-sm">
                                Edit User
                            </button>
                        @endif
                        <button type="button"
                                wire:click="closeViewModal"
                                class="btn btn-outline-secondary btn-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Temporary Credentials Share Modal for Users -->
    <x-modal name="temp-credentials-modal" title="Temporary Password Generated" sub="Share these temporary credentials with the staff member" :wide="false">
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
