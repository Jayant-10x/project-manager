<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PaymentTerm;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class MoneyCalculationService {
    public function calculateProjectMoney(int $projectId): array {
        $dueTotal = (float)PaymentTerm::query()
            ->where('project_id', $projectId)
            ->where('status', '<>', 'Cancelled')
            ->sum('amount');

        $payments = Payment::query()
            ->where('project_id', $projectId)
            ->selectRaw('COALESCE(SUM(amount), 0) as received, COALESCE(SUM(tds), 0) as tds')
            ->first();

        $received = $payments ? (float)$payments->received : 0.0;
        $tds = $payments ? (float)$payments->tds : 0.0;

        return [
            'scheduled' => round($dueTotal, 2),
            'received' => round($received, 2),
            'tds' => round($tds, 2),
            'due' => round(max(0, $dueTotal - $received - $tds), 2),
        ];
    }

    public function recomputeTerm(int $termId): void {
        $term = PaymentTerm::query()->find($termId);
        if (!$term) {
            return;
        }

        $summary = Payment::query()
            ->where('term_id', $termId)
            ->selectRaw('COALESCE(SUM(amount), 0) as paid, COALESCE(SUM(tds), 0) as tds')
            ->first();

        $paid = $summary ? (float)$summary->paid : 0.0;
        $tds = $summary ? (float)$summary->tds : 0.0;
        $amount = (float)$term->amount;

        if ($term->status === 'Cancelled') {
            $status = 'Cancelled';
        } elseif (($paid + $tds) >= ($amount - 0.01)) {
            $status = 'Paid';
        } elseif ($paid > 0) {
            $status = 'Partial';
        } else {
            $limit = $term->due_date ? Carbon::parse($term->due_date)->addDays((int)$term->grace_days)->toDateString() : null;
            $status = ($limit && $limit < Carbon::today()->toDateString()) ? 'Overdue' : 'Pending';
        }

        $term->update([
            'received' => $paid,
            'tds' => $tds,
            'status' => $status,
        ]);
    }

    public function refreshOverdueStatus(): void {
        $today = Carbon::today()->toDateString();

        PaymentTerm::query()
            ->whereIn('status', ['Pending', 'Partial'])
            ->whereNotNull('due_date')
            ->whereRaw('DATE_ADD(due_date, INTERVAL grace_days DAY) < ?', [$today])
            ->update(['status' => 'Overdue']);

        DB::statement('
            UPDATE payment_terms
            SET status = IF(received > 0, "Partial", "Pending")
            WHERE status = "Overdue"
              AND (due_date IS NULL OR DATE_ADD(due_date, INTERVAL grace_days DAY) >= ?)
        ', [$today]);
    }
}
