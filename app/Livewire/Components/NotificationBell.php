<?php

namespace App\Livewire\Components;

use App\Models\Notification;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationBell extends Component
{
    public int $unread = 0;

    public function mount(): void
    {
        $this->updateUnreadCount();
    }

    #[On('notification-updated')]
    public function refreshBell(?int $unreadCount = null): void
    {
        if ($unreadCount !== null) {
            $this->unread = $unreadCount;
        } else {
            $this->updateUnreadCount();
        }
    }

    public function updateUnreadCount(): void
    {
        $this->unread = Notification::query()
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();
    }

    public function render()
    {
        return view('livewire.components.notification-bell');
    }
}
