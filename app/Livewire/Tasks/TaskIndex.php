<?php

namespace App\Livewire\Tasks;

use App\Models\Followup;
use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Illuminate\Validation\Rule;
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

    /**
     * Resolves user IDs eligible for task assignment based on caller RBAC permissions.
     *
     * @return array<int>
     */
    protected function getAllowedAssigneeIds(): array
    {
        $currentUser = auth()->user();
        if (! $currentUser) {
            return [];
        }

        return match ($currentUser->role) {
            'Admin' => User::where('status', 'Active')->pluck('id')->all(),
            'Manager', 'Coordinator' => User::where('status', 'Active')->whereIn('id', $currentUser->getDownlineUserIds())->pluck('id')->all(),
            default => [$currentUser->id],
        };
    }

    protected function rules(): array
    {
        $allowedAssigneeIds = $this->getAllowedAssigneeIds();

        return [
            'title' => 'required|string|max:200',
            'project_id' => 'nullable|integer|exists:projects,id',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'due_date' => 'nullable|date',
            'assigned_to' => ['required', 'integer', Rule::in($allowedAssigneeIds)],
        ];
    }

    protected function messages(): array
    {
        return [
            'assigned_to.in' => 'You can only assign tasks to yourself or team members in your downline.',
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
            ->when(auth()->user()->role !== 'Admin', function ($query) {
                $query->where('assigned_to', auth()->id());
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
            ->when(auth()->user()->role !== 'Admin', function ($query) {
                $query->where('assigned_to', auth()->id());
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
        $currentUser = auth()->user();

        // Sanitize empty strings into null before validation
        $this->project_id = ! empty($this->project_id) ? (int) $this->project_id : null;
        $this->due_date = ! empty($this->due_date) ? $this->due_date : null;
        $this->title = trim($this->title);

        if ($currentUser->role === 'Sales') {
            $this->assigned_to = $currentUser->id;
        } else {
            $this->assigned_to = ! empty($this->assigned_to) ? (int) $this->assigned_to : $currentUser->id;
        }

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
            'created_by' => $currentUser->id,
            'done' => false,
        ]);

        app(AuditNotificationService::class)->log(
            $currentUser->id,
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
        $currentUser = auth()->user();
        $today = Carbon::today()->toDateString();

        $query = Task::query()
            ->when($currentUser->role !== 'Admin', fn ($taskQuery) => $taskQuery->where('assigned_to', $currentUser->id))
            ->with(['project.customer', 'customer', 'assignedTo'])
            ->when($this->filterPriority, fn ($taskQuery) => $taskQuery->where('priority', $this->filterPriority))
            ->when($this->filterProjectId, fn ($taskQuery) => $taskQuery->where('project_id', $this->filterProjectId))
            ->when($this->filterAssignedTo, fn ($taskQuery) => $taskQuery->where('assigned_to', $this->filterAssignedTo))
            ->when($this->filterStatus === 'open', fn ($taskQuery) => $taskQuery->where('done', false))
            ->when($this->filterStatus === 'done', fn ($taskQuery) => $taskQuery->where('done', true))
            ->when($this->filterSearch, function ($searchQuery) {
                $term = '%'.trim($this->filterSearch).'%';
                $searchQuery->where(function ($subQuery) use ($term) {
                    $subQuery->where('title', 'like', $term)
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', $term)->orWhere('code', 'like', $term))
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('company', 'like', $term)->orWhere('contact_name', 'like', $term));
                });
            })
            ->orderBy('done')
            ->orderByRaw('due_date IS NULL, due_date ASC');

        $tasks = $query->get();

        $baseTasks = Task::query()
            ->when($currentUser->role !== 'Admin', fn ($taskQuery) => $taskQuery->where('assigned_to', $currentUser->id))
            ->get();
        $openCount = $baseTasks->where('done', false)->count();
        $doneCount = $baseTasks->where('done', true)->count();
        $urgentCount = $baseTasks->where('done', false)->whereIn('priority', ['Urgent', 'High'])->count();

        // Follow-up calls due within 7 days
        $followups = Followup::query()
            ->whereNotNull('next_date')
            ->where('next_date', '<=', Carbon::today()->addDays(7)->toDateString())
            ->when($currentUser->role !== 'Admin', function ($q) use ($currentUser) {
                $q->where('created_by', $currentUser->id)
                    ->orWhereHas('customer', fn ($cq) => $cq->forUser($currentUser));
            })
            ->with(['customer', 'project'])
            ->orderBy('next_date')
            ->get();

        $projects = Project::query()->forUser($currentUser)->orderBy('name')->get();
        $users = User::whereIn('id', $this->getAllowedAssigneeIds())->orderBy('name')->get();

        return view('livewire.tasks.task-index', [
            'tasks' => $tasks,
            'openCount' => $openCount,
            'doneCount' => $doneCount,
            'urgentCount' => $urgentCount,
            'followups' => $followups,
            'projects' => $projects,
            'users' => $users,
            'today' => $today,
        ])->layoutData([
            'header' => 'My Tasks',
            'subtitle' => 'Personal execution checklist and scheduled follow-ups',
        ]);
    }
}
