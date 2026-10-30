<?php

namespace App\Livewire\Projects;

use App\Models\Milestone;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectScheduleModal extends Component
{
    public ?int $projectId = null;

    public string $projectCode = '';

    public float $projectValue = 0.0;

    public ?string $projectStartDate = null;

    public ?string $projectDeliveryDate = null;

    public array $terms = [];

    public array $availableMilestones = [];

    #[On('open-schedule-modal')]
    public function load(int $projectId): void
    {
        $this->resetValidation();
        $this->projectId = $projectId;
        $project = Project::with(['terms', 'milestones'])->findOrFail($projectId);
        $this->projectCode = $project->code;
        $this->projectValue = (float) $project->value;
        $this->projectStartDate = $project->start_date?->toDateString();
        $this->projectDeliveryDate = $project->delivery_date?->toDateString();

        $this->availableMilestones = $project->milestones->map(fn ($m) => [
            'id' => $m->id,
            'name' => $m->name,
        ])->toArray();

        $this->terms = $project->terms()->orderBy('seq')->get()->map(fn ($t) => [
            'id' => $t->id,
            'label' => $t->label,
            'amount' => (float) $t->amount,
            'trigger' => $t->trigger_type,
            'milestoneId' => $t->milestone_id,
            'dueDate' => $t->due_date?->toDateString() ?? '',
            'graceDays' => (int) $t->grace_days,
            'locked' => (float) $t->received > 0 || (float) $t->tds > 0 || $t->status === 'Paid',
        ])->toArray();

        if (empty($this->terms)) {
            $this->addRow();
        }

        $this->dispatch('open-modal', 'project-schedule-modal');
    }

    public function addRow(): void
    {
        $this->terms[] = [
            'id' => null,
            'label' => 'Installment '.(count($this->terms) + 1),
            'amount' => '',
            'trigger' => 'fixed',
            'milestoneId' => null,
            'dueDate' => '',
            'graceDays' => 0,
            'locked' => false,
        ];
    }

    public function split5050(): void
    {
        $projectValue = $this->projectValue;
        $milestoneCount = count($this->availableMilestones);
        $lastMilestone = $milestoneCount > 0 ? end($this->availableMilestones)['id'] : null;

        $advanceAmount = round($projectValue * 0.5, 2);
        $deliveryAmount = round($projectValue - $advanceAmount, 2);

        $advanceDate = $this->projectStartDate ?? now()->toDateString();
        $deliveryDate = $this->projectDeliveryDate ?? now()->addDays(60)->toDateString();

        $this->terms = [
            [
                'id' => null,
                'label' => 'Advance',
                'amount' => $advanceAmount,
                'trigger' => 'fixed',
                'milestoneId' => null,
                'dueDate' => $advanceDate,
                'graceDays' => 0,
                'locked' => false,
            ],
            [
                'id' => null,
                'label' => 'On delivery',
                'amount' => $deliveryAmount,
                'trigger' => $lastMilestone ? 'milestone' : 'fixed',
                'milestoneId' => $lastMilestone,
                'dueDate' => $lastMilestone ? '' : $deliveryDate,
                'graceDays' => 7,
                'locked' => false,
            ],
        ];

        $this->resetValidation();
    }

    public function autoSplit(): void
    {
        $projectValue = $this->projectValue;
        $milestoneCount = count($this->availableMilestones);
        $firstMilestone = $milestoneCount > 0 ? $this->availableMilestones[0]['id'] : null;
        $lastMilestone = $milestoneCount > 1 ? end($this->availableMilestones)['id'] : ($milestoneCount === 1 ? $firstMilestone : null);

        $advanceAmount = round($projectValue * 0.3, 2);
        $midAmount = round($projectValue * 0.4, 2);
        $deliveryAmount = round($projectValue - $advanceAmount - $midAmount, 2);

        // Staggered dates when no milestones are available
        $advanceDate = $this->projectStartDate ?? now()->toDateString();
        $deliveryDate = $this->projectDeliveryDate ?? now()->addDays(60)->toDateString();

        $advanceCarbon = Carbon::parse($advanceDate);
        $deliveryCarbon = Carbon::parse($deliveryDate);
        $daysDiff = (int) round($advanceCarbon->startOfDay()->diffInDays($deliveryCarbon->startOfDay(), false));
        $midDate = ($daysDiff > 14)
            ? $advanceCarbon->copy()->addDays((int) floor($daysDiff / 2))->toDateString()
            : now()->addDays(30)->toDateString();

        $this->terms = [
            [
                'id' => null,
                'label' => 'Advance',
                'amount' => $advanceAmount,
                'trigger' => 'fixed',
                'milestoneId' => null,
                'dueDate' => $advanceDate,
                'graceDays' => 0,
                'locked' => false,
            ],
            [
                'id' => null,
                'label' => 'Mid project',
                'amount' => $midAmount,
                'trigger' => $firstMilestone ? 'milestone' : 'fixed',
                'milestoneId' => $firstMilestone,
                'dueDate' => $firstMilestone ? '' : $midDate,
                'graceDays' => 7,
                'locked' => false,
            ],
            [
                'id' => null,
                'label' => 'On delivery',
                'amount' => $deliveryAmount,
                'trigger' => ($lastMilestone && $lastMilestone !== $firstMilestone) ? 'milestone' : 'fixed',
                'milestoneId' => ($lastMilestone && $lastMilestone !== $firstMilestone) ? $lastMilestone : null,
                'dueDate' => ($lastMilestone && $lastMilestone !== $firstMilestone) ? '' : $deliveryDate,
                'graceDays' => 7,
                'locked' => false,
            ],
        ];

        $this->resetValidation();
    }

    public function removeRow(int $index): void
    {
        if (isset($this->terms[$index])) {
            if (! empty($this->terms[$index]['locked'])) {
                $this->addError("terms.{$index}.locked", 'Cannot delete an installment that already has payments recorded.');

                return;
            }
            unset($this->terms[$index]);
            $this->terms = array_values($this->terms);
            $this->resetValidation();
        }
    }

    protected function rules(): array
    {
        return [
            'terms' => 'required|array|min:1',
            'terms.*.label' => 'required|string|max:80',
            'terms.*.amount' => 'required|numeric|min:0.01',
            'terms.*.trigger' => 'required|in:fixed,milestone,recurring',
            'terms.*.milestoneId' => 'nullable|required_if:terms.*.trigger,milestone|exists:milestones,id',
            'terms.*.dueDate' => 'nullable|required_if:terms.*.trigger,fixed|date',
            'terms.*.graceDays' => 'required|integer|min:0|max:180',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'terms.*.label' => 'installment label',
            'terms.*.amount' => 'installment amount',
            'terms.*.trigger' => 'trigger mode',
            'terms.*.milestoneId' => 'milestone',
            'terms.*.dueDate' => 'due date',
            'terms.*.graceDays' => 'grace days',
        ];
    }

    public function updated(string $propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        // Pre-normalize types
        foreach ($this->terms as $idx => $t) {
            $this->terms[$idx]['amount'] = (! isset($t['amount']) || $t['amount'] === '' || $t['amount'] === null) ? 0.0 : (float) $t['amount'];
            $graceDaysVal = $t['graceDays'] ?? ($t['grace_days'] ?? 0);
            $this->terms[$idx]['graceDays'] = ($graceDaysVal === '' || $graceDaysVal === null) ? 0 : (int) $graceDaysVal;
            $this->terms[$idx]['milestoneId'] = ! empty($t['milestoneId']) ? (int) $t['milestoneId'] : null;
            $this->terms[$idx]['dueDate'] = ! empty($t['dueDate']) ? $t['dueDate'] : null;
        }

        $this->validate();

        $project = Project::findOrFail($this->projectId);

        // Find locked term IDs that have payments or TDS logged
        $lockedTerms = PaymentTerm::where('project_id', $this->projectId)
            ->where(function ($q) {
                $q->where('received', '>', 0)
                    ->orWhere('tds', '>', 0)
                    ->orWhere('status', 'Paid');
            })
            ->get()
            ->keyBy('id');

        $activeIds = [];
        $seq = 0;

        foreach ($this->terms as $t) {
            $seq++;
            $termId = ! empty($t['id']) ? (int) $t['id'] : null;
            $amt = (float) $t['amount'];

            $dueDate = ! empty($t['dueDate']) ? $t['dueDate'] : null;
            if ($t['trigger'] === 'milestone' && ! empty($t['milestoneId'])) {
                $milestoneDueDate = Milestone::where('id', $t['milestoneId'])->value('due_date');
                if ($milestoneDueDate) {
                    $dueDate = Carbon::parse($milestoneDueDate)->toDateString();
                }
            }

            $payload = [
                'seq' => $seq,
                'label' => trim($t['label']),
                'amount' => $amt,
                'percent' => $this->projectValue > 0 ? round(($amt / $this->projectValue) * 100, 2) : 0,
                'trigger_type' => $t['trigger'],
                'milestone_id' => ($t['trigger'] === 'milestone') ? $t['milestoneId'] : null,
                'due_date' => $dueDate,
                'grace_days' => (int) $t['graceDays'],
            ];

            if ($termId && $lockedTerms->has($termId)) {
                // If row is locked, update non-destructive fields only
                $term = $lockedTerms->get($termId);
                $term->update([
                    'seq' => $seq,
                    'label' => trim($t['label']),
                    'grace_days' => (int) $t['graceDays'],
                ]);
                $activeIds[] = $termId;
            } elseif ($termId) {
                // Update existing unlocked row
                $term = PaymentTerm::where('project_id', $this->projectId)->find($termId);
                if ($term) {
                    $term->update($payload);
                    $activeIds[] = $term->id;
                }
            } else {
                // Create new term
                $payload['project_id'] = $this->projectId;
                $payload['status'] = 'Pending';
                $payload['received'] = 0.00;
                $payload['tds'] = 0.00;
                $newTerm = PaymentTerm::create($payload);
                $activeIds[] = $newTerm->id;
            }
        }

        // Delete only removed terms that do not have recorded payments
        PaymentTerm::where('project_id', $this->projectId)
            ->whereNotIn('id', $activeIds)
            ->whereNotIn('id', $lockedTerms->keys()->toArray())
            ->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Updated Schedule',
            'Project',
            $project->id,
            $project->code,
            "Updated payment schedule for project {$project->name}"
        );

        app(AuditNotificationService::class)->notifyProjectStakeholders(
            $project,
            'Schedule Updated',
            "Payment Schedule Updated: {$project->name}",
            "Payment schedule for project {$project->name} ({$project->code}) was updated by ".auth()->user()->name.'.',
            'project',
            $project->id
        );

        $this->dispatch('close-modal', 'project-schedule-modal');
        $this->dispatch('project-saved');
        $this->dispatch('toast', ['msg' => 'Payment schedule saved successfully', 'type' => 'ok']);
    }

    public function render()
    {
        $numericAmounts = array_filter(
            array_column($this->terms, 'amount'),
            fn ($v) => is_numeric($v)
        );
        $total = (float) array_sum($numericAmounts);
        $diff = (float) ($this->projectValue - $total);

        return view('livewire.projects.project-schedule-modal', [
            'total' => $total,
            'diff' => $diff,
        ]);
    }
}
