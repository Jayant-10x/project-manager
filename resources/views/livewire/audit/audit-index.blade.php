<div x-data="{ activeLog: null, showDrawer: false }">
    <!-- Page Header Count Summary -->
    <div class="page-head mb-4">
        <span class="info text-sm text-[#8894ac]">
            {{ $logs->total() }} {{ \Illuminate\Support\Str::plural('entry', $logs->total()) }}
        </span>
    </div>

    <!-- Filterbar -->
    @if($actions->count() > 1 || $users->count() > 1 || $logs->total() > 0 || $search !== '')
    <div class="card mb-4">
        <div class="card-body py-2.5">
            <div class="filterbar">
                <div class="gsearch" style="width:240px">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" placeholder="Search action, item, details...">
                </div>

                @if($actions->count() > 1)
                    <select wire:model.live="action" class="i" style="width:auto">
                        <option value="">All actions</option>
                        @foreach($actions as $actionType)
                            <option value="{{ $actionType }}">{{ $actionType }}</option>
                        @endforeach
                    </select>
                @endif

                @if($users->count() > 1)
                    <select wire:model.live="user_id" class="i" style="width:auto; max-width: 180px;">
                        <option value="">All users</option>
                        @foreach($users as $auditUser)
                            <option value="{{ $auditUser->id }}">{{ $auditUser->name }}</option>
                        @endforeach
                    </select>
                @endif

                @if($search !== '' || $action !== '' || $user_id !== '')
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
    @endif

    <!-- Activity Log Table -->
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/80 border-b border-[#eef2f9] text-[#8894ac] uppercase text-[11px] font-bold tracking-wider">
                        <th class="py-2.5 px-3 whitespace-nowrap w-36">Time</th>
                        <th class="py-2.5 px-3 whitespace-nowrap w-44">User</th>
                        <th class="py-2.5 px-3 whitespace-nowrap w-36">Action</th>
                        <th class="py-2.5 px-3 whitespace-nowrap w-40">Affected Item</th>
                        <th class="py-2.5 px-3 min-w-50">Details</th>
                        <th class="py-2.5 px-3 whitespace-nowrap w-32" style="text-align:center">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-[#eef2f9] text-[#0d1b34]">
                    @forelse($logs as $activityLog)
                        @php
                            $actionName = strtolower($activityLog->action ?? '');
                            $badgeStyle = match (true) {
                                str_contains($actionName, 'payment') => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                str_contains($actionName, 'create') || str_contains($actionName, 'added') => 'bg-blue-50 text-[#0b3d91] border-blue-200',
                                str_contains($actionName, 'delete') || str_contains($actionName, 'cancel') => 'bg-rose-50 text-rose-700 border-rose-200',
                                str_contains($actionName, 'password') || str_contains($actionName, 'security') || str_contains($actionName, 'auth') => 'bg-purple-50 text-purple-700 border-purple-200',
                                str_contains($actionName, 'delayed') || str_contains($actionName, 'risk') || str_contains($actionName, 'chase') => 'bg-amber-50 text-amber-700 border-amber-200',
                                default => 'bg-slate-50 text-slate-700 border-slate-200',
                            };
                            $actor = $activityLog->user?->name ?? ($activityLog->who ?? 'System');
                            $hasDiffs = ! empty($activityLog->changes) && is_array($activityLog->changes);
                            $logTime = $activityLog->at_time ? \Carbon\Carbon::parse($activityLog->at_time) : $activityLog->created_at;
                            $entityUrl = ($activityLog->entity === 'Project' && $activityLog->entity_id) ? route('projects.show', $activityLog->entity_id) : (($activityLog->entity === 'Customer' && $activityLog->entity_id) ? route('customers.show', $activityLog->entity_id) : null);
                        @endphp
                        <tr class="hover:bg-slate-50/70 transition">
                            <!-- Time -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <div class="font-semibold text-gray-800">{{ $logTime?->format('d M Y') ?? '—' }}</div>
                                <div class="text-[11px] text-[#8894ac]">{{ $logTime?->format('H:i') ?? '—' }} · {{ $logTime?->diffForHumans(null, true) }}</div>
                            </td>

                            <!-- User -->
                            <td class="py-2.5 px-3">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-slate-100 text-[#0b3d91] font-bold text-[10px] flex items-center justify-center shrink-0 border border-slate-200">
                                        {{ strtoupper(substr($actor, 0, 2)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-semibold truncate max-w-32" title="{{ $actor }}">{{ $actor }}</div>
                                        @if($activityLog->user?->role)
                                            <div class="text-[10px] text-gray-400">{{ $activityLog->user->role }}</div>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            <!-- Action -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold border {{ $badgeStyle }}">
                                    {{ $activityLog->action }}
                                </span>
                            </td>

                            <!-- Affected Item -->
                            <td class="py-2.5 px-3 whitespace-nowrap">
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    @if($activityLog->entity)
                                        <span class="font-medium text-gray-700">{{ $activityLog->entity }}</span>
                                    @endif
                                    @if($activityLog->entity_code)
                                        @if($entityUrl)
                                            <a href="{{ $entityUrl }}" wire:navigate
                                               class="pill hover:bg-blue-100 hover:text-blue-800 transition font-semibold text-[11px]"
                                               title="View Item">
                                                {{ $activityLog->entity_code }} ↗
                                            </a>
                                        @else
                                            <span class="pill text-[11px]">{{ $activityLog->entity_code }}</span>
                                        @endif
                                    @endif
                                </div>
                            </td>

                            <!-- Details -->
                            <td class="py-2.5 px-3">
                                <div class="text-xs text-gray-600 line-clamp-1 max-w-md" title="{{ $activityLog->detail }}">
                                    {{ $activityLog->detail ?: '—' }}
                                </div>
                            </td>

                            <!-- Additional Info Button -->
                            <td class="py-2.5 px-3 text-center whitespace-nowrap">
                                <button type="button"
                                        @click="activeLog = {{ json_encode([
                                            'id' => $activityLog->id,
                                            'time' => $logTime?->format('d M Y, H:i'),
                                            'timeAgo' => $logTime?->diffForHumans(),
                                            'actor' => $actor,
                                            'role' => $activityLog->user?->role ?? 'System',
                                            'action' => $activityLog->action,
                                            'entity' => $activityLog->entity,
                                            'entity_code' => $activityLog->entity_code,
                                            'entity_url' => $entityUrl,
                                            'detail' => $activityLog->detail,
                                            'changes' => $activityLog->changes,
                                        ]) }}; showDrawer = true;"
                                        class="btn btn-outline-primary"
                                        title="View audit details and field transitions">
                                    <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                                    </svg>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-12 text-center text-slate-400">
                                <div class="flex items-center justify-center mb-2 text-slate-300">
                                    <svg viewBox="0 0 24 24" class="w-8 h-8 stroke-current fill-none">
                                        <circle cx="12" cy="12" r="9"/>
                                        <path d="M12 7v5l3 2"/>
                                    </svg>
                                </div>
                                <p class="font-semibold text-slate-700 text-sm">No activity logs recorded</p>
                                <div class="text-xs text-slate-400 mt-1">Actions performed in the system will automatically appear here.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-3 border-t border-[#e2e8f3]">{{ $logs->links() }}</div>
    </div>

    <!-- Additional Info Slide-Over Side Drawer -->
    <div x-cloak x-show="showDrawer"
         x-on:keydown.escape.window="showDrawer = false"
         class="relative z-[140]">
        <!-- Backdrop Overlay -->
        <div x-show="showDrawer"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100"
             x-transition:leave="transition ease-in duration-150"
             x-transition:leave-start="opacity-100"
             x-transition:leave-end="opacity-0"
             @click="showDrawer = false"
             class="fixed inset-0 bg-[#08142a]/60 backdrop-blur-xs cursor-pointer">
        </div>

        <!-- Slide-Over Panel -->
        <div x-show="showDrawer"
             x-transition:enter="transition ease-out duration-250 transform"
             x-transition:enter-start="translate-x-full"
             x-transition:enter-end="translate-x-0"
             x-transition:leave="transition ease-in duration-200 transform"
             x-transition:leave-start="translate-x-0"
             x-transition:leave-end="translate-x-full"
             class="fixed top-0 right-0 bottom-0 w-full sm:w-130 bg-white shadow-2xl flex flex-col z-[141] border-l border-[#e2e8f3] overflow-hidden">

            <!-- Drawer Header -->
            <div class="px-6 py-4.5 bg-gradient-to-r from-[#0b3d91] to-[#12325f] text-white flex items-center justify-between shrink-0 shadow-xs">
                <div class="flex items-center gap-3 min-w-0">
                    <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center border border-white/15 shrink-0">
                        <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/>
                        </svg>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-base font-bold text-white tracking-tight leading-tight truncate" x-text="activeLog?.action ?? 'Activity Details'"></h3>
                        <p class="text-xs text-blue-200/90 font-medium">Full Event Narrative &amp; Field Transitions</p>
                    </div>
                </div>

                <button type="button"
                        @click="showDrawer = false"
                        class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer"
                        title="Close Drawer">
                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                    </svg>
                </button>
            </div>

            <!-- Drawer Body -->
            <div class="flex-1 overflow-y-auto px-6 py-5 space-y-5" x-if="activeLog">
                <!-- Meta Info Strip -->
                <div class="p-4 bg-slate-50/80 rounded-2xl border border-slate-200/80 space-y-3">
                    <div class="flex items-center justify-between gap-2 border-b border-slate-200/60 pb-3">
                        <div class="flex items-center gap-2.5">
                            <div class="w-8 h-8 rounded-full bg-blue-100 text-[#0b3d91] font-bold text-xs flex items-center justify-center border border-blue-200"
                                 x-text="(activeLog?.actor || 'US').substring(0, 2).toUpperCase()">
                            </div>
                            <div>
                                <div class="font-bold text-sm text-[#0d1b34]" x-text="activeLog?.actor"></div>
                                <div class="text-[11px] text-gray-500 font-medium" x-text="activeLog?.role"></div>
                            </div>
                        </div>
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-[#0b3d91] border border-blue-200"
                              x-text="activeLog?.action">
                        </span>
                    </div>

                    <div class="grid grid-cols-2 gap-3 text-xs pt-1">
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wide block">Event Time</span>
                            <span class="font-semibold text-gray-800" x-text="activeLog?.time"></span>
                            <span class="text-[11px] text-gray-500 block" x-text="activeLog?.timeAgo"></span>
                        </div>
                        <div>
                            <span class="text-[11px] font-bold text-gray-400 uppercase tracking-wide block">Affected Item</span>
                            <template x-if="activeLog?.entity_url">
                                <a :href="activeLog.entity_url"
                                   class="font-semibold text-blue-600 hover:underline inline-flex items-center gap-1 mt-0.5">
                                    <span x-text="activeLog.entity"></span>
                                    <span class="pill text-[10px]" x-text="activeLog.entity_code"></span>
                                    <span>↗</span>
                                </a>
                            </template>
                            <template x-if="!activeLog?.entity_url">
                                <span class="font-semibold text-gray-800" x-text="(activeLog?.entity || '—') + (activeLog?.entity_code ? ' (' + activeLog.entity_code + ')' : '')"></span>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Event Details Narrative -->
                <div class="space-y-1.5">
                    <span class="text-xs font-bold text-[#0d1b34] uppercase tracking-wide flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/>
                        </svg>
                        <span>Event Narrative</span>
                    </span>
                    <div class="p-3.5 bg-gray-50/70 rounded-xl border border-gray-100 text-xs text-gray-700 leading-relaxed break-words"
                         x-text="activeLog?.detail || 'No additional text narrative recorded.'">
                    </div>
                </div>

                <!-- Field Transitions & Detailed Changes -->
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-bold text-[#0d1b34] uppercase tracking-wide flex items-center gap-1.5">
                            <svg class="w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/>
                            </svg>
                            <span>Field Level Transitions</span>
                        </span>
                        <template x-if="activeLog?.changes && Object.keys(activeLog.changes).length > 0">
                            <span class="text-[11px] font-semibold text-blue-600 bg-blue-50 px-2 py-0.5 rounded-full border border-blue-200"
                                  x-text="Object.keys(activeLog.changes).length + ' field ' + (Object.keys(activeLog.changes).length === 1 ? 'change' : 'changes')">
                            </span>
                        </template>
                    </div>

                    <template x-if="activeLog?.changes && Object.keys(activeLog.changes).length > 0">
                        <div class="bg-white rounded-xl border border-slate-200 overflow-hidden shadow-2xs">
                            <table class="w-full text-xs text-left">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-600 font-semibold text-[11px]">
                                        <th class="py-2 px-3 w-1/3">Field</th>
                                        <th class="py-2 px-3 w-1/3">Previous (Old)</th>
                                        <th class="py-2 px-1 text-center w-6"></th>
                                        <th class="py-2 px-3 w-1/3">Updated (New)</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="(diff, field) in activeLog.changes" :key="field">
                                        <tr class="hover:bg-slate-50/50">
                                            <td class="py-2 px-3 font-semibold text-[#0d1b34]" x-text="field.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())"></td>
                                            <td class="py-2 px-3 text-slate-600">
                                                <span class="inline-block px-1.5 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-100 font-mono text-[11px]"
                                                      x-text="diff.old ?? '—'">
                                                </span>
                                            </td>
                                            <td class="py-2 px-1 text-center text-slate-400 font-bold">➔</td>
                                            <td class="py-2 px-3 text-slate-800">
                                                <span class="inline-block px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-800 border border-emerald-100 font-mono text-[11px]"
                                                      x-text="diff.new ?? '—'">
                                                </span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>

                    <template x-if="!activeLog?.changes || Object.keys(activeLog.changes).length === 0">
                        <div class="p-4 bg-slate-50 rounded-xl border border-slate-200 text-center text-xs text-gray-500">
                            No field-level diffs recorded for this event.
                        </div>
                    </template>
                </div>
            </div>

            <!-- Drawer Footer -->
            <div class="px-6 py-3.5 bg-gray-50 border-t border-gray-100 flex items-center justify-end shrink-0">
                <button type="button"
                        @click="showDrawer = false"
                        class="btn btn-outline-secondary btn-sm">
                    Close Details
                </button>
            </div>
        </div>
    </div>
</div>
