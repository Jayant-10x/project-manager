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
            <button wire:click="markAllAsRead" class="btn btn-ghost btn-sm">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
                Mark all read
            </button>
        @endif
    </div>

    <!-- Notifications List Card -->
    <div class="card overflow-hidden">
        <div class="card-body p-0">
            @forelse($notifications as $n)
                @php
                    $kindConfig = match ($n->kind) {
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
                <div wire:click="openNotification({{ $n->id }})"
                     class="mini-item flex items-center gap-3.5 p-3.5 border-b last:border-b-0 border-[#eef2f9] transition cursor-pointer hover:bg-slate-50"
                     style="{{ $n->is_read ? '' : 'background:#f6f9ff;box-shadow:inset 3px 0 0 #0b3d91;' }}">

                    <span class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
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

                    <div style="flex:1;min-width:0">
                        <div class="t font-semibold text-sm text-[#0d1b34]">{{ $n->title }}</div>
                        <div class="s text-xs text-[#4a5875] mt-0.5 leading-relaxed">{{ $n->body }}</div>
                        <div class="s text-[11px] text-[#8894ac] mt-1">{{ $n->created_at->diffForHumans() }}</div>
                    </div>

                    @if(!$n->is_read)
                        <span class="badge b-navy text-xs flex-shrink-0">New</span>
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
