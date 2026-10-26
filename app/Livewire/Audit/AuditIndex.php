<?php

namespace App\Livewire\Audit;

use App\Models\ActivityLog;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AuditIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $action = '';

    #[Url]
    public string $user_id = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    public function updatingUserId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'action', 'user_id']);
        $this->resetPage();
    }

    public function render()
    {
        $currentUser = auth()->user();

        $query = ActivityLog::with('user');

        if ($currentUser->role === 'Admin') {
            $allowedUserIds = null;
        } else {
            // Manager, Coordinator, Sales and downline are strictly limited to their own hierarchy
            $allowedUserIds = $currentUser->getDownlineUserIds();
            $query->whereIn('user_id', $allowedUserIds);
        }

        // Apply user_id filter with anti-tampering protection
        if ($this->user_id !== '') {
            $targetUserId = (int) $this->user_id;
            if ($allowedUserIds === null || in_array($targetUserId, $allowedUserIds, true)) {
                $query->where('user_id', $targetUserId);
            } else {
                $this->user_id = '';
            }
        }

        $query->when($this->action, fn ($logQuery) => $logQuery->where('action', $this->action))
            ->when($this->search, function ($searchQuery) {
                $term = '%'.trim($this->search).'%';
                $searchQuery->where(function ($subQuery) use ($term) {
                    $subQuery->where('action', 'like', $term)
                        ->orWhere('entity', 'like', $term)
                        ->orWhere('entity_code', 'like', $term)
                        ->orWhere('detail', 'like', $term)
                        ->orWhereHas('user', fn ($userQuery) => $userQuery->where('name', 'like', $term));
                });
            })
            ->orderByDesc('id');

        if ($allowedUserIds === null) {
            $users = User::query()
                ->whereIn('id', ActivityLog::whereNotNull('user_id')->distinct()->pluck('user_id'))
                ->orderBy('name')
                ->get();
        } else {
            $users = User::query()
                ->whereIn('id', $allowedUserIds)
                ->whereIn('id', ActivityLog::whereNotNull('user_id')->distinct()->pluck('user_id'))
                ->orderBy('name')
                ->get();
        }

        $actionsQuery = ActivityLog::query();
        if ($allowedUserIds !== null) {
            $actionsQuery->whereIn('user_id', $allowedUserIds);
        }
        $actions = $actionsQuery->distinct()->pluck('action');

        return view('livewire.audit.audit-index', [
            'logs' => $query->paginate(30),
            'users' => $users,
            'actions' => $actions,
        ])->layoutData([
            'header' => 'Activity Log',
            'subtitle' => 'Who changed what, and when',
        ]);
    }
}
