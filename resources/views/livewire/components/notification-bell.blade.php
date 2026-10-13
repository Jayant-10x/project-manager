<div wire:poll.60s class="relative inline-block">
    <a href="{{ route('notifications.index') }}" wire:navigate class="icon-sq" title="Notifications">
        <svg viewBox="0 0 24 24" class="w-4 h-4">
            <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.7 21a2 2 0 01-3.4 0"/>
        </svg>
        @if($unread > 0)
            <span class="nav-count warn absolute -top-1 -right-1 text-xs">{{ $unread }}</span>
        @endif
    </a>
</div>
