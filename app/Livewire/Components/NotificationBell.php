<?php

namespace App\Livewire\Components;

use App\Models\Notification;
use Livewire\Component;

class NotificationBell extends Component {
    public function render() {
        $unread = Notification::query()
            ->where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        return view('livewire.components.notification-bell', compact('unread'));
    }
}
