<?php

namespace App\Livewire\Components;

use App\Models\Notification;
use Livewire\Attributes\On;
use Livewire\Component;

class NotificationDrawer extends Component
{
    public string $filter = 'all';

    #[On('notification-updated')]
    public function refreshDrawer(): void
    {
        // Re-renders the drawer when notifications change
    }

    public function setFilter(string $filter): void
    {
        $this->filter = $filter;
    }

    public function markAsRead(int $id): void
    {
        Notification::where('user_id', auth()->id())
            ->where('id', $id)
            ->update(['is_read' => true]);

        $unreadCount = Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->count();

        $this->dispatch('notification-updated', unreadCount: $unreadCount);
    }

    public function markAllAsRead(): void
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->dispatch('notification-updated', unreadCount: 0);
        $this->dispatch('toast', ['msg' => 'All marked as read', 'kind' => 'ok']);
    }

    public function openNotification(int $id)
    {
        $notif = Notification::where('user_id', auth()->id())->findOrFail($id);

        if (! $notif->is_read) {
            $notif->update(['is_read' => true]);
            $unreadCount = Notification::where('user_id', auth()->id())
                ->where('is_read', false)
                ->count();
            $this->dispatch('notification-updated', unreadCount: $unreadCount);
        }

        if ($notif->link_type === 'project' && $notif->link_id) {
            return $this->redirect(route('projects.show', $notif->link_id), navigate: true);
        }

        if ($notif->link_type === 'customer' && $notif->link_id) {
            return $this->redirect(route('customers.show', $notif->link_id), navigate: true);
        }

        return $this->redirect(route('notifications.show', $notif->id), navigate: true);
    }

    public function render()
    {
        $userId = auth()->id();

        $query = Notification::where('user_id', $userId)
            ->orderByDesc('id');

        $unreadCount = Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();

        $totalCount = Notification::where('user_id', $userId)->count();

        if ($this->filter === 'unread') {
            $query->where('is_read', false);
        }

        $notifications = $query->limit(20)->get();

        return view('livewire.components.notification-drawer', [
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'totalCount' => $totalCount,
        ]);
    }
}
