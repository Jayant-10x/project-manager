<?php

namespace App\Livewire\Schedule;

use App\Models\Customer;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Services\AuditNotificationService;
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

    #[Url]
    public string $search = '';

    #[Url]
    public string $customer_id = '';

    #[Url]
    public string $trigger_type = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->hasPermission('schedules', 'view')) {
            abort(403, 'Unauthorized access to payment schedule.');
        }
    }

    public function setStatus(string $status): void
    {
        $this->status = ($this->status === $status) ? '' : $status;
    }

    public function clearFilters(): void
    {
        $this->reset(['status', 'search', 'customer_id', 'trigger_type']);
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

        $allTerms = PaymentTerm::forUser($user)
            ->with(['project.customer'])
            ->orderByRaw("CASE status WHEN 'Overdue' THEN 1 WHEN 'Pending' THEN 2 WHEN 'Partial' THEN 3 WHEN 'Paid' THEN 4 WHEN 'Cancelled' THEN 5 ELSE 6 END")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')
            ->get();

        $terms = $allTerms
            ->when($this->status, function ($collection) {
                if ($this->status === 'Upcoming') {
                    return $collection->whereIn('status', ['Pending', 'Partial']);
                }

                return $collection->where('status', $this->status);
            })
            ->when($this->trigger_type, function ($collection) {
                return $collection->where('trigger_type', $this->trigger_type);
            })
            ->when($this->customer_id, function ($collection) {
                return $collection->where('project.customer_id', (int) $this->customer_id);
            })
            ->when($this->search, function ($collection) {
                $searchQuery = strtolower(trim($this->search));

                return $collection->filter(function ($term) use ($searchQuery) {
                    return str_contains(strtolower($term->label ?? ''), $searchQuery) ||
                           str_contains(strtolower($term->project?->name ?? ''), $searchQuery) ||
                           str_contains(strtolower($term->project?->code ?? ''), $searchQuery) ||
                           str_contains(strtolower($term->project?->customer?->display_name ?? ''), $searchQuery);
                });
            });

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Exported CSV',
            'Schedule',
            null,
            '',
            "Exported {$terms->count()} schedule payment terms to CSV"
        );

        $filename = 'payment-schedule-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($terms) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM for Excel UTF-8
            fputcsv($handle, ['Customer', 'Project Code', 'Project', '#', 'Installment', 'Trigger', 'Amount', 'Received', 'TDS', 'Balance', 'Due Date', 'Status']);

            foreach ($terms as $term) {
                $balance = max(0, (float) $term->amount - (float) $term->received - (float) $term->tds);
                $triggerLabel = match ($term->trigger_type) {
                    'milestone' => 'Milestone linked',
                    'recurring' => 'Recurring monthly',
                    default => 'Fixed date',
                };

                $dueDate = $term->due_date ? Carbon::parse($term->due_date)->format('Y-m-d') : '';

                fputcsv($handle, [
                    $term->project?->customer?->display_name ?? '',
                    $term->project?->code ?? '',
                    $term->project?->name ?? '',
                    $term->seq,
                    $term->label,
                    $triggerLabel,
                    $term->amount,
                    $term->received,
                    $term->tds,
                    round($balance, 2),
                    $dueDate,
                    $term->status,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function render()
    {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        $totalProjectsCount = Project::forUser($user)->count();

        $allTerms = PaymentTerm::forUser($user)
            ->with(['project.customer'])
            ->orderByRaw("CASE status WHEN 'Overdue' THEN 1 WHEN 'Pending' THEN 2 WHEN 'Partial' THEN 3 WHEN 'Paid' THEN 4 WHEN 'Cancelled' THEN 5 ELSE 6 END")
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')
            ->get();

        // 4 KPI metrics computed across all terms
        $upcomingTerms = $allTerms->whereIn('status', ['Pending', 'Partial']);
        $upcomingSum = (float) $upcomingTerms->sum(fn ($term) => max(0, (float) $term->amount - (float) $term->received - (float) $term->tds));

        $overdueTerms = $allTerms->where('status', 'Overdue');
        $overdueSum = (float) $overdueTerms->sum(fn ($term) => max(0, (float) $term->amount - (float) $term->received - (float) $term->tds));

        $paidTerms = $allTerms->where('status', 'Paid');
        $cashCollected = (float) $allTerms->sum('received');
        $tdsCollected = (float) $allTerms->sum('tds');
        $collectedSum = round($cashCollected + $tdsCollected, 2); // Option B: Gross Settled Revenue

        $totalScheduledSum = (float) $allTerms->where('status', '!=', 'Cancelled')->sum('amount');

        // Filtered terms for the table
        $terms = $allTerms
            ->when($this->status, function ($collection) {
                if ($this->status === 'Upcoming') {
                    return $collection->whereIn('status', ['Pending', 'Partial']);
                }

                return $collection->where('status', $this->status);
            })
            ->when($this->trigger_type, function ($collection) {
                return $collection->where('trigger_type', $this->trigger_type);
            })
            ->when($this->customer_id, function ($collection) {
                return $collection->where('project.customer_id', (int) $this->customer_id);
            })
            ->when($this->search, function ($collection) {
                $searchQuery = strtolower(trim($this->search));

                return $collection->filter(function ($term) use ($searchQuery) {
                    return str_contains(strtolower($term->label ?? ''), $searchQuery) ||
                           str_contains(strtolower($term->project?->name ?? ''), $searchQuery) ||
                           str_contains(strtolower($term->project?->code ?? ''), $searchQuery) ||
                           str_contains(strtolower($term->project?->customer?->display_name ?? ''), $searchQuery);
                });
            });

        $customers = Customer::query()
            ->whereHas('projects', fn ($projectQuery) => $projectQuery->forUser($user))
            ->orderBy('company')
            ->get();

        return view('livewire.schedule.schedule-index', [
            'terms' => $terms,
            'customers' => $customers,
            'totalProjectsCount' => $totalProjectsCount,
            'upcomingSum' => $upcomingSum,
            'upcomingCount' => $upcomingTerms->count(),
            'overdueSum' => $overdueSum,
            'overdueCount' => $overdueTerms->count(),
            'collectedSum' => $collectedSum,
            'cashCollected' => $cashCollected,
            'tdsCollected' => $tdsCollected,
            'paidCount' => $paidTerms->count(),
            'totalScheduledSum' => $totalScheduledSum,
            'today' => $today,
        ])->layoutData([
            'header' => 'Payment Schedule',
            'subtitle' => 'Full installment timeline across all assigned projects',
        ]);
    }
}
