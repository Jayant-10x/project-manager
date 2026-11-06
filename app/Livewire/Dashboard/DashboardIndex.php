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

    #[Url(as: 'period')]
    public string $datePreset = 'today';

    #[Url(as: 'from')]
    public ?string $dateFrom = null;

    #[Url(as: 'to')]
    public ?string $dateTo = null;

    public function mount(): void
    {
        if (empty($this->datePreset)) {
            $this->datePreset = 'today';
        }

        if ($this->datePreset !== 'custom') {
            [$from, $to] = $this->calculateDatesForPreset($this->datePreset);
            $this->dateFrom = $from;
            $this->dateTo = $to;
        } elseif (! $this->dateFrom || ! $this->dateTo) {
            $this->dateFrom = Carbon::today()->toDateString();
            $this->dateTo = Carbon::today()->toDateString();
        }
    }

    public function selectPreset(string $preset): void
    {
        $this->datePreset = $preset;
        if ($preset !== 'custom') {
            [$from, $to] = $this->calculateDatesForPreset($preset);
            $this->dateFrom = $from;
            $this->dateTo = $to;
        }
    }

    public function applyCustomRange(?string $from = null, ?string $to = null): void
    {
        $from = $from ?: ($this->dateFrom ?: Carbon::today()->toDateString());
        $to = $to ?: ($this->dateTo ?: Carbon::today()->toDateString());

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $this->datePreset = 'custom';
        $this->dateFrom = $from;
        $this->dateTo = $to;
    }

    public function calculateDatesForPreset(string $preset): array
    {
        return match ($preset) {
            'today' => [
                Carbon::today()->toDateString(),
                Carbon::today()->toDateString(),
            ],
            'yesterday' => [
                Carbon::yesterday()->toDateString(),
                Carbon::yesterday()->toDateString(),
            ],
            'last_7_days' => [
                Carbon::today()->subDays(6)->toDateString(),
                Carbon::today()->toDateString(),
            ],
            'last_30_days' => [
                Carbon::today()->subDays(29)->toDateString(),
                Carbon::today()->toDateString(),
            ],
            'this_week' => [
                Carbon::now()->startOfWeek()->toDateString(),
                Carbon::now()->endOfWeek()->toDateString(),
            ],
            'last_week' => [
                Carbon::now()->subWeek()->startOfWeek()->toDateString(),
                Carbon::now()->subWeek()->endOfWeek()->toDateString(),
            ],
            'this_month' => [
                Carbon::now()->startOfMonth()->toDateString(),
                Carbon::now()->endOfMonth()->toDateString(),
            ],
            'last_month' => [
                Carbon::now()->subMonthNoOverflow()->startOfMonth()->toDateString(),
                Carbon::now()->subMonthNoOverflow()->endOfMonth()->toDateString(),
            ],
            'this_quarter' => [
                Carbon::now()->startOfQuarter()->toDateString(),
                Carbon::now()->endOfQuarter()->toDateString(),
            ],
            'last_quarter' => [
                Carbon::now()->subQuarter()->startOfQuarter()->toDateString(),
                Carbon::now()->subQuarter()->endOfQuarter()->toDateString(),
            ],
            'ytd' => [
                Carbon::now()->startOfYear()->toDateString(),
                Carbon::today()->toDateString(),
            ],
            'last_year' => [
                Carbon::now()->subYear()->startOfYear()->toDateString(),
                Carbon::now()->subYear()->endOfYear()->toDateString(),
            ],
            'all_time' => [
                null,
                null,
            ],
            default => [
                Carbon::today()->toDateString(),
                Carbon::today()->toDateString(),
            ],
        };
    }

    public function resolveActiveDateRange(): array
    {
        if ($this->datePreset === 'all_time') {
            return [null, null];
        }

        if ($this->datePreset === 'custom') {
            if ($this->dateFrom && $this->dateTo) {
                return [
                    min($this->dateFrom, $this->dateTo),
                    max($this->dateFrom, $this->dateTo),
                ];
            }

            return [Carbon::today()->toDateString(), Carbon::today()->toDateString()];
        }

        return $this->calculateDatesForPreset($this->datePreset);
    }

    public function getPeriodLabel(): string
    {
        return match ($this->datePreset) {
            'today' => 'Today',
            'yesterday' => 'Yesterday',
            'last_7_days' => 'Last 7 Days',
            'last_30_days' => 'Last 30 Days',
            'this_week' => 'This Week',
            'last_week' => 'Last Week',
            'this_month' => 'This Month',
            'last_month' => 'Last Month',
            'this_quarter' => 'This Quarter',
            'last_quarter' => 'Last Quarter',
            'ytd' => 'Year to Date (YTD)',
            'last_year' => 'Last Year',
            'all_time' => 'All Time',
            'custom' => ($this->dateFrom && $this->dateTo)
                ? Carbon::parse($this->dateFrom)->format('d M Y').' – '.Carbon::parse($this->dateTo)->format('d M Y')
                : 'Custom Range',
            default => 'Today',
        };
    }

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
            $downlineIds = current_user_downline_ids(excludeSelf: true);
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

        [$startDate, $endDate] = $this->resolveActiveDateRange();

        // Common Core Money metrics (scoped to visible projects & active date range)
        $sched = (float) PaymentTerm::whereIn('project_id', $projectIds)
            ->where('status', '!=', 'Cancelled')
            ->sum('amount');

        $paymentsQuery = Payment::query()
            ->whereIn('project_id', $projectIds);

        if ($startDate && $endDate) {
            $paymentsQuery->whereBetween('paid_on', [$startDate, $endDate]);
        }

        $rec = (float) (clone $paymentsQuery)->sum('amount');
        $tds = (float) (clone $paymentsQuery)->sum('tds');
        $settled = round($rec + $tds, 2);

        $dueQuery = PaymentTerm::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', '!=', 'Cancelled')
            ->whereIn('status', ['Pending', 'Partial', 'Overdue']);

        if ($startDate && $endDate) {
            $dueQuery->whereBetween('due_date', [$startDate, $endDate]);
        }

        $due = (float) $dueQuery->selectRaw('COALESCE(SUM(CASE WHEN amount > (COALESCE(received, 0) + COALESCE(tds, 0)) THEN amount - COALESCE(received, 0) - COALESCE(tds, 0) ELSE 0 END), 0) as val')->value('val');
        $due = round(max(0, $due), 2);

        $overdueTermsQuery = PaymentTerm::query()
            ->whereIn('project_id', $projectIds)
            ->where('status', '!=', 'Cancelled')
            ->where('status', 'Overdue');

        if ($startDate && $endDate) {
            $overdueTermsQuery->where('due_date', '<=', $endDate);
        }

        $overdueSum = (float) (clone $overdueTermsQuery)->selectRaw('COALESCE(SUM(CASE WHEN amount > (COALESCE(received, 0) + COALESCE(tds, 0)) THEN amount - COALESCE(received, 0) - COALESCE(tds, 0) ELSE 0 END), 0) as val')->value('val');
        $overdueCount = (clone $overdueTermsQuery)->count();

        $thisMonthRec = (float) Payment::whereIn('project_id', $projectIds)
            ->whereBetween('paid_on', [Carbon::now()->startOfMonth()->toDateString(), Carbon::now()->endOfMonth()->toDateString()])
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
                $startOfMonth = $monthObj->copy()->startOfMonth()->toDateString();
                $endOfMonth = $monthObj->copy()->endOfMonth()->toDateString();

                $monthlyValue = (float) Payment::whereIn('project_id', $projectIds)
                    ->whereBetween('paid_on', [$startOfMonth, $endOfMonth])
                    ->sum('amount');

                $trend[] = [
                    'label' => $monthObj->format('M'),
                    'value' => $monthlyValue,
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

            $salesPeople = $salesScopeQuery->with(['salesProjects' => function ($subQuery) use ($startDate, $endDate) {
                $subQuery->with(['terms' => function ($tq) {
                    $tq->where('status', '!=', 'Cancelled');
                }, 'payments' => function ($pq) use ($startDate, $endDate) {
                    if ($startDate && $endDate) {
                        $pq->whereBetween('paid_on', [$startDate, $endDate]);
                    }
                }, 'customer']);
            }])
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

        // Payment Aging (for Admin & Manager)
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
                    'projects as active_projects_count' => fn ($projectQuery) => $projectQuery->where('status', 'In Progress'),
                    'projects as delayed_projects_count' => fn ($projectQuery) => $projectQuery->where('health', 'Delayed'),
                    'projects as on_track_projects_count' => fn ($projectQuery) => $projectQuery->where('health', 'On Track'),
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
                ->where(function ($milestoneQuery) {
                    $milestoneQuery->where('done', true)->orWhere('status', 'Done');
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

            $cashRealized = (float) (clone $paymentsQuery)->sum('amount');
            $tdsRealized = (float) (clone $paymentsQuery)->sum('tds');
            $grossRealized = round($cashRealized + $tdsRealized, 2);
            $realizationRate = $totalDealsValue > 0 ? min(100, round(($grossRealized / $totalDealsValue) * 100)) : 0;
            $customersAcquired = Customer::where('sales_person_id', $user->id)->count();

            $recentDeals = (clone $salesProjects)
                ->with('customer')
                ->orderByDesc('id')
                ->limit(4)
                ->get();

            $salesDueQuery = PaymentTerm::query()
                ->whereIn('project_id', $projectIds)
                ->where('status', '!=', 'Cancelled')
                ->whereIn('status', ['Pending', 'Partial', 'Overdue']);

            if ($startDate && $endDate) {
                $salesDueQuery->whereBetween('due_date', [$startDate, $endDate]);
            }

            $dueAmount = (float) $salesDueQuery->selectRaw('COALESCE(SUM(CASE WHEN amount > (COALESCE(received, 0) + COALESCE(tds, 0)) THEN amount - COALESCE(received, 0) - COALESCE(tds, 0) ELSE 0 END), 0) as val')->value('val');
            $dueAmount = round(max(0, $dueAmount), 2);

            $salesOverdueTerms = PaymentTerm::query()
                ->whereIn('project_id', $projectIds)
                ->where('status', '!=', 'Cancelled')
                ->where('status', 'Overdue');

            if ($startDate && $endDate) {
                $salesOverdueTerms->where('due_date', '<=', $endDate);
            }

            $salesOverdueSum = (float) (clone $salesOverdueTerms)->selectRaw('COALESCE(SUM(CASE WHEN amount > (COALESCE(received, 0) + COALESCE(tds, 0)) THEN amount - COALESCE(received, 0) - COALESCE(tds, 0) ELSE 0 END), 0) as val')->value('val');
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
            'datePreset' => $this->datePreset,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'periodLabel' => $this->getPeriodLabel(),
            'startDate' => $startDate,
            'endDate' => $endDate,
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
