<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class NotificationDetail extends Component
{
    public Notification $notification;

    public function mount(Notification $notification): void
    {
        // Scope access to recipient
        if ($notification->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to notification.');
        }

        // Auto-mark as read on open
        if (! $notification->is_read) {
            $notification->update(['is_read' => true]);
            $this->dispatch('notification-updated');
        }

        $this->notification = $notification;
    }

    public function toggleRead(): void
    {
        $this->notification->update([
            'is_read' => ! $this->notification->is_read,
        ]);
        $this->dispatch('notification-updated');
        $this->dispatch('toast', [
            'msg' => $this->notification->is_read ? 'Marked as read' : 'Marked as unread',
            'type' => 'ok',
        ]);
    }

    public function deleteNotification()
    {
        $this->notification->delete();
        $this->dispatch('notification-updated');
        $this->dispatch('toast', ['msg' => 'Notification deleted', 'type' => 'ok']);

        return $this->redirect(route('notifications.index'), navigate: true);
    }

    public function navigateToTarget()
    {
        if (! $this->notification->canAccessTarget(auth()->user())) {
            $this->dispatch('toast', ['msg' => 'You do not have permission to view the linked record.', 'type' => 'err']);

            return;
        }

        if ($this->notification->link_type === 'project' && $this->notification->link_id) {
            return $this->redirect(route('projects.show', $this->notification->link_id), navigate: true);
        }

        if ($this->notification->link_type === 'customer' && $this->notification->link_id) {
            return $this->redirect(route('customers.show', $this->notification->link_id), navigate: true);
        }

        if ($this->notification->link_type === 'settings') {
            $params = $this->notification->link_id ? ['highlight_user' => $this->notification->link_id] : [];

            return $this->redirect(route('settings.index', $params), navigate: true);
        }

        return $this->redirect(route('notifications.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.notifications.notification-detail')->layoutData([
            'header' => 'Notification Detail',
            'subtitle' => 'Alert information and associated record actions',
        ]);
    }
}
