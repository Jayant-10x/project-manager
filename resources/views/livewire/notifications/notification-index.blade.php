<div>
    <!-- Page Header with Summary & Mark All Action -->
    <div class="page-head mb-4">
        <span class="info text-sm text-[#8894ac]">
            {{ $notifications->total() }} notifications
            @if($unreadCount > 0)
                · <b style="color:#d94b4b">{{ $unreadCount }} unread</b>
            @endif
        </span>
        <span class="spacer"></span>
        @if($unreadCount > 0)
            <button type="button"
                    @click="$dispatch('confirm-dialog', {
                        title: 'Mark All as Read?',
                        message: 'Are you sure you want to mark all unread notifications as read?',
                        confirmText: 'Mark All Read',
                        cancelText: 'Cancel',
                        danger: false,
                        wire: $wire,
                        action: 'markAllAsRead'
                    })"
                    class="btn btn-ghost btn-sm">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
                Mark all read
            </button>
        @endif
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
                    <input wire:model.live.debounce.300ms="search" placeholder="Title, alert message...">
                </div>

                <select wire:model.live="status" class="i" style="width:auto">
                    <option value="">All status</option>
                    <option value="unread">Unread only</option>
                    <option value="read">Read only</option>
                </select>

                <select wire:model.live="type" class="i" style="width:auto">
                    <option value="">All types</option>
                    <option value="password_reset">Password Reset & Security</option>
                    <option value="payment">Payments & Receivables</option>
                    <option value="overdue">Overdue Alerts</option>
                    <option value="project">Projects & Delivery</option>
                    <option value="milestone">Milestones</option>
                    <option value="customer">Customers & CRM</option>
                    <option value="task">Tasks & Coordination</option>
                    <option value="followup">Follow-ups</option>
                    <option value="amc">AMC Renewals</option>
                    <option value="team">Team & Users</option>
                    <option value="general">General</option>
                </select>

                @if($search !== '' || $status !== '' || $type !== '')
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

    <!-- Notifications List Card -->
    <div class="card overflow-hidden">
        <div class="card-body p-0">
            @forelse($notifications as $notification)
                @php
                    $kindConfig = $notification->kind_config;
                    $color = $kindConfig['color'];
                    $icon = $kindConfig['icon'];
                @endphp
                <div wire:click="openNotification({{ $notification->id }})"
                     class="mini-item flex items-center gap-3.5 p-3.5 border-b last:border-b-0 border-[#eef2f9] transition cursor-pointer hover:bg-slate-50 pl-5!"
                     style="{{ $notification->is_read ? '' : 'background:#f6f9ff;box-shadow:inset 3px 0 0 #0b3d91;' }}">

                    <span class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0"
                          style="background: {{ $color }}1a; color: {{ $color }}">
                        @if($icon === 'lock')
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                <path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                        @elseif($icon === 'clock')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><circle cx="12" cy="12" r="9"/><path
                                    d="M12 7v5l3 2"/></svg>
                        @elseif($icon === 'alert')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><circle cx="12" cy="12" r="9"/><path
                                    d="M12 8v5M12 16.5v.5"/></svg>
                        @elseif($icon === 'milestone')
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                                <path d="M4 22v-7"/>
                            </svg>
                        @elseif($icon === 'refresh')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path d="M21 12a9 9 0 11-3-6.7M21 3v6h-6"/></svg>
                        @elseif($icon === 'chat')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        @elseif($icon === 'flag')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/></svg>
                        @elseif($icon === 'cash')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><rect x="2" y="6" width="20" height="12" rx="2"/><circle
                                    cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                        @elseif($icon === 'cust')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle
                                    cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/></svg>
                        @elseif($icon === 'proj')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                        @elseif($icon === 'task')
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="9 11 12 14 22 4"/>
                                <path d="M21 12v7a2 2 0 01-2 2H5a2 2 0 01-2-2V5a2 2 0 012-2h11"/>
                            </svg>
                        @elseif($icon === 'users')
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                                <path d="M16 3.13a4 4 0 010 7.75"/>
                            </svg>
                        @else
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                        @endif
                    </span>

                    <div style="flex:1;min-width:0">
                        <div class="flex items-center gap-2 flex-wrap">
                            <span class="t font-semibold text-sm text-[#0d1b34]">{{ $notification->title }}</span>
                            <span class="badge text-[10px] font-semibold py-0.5 px-2 rounded-full shrink-0"
                                  style="background: {{ $color }}18; color: {{ $color }}; border: 1px solid {{ $color }}33">
                                {{ $kindConfig['badge'] }}
                            </span>
                        </div>
                        <div class="s text-xs text-[#4a5875] mt-0.5 leading-relaxed">{{ $notification->body }}</div>
                        <div
                            class="s text-[11px] text-[#8894ac] mt-1">{{ $notification->created_at?->diffForHumans() ?? 'Just now' }}</div>
                    </div>

                    @if(!$notification->is_read)
                        <span class="badge b-navy text-xs shrink-0">New</span>
                    @endif
                </div>
            @empty
                <div class="empty">
                    <span class="flex items-center justify-center">
                        <svg viewBox="0 0 24 24">
                        <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.7 21a2 2 0 01-3.4 0"/>
                    </svg>
                    </span>
                    <p>No notifications</p>
                    <div class="sm">Payment due, overdue and follow-up alerts appear here</div>
                </div>
            @endforelse
        </div>
        <div class="p-4 border-t border-[#e2e8f3]">{{ $notifications->links() }}</div>
    </div>
</div>
