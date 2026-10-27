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
    public string $take_person_id = '';

    #[Url]
    public string $project_type = '';

    #[Url]
    public string $sales_person_id = '';

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

    public function updatingTakePersonId(): void
    {
        $this->resetPage();
    }

    public function updatingProjectType(): void
    {
        $this->resetPage();
    }

    public function updatingSalesPersonId(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'health', 'status', 'customer_id', 'coordinator_id', 'take_person_id', 'project_type', 'sales_person_id']);
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
            ->with(['customer', 'terms', 'payments', 'milestones', 'coordinator', 'salesPerson', 'takePerson'])
            ->when($this->search, function ($builder) {
                $searchTerm = '%'.trim($this->search).'%';
                $builder->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'like', $searchTerm)
                        ->orWhere('project_code', 'like', $searchTerm)
                        ->orWhere('project_type', 'like', $searchTerm)
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('company', 'like', $searchTerm)->orWhere('contact_name', 'like', $searchTerm))
                        ->orWhereHas('coordinator', fn ($coordinatorQuery) => $coordinatorQuery->where('name', 'like', $searchTerm))
                        ->orWhereHas('takePerson', fn ($tpQuery) => $tpQuery->where('name', 'like', $searchTerm))
                        ->orWhereHas('salesPerson', fn ($salesQuery) => $salesQuery->where('name', 'like', $searchTerm));
                });
            })
            ->when($this->health, fn ($builder) => $builder->where('health', $this->health))
            ->when($this->status, fn ($builder) => $builder->where('status', $this->status))
            ->when($this->customer_id, fn ($builder) => $builder->where('customer_id', $this->customer_id))
            ->when($this->coordinator_id, fn ($builder) => $builder->where('coordinator_id', $this->coordinator_id))
            ->when($this->take_person_id, fn ($builder) => $builder->where('take_person_id', $this->take_person_id))
            ->when($this->sales_person_id, fn ($builder) => $builder->where('sales_person_id', $this->sales_person_id))
            ->when($this->project_type, fn ($builder) => $builder->where('project_type', $this->project_type))
            ->orderByRaw("CASE health WHEN 'Delayed' THEN 1 WHEN 'At Risk' THEN 2 WHEN 'On Track' THEN 3 WHEN 'Completed' THEN 4 ELSE 5 END")
            ->orderBy('delivery_date');

        $allProjects = (clone $query)->get();
        $totalDue = $allProjects->sum(fn ($project) => $project->money['due']);
        $totalOverdue = $allProjects->sum(fn ($project) => $project->money['overdue']);

        $customers = Customer::query()
            ->whereHas('projects', fn ($projQuery) => $projQuery->forUser($user))
            ->orderBy('company')
            ->get();

        $coordinatorsQuery = User::query()
            ->where('status', 'Active')
            ->whereIn('id', Project::forUser($user)->whereNotNull('coordinator_id')->distinct()->pluck('coordinator_id'));
        $coordinators = ($user->role === 'Coordinator') ? collect() : $coordinatorsQuery->orderBy('name')->get();

        $takePersons = ($user->isTakePerson()) ? collect() : User::where(function ($q) use ($user) {
            $q->whereIn('role', ['TakePerson', 'Take Person'])
                ->orWhereIn('id', Project::forUser($user)->whereNotNull('take_person_id')->distinct()->pluck('take_person_id'));
        })->where('status', 'Active')->orderBy('name')->get();

        $salesPeopleQuery = User::where('role', 'Sales')->where('status', 'Active');
        if ($user->role === 'Manager') {
            $salesPeopleQuery->whereIn('id', $user->getDownlineUserIds());
        }
        $salesPeople = ($user->role === 'Sales') ? collect() : $salesPeopleQuery->orderBy('name')->get();

        $projectTypes = Project::forUser($user)
            ->whereNotNull('project_type')
            ->where('project_type', '!=', '')
            ->distinct()
            ->pluck('project_type');

        return view('livewire.projects.project-index', [
            'projects' => $query->paginate(15),
            'customers' => $customers,
            'coordinators' => $coordinators,
            'takePersons' => $takePersons,
            'salesPeople' => $salesPeople,
            'projectTypes' => $projectTypes,
            'totalDue' => $totalDue,
            'totalOverdue' => $totalOverdue,
        ])->layoutData([
            'header' => 'Projects',
            'subtitle' => 'Timeline, milestones and delivery health',
        ]);
    }
}
