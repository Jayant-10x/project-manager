<?php

namespace App\Livewire\Projects;

use App\Models\Customer;
use App\Models\Project;
use App\Models\User;
use App\Services\CodeGeneratorService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectModal extends Component {
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

    public function mount(): void {
        $this->start_date = now()->toDateString();
    }

    #[On('open-project-modal')]
    public function loadProject(?int $id = null): void {
        $this->resetValidation();
        $this->reset();
        $this->projectId = $id;

        if ($id) {
            $p = Project::findOrFail($id);
            $this->customer_id = $p->customer_id;
            $this->name = $p->name ?? '';
            $this->project_type = $p->project_type ?? 'Website';
            $this->value = $p->value !== null ? (float)$p->value : null;
            $this->cost = $p->cost !== null ? (float)$p->cost : 0.0;
            $this->coordinator_id = $p->coordinator_id;
            $this->start_date = $p->start_date instanceof Carbon ? $p->start_date->toDateString() : ($p->start_date ? substr((string)$p->start_date, 0, 10) : null);
            $this->delivery_date = $p->delivery_date instanceof Carbon ? $p->delivery_date->toDateString() : ($p->delivery_date ? substr((string)$p->delivery_date, 0, 10) : null);
            $this->status = $p->status ?? 'Onboarding';
            $this->health = $p->health ?? 'On Track';
            $this->delay_reason = $p->delay_reason ?? '';
            $this->description = $p->description ?? '';
            $this->amc_required = (bool)$p->amc_required;
            $this->amc_start = $p->amc_start instanceof Carbon ? $p->amc_start->toDateString() : ($p->amc_start ? substr((string)$p->amc_start, 0, 10) : null);
            $this->amc_end = $p->amc_end instanceof Carbon ? $p->amc_end->toDateString() : ($p->amc_end ? substr((string)$p->amc_end, 0, 10) : null);
            $this->amc_amount = $p->amc_amount !== null ? (float)$p->amc_amount : 0.0;
            $this->renew_alert_days = (int)($p->renew_alert_days ?: 7);
            $this->handover_done = (bool)$p->handover_done;
            $this->handover_notes = $p->handover_notes ?? '';
            $this->notes = $p->notes ?? '';
            $this->sales_person_id = $p->sales_person_id;
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

    protected function rules(): array {
        return [
            'customer_id' => 'required|integer|exists:customers,id',
            'name' => 'required|string|max:160',
            'project_type' => 'nullable|string|max:60',
            'value' => 'required|numeric|min:0',
            'cost' => 'required|numeric|min:0',
            'coordinator_id' => 'nullable|integer|exists:users,id',
            'start_date' => 'nullable|date',
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

    public function updated(string $propertyName): void {
        $this->validateOnly($propertyName);
    }

    public function save(): void {
        $this->customer_id = $this->customer_id ? (int)$this->customer_id : null;
        $this->coordinator_id = $this->coordinator_id ? (int)$this->coordinator_id : null;
        $this->cost = ($this->cost === null || $this->cost === '') ? 0.0 : (float)$this->cost;
        $this->start_date = !empty($this->start_date) ? $this->start_date : null;
        $this->delivery_date = !empty($this->delivery_date) ? $this->delivery_date : null;

        if (!$this->amc_required) {
            $this->amc_start = null;
            $this->amc_end = null;
            $this->amc_amount = 0.0;
        } else {
            $this->amc_start = !empty($this->amc_start) ? $this->amc_start : null;
            $this->amc_end = !empty($this->amc_end) ? $this->amc_end : null;
            $this->amc_amount = ($this->amc_amount === null || $this->amc_amount === '') ? 0.0 : (float)$this->amc_amount;
        }

        $this->renew_alert_days = (int)($this->renew_alert_days ?: 7);

        $validated = $this->validate();
        $codeService = app(CodeGeneratorService::class);

        if ($this->projectId) {
            $project = Project::findOrFail($this->projectId);
            $project->update($validated);
        } else {
            $validated['code'] = $codeService->generate('project', 'PJT-');
            $validated['created_by'] = auth()->id();
            if (auth()->check() && auth()->user()->role !== 'Admin' && empty($validated['coordinator_id'])) {
                $validated['coordinator_id'] = auth()->id();
            }
            Project::create($validated);
        }

        $this->reset();
        $this->dispatch('project-saved');
        $this->dispatch('close-modal', 'project-modal');
        $this->redirect(route('projects.index'), navigate: true);
    }

    public function render() {
        return view('livewire.projects.project-modal', [
            'customers' => Customer::orderBy('company')->get(),
            'salesUsers' => User::where('role', 'Sales')->where('status', 'Active')->orderBy('name')->get(),
            'coordinators' => User::where('status', 'Active')->orderBy('name')->get()
        ]);
    }
}
