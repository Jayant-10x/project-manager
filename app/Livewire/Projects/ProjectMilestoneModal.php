<?php

namespace App\Livewire\Projects;

use App\Models\Milestone;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Livewire\Attributes\On;
use Livewire\Component;

class ProjectMilestoneModal extends Component
{
    public ?int $projectId = null;

    public string $projectCode = '';

    public array $items = [];

    #[On('open-milestone-modal')]
    public function load(int $projectId): void
    {
        $this->resetValidation();
        $this->projectId = $projectId;
        $project = Project::findOrFail($projectId);
        $this->projectCode = $project->code;

        $dbItems = $project->milestones()->orderBy('sort_order')->get();
        if ($dbItems->isEmpty()) {
            $deliveryDate = $project->delivery_date?->toDateString() ?? '';
            $this->items = [
                ['id' => null, 'name' => 'Kickoff & requirement gathering', 'dueDate' => '', 'status' => 'Pending', 'done' => false],
                ['id' => null, 'name' => 'Design approval', 'dueDate' => '', 'status' => 'Pending', 'done' => false],
                ['id' => null, 'name' => 'Development complete', 'dueDate' => '', 'status' => 'Pending', 'done' => false],
                ['id' => null, 'name' => 'UAT & sign-off', 'dueDate' => '', 'status' => 'Pending', 'done' => false],
                ['id' => null, 'name' => 'Live / delivery', 'dueDate' => $deliveryDate, 'status' => 'Pending', 'done' => false],
            ];
        } else {
            $this->items = $dbItems->map(fn ($m) => [
                'id' => $m->id,
                'name' => $m->name,
                'dueDate' => $m->due_date?->toDateString() ?? '',
                'status' => $m->status ?? 'Pending',
                'done' => (bool) $m->done,
            ])->toArray();
        }

        $this->dispatch('open-modal', 'project-milestone-modal');
    }

    public function addRow(): void
    {
        $this->items[] = [
            'id' => null,
            'name' => '',
            'dueDate' => '',
            'status' => 'Pending',
            'done' => false,
        ];
    }

    public function removeRow(int $index): void
    {
        if (isset($this->items[$index])) {
            unset($this->items[$index]);
            $this->items = array_values($this->items);
            $this->resetValidation();
        }
    }

    protected function rules(): array
    {
        return [
            'items' => 'required|array|min:1',
            'items.*.name' => 'required|string|max:160',
            'items.*.dueDate' => 'nullable|date',
            'items.*.status' => 'required|in:Pending,In Progress,Done,Blocked',
            'items.*.done' => 'boolean',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'items.*.name' => 'milestone name',
            'items.*.dueDate' => 'due date',
            'items.*.status' => 'status',
        ];
    }

    public function updated(string $propertyName): void
    {
        // Automatically sync checkbox status with dropdown
        if (str_ends_with($propertyName, '.done')) {
            $parts = explode('.', $propertyName);
            $idx = $parts[1] ?? null;
            if ($idx !== null && isset($this->items[$idx])) {
                $this->items[$idx]['status'] = $this->items[$idx]['done'] ? 'Done' : 'In Progress';
            }
        } elseif (str_ends_with($propertyName, '.status')) {
            $parts = explode('.', $propertyName);
            $idx = $parts[1] ?? null;
            if ($idx !== null && isset($this->items[$idx])) {
                $this->items[$idx]['done'] = ($this->items[$idx]['status'] === 'Done');
            }
        }

        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        // Normalize empty string dates to null before validation
        foreach ($this->items as $idx => $m) {
            $this->items[$idx]['dueDate'] = ! empty($m['dueDate']) ? $m['dueDate'] : null;
            $this->items[$idx]['name'] = trim($m['name'] ?? '');
        }

        $this->validate();

        $project = Project::findOrFail($this->projectId);
        $existingMilestones = Milestone::where('project_id', $this->projectId)->get()->keyBy('id');
        $seenIds = [];
        $diffs = [];

        foreach ($this->items as $idx => $m) {
            $done = ! empty($m['done']) || ($m['status'] === 'Done');
            $status = $done ? 'Done' : ($m['status'] ?? 'Pending');

            $payload = [
                'name' => $m['name'],
                'due_date' => $m['dueDate'],
                'status' => $status,
                'done' => $done,
                'done_on' => $done ? now()->toDateString() : null,
                'sort_order' => $idx + 1,
            ];

            if (! empty($m['id'])) {
                $milestone = $existingMilestones->get($m['id']);
                if ($milestone) {
                    $itemChanges = [];
                    if ($milestone->name !== $payload['name']) {
                        $itemChanges[] = "name: '{$milestone->name}' -> '{$payload['name']}'";
                    }
                    $oldDue = $milestone->due_date ? Carbon::parse($milestone->due_date)->toDateString() : null;
                    if ($oldDue !== $payload['due_date']) {
                        $itemChanges[] = 'due: '.($oldDue ?: 'none').' -> '.($payload['due_date'] ?: 'none');
                    }
                    if ($milestone->status !== $payload['status']) {
                        $itemChanges[] = "status: {$milestone->status} -> {$payload['status']}";
                    }
                    if (! empty($itemChanges)) {
                        $diffs[] = "Edited '{$milestone->name}' (".implode(', ', $itemChanges).')';
                    }
                    $milestone->update($payload);
                    $seenIds[] = $milestone->id;
                }
            } else {
                $created = $project->milestones()->create($payload);
                $seenIds[] = $created->id;
                $diffs[] = "Added '{$created->name}' (status: {$payload['status']})";
            }
        }

        // Clean up removed milestones that are not locked to payment terms
        $removed = Milestone::where('project_id', $this->projectId)
            ->whereNotIn('id', $seenIds)
            ->get();
        foreach ($removed as $rm) {
            $diffs[] = "Removed '{$rm->name}'";
            $rm->delete();
        }

        $detailText = ! empty($diffs)
            ? "Updated milestones for {$project->name}: ".implode('; ', $diffs)
            : "Updated milestones for project {$project->name}";

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Updated Milestones',
            'Project',
            $project->id,
            $project->code,
            $detailText
        );

        $this->dispatch('close-modal', 'project-milestone-modal');
        $this->dispatch('project-saved');
        $this->dispatch('toast', ['msg' => 'Milestones updated successfully', 'type' => 'ok']);
    }

    public function render()
    {
        return view('livewire.projects.project-milestone-modal');
    }
}
