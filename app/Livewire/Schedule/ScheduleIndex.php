<?php

namespace App\Livewire\Schedule;

use App\Models\PaymentTerm;
use App\Models\Project;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class ScheduleIndex extends Component
{
    #[Url]
    public string $status = '';

    public function setStatus(string $status): void
    {
        $this->status = ($this->status === $status) ? '' : $status;
    }

    #[On('payment-saved')]
    #[On('payment-deleted')]
    public function refreshSchedule(): void
    {
        // Re-renders the component and recalculates KPIs
    }

    public function exportCsv()
    {
        $user = auth()->user();
        $projectIds = Project::forUser($user)->pluck('id');

        $terms = PaymentTerm::query()
            ->whereIn('project_id', $projectIds)
            ->with(['project.customer'])
            ->orderByRaw("CASE status WHEN 'Overdue' THEN 1 WHEN 'Pending' THEN 2 WHEN 'Partial' THEN 3 WHEN 'Paid' THEN 4 WHEN 'Cancelled' THEN 5 ELSE 6 END")
            ->orderBy('due_date')
            ->get();

        $filename = 'payment-schedule-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($terms) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
            fputcsv($handle, ['Customer', 'Project Code', 'Project', '#', 'Installment', 'Trigger', 'Amount', 'Received', 'TDS', 'Balance', 'Due Date', 'Status']);

            foreach ($terms as $t) {
                $bal = max(0, (float) $t->amount - (float) $t->received - (float) $t->tds);
                $triggerLabel = match ($t->trigger_type) {
                    'milestone' => 'Milestone linked',
                    'recurring' => 'Recurring monthly',
                    default => 'Fixed date',
                };

                $dueDate = $t->due_date ? Carbon::parse($t->due_date)->format('Y-m-d') : '';

                fputcsv($handle, [
                    $t->project?->customer?->company ?? '',
                    $t->project?->code ?? '',
                    $t->project?->name ?? '',
                    $t->seq,
                    $t->label,
                    $triggerLabel,
                    $t->amount,
                    $t->received,
                    $t->tds,
                    round($bal, 2),
                    $dueDate,
                    $t->status,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function render()
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        $projectsQuery = Project::forUser($user);
        $projectIds = (clone $projectsQuery)->pluck('id');
        $totalProjectsCount = (clone $projectsQuery)->count();

        $allTerms = PaymentTerm::query()
            ->whereIn('project_id', $projectIds)
            ->with(['project.customer'])
            ->orderByRaw("CASE status WHEN 'Overdue' THEN 1 WHEN 'Pending' THEN 2 WHEN 'Partial' THEN 3 WHEN 'Paid' THEN 4 WHEN 'Cancelled' THEN 5 ELSE 6 END")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')
            ->get();

        // 4 KPI metrics computed across all terms
        $upcomingTerms = $allTerms->whereIn('status', ['Pending', 'Partial']);
        $upcomingSum = (float) $upcomingTerms->sum(fn ($t) => max(0, (float) $t->amount - (float) $t->received - (float) $t->tds));

        $overdueTerms = $allTerms->where('status', 'Overdue');
        $overdueSum = (float) $overdueTerms->sum(fn ($t) => max(0, (float) $t->amount - (float) $t->received - (float) $t->tds));

        $paidTerms = $allTerms->where('status', 'Paid');
        $collectedSum = (float) $allTerms->sum('received');

        $totalScheduledSum = (float) $allTerms->where('status', '!=', 'Cancelled')->sum('amount');

        // Filtered terms for the table
        $terms = $allTerms->when($this->status, function ($collection) {
            if ($this->status === 'Upcoming') {
                return $collection->whereIn('status', ['Pending', 'Partial']);
            }

            return $collection->where('status', $this->status);
        });

        return view('livewire.schedule.schedule-index', [
            'terms' => $terms,
            'totalProjectsCount' => $totalProjectsCount,
            'upcomingSum' => $upcomingSum,
            'upcomingCount' => $upcomingTerms->count(),
            'overdueSum' => $overdueSum,
            'overdueCount' => $overdueTerms->count(),
            'collectedSum' => $collectedSum,
            'paidCount' => $paidTerms->count(),
            'totalScheduledSum' => $totalScheduledSum,
            'today' => $today,
        ])->layoutData([
            'header' => 'Payment Schedule',
            'subtitle' => 'Full installment timeline across all assigned projects',
        ]);
    }
}
