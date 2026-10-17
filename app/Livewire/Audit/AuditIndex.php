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
        $query = ActivityLog::with('user')
            ->when($this->action, fn ($q) => $q->where('action', $this->action))
            ->when($this->user_id, fn ($q) => $q->where('user_id', $this->user_id))
            ->when($this->search, function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('action', 'like', $term)
                        ->orWhere('entity', 'like', $term)
                        ->orWhere('entity_code', 'like', $term)
                        ->orWhere('detail', 'like', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $term));
                });
            })
            ->orderByDesc('id');

        $users = User::query()
            ->whereIn('id', ActivityLog::whereNotNull('user_id')->distinct()->pluck('user_id'))
            ->orderBy('name')
            ->get();

        $actions = ActivityLog::distinct()->pluck('action');

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
