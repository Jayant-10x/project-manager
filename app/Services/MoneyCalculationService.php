<?php

namespace App\Services;

use App\Models\PaymentTerm;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MoneyCalculationService {
    public function refreshOverdueStatus(): void {
        $today = Carbon::today()->toDateString();

        PaymentTerm::query()
            ->whereIn('status', ['Pending', 'Partial'])
            ->whereNotNull('due_date')
            ->whereRaw('DATE_ADD(due_date, INTERVAL grace_days DAY) < ?', [$today])
            ->update(['status' => 'Overdue']);

        PaymentTerm::query()
            ->where('status', 'Overdue')
            ->whereNotNull('due_date')
            ->whereRaw('DATE_ADD(due_date, INTERVAL grace_days DAY) >= ?', [$today])
            ->update([
                'status' => DB::raw("CASE WHEN received > 0 THEN 'Partial' ELSE 'Pending' END")
            ]);
    }
}
