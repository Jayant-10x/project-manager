<?php

namespace App\Livewire\Projects;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class ProjectIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $health = '';

    #[Url]
    public string $status = '';

    #[Url]
    public string $customer_id = '';

    #[Url]
    public string $coordinator_id = '';

    #[Url]
    public string $project_type = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingHealth(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingCustomerId(): void
    {
        $this->resetPage();
    }

    public function updatingCoordinatorId(): void
    {
        $this->resetPage();
    }

    public function updatingProjectType(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'health', 'status', 'customer_id', 'coordinator_id', 'project_type']);
        $this->resetPage();
    }

    #[On('project-saved')]
    public function refreshProjects(): void
    {
        $this->resetPage();
    }

    public function render()
    {
        $user = auth()->user();

        $query = Project::query()
            ->forUser($user)
            ->with(['customer', 'terms', 'payments', 'milestones', 'coordinator'])
            ->when($this->search, function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('name', 'like', $term)
                        ->orWhere('project_code', 'like', $term)
                        ->orWhere('project_type', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('company', 'like', $term))
                        ->orWhereHas('coordinator', fn ($u) => $u->where('name', 'like', $term));
                });
            })
            ->when($this->health, fn ($q) => $q->where('health', $this->health))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->when($this->customer_id, fn ($q) => $q->where('customer_id', $this->customer_id))
            ->when($this->coordinator_id, fn ($q) => $q->where('coordinator_id', $this->coordinator_id))
            ->when($this->project_type, fn ($q) => $q->where('project_type', $this->project_type))
            ->orderByRaw("CASE health WHEN 'Delayed' THEN 1 WHEN 'At Risk' THEN 2 WHEN 'On Track' THEN 3 WHEN 'Completed' THEN 4 ELSE 5 END")
            ->orderBy('delivery_date');

        $allProjects = (clone $query)->get();
        $totalDue = $allProjects->sum(fn ($p) => $p->money['due']);
        $totalOverdue = $allProjects->sum(fn ($p) => $p->money['overdue']);

        $customers = Customer::query()
            ->whereHas('projects', fn ($q) => $q->forUser($user))
            ->orderBy('company')
            ->get();

        $coordinators = User::query()
            ->whereIn('id', Project::forUser($user)->whereNotNull('coordinator_id')->distinct()->pluck('coordinator_id'))
            ->orderBy('name')
            ->get();

        $projectTypes = Project::forUser($user)
            ->whereNotNull('project_type')
            ->where('project_type', '!=', '')
            ->distinct()
            ->pluck('project_type');

        return view('livewire.projects.project-index', [
            'projects' => $query->paginate(15),
            'customers' => $customers,
            'coordinators' => $coordinators,
            'projectTypes' => $projectTypes,
            'totalDue' => $totalDue,
            'totalOverdue' => $totalOverdue,
        ])->layoutData([
            'header' => 'Projects',
            'subtitle' => 'Timeline, milestones and delivery health',
        ]);
    }
}
