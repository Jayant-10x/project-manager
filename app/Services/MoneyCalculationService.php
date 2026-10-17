<?php

namespace App\Services;

use App\Models\PaymentTerm;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MoneyCalculationService
{
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
