<?php

namespace App\Livewire\Components;

use App\Models\Customer;
use App\Models\Followup;
use App\Models\Notification;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use Livewire\Attributes\On;
use Livewire\Component;

class DynamicCounter extends Component
{
    public string $type;

    public string $class = 'nav-count';

    public bool $warn = false;

    public bool $hideZero = false;

    public ?string $poll = null;

    public function mount(
        string $type,
        string $class = 'nav-count',
        bool $warn = false,
        bool $hideZero = false,
        ?string $poll = null
    ): void {
        $this->type = $type;
        $this->class = $class;
        $this->warn = $warn;
        $this->hideZero = $hideZero;
        $this->poll = $poll;
    }

    #[On('counter-refresh')]
    #[On('customer-saved')]
    #[On('customer-deleted')]
    #[On('project-saved')]
    #[On('project-deleted')]
    #[On('task-saved')]
    #[On('task-updated')]
    #[On('followup-saved')]
    #[On('payment-saved')]
    #[On('payment-deleted')]
    #[On('notification-updated')]
    public function refreshCounter(): void
    {
        // Re-renders the component dynamically
    }

    public function getCountProperty(): int
    {
        $user = auth()->user();
        if (! $user) {
            return 0;
        }

        return match ($this->type) {
            'customers' => Customer::forUser($user)->count(),
            'projects' => Project::forUser($user)->count(),
            'tasks' => Task::where('assigned_to', $user->id)->where('done', false)->count(),
            'today' => $this->getTodayCount($user),
            'notifications' => Notification::where('user_id', $user->id)->where('is_read', false)->count(),
            default => 0,
        };
    }

    protected function getTodayCount($user): int
    {
        $assignedProjectIds = Project::forUser($user)->pluck('id');
        $overdueCount = PaymentTerm::whereIn('project_id', $assignedProjectIds)->where('status', 'Overdue')->count();
        $followupAlertDays = (int) Setting::getValue('followup_alert_days', 1);
        $fupCount = Followup::whereNotNull('next_date')
            ->where('next_date', '<=', now()->addDays($followupAlertDays)->toDateString())
            ->count();

        return $overdueCount + $fupCount;
    }

    public function render()
    {
        return view('livewire.components.dynamic-counter');
    }
}
