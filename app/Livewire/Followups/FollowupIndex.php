<?php

namespace App\Livewire\Followups;

use App\Models\Followup;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class FollowupIndex extends Component
{
    use WithPagination;

    #[On('followup-saved')]
    public function refreshList(): void
    {
        $this->resetPage();
    }

    public function deleteFollowup(int $id): void
    {
        $user = auth()->user();
        $fu = Followup::findOrFail($id);

        if ($user->role !== 'Admin' && $fu->created_by !== $user->id) {
            $this->dispatch('toast', ['msg' => 'You can only remove follow-ups you logged.', 'type' => 'err']);

            return;
        }

        $fu->delete();
        $this->dispatch('toast', ['msg' => 'Follow-up removed', 'type' => 'ok']);
    }

    public function render()
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $followupsQuery = Followup::with(['customer', 'project', 'createdBy'])
            ->orderByDesc('happened_on');

        $totalCount = (clone $followupsQuery)->count();

        // Pending/Overdue touchpoints due on or before today
        $dueList = Followup::with('customer')
            ->whereNotNull('next_date')
            ->where('next_date', '<=', $today)
            ->orderBy('next_date')
            ->get();

        return view('livewire.followups.followup-index', [
            'followups' => $followupsQuery->paginate(20),
            'totalCount' => $totalCount,
            'dueList' => $dueList,
            'today' => $today,
        ])->layoutData([
            'header' => 'Follow-ups',
            'subtitle' => 'Conversation history and what to do next',
        ]);
    }
}
