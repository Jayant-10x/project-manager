<?php

namespace App\Livewire\Reports;

use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class ReportIndex extends Component {
    public function render() {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();
        // Automatically aggregates downline projects via updated scopeForUser()
        $projectsQuery = Project::query()->forUser($user)->with(['milestones', 'terms', 'payments', 'customer']);
        $projects = $projectsQuery->get();
        $projectIds = $projects->pluck('id');

        // 1. KPI Aggregates
        $totalReceived = (float)Payment::whereIn('project_id', $projectIds)->sum('amount');
        $totalScheduled = (float)PaymentTerm::whereIn('project_id', $projectIds)
            ->where('status', '!=', 'Cancelled')
            ->sum('amount');
        $totalTds = (float)Payment::whereIn('project_id', $projectIds)->sum('tds');

        $overdueTerms = PaymentTerm::whereIn('project_id', $projectIds)
            ->where('status', 'Overdue')
            ->get();
        $overdueAmt = (float)$overdueTerms->sum(fn($t) => max(0, $t->amount - $t->received - $t->tds));
        $overdueCount = $overdueTerms->count();

        $collectionRate = $totalScheduled > 0 ? round(($totalReceived / $totalScheduled) * 100, 1) : 0;
        $avgProjectValue = $projects->count() > 0 ? round($totalScheduled / $projects->count(), 2) : 0;

        // 2. 6-Month Collection Trend
        $months = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthObj = Carbon::now()->subMonths($i);
            $startM = $monthObj->copy()->startOfMonth()->toDateString();
            $endM = $monthObj->copy()->endOfMonth()->toDateString();

            $amt = (float)Payment::whereIn('project_id', $projectIds)
                ->whereBetween('paid_on', [$startM, $endM])
                ->sum('amount');

            $months[] = ['label' => $monthObj->format('M'), 'value' => $amt];
        }

        // 3. Payment Mode Breakdown
        $modeData = Payment::whereIn('project_id', $projectIds)
            ->select('mode', DB::raw('SUM(amount) as val'))
            ->groupBy('mode')
            ->pluck('val', 'mode')
            ->toArray();

        $modes = [];
        foreach ($modeData as $mName => $mAmt) {
            $modes[] = ['label' => $mName, 'value' => (float)$mAmt];
        }
        usort($modes, fn($a, $b) => $b['value'] <=> $a['value']);

        // 4. Payment Ageing Breakdown (0-30, 31-60, 61-90, 90+)
        $ageing = ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];
        foreach ($overdueTerms as $ot) {
            $bal = max(0, (float)$ot->amount - (float)$ot->received - (float)$ot->tds);
            $days = $ot->due_date ? abs(Carbon::parse($today)->diffInDays($ot->due_date, false)) : 0;
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

        // 6. Milestone vs Collection Variance Rows
        $varianceRows = [];
        foreach ($projects as $p) {
            $totMs = $p->milestones->count();
            $doneMs = $p->milestones->where('done', true)->count();
            $msPct = $totMs > 0 ? min(100, round(($doneMs / $totMs) * 100)) : 0;

            $pSched = (float)$p->money['scheduled'];
            $pRec = (float)$p->money['received'];
            $moneyPct = $pSched > 0 ? min(100, round(($pRec / $pSched) * 100)) : 0;

            $gap = $msPct - $moneyPct;

            $varianceRows[] = [
                'project' => $p,
                'msPct' => $msPct,
                'moneyPct' => $moneyPct,
                'gap' => $gap,
                'due' => (float)$p->money['due'],
                'health' => $p->health,
            ];
        }

        usort($varianceRows, fn($a, $b) => $b['gap'] <=> $a['gap']);

        return view('livewire.reports.report-index', [
            'totalReceived' => $totalReceived,
            'totalScheduled' => $totalScheduled,
            'totalTds' => $totalTds,
            'overdueAmt' => $overdueAmt,
            'overdueCount' => $overdueCount,
            'collectionRate' => $collectionRate,
            'avgProjectValue' => $avgProjectValue,
            'projectsCount' => $projects->count(),
            'trend' => $months,
            'modes' => $modes,
            'ageing' => $ageing,
            'healthDistribution' => $healthDistribution,
            'varianceRows' => $varianceRows,
        ])->layoutData([
            'header' => 'Reports',
            'subtitle' => 'Collection, ageing and sales performance',
        ]);
    }
}
