<?php

namespace App\Livewire\Tasks;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class TaskIndex extends Component
{
    public string $title = '';

    public ?int $project_id = null;

    public string $priority = 'Medium';

    public ?string $due_date = null;

    public ?int $assigned_to = null;

    #[Url]
    public string $filterSearch = '';

    #[Url]
    public string $filterPriority = '';

    #[Url]
    public string $filterStatus = ''; // 'open', 'done'

    #[Url]
    public string $filterProjectId = '';

    #[Url]
    public string $filterAssignedTo = '';

    public function clearFilters(): void
    {
        $this->reset(['filterSearch', 'filterPriority', 'filterStatus', 'filterProjectId', 'filterAssignedTo']);
    }

    public function mount(): void
    {
        $this->assigned_to = auth()->id();
        $this->due_date = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'title' => 'required|string|max:200',
            'project_id' => 'nullable|integer|exists:projects,id',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|integer|exists:users,id',
        ];
    }

    public function updated(string $propertyName): void
    {
        if ($propertyName === 'project_id' && empty($this->project_id)) {
            $this->project_id = null;
        }
        if ($propertyName === 'due_date' && empty($this->due_date)) {
            $this->due_date = null;
        }

        $this->validateOnly($propertyName);
    }

    public function toggleDone(int $taskId): void
    {
        $task = Task::query()
            ->when(auth()->user()->role !== 'Admin', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->findOrFail($taskId);

        $task->done = ! $task->done;
        $task->save();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            $task->done ? 'Completed Task' : 'Reopened Task',
            'Task',
            $task->id,
            '',
            ($task->done ? 'Marked task as done: ' : 'Reopened task: ').$task->title
        );

        $this->dispatch('task-updated');
    }

    public function deleteTask(int $taskId): void
    {
        $task = Task::query()
            ->when(auth()->user()->role !== 'Admin', function ($q) {
                $q->where('assigned_to', auth()->id());
            })
            ->findOrFail($taskId);

        $title = $task->title;
        $id = $task->id;

        $task->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Task',
            'Task',
            $id,
            '',
            "Deleted task: {$title}"
        );

        $this->dispatch('task-updated');
    }

    public function addTask(): void
    {
        // Sanitize empty strings into null before validation
        $this->project_id = ! empty($this->project_id) ? (int) $this->project_id : null;
        $this->assigned_to = ! empty($this->assigned_to) ? (int) $this->assigned_to : auth()->id();
        $this->due_date = ! empty($this->due_date) ? $this->due_date : null;
        $this->title = trim($this->title);

        $this->validate();

        $customerId = null;
        if ($this->project_id) {
            $customerId = Project::find($this->project_id)?->customer_id;
        }

        $task = Task::create([
            'title' => $this->title,
            'project_id' => $this->project_id,
            'customer_id' => $customerId,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'assigned_to' => $this->assigned_to,
            'created_by' => auth()->id(),
            'done' => false,
        ]);

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Created Task',
            'Task',
            $task->id,
            '',
            "Created task: {$task->title}"
        );

        $this->dispatch('task-saved');
        $this->dispatch('close-modal', 'add-task-modal');
        $this->dispatch('toast', ['msg' => 'Task added successfully', 'type' => 'ok']);

        $this->reset(['title', 'project_id']);
        $this->priority = 'Medium';
        $this->due_date = now()->toDateString();
        $this->assigned_to = auth()->id();
        $this->resetValidation();
    }

    public function render()
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $query = Task::query()
            ->when($user->role !== 'Admin', fn ($q) => $q->where('assigned_to', $user->id))
            ->with(['project.customer', 'customer', 'assignedTo'])
            ->when($this->filterPriority, fn ($q) => $q->where('priority', $this->filterPriority))
            ->when($this->filterProjectId, fn ($q) => $q->where('project_id', $this->filterProjectId))
            ->when($this->filterAssignedTo, fn ($q) => $q->where('assigned_to', $this->filterAssignedTo))
            ->when($this->filterStatus === 'open', fn ($q) => $q->where('done', false))
            ->when($this->filterStatus === 'done', fn ($q) => $q->where('done', true))
            ->when($this->filterSearch, function ($q) {
                $term = '%'.trim($this->filterSearch).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', $term)
                        ->orWhereHas('project', fn ($p) => $p->where('name', 'like', $term)->orWhere('code', 'like', $term))
                        ->orWhereHas('customer', fn ($c) => $c->where('company', 'like', $term));
                });
            })
            ->orderBy('done')
            ->orderByRaw('due_date IS NULL, due_date ASC');

        $tasks = $query->get();

        $baseTasks = Task::query()
            ->when($user->role !== 'Admin', fn ($q) => $q->where('assigned_to', $user->id))
            ->get();
        $openCount = $baseTasks->where('done', false)->count();
        $doneCount = $baseTasks->where('done', true)->count();

        $projects = Project::query()->forUser($user)->orderBy('name')->get();
        $users = User::where('status', 'Active')->orderBy('name')->get();

        return view('livewire.tasks.task-index', [
            'tasks' => $tasks,
            'openCount' => $openCount,
            'doneCount' => $doneCount,
            'projects' => $projects,
            'users' => $users,
            'today' => $today,
        ])->layoutData([
            'header' => 'My Tasks',
            'subtitle' => 'Personal execution checklist and scheduled follow-ups',
        ]);
    }
}
