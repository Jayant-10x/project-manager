<?php

namespace App\Livewire\Projects;

use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectRecurringModal extends Component
{
    public ?int $projectId = null;

    public string $projectCode = '';

    public ?float $amount = null;

    public ?int $months = 6;

    public string $startDate = '';

    public string $label = 'Monthly retainer';

    #[On('open-recurring-modal')]
    public function load(int $projectId): void
    {
        $this->resetValidation();
        $this->projectId = $projectId;
        $project = Project::findOrFail($projectId);
        $payments = Payment::where('project_id', $projectId);
        $payments_received = $payments->sum('amount') + $payments->sum('tds');
        $this->projectCode = $project->code;
        $this->amount = $project->value > 0 ? round((float) ($project->value - $payments_received) / 6, 2) : null;
        //        $this->months = 6;
        $this->label = 'Monthly retainer';
        $this->startDate = $project->start_date?->toDateString() ?? now()->toDateString();

        $this->dispatch('open-modal', 'project-recurring-modal');
    }

    protected function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:0.01',
            'months' => 'required|integer|min:1|max:60',
            'startDate' => 'required|date',
            'label' => 'required|string|max:80',
        ];
    }

    public function updated(string $propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function generate(): void
    {
        $this->amount = ($this->amount === null || $this->amount === '') ? null : (float) $this->amount;
        $this->months = ($this->months === null || $this->months === '') ? null : (int) $this->months;
        $this->label = trim($this->label);

        $this->validate();

        $project = Project::findOrFail($this->projectId);
        $maxSeq = (int) $project->terms()->max('seq');

        DB::transaction(function () use ($project, $maxSeq) {
            for ($i = 0; $i < $this->months; $i++) {
                $maxSeq++;
                $base = Carbon::parse($this->startDate)->addMonthsNoOverflow($i);
                $monthLabel = $this->label.' — '.$base->format('M Y');

                PaymentTerm::create([
                    'project_id' => $this->projectId,
                    'seq' => $maxSeq,
                    'label' => $monthLabel,
                    'amount' => (float) $this->amount,
                    'percent' => $project->value > 0 ? round(((float) $this->amount / $project->value) * 100, 2) : 0,
                    'trigger_type' => 'recurring',
                    'milestone_id' => null,
                    'due_date' => $base->toDateString(),
                    'grace_days' => 0,
                    'status' => ($base->toDateString() < now()->toDateString()) ? 'Overdue' : 'Pending',
                    'received' => 0.00,
                    'tds' => 0.00,
                    'notes' => 'Auto-generated recurring retainer',
                ]);
            }

            $auditService = app(AuditNotificationService::class);
            $auditService->log(
                auth()->id(),
                'Generated Recurring Schedule',
                'PaymentTerm',
                $project->id,
                $project->code,
                "Generated {$this->months} recurring installments of ₹".number_format((float) $this->amount, 2)." each for project {$project->name}"
            );

            $auditService->notifyProjectStakeholders(
                $project,
                'Recurring Schedule Generated',
                "Recurring Schedule: {$project->name}",
                "Generated {$this->months} recurring installments of ₹".number_format((float) $this->amount, 2)." each for {$project->name} by ".auth()->user()->name.'.',
                'project',
                $project->id
            );
        });

        $this->dispatch('close-modal', 'project-recurring-modal');
        $this->dispatch('project-saved');
        $this->dispatch('toast', ['msg' => 'Recurring installments generated successfully', 'type' => 'ok']);
    }

    public function render()
    {
        return view('livewire.projects.project-recurring-modal');
    }
}
