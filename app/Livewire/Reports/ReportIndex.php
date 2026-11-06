<?php

namespace App\Livewire\Reports;

use App\Models\Customer;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ReportIndex extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || ! $user->hasPermission('reports', 'view')) {
            abort(403, 'Unauthorized access to Reports module.');
        }
    }

    public function render()
    {
        $user = auth()->user();
        $role = $user->role ?? 'Sales';
        $today = Carbon::today()->toDateString();

        // Projects scoped to user's downline authority
        $projectsQuery = Project::query()->forUser($user)->with(['milestones', 'terms', 'payments', 'customer']);
        $projects = $projectsQuery->get();
        $projectIds = $projects->pluck('id');

        // 1. Core Financial Aggregates
        $totalCash = (float) Payment::whereIn('project_id', $projectIds)->sum('amount');
        $totalTds = (float) Payment::whereIn('project_id', $projectIds)->sum('tds');
        $totalReceived = round($totalCash + $totalTds, 2); // Option B: Gross Realized Revenue (Cash + TDS)
        $totalScheduled = (float) PaymentTerm::whereIn('project_id', $projectIds)
            ->where('status', '!=', 'Cancelled')
            ->sum('amount');
        $totalDue = round(max(0, $totalScheduled - $totalCash - $totalTds), 2);

        $overdueTerms = PaymentTerm::whereIn('project_id', $projectIds)
            ->where('status', 'Overdue')
            ->get();
        $overdueAmount = (float) $overdueTerms->sum(fn ($term) => max(0, $term->amount - $term->received - $term->tds));
        $overdueCount = $overdueTerms->count();

        $collectionRate = $totalScheduled > 0 ? round(($totalReceived / $totalScheduled) * 100, 1) : 0;
        $totalContractValue = (float) $projects->sum('value');
        $avgProjectValue = $projects->count() > 0 ? round($totalContractValue / $projects->count(), 2) : 0;

        // 2. 6-Month Realized Revenue Trend
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthObj = Carbon::now()->subMonths($i);
            $startOfMonth = $monthObj->copy()->startOfMonth()->toDateString();
            $endOfMonth = $monthObj->copy()->endOfMonth()->toDateString();

            $monthlyAmount = (float) Payment::whereIn('project_id', $projectIds)
                ->whereBetween('paid_on', [$startOfMonth, $endOfMonth])
                ->sum('amount');

            $months[] = ['label' => $monthObj->format('M'), 'value' => $monthlyAmount];
        }

        // 3. Payment Mode Breakdown
        $modeData = Payment::whereIn('project_id', $projectIds)
            ->select('mode', DB::raw('SUM(amount) as val'))
            ->groupBy('mode')
            ->pluck('val', 'mode')
            ->toArray();

        $modes = [];
        foreach ($modeData as $mName => $mAmt) {
            $modes[] = ['label' => $mName, 'value' => (float) $mAmt];
        }
        usort($modes, fn ($firstMode, $secondMode) => $secondMode['value'] <=> $firstMode['value']);

        // 4. Payment Aging Breakdown (0-30, 31-60, 61-90, 90+)
        $ageing = ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];
        foreach ($overdueTerms as $overdueTerm) {
            $bal = max(0, (float) $overdueTerm->amount - (float) $overdueTerm->received - (float) $overdueTerm->tds);
            $days = $overdueTerm->due_date ? (int) round(abs(Carbon::parse($today)->startOfDay()->diffInDays(Carbon::parse($overdueTerm->due_date)->startOfDay(), false))) : 0;
            if ($days <= 30) {
                $ageing['0-30'] += $bal;
            } elseif ($days <= 60) {
                $ageing['31-60'] += $bal;
            } elseif ($days <= 90) {
                $ageing['61-90'] += $bal;
            } else {
                $ageing['90+'] += $bal;
            }
        }

        // 5. Project Health Distribution
        $healthDistribution = [
            'On Track' => $projects->where('health', 'On Track')->count(),
            'At Risk' => $projects->where('health', 'At Risk')->count(),
            'Delayed' => $projects->where('health', 'Delayed')->count(),
            'Completed' => $projects->where('health', 'Completed')->count(),
        ];

        // 6. Milestone vs Payment Variance Rows
        $varianceRows = [];
        foreach ($projects as $project) {
            $totalMilestones = $project->milestones->count();
            $completedMilestones = $project->milestones->where('done', true)->count();
            $milestonePercentage = $totalMilestones > 0 ? min(100, round(($completedMilestones / $totalMilestones) * 100)) : 0;

            $scheduledProjectAmount = (float) $project->money['scheduled'];
            $receivedProjectAmount = (float) $project->money['received'];
            $moneyPercentage = $scheduledProjectAmount > 0 ? min(100, round(($receivedProjectAmount / $scheduledProjectAmount) * 100)) : 0;

            $gap = $milestonePercentage - $moneyPercentage;

            $varianceRows[] = [
                'project' => $project,
                'msPct' => $milestonePercentage,
                'milestonePercentage' => $milestonePercentage,
                'moneyPct' => $moneyPercentage,
                'gap' => $gap,
                'due' => (float) $project->money['due'],
                'health' => $project->health,
            ];
        }
        usort($varianceRows, fn ($rowA, $rowB) => $rowB['gap'] <=> $rowA['gap']);

        // 7. Role-Specific Data Sets
        // Admin: Full Sales Attribution Leaderboard
        $salesRows = [];
        if (in_array($role, ['Admin', 'Manager'], true)) {
            $salesPeopleQuery = User::where('role', 'Sales')->where('status', 'Active')->with(['salesProjects.payments']);
            if ($role === 'Manager') {
                $salesPeopleQuery->whereIn('id', current_user_downline_ids());
            }
            $salesPeople = $salesPeopleQuery->orderBy('name')->get();

            foreach ($salesPeople as $salesPerson) {
                $salesValue = (float) $salesPerson->salesProjects->sum('value');
                $receivedAmount = (float) $salesPerson->salesProjects->flatMap->payments->sum('amount');
                $tdsAmount = (float) $salesPerson->salesProjects->flatMap->payments->sum('tds');
                $settled = round($receivedAmount + $tdsAmount, 2);
                $rate = $salesValue > 0 ? round(($settled / $salesValue) * 100) : 0;
                $salesRows[] = [
                    'name' => $salesPerson->name,
                    'customers' => $salesPerson->salesProjects->pluck('customer_id')->unique()->count(),
                    'projects' => $salesPerson->salesProjects->count(),
                    'value' => $salesValue,
                    'received' => $settled,
                    'cash' => $receivedAmount,
                    'tds' => $tdsAmount,
                    'rate' => min(100, $rate),
                ];
            }
            usort($salesRows, fn ($rowA, $rowB) => $rowB['value'] <=> $rowA['value']);
        }

        // Manager: Coordinator Downline Workload Velocity
        $teamCoordinators = collect();
        if ($role === 'Manager') {
            $teamCoordinators = User::where('role', 'Coordinator')
                ->whereIn('id', $user->getDownlineUserIds())
                ->where('status', 'Active')
                ->withCount([
                    'projects as active_projects_count' => fn ($projectQuery) => $projectQuery->where('status', 'In Progress'),
                    'projects as delayed_projects_count' => fn ($projectQuery) => $projectQuery->where('health', 'Delayed'),
                    'projects as on_track_projects_count' => fn ($projectQuery) => $projectQuery->where('health', 'On Track'),
                ])
                ->orderBy('name')
                ->get();
        }

        // Coordinator & Tech Person: Milestone Velocity & Upcoming Deadlines
        $coordinatorData = [];
        if ($role === 'Coordinator' || $user->isTechPerson()) {
            $isTechPerson = $user->isTechPerson();
            $totalMilestones = Milestone::whereIn('project_id', $projectIds)->count();
            $completedMilestones = Milestone::whereIn('project_id', $projectIds)->where('done', true)->count();
            $milestoneRate = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;

            $upcomingDeliveries = Project::where($isTechPerson ? 'tech_person_id' : 'coordinator_id', $user->id)
                ->where('status', 'In Progress')
                ->whereNotNull('delivery_date')
                ->whereBetween('delivery_date', [$today, Carbon::parse($today)->addDays(60)->toDateString()])
                ->with('customer')
                ->orderBy('delivery_date')
                ->limit(6)
                ->get();

            $coordinatorData = [
                'totalMilestones' => $totalMilestones,
                'completedMilestones' => $completedMilestones,
                'milestoneRate' => $milestoneRate,
                'upcomingDeliveries' => $upcomingDeliveries,
            ];
        }

        // Sales: Deal Origination, Quota Target & Customer Fulfillment
        $salesData = [];
        if ($role === 'Sales') {
            $salesProjects = Project::where('sales_person_id', $user->id);
            $totalDealsValue = (float) (clone $salesProjects)->sum('value');
            $dealsCount = (clone $salesProjects)->count();
            $avgDealSize = $dealsCount > 0 ? round($totalDealsValue / $dealsCount, 2) : 0.0;
            $target = (float) ($user->target ?? 0.0);
            $targetProgress = $target > 0 ? min(100, round(($totalDealsValue / $target) * 100)) : 0;

            $cashRealized = (float) Payment::whereIn('project_id', $projectIds)->sum('amount');
            $tdsRealized = (float) Payment::whereIn('project_id', $projectIds)->sum('tds');
            $grossRealized = round($cashRealized + $tdsRealized, 2);
            $realizationRate = $totalDealsValue > 0 ? min(100, round(($grossRealized / $totalDealsValue) * 100)) : 0;
            $dueAmount = round(max(0, $totalDealsValue - $grossRealized), 2);
            $customersAcquired = Customer::where('sales_person_id', $user->id)->count();

            $deals = (clone $salesProjects)
                ->with(['customer', 'payments'])
                ->orderByDesc('id')
                ->get();

            $salesData = [
                'totalDealsValue' => $totalDealsValue,
                'dealsCount' => $dealsCount,
                'avgDealSize' => $avgDealSize,
                'target' => $target,
                'targetProgress' => $targetProgress,
                'cashRealized' => $cashRealized,
                'tdsRealized' => $tdsRealized,
                'grossRealized' => $grossRealized,
                'realizationRate' => $realizationRate,
                'dueAmount' => $dueAmount,
                'overdueAmount' => $overdueAmount,
                'overdueCount' => $overdueCount,
                'customersAcquired' => $customersAcquired,
                'deals' => $deals,
            ];
        }

        return view('livewire.reports.report-index', [
            'role' => $role,
            'totalReceived' => $totalReceived,
            'totalCash' => $totalCash,
            'totalScheduled' => $totalScheduled,
            'totalTds' => $totalTds,
            'totalDue' => $totalDue,
            'overdueAmt' => $overdueAmount,
            'overdueAmount' => $overdueAmount,
            'overdueCount' => $overdueCount,
            'collectionRate' => $collectionRate,
            'avgProjectValue' => $avgProjectValue,
            'projectsCount' => $projects->count(),
            'trend' => $months,
            'modes' => $modes,
            'ageing' => $ageing,
            'healthDistribution' => $healthDistribution,
            'varianceRows' => $varianceRows,
            'salesRows' => $salesRows,
            'teamCoordinators' => $teamCoordinators,
            'coordinatorData' => $coordinatorData,
            'salesData' => $salesData,
        ])->layoutData([
            'header' => 'Reports & Analytics',
            'subtitle' => 'Role-filtered collection, delivery velocity and portfolio analytics',
        ]);
    }
}
