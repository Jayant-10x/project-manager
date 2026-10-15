<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class NotificationIndex extends Component {
    use WithPagination;

    public function markAllAsRead(): void {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->dispatch('toast', ['msg' => 'All marked as read', 'type' => 'ok']);
    }

    public function openNotification(int $id) {
        $notif = Notification::where('user_id', auth()->id())->findOrFail($id);

        if (!$notif->is_read) {
            $notif->update(['is_read' => true]);
        }

        if ($notif->link_type === 'project' && $notif->link_id) {
            return $this->redirect(route('projects.show', $notif->link_id), navigate: true);
        }

        if ($notif->link_type === 'customer' && $notif->link_id) {
            return $this->redirect(route('customers.show', $notif->link_id), navigate: true);
        }

        return $this->redirect(route('notifications.show', $notif->id), navigate: true);
    }

    public function render() {
        $user = auth()->user();

        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('id')
            ->paginate(30);

        $unreadCount = Notification::where('user_id', $user->id)
            ->where('is_read', false)
            ->count();

        return view('livewire.notifications.notification-index', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
        ])->layoutData([
            'header' => 'Notifications',
            'subtitle' => 'Payment due, overdue, AMC and follow-up alerts',
        ]);
    }
}
