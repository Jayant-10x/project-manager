<div x-data="{ open: false }"
     x-on:toggle-notification-drawer.window="open = !open"
     x-on:open-notification-drawer.window="open = true"
     x-on:close-notification-drawer.window="open = false"
     x-on:keydown.escape.window="open = false"
     class="relative z-50">

    <!-- Backdrop Overlay -->
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="open = false"
         class="fixed inset-0 bg-[#08142a]/50 backdrop-blur-sm">
    </div>

    <!-- Right Drawer Flyout Panel -->
    <div x-show="open"
         x-cloak
         x-transition:enter="transition ease-out duration-250 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 bottom-0 w-full sm:w-105 bg-white shadow-2xl flex flex-col z-50 border-l border-[#e2e8f3]">

        <!-- Drawer Header -->
        <div class="px-5 py-4 border-b border-[#eef2f9] flex items-center justify-between bg-white">
            <div class="flex items-center gap-2.5">
                <h3 class="text-base font-bold text-[#0d1b34]">Notifications</h3>
                @if($unreadCount > 0)
                    <span class="nav-count warn text-xs">{{ $unreadCount }}</span>
                @endif
            </div>

            <div class="flex items-center gap-2">
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
                            class="text-xs font-semibold text-[#0b3d91] hover:underline px-2 py-1 rounded">
                        Mark all as read
                    </button>
                @endif
                @if($totalCount > 0)
                    <button type="button"
                            @click="$dispatch('confirm-dialog', {
                                title: 'Clear All Notifications?',
                                message: 'Are you sure you want to permanently clear all notifications from your list?',
                                confirmText: 'Clear All',
                                cancelText: 'Cancel',
                                danger: true,
                                wire: $wire,
                                action: 'clearAll'
                            })"
                            class="text-xs font-semibold text-[#8894ac] hover:text-[#d94b4b] px-2 py-1 rounded"
                            title="Clear all notifications">
                        Clear all
                    </button>
                @endif
                <button type="button"
                        @click="open = false"
                        class="icon-sq w-8 h-8 rounded-lg text-[#8894ac] hover:text-[#0d1b34]"
                        title="Close drawer">
                    <svg viewBox="0 0 24 24" class="w-4 h-4">
                        <path d="M18 6L6 18M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Filter Tabs (All vs Unread) -->
        <div class="px-5 pt-3 pb-2 border-b border-[#eef2f9] bg-[#fbfcfe] flex items-center justify-between">
            <div class="tabs border-none p-0">
                <button type="button"
                        wire:click="setFilter('all')"
                        class="tab text-xs py-2 px-3 {{ $filter === 'all' ? 'active' : '' }}">
                    All <span class="n">{{ $totalCount }}</span>
                </button>
                <button type="button"
                        wire:click="setFilter('unread')"
                        class="tab text-xs py-2 px-3 {{ $filter === 'unread' ? 'active' : '' }}">
                    Unread <span class="n">{{ $unreadCount }}</span>
                </button>
            </div>

            <span class="text-[11px] text-[#8894ac]">Recent 20</span>
        </div>

        <!-- Drawer Notification Feed -->
        <div class="flex-1 overflow-y-auto divide-y divide-[#eef2f9]">
            @forelse($notifications as $n)
                @php
                    $kindConfig = match ($n->type) {
                        'payment_due' => ['color' => '#e0a13a', 'icon' => 'clock'],
                        'overdue'     => ['color' => '#d94b4b', 'icon' => 'alert'],
                        'amc'         => ['color' => '#5aa236', 'icon' => 'refresh'],
                        'followup'    => ['color' => '#0b3d91', 'icon' => 'chat'],
                        'handover'    => ['color' => '#7a5af8', 'icon' => 'flag'],
                        'payment'     => ['color' => '#5aa236', 'icon' => 'cash'],
                        'customer'    => ['color' => '#0b3d91', 'icon' => 'cust'],
                        'project'     => ['color' => '#2e75b6', 'icon' => 'proj'],
                        default       => ['color' => '#8894ac', 'icon' => 'bell'],
                    };
                    $c = $kindConfig['color'];
                    $ico = $kindConfig['icon'];
                @endphp
                <div wire:key="drawer-notif-{{ $n->id }}"
                     class="p-4 transition flex items-start gap-3 hover:bg-[#fafcff] cursor-pointer group"
                     style="{{ $n->is_read ? 'background:#fff;' : 'background:#f6f9ff; box-shadow:inset 3px 0 0 #0b3d91;' }}"
                     wire:click="openNotification({{ $n->id }})">

                    <!-- Kind Icon Plate -->
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5"
                          style="background: {{ $c }}1a; color: {{ $c }}">
                        @if($ico === 'clock')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><circle cx="12" cy="12" r="9"/><path
                                    d="M12 7v5l3 2"/></svg>
                        @elseif($ico === 'alert')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><circle cx="12" cy="12" r="9"/><path
                                    d="M12 8v5M12 16.5v.5"/></svg>
                        @elseif($ico === 'refresh')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path d="M21 12a9 9 0 11-3-6.7M21 3v6h-6"/></svg>
                        @elseif($ico === 'chat')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                        @elseif($ico === 'flag')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/></svg>
                        @elseif($ico === 'cash')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><rect x="2" y="6" width="20" height="12" rx="2"/><circle
                                    cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                        @elseif($ico === 'cust')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle
                                    cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/></svg>
                        @elseif($ico === 'proj')
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                        @else
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><path
                                    d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                        @endif
                    </span>

                    <!-- Content -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center justify-between gap-2">
                            <div class="text-xs font-bold text-[#0d1b34] truncate">{{ $n->title }}</div>
                            <span
                                class="text-[10.5px] text-[#8894ac] whitespace-nowrap">{{ $n->created_at?->diffForHumans(short: true) ?? 'Just now' }}</span>
                        </div>
                        <div class="text-xs text-[#4a5875] mt-0.5 line-clamp-2 leading-relaxed">
                            {{ $n->body }}
                        </div>
                    </div>

                    <!-- Actions (Mark Read & Delete) -->
                    <div class="flex items-center gap-1 opacity-0 group-hover:opacity-100 transition flex-shrink-0 mt-0.5">
                        @if(!$n->is_read)
                            <button type="button"
                                    wire:click.stop="markAsRead({{ $n->id }})"
                                    class="w-6 h-6 rounded-md flex items-center justify-center text-[#8894ac] hover:text-[#0b3d91] hover:bg-[#e8eefc]"
                                    title="Mark as read">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                    <path d="M20 6L9 17l-5-5"/>
                                </svg>
                            </button>
                        @endif
                        <button type="button"
                                wire:click.stop="deleteNotification({{ $n->id }})"
                                class="w-6 h-6 rounded-md flex items-center justify-center text-[#8894ac] hover:text-[#d94b4b] hover:bg-[#fdeaea]"
                                title="Delete notification">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M3 6h18M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2M10 11v6M14 11v6"/>
                            </svg>
                        </button>
                    </div>
                </div>
            @empty
                <div class="p-10 text-center text-[#8894ac]">
                    <svg viewBox="0 0 24 24" class="w-9 h-9 mx-auto mb-2 opacity-40">
                        <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
                        <path d="M13.7 21a2 2 0 01-3.4 0"/>
                    </svg>
                    <p class="text-xs font-semibold">No {{ $filter === 'unread' ? 'unread' : '' }} notifications</p>
                    <p class="text-[11px] mt-0.5 opacity-80">You're all caught up!</p>
                </div>
            @endforelse
        </div>

        <!-- Drawer Footer -->
        <div class="p-3.5 border-t border-[#eef2f9] bg-[#fbfcfe]">
            <a href="{{ Route::has('notifications.index') ? route('notifications.index') : '#' }}"
               wire:navigate
               @click="open = false"
               class="btn btn-outline-primary btn-sm w-full justify-center text-xs font-semibold py-2">
                View all notifications
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 ml-1">
                    <path d="M9 18l6-6-6-6"/>
                </svg>
            </a>
        </div>
    </div>
</div>
