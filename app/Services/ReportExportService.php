<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService {
    public function exportCsv(string $kind): StreamedResponse {
        $today = Carbon::today()->toDateString();
        $filename = "{$kind}-{$today}.csv";

        return response()->streamDownload(function () use ($kind) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel rupee & unicode support

            match ($kind) {
                'customers' => $this->streamCustomers($handle),
                'payments' => $this->streamPayments($handle),
                'overdue' => $this->streamOverdue($handle),
                'schedule' => $this->streamSchedule($handle),
                'sales' => $this->streamSales($handle),
                default => fputcsv($handle, ['Error', 'Unknown export type']),
            };

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    private function streamCustomers($handle): void {
        fputcsv($handle, ['Code', 'Company', 'Contact', 'Phone', 'Email', 'City', 'GSTIN', 'Source', 'Onboarded By', 'Status', 'Projects', 'Total Value', 'Received', 'Due']);
        $customers = Customer::query()
            ->select('customers.*')
            ->selectSub('SELECT COUNT(*) FROM projects WHERE projects.customer_id = customers.id', 'proj_count')
            ->selectSub('SELECT COALESCE(SUM(value), 0) FROM projects WHERE projects.customer_id = customers.id', 'proj_value')
            ->selectSub('SELECT COALESCE(SUM(amount), 0) FROM payments WHERE payments.customer_id = customers.id', 'received')
            ->orderBy('company')
            ->get();

        foreach ($customers as $c) {
            $due = round((float)$c->proj_value - (float)$c->received, 2);
            fputcsv($handle, [
                $c->code, $c->company, $c->contact_name, $c->phone, $c->email, $c->city,
                $c->gstin, $c->source, $c->onboarded_by, $c->status,
                $c->proj_count, $c->proj_value, $c->received, $due
            ]);
        }
    }

    private function streamPayments($handle): void {
        fputcsv($handle, ['Receipt', 'Date', 'Customer', 'Project Code', 'Project', 'Amount', 'TDS', 'Mode', 'Reference', 'Bank', 'Entered By', 'Note']);
        $payments = Payment::query()
            ->with(['customer', 'project'])
            ->orderByDesc('paid_on')
            ->orderByDesc('id')
            ->get();

        foreach ($payments as $p) {
            fputcsv($handle, [
                $p->code, $p->paid_on->toDateString(), $p->customer?->company, $p->project?->code,
                $p->project?->name, $p->amount, $p->tds, $p->mode, $p->reference, $p->bank,
                $p->entered_by, $p->note
            ]);
        }
    }

    private function streamOverdue($handle): void {
        fputcsv($handle, ['Customer', 'Project Code', 'Project', 'Installment', 'Amount', 'Received', 'Balance', 'Due Date', 'Days Overdue']);
        $terms = PaymentTerm::query()
            ->with(['project.customer'])
            ->where('status', 'Overdue')
            ->orderBy('due_date')
            ->get();

        $today = Carbon::today();
        foreach ($terms as $t) {
            $balance = max(0, $t->amount - $t->received - $t->tds);
            $days = $t->due_date ? Carbon::parse($t->due_date)->diffInDays($today) : 0;
            fputcsv($handle, [
                $t->project?->customer?->company, $t->project?->code, $t->project?->name,
                $t->label, $t->amount, $t->received, $balance, $t->due_date?->toDateString(), $days
            ]);
        }
    }

    private function streamSchedule($handle): void {
        fputcsv($handle, ['Customer', 'Project Code', 'Project', '#', 'Installment', 'Trigger', 'Amount', 'Received', 'TDS', 'Balance', 'Due Date', 'Status']);
        $terms = PaymentTerm::query()
            ->with(['project.customer'])
            ->join('projects', 'projects.id', '=', 'payment_terms.project_id')
            ->orderBy('projects.code')
            ->orderBy('payment_terms.seq')
            ->select('payment_terms.*')
            ->get();

        foreach ($terms as $t) {
            $balance = round(max(0, $t->amount - $t->received - $t->tds), 2);
            fputcsv($handle, [
                $t->project?->customer?->company, $t->project?->code, $t->project?->name,
                $t->seq, $t->label, $t->trigger_type, $t->amount, $t->received, $t->tds,
                $balance, $t->due_date?->toDateString(), $t->status
            ]);
        }
    }

    private function streamSales($handle): void {
        fputcsv($handle, ['Sales Person', 'Customers', 'Projects', 'Total Value', 'Received']);
        $salesList = DB::table('customers as c')
            ->leftJoin('projects as p', 'p.customer_id', '=', 'c.id')
            ->where('c.onboarded_by', '<>', '')
            ->selectRaw('c.onboarded_by as sp, COUNT(DISTINCT c.id) as cust, COUNT(DISTINCT p.id) as proj, COALESCE(SUM(p.value), 0) as val')
            ->groupBy('c.onboarded_by')
            ->get();

        foreach ($salesList as $s) {
            $received = (float)Payment::query()
                ->whereHas('customer', fn($q) => $q->where('onboarded_by', $s->sp))
                ->sum('amount');

            fputcsv($handle, [$s->sp, $s->cust, $s->proj, $s->val, $received]);
        }
    }
}
