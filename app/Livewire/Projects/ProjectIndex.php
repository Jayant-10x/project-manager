<?php

namespace App\Livewire\Projects;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditNotificationService;
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
    public string $tech_person_id = '';

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

    public function updatingTechPersonId(): void
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
        $this->reset(['search', 'health', 'status', 'customer_id', 'coordinator_id', 'tech_person_id', 'project_type', 'sales_person_id']);
        $this->resetPage();
    }

    #[On('project-saved')]
    public function refreshProjects(): void
    {
        $this->resetPage();
    }

    public function approveProject(int $id): void
    {
        $project = Project::findOrFail($id);
        $user = auth()->user();
        if (! $project->canBeApprovedBy($user)) {
            abort(403, 'Unauthorized to approve this project.');
        }

        $project->update([
            'approval_status' => 'Approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $auditService = app(AuditNotificationService::class);

        $auditService->log(
            $user->id,
            'Approved Project',
            'Project',
            $project->id,
            $project->code,
            "Approved project {$project->name}"
        );

        $creatorId = $project->created_by ?: $project->sales_person_id;
        if ($creatorId && (int) $creatorId !== (int) $user->id) {
            $auditService->notify(
                $creatorId,
                'Project Approved',
                "Project Approved: {$project->name}",
                "Your submitted project {$project->name} ({$project->code}) was approved by {$user->name}.",
                'project',
                $project->id
            );
        }

        $this->dispatch('toast', ['msg' => 'Project approved successfully.', 'type' => 'ok']);
    }

    public function rejectProject(int $id, ?string $reason = null): void
    {
        $project = Project::findOrFail($id);
        $user = auth()->user();
        if (! $project->canBeApprovedBy($user)) {
            abort(403, 'Unauthorized to reject this project.');
        }

        $cleanReason = trim((string) $reason);

        $project->update([
            'approval_status' => 'Rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => $cleanReason !== '' ? $cleanReason : null,
        ]);

        $auditService = app(AuditNotificationService::class);
        $reasonText = $cleanReason !== '' ? " (Reason: {$cleanReason})" : '';

        $auditService->log(
            $user->id,
            'Rejected Project',
            'Project',
            $project->id,
            $project->code,
            "Rejected project {$project->name}{$reasonText}"
        );

        $creatorId = $project->created_by ?: $project->sales_person_id;
        if ($creatorId && (int) $creatorId !== (int) $user->id) {
            $auditService->notify(
                $creatorId,
                'Project Rejected',
                "Project Rejected: {$project->name}",
                "Your submitted project {$project->name} ({$project->code}) was rejected by {$user->name}.".($cleanReason !== '' ? " Reason: {$cleanReason}" : ''),
                'project',
                $project->id
            );
        }

        $this->dispatch('toast', ['msg' => 'Project rejected.', 'type' => 'warning']);
    }

    public function render()
    {
        $user = auth()->user();

        $query = Project::query()
            ->forUser($user)
            ->with(['customer', 'terms', 'payments', 'milestones', 'coordinator', 'salesPerson', 'techPerson'])
            ->when($this->search, function ($builder) {
                $searchTerm = '%'.trim($this->search).'%';
                $builder->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('name', 'like', $searchTerm)
                        ->orWhere('project_code', 'like', $searchTerm)
                        ->orWhere('project_type', 'like', $searchTerm)
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('company', 'like', $searchTerm)->orWhere('contact_name', 'like', $searchTerm))
                        ->orWhereHas('coordinator', fn ($coordinatorQuery) => $coordinatorQuery->where('name', 'like', $searchTerm))
                        ->orWhereHas('techPerson', fn ($tpQuery) => $tpQuery->where('name', 'like', $searchTerm))
                        ->orWhereHas('salesPerson', fn ($salesQuery) => $salesQuery->where('name', 'like', $searchTerm));
                });
            })
            ->when($this->health, fn ($builder) => $builder->where('health', $this->health))
            ->when($this->status, fn ($builder) => $builder->where('status', $this->status))
            ->when($this->customer_id, fn ($builder) => $builder->where('customer_id', $this->customer_id))
            ->when($this->coordinator_id, fn ($builder) => $builder->where('coordinator_id', $this->coordinator_id))
            ->when($this->tech_person_id, fn ($builder) => $builder->where('tech_person_id', $this->tech_person_id))
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

        $techPersons = ($user->isTechPerson()) ? collect() : User::where(function ($q) use ($user) {
            $q->where('role', 'TechPerson')
                ->orWhereIn('id', Project::forUser($user)->whereNotNull('tech_person_id')->distinct()->pluck('tech_person_id'));
        })->where('status', 'Active')->orderBy('name')->get();

        $salesPeopleQuery = User::where('role', 'Sales')->where('status', 'Active');
        if ($user->role === 'Manager') {
            $salesPeopleQuery->whereIn('id', current_user_downline_ids());
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
            'techPersons' => $techPersons,
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
