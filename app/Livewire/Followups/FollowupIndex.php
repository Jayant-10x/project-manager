<?php

namespace App\Livewire\Followups;

use App\Models\Customer;
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

    #[Url]
    public string $search = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $customer_id = '';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingType(): void
    {
        $this->resetPage();
    }

    public function updatingCustomerId(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'type', 'customer_id', 'date_from', 'date_to']);
        $this->resetPage();
    }

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
            ->when($this->type, fn ($q) => $q->where('type', $this->type))
            ->when($this->customer_id, fn ($q) => $q->where('customer_id', $this->customer_id))
            ->when($this->date_from, fn ($q) => $q->whereDate('happened_on', '>=', $this->date_from))
            ->when($this->date_to, fn ($q) => $q->whereDate('happened_on', '<=', $this->date_to))
            ->when($this->search, function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('summary', 'like', $term)
                        ->orWhere('detail', 'like', $term)
                        ->orWhere('next_note', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('company', 'like', $term))
                        ->orWhereHas('project', fn ($p) => $p->where('name', 'like', $term)->orWhere('project_code', 'like', $term));
                });
            })
            ->orderByDesc('happened_on');

        $totalCount = (clone $followupsQuery)->count();

        // Pending/Overdue touchpoints due on or before today
        $dueList = Followup::with('customer')
            ->whereNotNull('next_date')
            ->where('next_date', '<=', $today)
            ->orderBy('next_date')
            ->get();

        $customers = Customer::query()
            ->whereHas('followups')
            ->orderBy('company')
            ->get();

        return view('livewire.followups.followup-index', [
            'followups' => $followupsQuery->paginate(20),
            'customers' => $customers,
            'totalCount' => $totalCount,
            'dueList' => $dueList,
            'today' => $today,
        ])->layoutData([
            'header' => 'Follow-ups',
            'subtitle' => 'Conversation history and what to do next',
        ]);
    }
}
