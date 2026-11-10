<div class="relative inline-block">
    <button type="button"
            class="icon-sq relative"
            @click="if ('Notification' in window && Notification.permission === 'default') { Notification.requestPermission(); } $dispatch('toggle-notification-drawer')"
            title="Notifications">
        <svg viewBox="0 0 24 24" class="w-4 h-4">
            <path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/>
            <path d="M13.7 21a2 2 0 01-3.4 0"/>
        </svg>
        <span class="nav-count warn absolute -top-1 -right-1 text-xs"
              x-show="$store.notifications.unread > 0"
              x-text="$store.notifications.unread"
              style="{{ $unread > 0 ? '' : 'display: none;' }}">
            {{ $unread }}
        </span>
    </button>
</div>
