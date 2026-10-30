<?php

namespace App\Livewire\Dashboard;

use App\Models\Customer;
use App\Models\Milestone;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use App\Services\MoneyCalculationService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class DashboardIndex extends Component
{
    #[Url]
    public ?int $selectedUserId = null;

    #[On('payment-saved')]
    #[On('payment-deleted')]
    #[On('project-saved')]
    #[On('customer-saved')]
    #[On('counter-refresh')]
    public function refreshDashboard(): void
    {
        // Re-renders the dashboard reactively
    }

    public function render(MoneyCalculationService $moneyService)
    {
        $user = auth()->user();
        $moneyService->refreshOverdueStatus();

        $today = Carbon::today()->toDateString();
        $role = $user->role ?? 'Unknown';

        // Downline / Team filter setup
        $accessibleUsers = collect();
        if ($role === 'Admin') {
            $accessibleUsers = User::where('status', 'Active')
                ->where('id', '!=', $user->id)
                ->where('role', '!=', 'TechPerson')
                ->orderBy('role')
                ->orderBy('name', 'asc')
                ->get();
        } elseif ($role === 'Manager' || $role === 'Coordinator') {
            $downlineIds = array_diff($user->getDownlineUserIds(), [$user->id]);
            $accessibleUsers = User::whereIn('id', $downlineIds)
                ->where('status', 'Active')
                ->where('role', '!=', 'TechPerson')
                ->orderBy('role')
                ->orderBy('name')
                ->get();
        }

        // Active viewing target: either portfolio or selected downline person
        $activeViewingUser = $user;
        if ($this->selectedUserId && $accessibleUsers->contains('id', (int) $this->selectedUserId)) {
            $activeViewingUser = User::find($this->selectedUserId) ?? $user;
        }

        // Role-scoped projects for active viewing user
        $projectsQuery = Project::query()->forUser($activeViewingUser);
        $projectIds = (clone $projectsQuery)->pluck('id');

        // Common Core Money metrics (scoped to visible projects)
        $sched = (float) PaymentTerm::whereIn('project_id', $projectIds)
            ->where('status', '!=', 'Cancelled')
            ->sum('amount');

        $rec = (float) Payment::whereIn('project_id', $projectIds)->sum('amount');
        $tds = (float) Payment::whereIn('project_id', $projectIds)->sum('tds');
        $settled = round($rec + $tds, 2);
        $due = round(max(0, $sched - $rec - $tds), 2);

        $overdueTermsQuery = PaymentTerm::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', 'Overdue');

        $overdueSum = (float) (clone $overdueTermsQuery)->selectRaw('COALESCE(SUM(amount - received - tds), 0) as val')->value('val');
        $overdueCount = (clone $overdueTermsQuery)->count();

        $thisMonthRec = (float) Payment::whereIn('project_id', $projectIds)
            ->where('paid_on', '>=', Carbon::now()->startOfMonth()->toDateString())
            ->sum('amount');

        $collectionRate = $sched > 0 ? round(($settled / $sched) * 100, 1) : 0;

        // Project Health Breakdown (scoped to user)
        $healthRaw = Project::query()
            ->forUser($activeViewingUser)
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

        // 6-Month Collection Trend (for Admin, Manager, Coordinator)
        $trend = [];
        if ($role !== 'Sales' && ! $user->isTechPerson()) {
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
        }

        // Sales-wise performance (for Admin & Manager)
        $salesRows = [];
        if (in_array($role, ['Admin', 'Manager'])) {
            $salesScopeQuery = User::where('role', 'Sales')->where('status', 'Active');
            if ($role === 'Manager') {
                $downlineIds = $user->getDownlineUserIds();
                $salesScopeQuery->whereIn('id', $downlineIds);
            }

            $salesPeople = $salesScopeQuery->with(['salesProjects' => fn ($subQuery) => $subQuery->with(['terms', 'payments', 'customer'])])
                ->orderBy('name')
                ->get();

            foreach ($salesPeople as $salesPerson) {
                $projs = $salesPerson->salesProjects;
                $valAmt = (float) $projs->sum('value');
                $recAmt = (float) $projs->flatMap->payments->sum('amount');
                $tdsAmt = (float) $projs->flatMap->payments->sum('tds');
                $settledAmt = round($recAmt + $tdsAmt, 2);
                $rate = $valAmt > 0 ? round(($settledAmt / $valAmt) * 100) : 0;

                $salesRows[] = [
                    'name' => $salesPerson->name,
                    'customers' => $projs->pluck('customer_id')->unique()->count(),
                    'projects' => $projs->count(),
                    'value' => $valAmt,
                    'received' => $settledAmt,
                    'cash' => $recAmt,
                    'tds' => $tdsAmt,
                    'rate' => min(100, $rate),
                ];
            }

            usort($salesRows, fn ($firstRow, $secondRow) => $secondRow['value'] <=> $firstRow['value']);
        }

        // Payment Ageing (for Admin & Manager)
        $ageing = ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];
        if (in_array($role, ['Admin', 'Manager'])) {
            $allOverdue = (clone $overdueTermsQuery)->get(['amount', 'received', 'tds', 'due_date']);
            foreach ($allOverdue as $ot) {
                $bal = max(0, (float) $ot->amount - (float) $ot->received - (float) $ot->tds);
                $days = $ot->due_date ? (int) round(Carbon::parse($today)->startOfDay()->diffInDays(Carbon::parse($ot->due_date)->startOfDay(), false)) : 0;
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
        }

        // Manager Highlight: Team Coordinator Workload & Delivery
        $teamCoordinators = collect();
        if ($role === 'Manager') {
            $downlineIds = $user->getDownlineUserIds();
            $teamCoordinators = User::where('role', 'Coordinator')
                ->whereIn('id', $downlineIds)
                ->where('status', 'Active')
                ->withCount([
                    'projects as active_projects_count' => fn ($q) => $q->where('status', 'In Progress'),
                    'projects as delayed_projects_count' => fn ($q) => $q->where('health', 'Delayed'),
                    'projects as on_track_projects_count' => fn ($q) => $q->where('health', 'On Track'),
                ])
                ->orderBy('name')
                ->get();
        }

        // Coordinator & Tech Person Highlights: Upcoming Delivery Deadlines & Milestones Completion
        $coordinatorData = [];
        if ($role === 'Coordinator' || $user->isTechPerson()) {
            $isTechPerson = $user->isTechPerson();
            $activeProjectsCount = Project::where($isTechPerson ? 'tech_person_id' : 'coordinator_id', $user->id)
                ->where('status', 'In Progress')
                ->count();

            $totalMilestones = Milestone::whereIn('project_id', $projectIds)->count();
            $completedMilestones = Milestone::whereIn('project_id', $projectIds)
                ->where(function ($q) {
                    $q->where('done', true)->orWhere('status', 'Done');
                })->count();
            $milestoneRate = $totalMilestones > 0 ? round(($completedMilestones / $totalMilestones) * 100) : 0;

            $upcomingDeliveries = Project::where($isTechPerson ? 'tech_person_id' : 'coordinator_id', $user->id)
                ->where('status', 'In Progress')
                ->whereNotNull('delivery_date')
                ->whereBetween('delivery_date', [$today, Carbon::parse($today)->addDays(30)->toDateString()])
                ->with('customer')
                ->orderBy('delivery_date')
                ->limit(4)
                ->get();

            $coordinatorData = [
                'activeProjectsCount' => $activeProjectsCount,
                'totalMilestones' => $totalMilestones,
                'completedMilestones' => $completedMilestones,
                'milestoneRate' => $milestoneRate,
                'upcomingDeliveries' => $upcomingDeliveries,
            ];
        }

        // Sales Highlights: Target Achievement, Deal Pipeline & Fulfillment
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
            $customersAcquired = Customer::where('sales_person_id', $user->id)->count();

            $recentDeals = (clone $salesProjects)
                ->with('customer')
                ->orderByDesc('id')
                ->limit(4)
                ->get();

            $dueAmount = round(max(0, $totalDealsValue - $grossRealized), 2);

            $salesOverdueTerms = PaymentTerm::query()
                ->whereIn('project_id', $projectIds)
                ->where('status', 'Overdue');
            $salesOverdueSum = (float) (clone $salesOverdueTerms)->selectRaw('COALESCE(SUM(amount - received - tds), 0) as val')->value('val');
            $salesOverdueCount = (clone $salesOverdueTerms)->count();

            $thisMonthCollections = (float) Payment::whereIn('project_id', $projectIds)
                ->where('paid_on', '>=', Carbon::now()->startOfMonth()->toDateString())
                ->sum('amount');

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
                'overdueAmount' => $salesOverdueSum,
                'overdueCount' => $salesOverdueCount,
                'thisMonthCollections' => $thisMonthCollections,
                'customersAcquired' => $customersAcquired,
                'recentDeals' => $recentDeals,
            ];
        }

        return view('livewire.dashboard.dashboard-index', [
            'm' => [
                'scheduled' => $sched,
                'received' => $settled,
                'settled' => $settled,
                'cash' => $rec,
                'tds' => $tds,
                'due' => $due,
                'overdue' => $overdueSum,
                'thisMonth' => $thisMonthRec,
                'collectionRate' => $collectionRate,
            ],
            'overdueCount' => $overdueCount,
            'healthCounts' => $healthCounts,
            'totalProjectsCount' => $totalProjectsCount,
            'trend' => $trend,
            'salesRows' => $salesRows,
            'ageing' => $ageing,
            'teamCoordinators' => $teamCoordinators,
            'coordinatorData' => $coordinatorData,
            'salesData' => $salesData,
            'accessibleUsers' => $accessibleUsers,
            'selectedUserId' => $this->selectedUserId,
            'activeViewingUser' => $activeViewingUser,
            'user' => $user,
            'role' => $role,
        ])->layoutData([
            'header' => 'Dashboard',
            'subtitle' => match ($role) {
                'Admin' => 'Executive Company Overview & Financial Governance',
                'Manager' => 'Operations, Team Delivery & Revenue Execution',
                'Coordinator' => 'Project Milestones & Delivery Management',
                'TechPerson' => 'Project Execution & Milestone Delivery',
                'Sales' => 'Sales Performance & Customer Portfolio',
                default => 'Executive Portfolio & Financial Overview',
            },
        ]);
    }
}
