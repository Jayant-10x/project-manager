<?php

namespace App\Livewire\Tasks;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class TaskIndex extends Component {
    public string $title = '';
    public ?int $project_id = null;
    public string $priority = 'Medium';
    public ?string $due_date = null;
    public ?int $assigned_to_id = null;

    public function mount(): void {
        $this->assigned_to_id = auth()->id();
        $this->due_date = now()->toDateString();
    }

    protected function rules(): array {
        return [
            'title' => 'required|string|max:200',
            'project_id' => 'nullable|integer|exists:projects,id',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'due_date' => 'nullable|date',
            'assigned_to_id' => 'nullable|integer|exists:users,id',
        ];
    }

    public function updated(string $propertyName): void {
        if ($propertyName === 'project_id' && empty($this->project_id)) {
            $this->project_id = null;
        }
        if ($propertyName === 'due_date' && empty($this->due_date)) {
            $this->due_date = null;
        }

        $this->validateOnly($propertyName);
    }

    public function toggleDone(int $taskId): void {
        $task = Task::query()
            ->when(auth()->user()->role !== 'Admin', function ($q) {
                $q->where('assigned_to_id', auth()->id());
            })
            ->findOrFail($taskId);

        $task->done = !$task->done;
        $task->save();
    }

    public function deleteTask(int $taskId): void {
        $task = Task::query()
            ->when(auth()->user()->role !== 'Admin', function ($q) {
                $q->where('assigned_to_id', auth()->id());
            })
            ->findOrFail($taskId);

        $task->delete();
    }

    public function addTask(): void {
        // Sanitize empty strings into null before validation
        $this->project_id = !empty($this->project_id) ? (int)$this->project_id : null;
        $this->assigned_to_id = !empty($this->assigned_to_id) ? (int)$this->assigned_to_id : auth()->id();
        $this->due_date = !empty($this->due_date) ? $this->due_date : null;
        $this->title = trim($this->title);

        $this->validate();

        $customerId = null;
        if ($this->project_id) {
            $customerId = Project::find($this->project_id)?->customer_id;
        }

        Task::create([
            'title' => $this->title,
            'project_id' => $this->project_id,
            'customer_id' => $customerId,
            'priority' => $this->priority,
            'due_date' => $this->due_date,
            'assigned_to_id' => $this->assigned_to_id,
            'created_by_id' => auth()->id(),
            'done' => false,
        ]);

        $this->reset(['title', 'project_id']);
        $this->priority = 'Medium';
        $this->due_date = now()->toDateString();
        $this->assigned_to_id = auth()->id();
        $this->resetValidation();
    }

    public function render() {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $query = Task::query()
            ->when($user->role !== 'Admin', fn($q) => $q->where('assigned_to_id', $user->id))
            ->with(['project.customer', 'customer', 'assignedTo'])
            ->orderBy('done')
            ->orderByRaw('due_date IS NULL, due_date ASC');

        $tasks = $query->get();

        $openCount = $tasks->where('done', false)->count();
        $doneCount = $tasks->where('done', true)->count();

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
