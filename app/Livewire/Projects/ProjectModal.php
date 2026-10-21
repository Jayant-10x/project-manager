<?php

namespace App\Livewire\Projects;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\CodeGeneratorService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectModal extends Component
{
    public ?int $projectId = null;

    public ?int $customer_id = null;

    public string $name = '';

    public string $project_type = 'Website';

    public ?float $value = null;

    public ?float $cost = 0.0;

    public ?int $coordinator_id = null;

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

    public ?float $amc_amount = 0.0;

    public int $renew_alert_days = 7;

    // Handover fields
    public bool $handover_done = false;

    public string $handover_notes = '';

    public string $notes = '';

    public ?int $sales_id = null;

    public function mount(): void
    {
        $this->start_date = now()->toDateString();
    }

    #[On('open-project-modal')]
    public function loadProject(?int $id = null, ?int $customerId = null): void
    {
        $this->resetValidation();
        $this->reset();
        $this->projectId = $id;

        if ($customerId) {
            $this->customer_id = $customerId;
        }

        if ($id) {
            $project = Project::findOrFail($id);
            $this->customer_id = $project->customer_id;
            $this->name = $project->name ?? '';
            $this->project_type = $project->project_type ?? 'Website';
            $this->value = $project->value !== null ? (float) $project->value : null;
            $this->cost = $project->cost !== null ? (float) $project->cost : 0.0;
            $this->coordinator_id = $project->coordinator_id;
            $this->start_date = $project->start_date instanceof Carbon ? $project->start_date->toDateString() : ($project->start_date ? substr((string) $project->start_date, 0, 10) : null);
            $this->delivery_date = $project->delivery_date instanceof Carbon ? $project->delivery_date->toDateString() : ($project->delivery_date ? substr((string) $project->delivery_date, 0, 10) : null);
            $this->status = $project->status ?? 'Onboarding';
            $this->health = $project->health ?? 'On Track';
            $this->delay_reason = $project->delay_reason ?? '';
            $this->description = $project->description ?? '';
            $this->amc_required = (bool) $project->amc_required;
            $this->amc_start = $project->amc_start instanceof Carbon ? $project->amc_start->toDateString() : ($project->amc_start ? substr((string) $project->amc_start, 0, 10) : null);
            $this->amc_end = $project->amc_end instanceof Carbon ? $project->amc_end->toDateString() : ($project->amc_end ? substr((string) $project->amc_end, 0, 10) : null);
            $this->amc_amount = $project->amc_amount !== null ? (float) $project->amc_amount : 0.0;
            $this->renew_alert_days = (int) ($project->renew_alert_days ?: 7);
            $this->handover_done = (bool) $project->handover_done;
            $this->handover_notes = $project->handover_notes ?? '';
            $this->notes = $project->notes ?? '';
            $this->sales_person_id = $project->sales_person_id;
        } else {
            $this->start_date = now()->toDateString();
            $this->cost = 0.0;
            $this->amc_amount = 0.0;
            $this->renew_alert_days = 7;
            $this->sales_person_id = auth()->user()->role === 'Sales' ? auth()->id() : null;
            if (auth()->check() && auth()->user()->role === 'Coordinator') {
                $this->coordinator_id = auth()->id();
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
            'cost' => 'required|numeric|min:0',
            'coordinator_id' => 'required|integer|exists:users,id',
            'start_date' => 'required|date',
            'delivery_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'required|in:Onboarding,In Progress,On Hold,Delivered,Cancelled',
            'health' => 'required|in:On Track,At Risk,Delayed,Completed',
            'delay_reason' => 'nullable|string|max:200',
            'description' => 'nullable|string',
            'amc_required' => 'boolean',
            'amc_start' => 'nullable|required_if:amc_required,true|date',
            'amc_end' => 'nullable|required_if:amc_required,true|date|after_or_equal:amc_start',
            'amc_amount' => 'required|numeric|min:0',
            'renew_alert_days' => 'required|integer|min:1|max:180',
            'handover_done' => 'boolean',
            'handover_notes' => 'nullable|string',
            'notes' => 'nullable|string',
            'sales_person_id' => 'required|integer|exists:users,id',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'name' => 'project name',
            'customer_id' => 'customer',
            'sales_person_id' => 'salesperson',
            'coordinator_id' => 'coordinator',
        ];
    }

    public function updated(string $propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        $this->customer_id = $this->customer_id ? (int) $this->customer_id : null;
        $this->coordinator_id = $this->coordinator_id ? (int) $this->coordinator_id : null;
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

        $validated = $this->validate();
        $codeService = app(CodeGeneratorService::class);
        $isEdit = (bool) $this->projectId;

        $auditService = app(AuditNotificationService::class);

        if ($this->projectId) {
            $project = Project::findOrFail($this->projectId);
            $changes = [];
            if ($project->status !== $validated['status']) {
                $changes[] = "status: {$project->status} -> {$validated['status']}";
            }
            if ($project->health !== $validated['health']) {
                $changes[] = "health: {$project->health} -> {$validated['health']}";
            }
            if ((float) $project->value !== (float) $validated['value']) {
                $changes[] = 'value: ₹'.number_format($project->value).' -> ₹'.number_format($validated['value']);
            }
            $diffText = ! empty($changes) ? ' ('.implode(', ', $changes).')' : '';
            $project->update($validated);
            $auditService->log(auth()->id(), 'Updated Project', 'Project', $project->id, $project->code, "Updated project {$project->name}{$diffText}");
        } else {
            $validated['project_code'] = $codeService->generate('project', 'PJT-');
            $validated['created_by'] = auth()->id();
            if (auth()->check() && auth()->user()->role !== 'Admin' && empty($validated['coordinator_id'])) {
                $validated['coordinator_id'] = auth()->id();
            }
            $project = Project::create($validated);
            $auditService->log(auth()->id(), 'Created Project', 'Project', $project->id, $project->code, "Created project {$project->name} with value ₹".number_format($project->value, 2));
        }

        $this->reset();
        $this->dispatch('project-saved');
        $this->dispatch('close-modal', 'project-modal');
        $this->dispatch('toast', ['msg' => 'Project saved successfully', 'type' => 'ok']);

        if (! $isEdit) {
            $this->redirect(route('projects.show', $project->id), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.projects.project-modal', [
            'customers' => Customer::orderByRaw('COALESCE(NULLIF(company, ""), contact_name) ASC')->get(),
            'salesUsers' => User::where('role', 'Sales')->where('status', 'Active')->orderBy('name')->get(),
            'coordinators' => User::where('role', 'Coordinator')->where('status', 'Active')->orderBy('name')->get(),
        ]);
    }
}
