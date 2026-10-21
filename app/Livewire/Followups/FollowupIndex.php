<?php

namespace App\Livewire\Followups;

use App\Models\Customer;
use App\Models\Followup;
use App\Models\Project;
use App\Services\AuditNotificationService;
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
        $followup = Followup::findOrFail($id);

        if ($user->role !== 'Admin' && $followup->created_by !== $user->id) {
            $this->dispatch('toast', ['msg' => 'You can only remove follow-ups you logged.', 'type' => 'err']);

            return;
        }

        $summary = $followup->summary;
        $followup->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Follow-up',
            'Followup',
            $id,
            '',
            "Deleted follow-up '{$summary}'"
        );

        $this->dispatch('toast', ['msg' => 'Follow-up removed', 'type' => 'ok']);
    }

    public function render()
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        $projectIds = Project::forUser($user)->pluck('id');
        $customerIds = Customer::forUser($user)->pluck('id');

        $followupsQuery = Followup::with(['customer', 'project', 'createdBy'])
            ->where(function ($query) use ($projectIds, $customerIds) {
                $query->whereIn('project_id', $projectIds)
                    ->orWhere(function ($subQuery) use ($customerIds) {
                        $subQuery->whereNull('project_id')
                            ->whereIn('customer_id', $customerIds);
                    });
            })
            ->when($this->type, fn ($query) => $query->where('type', $this->type))
            ->when($this->customer_id, fn ($query) => $query->where('customer_id', $this->customer_id))
            ->when($this->date_from, fn ($query) => $query->whereDate('happened_on', '>=', $this->date_from))
            ->when($this->date_to, fn ($query) => $query->whereDate('happened_on', '<=', $this->date_to))
            ->when($this->search, function ($searchQuery) {
                $term = '%'.trim($this->search).'%';
                $searchQuery->where(function ($subQuery) use ($term) {
                    $subQuery->where('summary', 'like', $term)
                        ->orWhere('detail', 'like', $term)
                        ->orWhere('next_note', 'like', $term)
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('company', 'like', $term)->orWhere('contact_name', 'like', $term))
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', $term)->orWhere('project_code', 'like', $term));
                });
            })
            ->orderByDesc('happened_on');

        $totalCount = (clone $followupsQuery)->count();

        // Pending/Overdue touchpoints due on or before today
        $dueList = Followup::with('customer')
            ->where(function ($query) use ($projectIds, $customerIds) {
                $query->whereIn('project_id', $projectIds)
                    ->orWhere(function ($subQuery) use ($customerIds) {
                        $subQuery->whereNull('project_id')
                            ->whereIn('customer_id', $customerIds);
                    });
            })
            ->whereNotNull('next_date')
            ->where('next_date', '<=', $today)
            ->orderBy('next_date')
            ->get();

        $customers = Customer::query()
            ->forUser($user)
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
