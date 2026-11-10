<?php

namespace App\Livewire\Projects;

use App\Models\Milestone;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
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

        $this->availableMilestones = $project->milestones->map(fn ($milestone) => [
            'id' => $milestone->id,
            'name' => $milestone->name,
        ])->toArray();

        $this->terms = $project->terms()->orderBy('seq')->get()->map(fn ($term) => [
            'id' => $term->id,
            'label' => $term->label,
            'amount' => (float) $term->amount,
            'trigger' => $term->trigger_type,
            'milestoneId' => $term->milestone_id,
            'dueDate' => $term->due_date?->toDateString() ?? '',
            'graceDays' => (int) $term->grace_days,
            'locked' => (float) $term->received > 0 || (float) $term->tds > 0 || $term->status === 'Paid',
            'received' => (float) $term->received,
            'tds' => (float) $term->tds,
            'due' => (float) $term->balance,
            'status' => $term->status ?? 'Pending',
        ])->toArray();

        if (empty($this->terms)) {
            $this->addRow();
        }

        $this->dispatch('open-modal', 'project-schedule-modal');
    }

    public function addRow(): void
    {
        $nonRecurringCount = count(array_filter($this->terms, fn ($termItem) => ($termItem['trigger'] ?? '') !== 'recurring'));

        $this->terms[] = [
            'id' => null,
            'label' => 'Installment '.($nonRecurringCount + 1),
            'amount' => '',
            'trigger' => 'fixed',
            'milestoneId' => null,
            'dueDate' => '',
            'graceDays' => 0,
            'locked' => false,
            'received' => 0.0,
            'tds' => 0.0,
            'due' => 0.0,
            'status' => 'Pending',
        ];
    }

    public function addRecurringRow(): void
    {
        $recurringCount = count(array_filter($this->terms, fn ($termItem) => ($termItem['trigger'] ?? '') === 'recurring'));

        $this->terms[] = [
            'id' => null,
            'label' => 'Recurring Retainer '.($recurringCount + 1),
            'amount' => '',
            'trigger' => 'recurring',
            'milestoneId' => null,
            'dueDate' => now()->toDateString(),
            'graceDays' => 0,
            'locked' => false,
            'received' => 0.0,
            'tds' => 0.0,
            'due' => 0.0,
            'status' => 'Pending',
        ];
    }

    public function split5050(): void
    {
        // 1. Separate recurring terms and keep them intact (not removed, not counted in remaining amount)
        $recurringTerms = array_values(array_filter(
            $this->terms,
            fn ($termItem) => ($termItem['trigger'] ?? '') === 'recurring'
        ));

        // 2. Preserve any existing locked non-recurring terms
        $lockedNonRecurringTerms = array_values(array_filter(
            $this->terms,
            fn ($termItem) => ! empty($termItem['locked']) && ($termItem['trigger'] ?? '') !== 'recurring'
        ));
        $lockedTotal = (float) array_sum(array_column($lockedNonRecurringTerms, 'amount'));
        $remainingBalance = round(max(0, $this->projectValue - $lockedTotal), 2);

        if (! empty($lockedNonRecurringTerms) && $remainingBalance <= 0) {
            $this->dispatch('toast', [
                'msg' => 'Cannot split: Locked installments already cover or exceed the project value.',
                'type' => 'warning',
            ]);
            $this->terms = array_merge($lockedNonRecurringTerms, $recurringTerms);
            $this->resetValidation();

            return;
        }

        // Available milestones not already linked to locked terms
        $usedMilestoneIds = array_filter(array_column($lockedNonRecurringTerms, 'milestoneId'));
        $unusedMilestones = array_values(array_filter(
            $this->availableMilestones,
            fn ($milestone) => ! in_array($milestone['id'], $usedMilestoneIds)
        ));
        $unusedCount = count($unusedMilestones);

        $advanceDate = $this->projectStartDate ?? now()->toDateString();
        $deliveryDate = $this->projectDeliveryDate ?? now()->addDays(60)->toDateString();

        if (empty($lockedNonRecurringTerms)) {
            // Standard 50-50 Split when no non-recurring payments are locked
            $advanceAmount = round($this->projectValue * 0.5, 2);
            $deliveryAmount = round($this->projectValue - $advanceAmount, 2);
            $lastMilestone = $unusedCount > 0 ? end($unusedMilestones)['id'] : null;

            $newTerms = [
                [
                    'id' => null,
                    'label' => 'Advance',
                    'amount' => $advanceAmount,
                    'trigger' => 'fixed',
                    'milestoneId' => null,
                    'dueDate' => $advanceDate,
                    'graceDays' => 0,
                    'locked' => false,
                    'received' => 0.0,
                    'tds' => 0.0,
                    'due' => $advanceAmount,
                    'status' => 'Pending',
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
                    'received' => 0.0,
                    'tds' => 0.0,
                    'due' => $deliveryAmount,
                    'status' => 'Pending',
                ],
            ];

            $this->terms = array_merge($newTerms, $recurringTerms);

            if (! empty($recurringTerms)) {
                $this->dispatch('toast', [
                    'msg' => 'Preserved recurring payments and split project value (50-50).',
                    'type' => 'ok',
                ]);
            }
        } else {
            // Locked non-recurring payments exist: Retain locked terms and generate two new installments splitting the remaining balance 50/50
            $firstAmount = round($remainingBalance * 0.5, 2);
            $secondAmount = round($remainingBalance - $firstAmount, 2);

            $interimMilestone = $unusedCount > 1 ? $unusedMilestones[0]['id'] : null;
            $finalMilestone = $unusedCount > 0 ? end($unusedMilestones)['id'] : null;
            if ($interimMilestone === $finalMilestone) {
                $interimMilestone = null;
            }

            $advanceCarbon = Carbon::parse($advanceDate);
            $deliveryCarbon = Carbon::parse($deliveryDate);
            $daysDiff = (int) round($advanceCarbon->startOfDay()->diffInDays($deliveryCarbon->startOfDay(), false));
            $midDate = ($daysDiff > 14)
                ? $advanceCarbon->copy()->addDays((int) floor($daysDiff / 2))->toDateString()
                : now()->addDays(30)->toDateString();

            $newTerms = [
                [
                    'id' => null,
                    'label' => 'Interim Installment',
                    'amount' => $firstAmount,
                    'trigger' => $interimMilestone ? 'milestone' : 'fixed',
                    'milestoneId' => $interimMilestone,
                    'dueDate' => $interimMilestone ? '' : $midDate,
                    'graceDays' => 7,
                    'locked' => false,
                    'received' => 0.0,
                    'tds' => 0.0,
                    'due' => $firstAmount,
                    'status' => 'Pending',
                ],
                [
                    'id' => null,
                    'label' => 'On delivery',
                    'amount' => $secondAmount,
                    'trigger' => $finalMilestone ? 'milestone' : 'fixed',
                    'milestoneId' => $finalMilestone,
                    'dueDate' => $finalMilestone ? '' : $deliveryDate,
                    'graceDays' => 7,
                    'locked' => false,
                    'received' => 0.0,
                    'tds' => 0.0,
                    'due' => $secondAmount,
                    'status' => 'Pending',
                ],
            ];

            $this->terms = array_merge($lockedNonRecurringTerms, $newTerms, $recurringTerms);

            $this->dispatch('toast', [
                'msg' => 'Preserved locked payments and split remaining ₹'.number_format($remainingBalance, 2).' (50-50).',
                'type' => 'ok',
            ]);
        }

        $this->resetValidation();
    }

    public function autoSplit(): void
    {
        // 1. Separate recurring terms and keep them intact (not removed, not counted in remaining amount)
        $recurringTerms = array_values(array_filter(
            $this->terms,
            fn ($termItem) => ($termItem['trigger'] ?? '') === 'recurring'
        ));

        // 2. Preserve any existing locked non-recurring terms
        $lockedNonRecurringTerms = array_values(array_filter(
            $this->terms,
            fn ($termItem) => ! empty($termItem['locked']) && ($termItem['trigger'] ?? '') !== 'recurring'
        ));
        $lockedTotal = (float) array_sum(array_column($lockedNonRecurringTerms, 'amount'));
        $remainingBalance = round(max(0, $this->projectValue - $lockedTotal), 2);

        if (! empty($lockedNonRecurringTerms) && $remainingBalance <= 0) {
            $this->dispatch('toast', [
                'msg' => 'Cannot split: Locked installments already cover or exceed the project value.',
                'type' => 'warning',
            ]);
            $this->terms = array_merge($lockedNonRecurringTerms, $recurringTerms);
            $this->resetValidation();

            return;
        }

        // Available milestones not already linked to locked terms
        $usedMilestoneIds = array_filter(array_column($lockedNonRecurringTerms, 'milestoneId'));
        $unusedMilestones = array_values(array_filter(
            $this->availableMilestones,
            fn ($milestone) => ! in_array($milestone['id'], $usedMilestoneIds)
        ));
        $unusedCount = count($unusedMilestones);

        $advanceDate = $this->projectStartDate ?? now()->toDateString();
        $deliveryDate = $this->projectDeliveryDate ?? now()->addDays(60)->toDateString();

        $advanceCarbon = Carbon::parse($advanceDate);
        $deliveryCarbon = Carbon::parse($deliveryDate);
        $daysDiff = (int) round($advanceCarbon->startOfDay()->diffInDays($deliveryCarbon->startOfDay(), false));
        $midDate = ($daysDiff > 14)
            ? $advanceCarbon->copy()->addDays((int) floor($daysDiff / 2))->toDateString()
            : now()->addDays(30)->toDateString();

        if (empty($lockedNonRecurringTerms)) {
            // Standard 30/40/30 Split when no non-recurring payments are locked
            $firstMilestone = $unusedCount > 0 ? $unusedMilestones[0]['id'] : null;
            $lastMilestone = $unusedCount > 1 ? end($unusedMilestones)['id'] : ($unusedCount === 1 ? $firstMilestone : null);

            $advanceAmount = round($this->projectValue * 0.3, 2);
            $midAmount = round($this->projectValue * 0.4, 2);
            $deliveryAmount = round($this->projectValue - $advanceAmount - $midAmount, 2);

            $newTerms = [
                [
                    'id' => null,
                    'label' => 'Advance',
                    'amount' => $advanceAmount,
                    'trigger' => 'fixed',
                    'milestoneId' => null,
                    'dueDate' => $advanceDate,
                    'graceDays' => 0,
                    'locked' => false,
                    'received' => 0.0,
                    'tds' => 0.0,
                    'due' => $advanceAmount,
                    'status' => 'Pending',
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
                    'received' => 0.0,
                    'tds' => 0.0,
                    'due' => $midAmount,
                    'status' => 'Pending',
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
                    'received' => 0.0,
                    'tds' => 0.0,
                    'due' => $deliveryAmount,
                    'status' => 'Pending',
                ],
            ];

            $this->terms = array_merge($newTerms, $recurringTerms);

            if (! empty($recurringTerms)) {
                $this->dispatch('toast', [
                    'msg' => 'Preserved recurring payments and split project value (30/40/30).',
                    'type' => 'ok',
                ]);
            }
        } else {
            // Locked payments exist: Retain locked terms and generate 3 new installments for the remaining balance in 30/40/30 proportions
            $part1 = round($remainingBalance * 0.3, 2);
            $part2 = round($remainingBalance * 0.4, 2);
            $part3 = round($remainingBalance - $part1 - $part2, 2);

            $firstMilestone = $unusedCount > 0 ? $unusedMilestones[0]['id'] : null;
            $midMilestone = $unusedCount > 1 ? $unusedMilestones[1]['id'] : null;
            $lastMilestone = $unusedCount > 2 ? end($unusedMilestones)['id'] : null;

            $step1Date = $advanceCarbon->copy()->addDays(max(1, (int) floor($daysDiff / 3)))->toDateString();
            $step2Date = $advanceCarbon->copy()->addDays(max(2, (int) floor($daysDiff * 2 / 3)))->toDateString();

            $newTerms = [
                [
                    'id' => null,
                    'label' => 'Installment (30%)',
                    'amount' => $part1,
                    'trigger' => $firstMilestone ? 'milestone' : 'fixed',
                    'milestoneId' => $firstMilestone,
                    'dueDate' => $firstMilestone ? '' : $step1Date,
                    'graceDays' => 0,
                    'locked' => false,
                    'received' => 0.0,
                    'tds' => 0.0,
                    'due' => $part1,
                    'status' => 'Pending',
                ],
                [
                    'id' => null,
                    'label' => 'Mid project (40%)',
                    'amount' => $part2,
                    'trigger' => $midMilestone ? 'milestone' : 'fixed',
                    'milestoneId' => $midMilestone,
                    'dueDate' => $midMilestone ? '' : $step2Date,
                    'graceDays' => 7,
                    'locked' => false,
                    'received' => 0.0,
                    'tds' => 0.0,
                    'due' => $part2,
                    'status' => 'Pending',
                ],
                [
                    'id' => null,
                    'label' => 'On delivery (30%)',
                    'amount' => $part3,
                    'trigger' => $lastMilestone ? 'milestone' : 'fixed',
                    'milestoneId' => $lastMilestone,
                    'dueDate' => $lastMilestone ? '' : $deliveryDate,
                    'graceDays' => 7,
                    'locked' => false,
                    'received' => 0.0,
                    'tds' => 0.0,
                    'due' => $part3,
                    'status' => 'Pending',
                ],
            ];

            $this->terms = array_merge($lockedNonRecurringTerms, $newTerms, $recurringTerms);

            $this->dispatch('toast', [
                'msg' => 'Preserved locked payments and split remaining ₹'.number_format($remainingBalance, 2).' (30/40/30).',
                'type' => 'ok',
            ]);
        }

        $this->resetValidation();
    }

    public function removeRow(int $index): void
    {
        if (isset($this->terms[$index])) {
            if (! empty($this->terms[$index]['locked'])) {
                $this->addError("terms.{$index}.locked", 'Cannot delete an installment that already has payments recorded.');
                $this->dispatch('toast', [
                    'msg' => 'Cannot delete an installment that already has payments recorded.',
                    'type' => 'warning',
                ]);

                return;
            }

            if (count($this->terms) <= 1) {
                $this->addError('terms', 'At least one installment row must be retained.');
                $this->dispatch('toast', [
                    'msg' => 'At least one installment row must be retained.',
                    'type' => 'warning',
                ]);

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

    protected function messages(): array
    {
        return [
            'terms.required' => 'At least one installment row must be retained.',
            'terms.min' => 'At least one installment row must be retained.',
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
        if (empty($this->terms)) {
            $this->addError('terms', 'At least one installment row must be retained.');
            $this->dispatch('toast', [
                'msg' => 'At least one installment row must be retained.',
                'type' => 'warning',
            ]);

            return;
        }

        // Pre-normalize types
        foreach ($this->terms as $index => $termItem) {
            $this->terms[$index]['amount'] = (! isset($termItem['amount']) || $termItem['amount'] === '' || $termItem['amount'] === null) ? 0.0 : (float) $termItem['amount'];
            $graceDaysVal = $termItem['graceDays'] ?? ($termItem['grace_days'] ?? 0);
            $this->terms[$index]['graceDays'] = ($graceDaysVal === '' || $graceDaysVal === null) ? 0 : (int) $graceDaysVal;
            $this->terms[$index]['milestoneId'] = ! empty($termItem['milestoneId']) ? (int) $termItem['milestoneId'] : null;
            $this->terms[$index]['dueDate'] = ! empty($termItem['dueDate']) ? $termItem['dueDate'] : null;
        }

        $this->validate();

        $project = Project::findOrFail($this->projectId);

        // Find locked term IDs that have payments or TDS logged
        $lockedTerms = PaymentTerm::where('project_id', $this->projectId)
            ->where(function ($paymentTermQuery) {
                $paymentTermQuery->where('received', '>', 0)
                    ->orWhere('tds', '>', 0)
                    ->orWhere('status', 'Paid');
            })
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($project, $lockedTerms) {
            $activeIds = [];
            $seq = 0;

            foreach ($this->terms as $termItem) {
                $seq++;
                $termId = ! empty($termItem['id']) ? (int) $termItem['id'] : null;
                $termAmount = (float) $termItem['amount'];

                $dueDate = ! empty($termItem['dueDate']) ? $termItem['dueDate'] : null;
                if ($termItem['trigger'] === 'milestone' && ! empty($termItem['milestoneId'])) {
                    $milestoneDueDate = Milestone::where('id', $termItem['milestoneId'])->value('due_date');
                    if ($milestoneDueDate) {
                        $dueDate = Carbon::parse($milestoneDueDate)->toDateString();
                    }
                }

                $payload = [
                    'seq' => $seq,
                    'label' => trim($termItem['label']),
                    'amount' => $termAmount,
                    'percent' => $this->projectValue > 0 ? round(($termAmount / $this->projectValue) * 100, 2) : 0,
                    'trigger_type' => $termItem['trigger'],
                    'milestone_id' => ($termItem['trigger'] === 'milestone') ? $termItem['milestoneId'] : null,
                    'due_date' => $dueDate,
                    'grace_days' => (int) $termItem['graceDays'],
                ];

                if ($termId && $lockedTerms->has($termId)) {
                    // If row is locked, update non-destructive fields only
                    $term = $lockedTerms->get($termId);
                    $term->update([
                        'seq' => $seq,
                        'label' => trim($termItem['label']),
                        'grace_days' => (int) $termItem['graceDays'],
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
        });

        $this->dispatch('close-modal', 'project-schedule-modal');
        $this->dispatch('project-saved');
        $this->dispatch('toast', ['msg' => 'Payment schedule saved successfully', 'type' => 'ok']);
    }

    public function render()
    {
        $numericAmounts = array_filter(
            array_column($this->terms, 'amount'),
            fn ($amountValue) => is_numeric($amountValue)
        );
        $total = (float) array_sum($numericAmounts);
        $diff = (float) ($this->projectValue - $total);

        $totalReceived = 0.0;
        foreach ($this->terms as $term) {
            $totalReceived += (float) ($term['received'] ?? 0);
        }
        $totalPending = max(0.0, $total - $totalReceived);

        return view('livewire.projects.project-schedule-modal', [
            'total' => $total,
            'diff' => $diff,
            'totalReceived' => $totalReceived,
            'totalPending' => $totalPending,
        ]);
    }
}
