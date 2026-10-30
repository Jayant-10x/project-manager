<?php

namespace App\Livewire\Projects;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectModal extends Component
{
    public ?int $projectId = null;

    public bool $isRejected = false;

    public bool $isSaving = false;

    public ?int $customer_id = null;

    public string $name = '';

    public string $project_type = '';

    public ?float $value = null;

    public ?float $cost = null;

    public ?int $coordinator_id = null;

    public ?int $tech_person_id = null;

    public ?string $start_date = null;

    public ?string $delivery_date = null;

    public string $status = 'Onboarding';

    public string $health = 'On Track';

    public string $delay_reason = '';

    public string $description = '';

    public ?int $sales_person_id = null;

    // AMC fields
    public bool $amc_required = false;

    public ?string $amc_start = null;

    public ?string $amc_end = null;

    public ?float $amc_amount = null;

    public int $renew_alert_days = 7;

    // Handover fields
    public bool $handover_done = false;

    public string $handover_notes = '';

    public string $notes = '';

    public ?int $sales_id = null;

    public function mount(): void
    {
        $this->start_date = now()->toDateString();
        $this->status = 'Onboarding';
        $this->health = 'On Track';
        if (auth()->check() && auth()->user()->role === 'Sales') {
            $this->sales_person_id = auth()->id();
            $this->coordinator_id = auth()->user()->reporting_to;
        } elseif (auth()->check() && auth()->user()->role === 'Coordinator') {
            $this->coordinator_id = auth()->id();
        } elseif (auth()->check() && auth()->user()->isTechPerson()) {
            $this->tech_person_id = auth()->id();
        }
    }

    public function openModal(int|array|null $id = null, ?int $customerId = null): void
    {
        if (is_array($id)) {
            $customerId = $id['customerId'] ?? $customerId;
            $id = isset($id['id']) ? (int) $id['id'] : null;
        }

        $this->loadProject($id, $customerId);
    }

    #[On('open-project-modal')]
    public function loadProject(?int $id = null, ?int $customerId = null): void
    {
        $this->resetValidation();
        $this->reset();
        $this->projectId = $id;

        if ($customerId) {
            $this->customer_id = $customerId;
            $this->sales_person_id = Customer::find($customerId)?->sales_person_id;
        }

        if ($id) {
            $project = Project::findOrFail($id);
            $this->isRejected = $project->isRejected();
            $this->customer_id = $project->customer_id;
            $this->name = $project->name ?? '';
            $this->project_type = $project->project_type ?? 'Website';
            $this->value = $project->value !== null ? (float) $project->value : null;
            $this->cost = $project->cost !== null ? (float) $project->cost : null;
            $this->coordinator_id = $project->coordinator_id;
            $this->tech_person_id = $project->tech_person_id ?: null;
            $this->start_date = $project->start_date instanceof Carbon ? $project->start_date->toDateString() : ($project->start_date ? substr((string) $project->start_date, 0, 10) : null);
            $this->delivery_date = $project->delivery_date instanceof Carbon ? $project->delivery_date->toDateString() : ($project->delivery_date ? substr((string) $project->delivery_date, 0, 10) : null);
            $this->status = $project->status ?? 'Onboarding';
            $this->health = $project->health ?? 'On Track';
            $this->delay_reason = $project->delay_reason ?? '';
            $this->description = $project->description ?? '';
            $this->amc_required = (bool) $project->amc_required;
            $this->amc_start = $project->amc_start instanceof Carbon ? $project->amc_start->toDateString() : ($project->amc_start ? substr((string) $project->amc_start, 0, 10) : null);
            $this->amc_end = $project->amc_end instanceof Carbon ? $project->amc_end->toDateString() : ($project->amc_end ? substr((string) $project->amc_end, 0, 10) : null);
            $this->amc_amount = $project->amc_amount !== null ? (float) $project->amc_amount : null;
            $this->renew_alert_days = (int) ($project->renew_alert_days ?: 7);
            $this->handover_done = (bool) $project->handover_done;
            $this->handover_notes = $project->handover_notes ?? '';
            $this->notes = $project->notes ?? '';
            $this->sales_person_id = $project->sales_person_id;
        } else {
            $this->start_date = now()->toDateString();
            $this->status = 'Onboarding';
            $this->health = 'On Track';
            $this->cost = null;
            $this->amc_amount = null;
            $this->renew_alert_days = 7;
            if (auth()->check() && auth()->user()->role === 'Sales') {
                $this->sales_person_id = auth()->id();
                $this->coordinator_id = auth()->user()->reporting_to;
            } elseif (auth()->check() && auth()->user()->role === 'Coordinator') {
                $this->coordinator_id = auth()->id();
            } elseif (auth()->check() && auth()->user()->isTechPerson()) {
                $this->tech_person_id = auth()->id();
            }
        }

        $this->dispatch('open-modal', 'project-modal');
    }

