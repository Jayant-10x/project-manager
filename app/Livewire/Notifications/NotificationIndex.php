<?php

namespace App\Livewire\Notifications;

use App\Models\Notification;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class NotificationIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $type = '';

    #[Url]
    public string $status = ''; // 'unread', 'read'

    #[Url]
    public string $search = '';

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['type', 'status', 'search']);
        $this->resetPage();
    }

    public function markAllAsRead(): void
    {
        Notification::where('user_id', auth()->id())
            ->where('is_read', false)
            ->update(['is_read' => true]);

        $this->dispatch('toast', ['msg' => 'All marked as read', 'type' => 'ok']);
    }

    public function openNotification(int $id)
    {
        $notif = Notification::where('user_id', auth()->id())->findOrFail($id);

        if (! $notif->is_read) {
            $notif->update(['is_read' => true]);
        }

        // If the user has permission to the related target entity, redirect directly.
        // Otherwise, open the default notification preview to avoid 403 Forbidden redirects.
        if ($notif->canAccessTarget(auth()->user())) {
            if ($notif->link_type === 'project' && $notif->link_id) {
                return $this->redirect(route('projects.show', $notif->link_id), navigate: true);
            }

            if ($notif->link_type === 'customer' && $notif->link_id) {
                return $this->redirect(route('customers.show', $notif->link_id), navigate: true);
            }

            if ($notif->link_type === 'settings') {
                $params = $notif->link_id ? ['highlight_user' => $notif->link_id] : [];

                return $this->redirect(route('settings.index', $params), navigate: true);
            }

            if ($notif->link_type === 'team') {
                return $this->redirect(route('team.index'), navigate: true);
            }

            if ($notif->link_type === 'tasks') {
                return $this->redirect(route('tasks.index'), navigate: true);
            }

            if ($notif->link_type === 'payments') {
                return $this->redirect(route('payments.index'), navigate: true);
            }

            if ($notif->link_type === 'followups') {
                return $this->redirect(route('followups.index'), navigate: true);
            }
        }

        return $this->redirect(route('notifications.show', $notif->id), navigate: true);
    }

    public function render()
    {
        $user = auth()->user();

        $notifications = Notification::where('user_id', $user->id)
            ->ofType($this->type)
            ->when($this->status === 'unread', fn ($statusQuery) => $statusQuery->where('is_read', false))
            ->when($this->status === 'read', fn ($statusQuery) => $statusQuery->where('is_read', true))
            ->when($this->search, function ($searchQuery) {
                $term = '%'.trim($this->search).'%';
                $searchQuery->where(function ($filterQuery) use ($term) {
                    $filterQuery->where('title', 'like', $term)
                        ->orWhere('body', 'like', $term)
                        ->orWhere('type', 'like', $term);
                });
            })
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
            'subtitle' => 'Payment due, overdue, milestone, security and project alerts',
        ]);
    }
}
