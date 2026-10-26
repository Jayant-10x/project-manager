<?php

namespace App\Livewire\Projects;

use App\Models\Followup;
use App\Models\Milestone;
use App\Models\PaymentTerm;
use App\Services\AuditNotificationService;
use Livewire\Attributes\On;
use Livewire\Component;

class MilestoneChaseModal extends Component
{
    public ?int $milestoneId = null;

    public string $milestoneName = '';

    public string $projectCode = '';

    public string $projectName = '';

    public string $chase_notes = '';

    public ?string $next_chase_date = null;

    #[On('open-chase-modal')]
    public function openModal(?int $milestoneId = null, ?int $termId = null): void
    {
        $this->resetValidation();

        if (! $milestoneId && $termId) {
            $term = PaymentTerm::with(['milestone', 'project.customer'])->find($termId);
            if ($term) {
                if ($term->milestone_id && $term->milestone) {
                    $milestoneId = $term->milestone_id;
                } else {
                    $milestone = Milestone::create([
                        'project_id' => $term->project_id,
                        'name' => $term->label,
                        'due_date' => $term->due_date,
                        'status' => $term->status === 'Paid' ? 'Done' : 'Pending',
                        'done' => $term->status === 'Paid',
                        'sort_order' => $term->seq ?: 1,
                    ]);
                    $term->update(['milestone_id' => $milestone->id]);
                    $milestoneId = $milestone->id;
                }
            }
        }

        if (! $milestoneId) {
            return;
        }

        $this->milestoneId = $milestoneId;

        $milestone = Milestone::with('project.customer')->findOrFail($milestoneId);
        $this->milestoneName = $milestone->name;
        $this->projectCode = $milestone->project?->code ?? '';
        $this->projectName = $milestone->project?->name ?? '';
        $this->chase_notes = $milestone->chase_notes ?? '';
        $this->next_chase_date = $milestone->next_chase_date?->toDateString();

        $this->dispatch('open-modal', 'milestone-chase-modal');
    }

    protected function rules(): array
    {
        return [
            'chase_notes' => 'required|string|max:1000',
            'next_chase_date' => 'nullable|date',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'chase_notes' => 'collection chase note',
            'next_chase_date' => 'next chase date',
        ];
    }

    public function save(): void
    {
        $this->chase_notes = trim($this->chase_notes);
        $this->next_chase_date = ! empty($this->next_chase_date) ? $this->next_chase_date : null;

        $validated = $this->validate();

        $milestone = Milestone::with('project.customer')->findOrFail($this->milestoneId);
        $milestone->update([
            'chase_notes' => $validated['chase_notes'],
            'next_chase_date' => $validated['next_chase_date'],
        ]);

        // Smart Follow-up: record a customer/project follow-up entry
        if ($milestone->project) {
            Followup::create([
                'customer_id' => $milestone->project->customer_id,
                'project_id' => $milestone->project_id,
                'type' => 'Note',
                'summary' => "Collection Chase: {$milestone->name}",
                'detail' => $validated['chase_notes'],
                'happened_on' => now(),
                'next_date' => $validated['next_chase_date'],
                'next_note' => "Follow-up on {$milestone->name}",
                'created_by' => auth()->id(),
            ]);

            app(AuditNotificationService::class)->log(
                auth()->id(),
                'Logged Chase Note',
                'Milestone',
                $milestone->id,
                $milestone->project->code,
                "Logged chase note for milestone '{$milestone->name}': {$validated['chase_notes']}"
            );

            if ($milestone->project->coordinator_id && $milestone->project->coordinator_id !== auth()->id()) {
                app(AuditNotificationService::class)->notify(
                    $milestone->project->coordinator_id,
                    'Chase Note Logged',
                    "Chase note logged for {$milestone->name}",
                    $validated['chase_notes'],
                    'project',
                    $milestone->project->id
                );
            }
        }

        $this->reset(['milestoneId', 'milestoneName', 'projectCode', 'projectName', 'chase_notes', 'next_chase_date']);
        $this->dispatch('close-modal', 'milestone-chase-modal');
        $this->dispatch('project-saved');
        $this->dispatch('followup-saved');
        $this->dispatch('counter-refresh');
        $this->dispatch('toast', ['msg' => 'Collection chase note saved successfully', 'type' => 'ok']);
    }

    public function render()
    {
        return view('livewire.projects.milestone-chase-modal');
    }
}