    protected function rules(): array
    {
        return [
            'customer_id' => 'required|integer|exists:customers,id',
            'name' => 'required|string|max:160',
            'project_type' => 'nullable|string|max:60',
            'value' => 'required|numeric|min:0',
            'cost' => 'nullable|numeric|min:0',
            'coordinator_id' => (auth()->check() && in_array(auth()->user()->role, ['Coordinator', 'Sales'])) ? 'nullable|integer' : 'required|integer|exists:users,id',
            'tech_person_id' => 'nullable|integer|exists:users,id',
            'start_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:Onboarding,In Progress,On Hold,Delivered,Cancelled',
            'health' => 'required|in:On Track,At Risk,Delayed,Completed',
            'delay_reason' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'amc_required' => 'boolean',
            'amc_start' => 'nullable|required_if:amc_required,true|date',
            'amc_end' => 'nullable|required_if:amc_required,true|date|after_or_equal:amc_start',
            'amc_amount' => 'nullable|numeric|min:0',
            'renew_alert_days' => 'required|integer|min:1|max:180',
            'handover_done' => 'boolean',
            'handover_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'sales_person_id' => (auth()->check() && auth()->user()->role === 'Sales') ? 'nullable|integer' : 'required|integer|exists:users,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'project name',
            'customer_id' => 'customer',
            'sales_person_id' => 'salesperson',
            'coordinator_id' => 'coordinator',
            'tech_person_id' => 'tech person',
        ];
    }

