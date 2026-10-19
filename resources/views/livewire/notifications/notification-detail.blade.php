<div>
    <!-- Back to Notifications Navigation -->
    <div class="mb-4">
        <button type="button"
                @click="window.history.length > 1 ? window.history.back() : Livewire.navigate('{{ route('notifications.index') }}')"
                class="btn btn-ghost btn-sm inline-flex items-center gap-1.5"
                title="Go back">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back to Notifications
        </button>
    </div>

    @php
        $kindConfig = match ($notification->type) {
            'payment_due' => ['color' => '#e0a13a', 'icon' => 'clock', 'badge' => 'Payment Due'],
            'overdue'     => ['color' => '#d94b4b', 'icon' => 'alert', 'badge' => 'Overdue Alert'],
            'amc'         => ['color' => '#5aa236', 'icon' => 'refresh', 'badge' => 'AMC Renewal'],
            'followup'    => ['color' => '#0b3d91', 'icon' => 'chat', 'badge' => 'Follow-up Call'],
            'handover'    => ['color' => '#7a5af8', 'icon' => 'flag', 'badge' => 'Handover Pending'],
            'payment'     => ['color' => '#5aa236', 'icon' => 'cash', 'badge' => 'Payment Receipt'],
            'customer'    => ['color' => '#0b3d91', 'icon' => 'cust', 'badge' => 'Customer Event'],
            'project'     => ['color' => '#2e75b6', 'icon' => 'proj', 'badge' => 'Project Update'],
            default       => ['color' => '#8894ac', 'icon' => 'bell', 'badge' => 'System Notification'],
        };
        $c = $kindConfig['color'];
        $ico = $kindConfig['icon'];
    @endphp

    <div class="card <!--max-w-3xl-->">
        <div class="card-head flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="w-10 h-10 rounded-xl flex items-center justify-center flex-shrink-0"
                      style="background: {{ $c }}1a; color: {{ $c }}">
                    @if($ico === 'clock')
                        <svg viewBox="0 0 24 24" class="w-5 h-5"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    @elseif($ico === 'alert')
                        <svg viewBox="0 0 24 24" class="w-5 h-5"><circle cx="12" cy="12" r="9"/><path
                                d="M12 8v5M12 16.5v.5"/></svg>
                    @elseif($ico === 'refresh')
                        <svg viewBox="0 0 24 24" class="w-5 h-5"><path d="M21 12a9 9 0 11-3-6.7M21 3v6h-6"/></svg>
                    @elseif($ico === 'chat')
                        <svg viewBox="0 0 24 24" class="w-5 h-5"><path
                                d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg>
                    @elseif($ico === 'flag')
                        <svg viewBox="0 0 24 24" class="w-5 h-5"><path
                                d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path
                                d="M4 22v-7"/></svg>
                    @elseif($ico === 'cash')
                        <svg viewBox="0 0 24 24" class="w-5 h-5"><rect x="2" y="6" width="20" height="12" rx="2"/><circle
                                cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg>
                    @elseif($ico === 'cust')
                        <svg viewBox="0 0 24 24" class="w-5 h-5"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle
                                cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/></svg>
                    @elseif($ico === 'proj')
                        <svg viewBox="0 0 24 24" class="w-5 h-5"><path
                                d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg>
                    @else
                        <svg viewBox="0 0 24 24" class="w-5 h-5"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path
                                d="M13.7 21a2 2 0 01-3.4 0"/></svg>
                    @endif
                </span>
                <div>
                    <h3 class="text-base font-bold text-[#0d1b34]">{{ $notification->title }}</h3>
                    <div class="text-xs text-[#8894ac] mt-0.5">
                        {{ $notification->created_at?->format('d M Y, h:i A') ?? '—' }}
                        ({{ $notification->created_at?->diffForHumans() ?? 'Just now' }})
                    </div>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <span class="pill">{{ $kindConfig['badge'] }}</span>
                <span class="badge {{ $notification->is_read ? 'b-grey' : 'b-navy' }}">
                    {{ $notification->is_read ? 'Read' : 'Unread' }}
                </span>
            </div>
        </div>

        <div class="card-body p-6 space-y-6">
            <!-- Notification Message Body -->
            <div>
                <label class="text-xs font-bold text-gray-400 uppercase tracking-wider block mb-1.5">Notification
                    Message</label>
                <div class="p-4 rounded-xl bg-slate-50 border border-[#eef2f9] text-[#0d1b34] text-sm leading-relaxed">
                    {{ $notification->body }}
                </div>
            </div>

            <!-- Target Resource Mapping -->
            @if($notification->link_type && $notification->link_id)
                <div class="p-4 rounded-xl bg-[#e8eefc]/50 border border-[#c2d2f2] flex items-center justify-between">
                    <div>
                        <span
                            class="text-xs font-bold text-[#0b3d91] uppercase tracking-wider block">Linked Record</span>
                        <div class="text-sm font-semibold text-[#0d1b34] mt-0.5">
                            Target {{ ucfirst($notification->link_type) }} #{{ $notification->link_id }}
                        </div>
                    </div>
                    <button type="button" wire:click="navigateToTarget" class="btn btn-primary btn-sm">
                        View {{ ucfirst($notification->link_type) }}
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M9 18l6-6-6-6"/>
                        </svg>
                    </button>
                </div>
            @endif

            <!-- Action Controls -->
            <div class="flex items-center justify-between pt-4 border-t border-[#eef2f9]">
                <button type="button" wire:click="toggleRead" class="btn btn-ghost btn-sm">
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    Mark as {{ $notification->is_read ? 'Unread' : 'Read' }}
                </button>

                <button type="button"
                        class="btn btn-danger btn-sm"
                        @click="$dispatch('confirm-dialog', {
                            title: 'Delete Notification?',
                            message: 'Are you sure you want to permanently delete this notification?',
                            confirmText: 'Delete Notification',
                            cancelText: 'Cancel',
                            danger: true,
                            wire: $wire,
                            action: 'deleteNotification'
                        })">
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                        <path d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                    </svg>
                    Delete Notification
                </button>
            </div>
        </div>
    </div>
</div>
