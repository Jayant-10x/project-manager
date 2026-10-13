<?php

namespace App\Livewire\Today;

use App\Models\Followup;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\Setting;
use App\Models\Task;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class TodayWorkIndex extends Component {
    #[On('payment-saved')]
    #[On('payment-deleted')]
    #[On('project-saved')]
    #[On('task-saved')]
    public function refreshWorkQueue(): void {
        // Re-renders the operational triage queue
    }

    public function render() {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        $alertDays = (int)Setting::getValue('payment_alert_days', 7);
        $soonDate = Carbon::today()->addDays($alertDays)->toDateString();
        $followupSoon = Carbon::today()->addDays(7)->toDateString();

        // Scope to current coordinator unless Admin
        $projectsQuery = Project::query()->forUser($user);
        $projectIds = (clone $projectsQuery)->pluck('id');

        // 1. Overdue payments
        $overdue = PaymentTerm::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'Overdue')
            ->with(['project.customer'])
            ->orderBy('due_date')
            ->get();

        // 2. Upcoming payments within alert window
        $soon = PaymentTerm::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('status', ['Pending', 'Partial'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $soonDate])
            ->with(['project.customer'])
            ->orderBy('due_date')
            ->get();

        // 3. Follow-up calls due within 7 days
        $followups = Followup::query()
            ->whereNotNull('next_date')
            ->where('next_date', '<=', $followupSoon)
            ->where(function ($q) use ($projectIds) {
                $q->whereIn('project_id', $projectIds)->orWhereNull('project_id');
            })
            ->with('customer')
            ->orderBy('next_date')
            ->get();

        // 4. Projects Needing Attention (Delayed or At Risk)
        $lateProjects = Project::query()
            ->forUser($user)
            ->whereIn('health', ['Delayed', 'At Risk'])
            ->with(['customer', 'terms', 'payments'])
            ->orderBy('delivery_date')
            ->get();

        // Financial totals
        $totalOverdue = (float)$overdue->sum(fn($t) => max(0, (float)$t->amount - (float)$t->received - (float)$t->tds));
        $totalSoon = (float)$soon->sum(fn($t) => max(0, (float)$t->amount - (float)$t->received - (float)$t->tds));
        $openTasksCount = Task::where('assigned_to_id', $user->id)->where('done', false)->count();

        return view('livewire.today.today-work-index', [
            'overdue' => $overdue,
            'soon' => $soon,
            'totalOverdue' => $totalOverdue,
            'totalSoon' => $totalSoon,
            'followups' => $followups,
            'lateProjects' => $lateProjects,
            'openTasks' => $openTasksCount,
            'alertDays' => $alertDays,
            'today' => $today,
        ])->layoutData([
            'header' => "Today's Work",
            'subtitle' => 'Who to follow up with today, and what money is coming in',
        ]);
    }
}