    public function updated(string $propertyName): void
    {
        if ($propertyName === 'health' || $propertyName === 'status') {
            if ($this->health !== 'Delayed' && $this->status !== 'Delayed') {
                $this->delay_reason = '';
            }
        }

        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        if ($this->isSaving) {
            return;
        }
        $this->isSaving = true;

        try {
            $this->customer_id = $this->customer_id ? (int) $this->customer_id : null;
            $this->coordinator_id = $this->coordinator_id ? (int) $this->coordinator_id : null;
            $this->tech_person_id = $this->tech_person_id ? (int) $this->tech_person_id : null;
            $this->cost = ($this->cost === null || $this->cost === '') ? 0.0 : (float) $this->cost;
            $this->start_date = ! empty($this->start_date) ? $this->start_date : null;
            $this->delivery_date = ! empty($this->delivery_date) ? $this->delivery_date : null;

            if (! $this->amc_required) {
                $this->amc_start = null;
                $this->amc_end = null;
                $this->amc_amount = 0.0;
            } else {
                $this->amc_start = ! empty($this->amc_start) ? $this->amc_start : null;
                $this->amc_end = ! empty($this->amc_end) ? $this->amc_end : null;
                $this->amc_amount = ($this->amc_amount === null || $this->amc_amount === '') ? 0.0 : (float) $this->amc_amount;
            }

            $this->renew_alert_days = (int) ($this->renew_alert_days ?: 7);

            if (auth()->check() && auth()->user()->role === 'Sales') {
                $this->sales_person_id = auth()->id();
                if (! $this->projectId && auth()->user()->reporting_to) {
                    $this->coordinator_id = auth()->user()->reporting_to;
                }
            }

            $validated = $this->validate();

            if ($this->health !== 'Delayed' && $this->status !== 'Delayed') {
                $reasonToArchive = $this->delay_reason;
                if (empty($reasonToArchive) && $this->projectId) {
                    $existingProject = Project::find($this->projectId);
                    $reasonToArchive = $existingProject?->delay_reason;
                }
                if (! empty($reasonToArchive)) {
                    $archiveNote = '['.now()->format('d M Y').' Delay Resolved]: '.$reasonToArchive;
                    $this->notes = ! empty($this->notes) ? $this->notes."\n".$archiveNote : $archiveNote;
                    $validated['notes'] = $this->notes;
                }
                $this->delay_reason = '';
                $validated['delay_reason'] = null;
            }

            if (auth()->check() && auth()->user()->role === 'Coordinator') {
                $validated['coordinator_id'] = $this->coordinator_id ?: auth()->id();
            }

            if (auth()->check() && auth()->user()->isTechPerson()) {
                $validated['tech_person_id'] = $this->tech_person_id ?: auth()->id();
            }

            if (auth()->check() && auth()->user()->role === 'Sales') {
                $validated['sales_person_id'] = auth()->id();
                if (! $this->projectId && auth()->user()->reporting_to) {
                    $validated['coordinator_id'] = auth()->user()->reporting_to;
                }
            }
            $codeService = app(CodeGeneratorService::class);
            $isEdit = (bool) $this->projectId;

            $auditService = audit_service();

            if ($this->projectId) {
                $project = Project::findOrFail($this->projectId);
                $wasRejected = $project->isRejected();

                $project->fill($validated);

                if ($wasRejected) {
                    $project->approval_status = 'Pending';
                    $project->rejection_reason = null;
                    $project->approved_by = null;
                    $project->approved_at = null;
                }

                $structuredChanges = $auditService->computeChanges($project);
                $project->save();

                $diffSummaries = [];
                foreach ($structuredChanges as $field => $diff) {
                    $diffSummaries[] = "{$field}: {$diff['old']} -> {$diff['new']}";
                }
                $diffText = ! empty($diffSummaries) ? ' ('.implode(', ', $diffSummaries).')' : '';

                if ($wasRejected) {
                    $auditService->log(
                        auth()->id(),
                        'Resubmitted Project',
                        'Project',
                        $project->id,
                        $project->code,
                        'Salesperson '.auth()->user()->name." updated and resubmitted project {$project->name} for approval{$diffText}",
                        $structuredChanges
                    );

                    // Notify uplines for re-approval
                    $auditService->notifyUplines(
                        $project->sales_person_id ? User::find($project->sales_person_id) : auth()->user(),
                        'Project Approval Required',
                        "Project Resubmitted for Approval: {$project->name}",
                        'Salesperson '.auth()->user()->name." updated details and resubmitted project {$project->name} ({$project->code}) for review.",
                        'project',
                        $project->id
                    );
                } else {
                    $auditService->log(
                        auth()->id(),
                        'Updated Project',
                        'Project',
                        $project->id,
                        $project->code,
                        "Updated project {$project->name}{$diffText}",
                        $structuredChanges
                    );

                    // Notify if health worsened
                    if (isset($structuredChanges['health']) && in_array($project->health, ['At Risk', 'Delayed'], true)) {
                        $auditService->notifyProjectStakeholders(
                            $project,
                            'Project Health Alert',
                            "Project {$project->name} is {$project->health}",
                            "Alert: {$project->name} marked {$project->health}. Reason: ".($project->delay_reason ?: 'No reason specified'),
                            'project',
                            $project->id
                        );
                    } elseif (isset($structuredChanges['status'])) {
                        $auditService->notifyProjectStakeholders(
                            $project,
                            'Project Status Changed',
                            "Project {$project->name}: {$project->status}",
                            "Status of project {$project->name} ({$project->code}) changed to {$project->status} by ".auth()->user()->name.'.',
                            'project',
                            $project->id
                        );
                    } elseif (! empty($structuredChanges)) {
                        $auditService->notifyProjectStakeholders(
                            $project,
                            'Project Updated',
                            "Project Updated: {$project->name}",
                            "Project {$project->name} ({$project->code}) details were updated by ".auth()->user()->name.'.',
                            'project',
                            $project->id
                        );
                    }
                }

                // Notify if coordinator reassigned
                if (isset($structuredChanges['coordinator_id']) && $project->coordinator_id && $project->coordinator_id !== auth()->id()) {
                    $auditService->notify(
                        $project->coordinator_id,
                        'Project Assignment',
                        "Assigned to {$project->name}",
                        "You have been assigned as coordinator for project {$project->name} ({$project->code})",
                        'project',
                        $project->id
                    );
                }

                // Notify if tech person reassigned
                $assignedTechId = $project->tech_person_id;
                $techChanged = isset($structuredChanges['tech_person_id']);
                if ($techChanged && $assignedTechId && $assignedTechId !== auth()->id()) {
                    $auditService->notify(
                        $assignedTechId,
                        'Project Assignment',
                        "Assigned to {$project->name}",
                        "You have been assigned as Tech Person for project {$project->name} ({$project->code})",
                        'project',
                        $project->id
                    );
                }
            } else {
                $validated['project_code'] = $codeService->generate('project', 'PJT-');
                $validated['created_by'] = auth()->id();
                $isSales = auth()->check() && auth()->user()->role === 'Sales';
                $validated['approval_status'] = $isSales ? 'Pending' : 'Approved';
                if (auth()->check() && auth()->user()->role !== 'Admin' && empty($validated['coordinator_id'])) {
                    $validated['coordinator_id'] = ($isSales && auth()->user()->reporting_to)
                        ? auth()->user()->reporting_to
                        : auth()->id();
                }
                $project = Project::create($validated);

                if ($isSales) {
                    $auditService->log(
                        auth()->id(),
                        'Submitted Project',
                        'Project',
                        $project->id,
                        $project->code,
                        'Salesperson '.auth()->user()->name." submitted project {$project->name} for approval"
                    );

                    // Notify uplines for approval
                    $auditService->notifyUplines(
                        auth()->user(),
                        'Project Approval Required',
                        "New Project Approval: {$project->name}",
                        'Salesperson '.auth()->user()->name." submitted project {$project->name} ({$project->code}). approval required.",
                        'project',
                        $project->id
                    );
                } else {
                    $auditService->log(auth()->id(), 'Created Project', 'Project', $project->id, $project->code, "Created project {$project->name} with value ₹".number_format($project->value, 2));

                    // Notify assigned coordinator
                    if ($project->coordinator_id && $project->coordinator_id !== auth()->id()) {
                        $auditService->notify(
                            $project->coordinator_id,
                            'Project Assignment',
                            "New Project: {$project->name}",
                            "You have been assigned to project {$project->name} ({$project->code})",
                            'project',
                            $project->id
                        );
                    }

                    // Notify assigned tech person
                    $assignedTechPersonId = $project->tech_person_id;
                    if ($assignedTechPersonId && $assignedTechPersonId !== auth()->id()) {
                        $auditService->notify(
                            $assignedTechPersonId,
                            'Project Assignment',
                            "New Project (Tech Person): {$project->name}",
                            "You have been assigned as Tech Person for project {$project->name} ({$project->code})",
                            'project',
                            $project->id
                        );
                    }

                    // Notify stakeholders and uplines
                    $auditService->notifyProjectStakeholders(
                        $project,
                        'Project Created',
                        "New Project: {$project->name}",
                        "Project {$project->name} ({$project->code}) with value ₹".number_format($project->value, 2).' was initiated by '.auth()->user()->name.'.',
                        'project',
                        $project->id,
                        includeUplines: true,
                        includeAdmins: true,
                        excludeUserIds: array_filter([$project->coordinator_id, $assignedTechPersonId])
                    );
                }
            }

            $wasRejectedAction = $isEdit && isset($wasRejected) && $wasRejected;
            $this->reset();
            $this->dispatch('project-saved');
            $this->dispatch('close-modal', 'project-modal');
            $this->dispatch('toast', ['msg' => $wasRejectedAction ? 'Project updated and resubmitted for review' : 'Project saved successfully', 'type' => 'ok']);

            if (! $isEdit) {
                $this->redirect(route('projects.show', $project->id), navigate: true);
            }
        } finally {
            $this->isSaving = false;
        }
    }

    public function render()
    {
        $assignedCoordinator = null;
        if ($this->coordinator_id) {
            $assignedCoordinator = User::find($this->coordinator_id);
        } elseif (auth()->check() && auth()->user()->role === 'Sales') {
            $assignedCoordinator = auth()->user()->reportingTo;
        }

        $techPersonQuery = User::where('role', 'TechPerson')->where('status', 'Active');
        if ($this->tech_person_id) {
            $techPersonQuery->orWhere('id', $this->tech_person_id);
        }
        $techPersons = $techPersonQuery->orderBy('name')->get();

        return view('livewire.projects.project-modal', [
            'customers' => Customer::query()
                ->where(function ($q) {
                    $q->where('status', 'Active');
                    if ($this->customer_id) {
                        $q->orWhere('id', $this->customer_id);
                    }
                })
                ->orderByRaw('COALESCE(NULLIF(company, ""), contact_name) ASC')
                ->get(),
            'salesUsers' => User::where('role', 'Sales')->where('status', 'Active')->orderBy('name')->get(),
            'coordinators' => User::where('role', 'Coordinator')->where('status', 'Active')->orderBy('name')->get(),
            'techPersons' => $techPersons,
            'assignedCoordinator' => $assignedCoordinator,
        ]);
    }
}
