<?php

namespace App\Services;

use App\Models\PaymentTerm;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MoneyCalculationService
{
    /**
     * Compute Gross Settled Revenue: Cash Received + TDS Deducted.
     */
    public function calculateGrossSettled(float $received, float $tds): float
    {
        return round($received + $tds, 2);
    }

    /**
     * Compute Net Balance Due: max(0, Total Scheduled - Cash Received - TDS).
     */
    public function calculateBalanceDue(float $scheduled, float $received, float $tds): float
    {
        return round(max(0, $scheduled - $received - $tds), 2);
    }

    /**
     * Compute Collection / Realization Rate (%):
     * ((Cash Received + TDS) / Total Scheduled) * 100
     */
    public function calculateCollectionRate(float $scheduled, float $received, float $tds): float
    {
        if ($scheduled <= 0) {
            return 0.0;
        }

        $gross = $this->calculateGrossSettled($received, $tds);

        return round(($gross / $scheduled) * 100, 1);
    }

    public function refreshOverdueStatus(): void
    {
        $today = Carbon::today()->toDateString();

        $isSqlite = DB::getDriverName() === 'sqlite';
        $graceExpr = $isSqlite
            ? "date(due_date, '+' || coalesce(grace_days, 0) || ' days')"
            : 'DATE_ADD(due_date, INTERVAL COALESCE(grace_days, 0) DAY)';

        PaymentTerm::query()
            ->whereIn('status', ['Pending', 'Partial'])
            ->whereNotNull('due_date')
            ->whereRaw("{$graceExpr} < ?", [$today])
            ->update(['status' => 'Overdue']);

        PaymentTerm::query()
            ->where('status', 'Overdue')
            ->whereNotNull('due_date')
            ->whereRaw("{$graceExpr} >= ?", [$today])
            ->update([
                'status' => DB::raw("CASE WHEN received > 0 THEN 'Partial' ELSE 'Pending' END"),
            ]);
    }
}
