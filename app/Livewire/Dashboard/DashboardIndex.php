<?php

namespace App\Livewire\Dashboard;

use App\Models\Followup;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\Setting;
use App\Services\MoneyCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class DashboardIndex extends Component
{
    #[On('payment-saved')]
    #[On('payment-deleted')]
    #[On('project-saved')]
    #[On('customer-saved')]
    public function refreshDashboard(): void
    {
        // Re-renders the dashboard reactively
    }

    public function render(MoneyCalculationService $moneyService)
    {
        $user = auth()->user();
        $moneyService->refreshOverdueStatus();

        $today = Carbon::today()->toDateString();
        $alertDays = (int) Setting::getValue('payment_alert_days', 7);
        $soonDate = Carbon::today()->addDays($alertDays)->toDateString();
        $followupAlertDays = (int) Setting::getValue('followup_alert_days', 1);
        $followupSoon = Carbon::today()->addDays($followupAlertDays)->toDateString();

        // Strict role scoping
        $projectsQuery = Project::query()->forUser($user);
        $projectIds = (clone $projectsQuery)->pluck('id');

        // 1. Money metrics
        $sched = (float) PaymentTerm::whereIn('project_id', $projectIds)
            ->where('status', '!=', 'Cancelled')
            ->sum('amount');

        $rec = (float) Payment::whereIn('project_id', $projectIds)->sum('amount');
        $tds = (float) Payment::whereIn('project_id', $projectIds)->sum('tds');
        $due = round(max(0, $sched - $rec - $tds), 2);

        $overdueTermsQuery = PaymentTerm::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'Overdue');

        $overdueSum = (float) (clone $overdueTermsQuery)->selectRaw('COALESCE(SUM(amount - received - tds), 0) as val')->value('val');
        $overdueCount = (clone $overdueTermsQuery)->count();

        $thisMonthRec = (float) Payment::whereIn('project_id', $projectIds)
            ->where('paid_on', '>=', Carbon::now()->startOfMonth()->toDateString())
            ->sum('amount');

        $collectionRate = $sched > 0 ? round(($rec / $sched) * 100, 1) : 0;

        // 2. Project Health breakdown
        $healthRaw = Project::query()
            ->forUser($user)
            ->select('health', DB::raw('count(*) as count'))
            ->groupBy('health')
            ->pluck('count', 'health')
            ->toArray();

        $healthCounts = [
            'On Track' => $healthRaw['On Track'] ?? 0,
            'At Risk' => $healthRaw['At Risk'] ?? 0,
            'Delayed' => $healthRaw['Delayed'] ?? 0,
            'Completed' => $healthRaw['Completed'] ?? 0,
        ];
        $totalProjectsCount = array_sum($healthCounts);

        // 3. Mini-list Today's work
        $topOverdue = (clone $overdueTermsQuery)
            ->with(['project.customer'])
            ->orderBy('due_date')
            ->limit(3)
            ->get();

        $topFollowups = Followup::query()
            ->whereNotNull('next_date')
            ->where('next_date', '<=', $followupSoon)
            ->where(function ($q) use ($projectIds) {
                $q->whereIn('project_id', $projectIds)->orWhereNull('project_id');
            })
            ->with('customer')
            ->orderBy('next_date')
            ->limit(3)
            ->get();

        $topLateProjects = Project::query()
            ->forUser($user)
            ->whereIn('health', ['Delayed', 'At Risk'])
            ->with('customer')
            ->orderBy('delivery_date')
            ->limit(2)
            ->get();

        // 4. Upcoming & Full Overdue terms
        $upcomingTerms = PaymentTerm::query()
            ->whereIn('project_id', $projectIds)
            ->whereIn('status', ['Pending', 'Partial'])
            ->whereNotNull('due_date')
            ->whereBetween('due_date', [$today, $soonDate])
            ->with(['project.customer'])
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $fullOverdueTerms = (clone $overdueTermsQuery)
            ->with(['project.customer'])
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        // 5. 6-Month Collection Trend
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthObj = Carbon::now()->subMonths($i);
            $startM = $monthObj->copy()->startOfMonth()->toDateString();
            $endM = $monthObj->copy()->endOfMonth()->toDateString();

            $val = (float) Payment::whereIn('project_id', $projectIds)
                ->whereBetween('paid_on', [$startM, $endM])
                ->sum('amount');

            $trend[] = [
                'label' => $monthObj->format('M'),
                'value' => $val,
            ];
        }

        // 6. Sales-wise performance
        $salesRows = [];
        if ($user->role === 'Admin') {
            $salesData = DB::table('customers')
                ->leftJoin('projects', 'projects.customer_id', '=', 'customers.id')
                ->whereNotNull('customers.sales_person_id')
                ->leftJoin('users as sales_reps', 'sales_reps.id', '=', 'customers.sales_person_id')
                ->select(
                    'sales_reps.name',
                    DB::raw('COUNT(DISTINCT customers.id) as customers_count'),
                    DB::raw('COUNT(DISTINCT projects.id) as projects_count'),
                    DB::raw('COALESCE(SUM(projects.value), 0) as total_val')
                )
                ->groupBy('sales_reps.name')
                ->orderByDesc('total_val')
                ->get();

            $salesReceipts = DB::table('payments')
                ->join('customers', 'customers.id', '=', 'payments.customer_id')
                ->whereNotNull('customers.sales_person_id')
                ->leftJoin('users as sales_reps', 'sales_reps.id', '=', 'customers.sales_person_id')
                ->groupBy('sales_reps.name')
                ->select('sales_reps.name', DB::raw('COALESCE(SUM(payments.amount), 0) as received'))
                ->pluck('received', 'name')
                ->toArray();

            foreach ($salesData as $sd) {
                $recAmt = (float) ($salesReceipts[$sd->name] ?? 0);
                $valAmt = (float) $sd->total_val;
                $rate = $valAmt > 0 ? round(($recAmt / $valAmt) * 100) : 0;
                $salesRows[] = [
                    'name' => $sd->name ?? 'Unassigned',
                    'customers' => $sd->customers_count,
                    'projects' => $sd->projects_count,
                    'value' => $valAmt,
                    'received' => $recAmt,
                    'rate' => min(100, $rate),
                ];
            }
        }

        // 7. AMC / Renewal
        $isSqlite = DB::getDriverName() === 'sqlite';
        $amcExpr = $isSqlite
            ? "amc_end <= date('now', '+' || coalesce(renew_alert_days, 7) || ' days')"
            : 'amc_end <= DATE_ADD(CURDATE(), INTERVAL COALESCE(renew_alert_days, 7) DAY)';

        $amcProjects = Project::query()
            ->forUser($user)
            ->where('amc_required', 1)
            ->whereNotNull('amc_end')
            ->whereRaw($amcExpr)
            ->with('customer')
            ->orderBy('amc_end')
            ->limit(6)
            ->get();

        // 8. Handover Pending
        $handoverProjects = Project::query()
            ->forUser($user)
            ->where('status', 'Delivered')
            ->where('handover_done', 0)
            ->with('customer')
            ->limit(6)
            ->get();

        // 9. Payment Ageing (0-30, 31-60, 61-90, 90+)
        $ageing = ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];
        $allOverdue = (clone $overdueTermsQuery)->get(['amount', 'received', 'tds', 'due_date']);
        foreach ($allOverdue as $ot) {
            $bal = max(0, (float) $ot->amount - (float) $ot->received - (float) $ot->tds);
            $days = $ot->due_date ? Carbon::parse($today)->diffInDays(Carbon::parse($ot->due_date), false) : 0;
            $overDays = abs($days);
            if ($overDays <= 30) {
                $ageing['0-30'] += $bal;
            } elseif ($overDays <= 60) {
                $ageing['31-60'] += $bal;
            } elseif ($overDays <= 90) {
                $ageing['61-90'] += $bal;
            } else {
                $ageing['90+'] += $bal;
            }
        }

        return view('livewire.dashboard.dashboard-index', [
            'm' => [
                'scheduled' => $sched,
                'received' => $rec,
                'due' => $due,
                'overdue' => $overdueSum,
                'thisMonth' => $thisMonthRec,
                'collectionRate' => $collectionRate,
            ],
            'overdueCount' => $overdueCount,
            'healthCounts' => $healthCounts,
            'totalProjectsCount' => $totalProjectsCount,
            'topOverdue' => $topOverdue,
            'topFollowups' => $topFollowups,
            'topLateProjects' => $topLateProjects,
            'upcomingTerms' => $upcomingTerms,
            'fullOverdueTerms' => $fullOverdueTerms,
            'trend' => $trend,
            'salesRows' => $salesRows,
            'amcProjects' => $amcProjects,
            'handoverProjects' => $handoverProjects,
            'ageing' => $ageing,
            'alertDays' => $alertDays,
            'today' => $today,
        ])->layoutData([
            'header' => 'Dashboard',
            'subtitle' => 'Receivables, projects and everything due today',
        ]);
    }
}
