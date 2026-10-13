<?php

namespace App\Livewire\Payments;

use App\Models\Payment;
use App\Models\PaymentTerm;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PaymentIndex extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $mode = '';

    public function updatingSearch(): void {
        $this->resetPage();
    }

    public function updatingMode(): void {
        $this->resetPage();
    }

    public function clearFilters(): void {
        $this->reset(['search', 'mode']);
        $this->resetPage();
    }

    #[On('payment-saved')]
    public function refreshPayments(): void {
        $this->resetPage();
    }

    public function deletePayment(int $id): void {
        $user = auth()->user();
        $payment = Payment::query()
            ->whereHas('project', fn($q) => $q->forUser($user))
            ->findOrFail($id);

        // Coordinators can only delete their own entries unless Admin
        if ($user->role !== 'Admin' && $payment->entered_by_id !== $user->id) {
            $this->dispatch('toast', ['msg' => 'You can only remove receipts you entered.', 'kind' => 'err']);
            return;
        }

        $termId = $payment->term_id;
        $payment->delete();

        if ($termId) {
            $term = PaymentTerm::find($termId);
            if ($term) {
                $remPaid = (float)Payment::where('term_id', $termId)->sum('amount');
                $remTds = (float)Payment::where('term_id', $termId)->sum('tds');
                $term->received = $remPaid;
                $term->tds = $remTds;

                if (($remPaid + $remTds) >= ($term->amount - 0.01)) {
                    $term->status = 'Paid';
                } elseif ($remPaid > 0) {
                    $term->status = 'Partial';
                } else {
                    $dueDate = $term->due_date ? Carbon::parse($term->due_date) : null;
                    $limit = $dueDate ? $dueDate->copy()->addDays($term->grace_days)->toDateString() : null;
                    $term->status = ($limit && $limit < now()->toDateString()) ? 'Overdue' : 'Pending';
                }
                $term->save();
            }
        }

        $this->dispatch('payment-deleted');
    }

    public function exportCsv() {
        $user = auth()->user();
        $payments = Payment::query()
            ->whereHas('project', fn($q) => $q->forUser($user))
            ->with(['project.customer', 'customer', 'term', 'enteredBy'])
            ->orderByDesc('paid_on')
            ->get();

        $filename = 'payments-' . now()->toDateString() . '.csv';

        return response()->streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($handle, ['Receipt', 'Date', 'Customer', 'Project Code', 'Project', 'Amount', 'TDS', 'Mode', 'Reference', 'Bank', 'Entered By', 'Note']);

            foreach ($payments as $p) {
                fputcsv($handle, [
                    $p->code,
                    $p->paid_on?->format('Y-m-d') ?? '',
                    $p->customer?->company ?? '',
                    $p->project?->code ?? '',
                    $p->project?->name ?? '',
                    $p->amount,
                    $p->tds,
                    $p->mode,
                    $p->reference,
                    $p->bank,
                    $p->enteredBy?->name ?? '',
                    $p->note,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function render() {
        $user = auth()->user();

        $query = Payment::query()
            ->whereHas('project', fn($q) => $q->forUser($user))
            ->with(['project.customer', 'customer', 'term', 'enteredBy'])
            ->when($this->search, function ($q) {
                $term = '%' . trim($this->search) . '%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('code', 'like', $term)
                        ->orWhere('reference', 'like', $term)
                        ->orWhere('bank', 'like', $term)
                        ->orWhere('mode', 'like', $term)
                        ->orWhereHas('customer', fn($c) => $c->where('company', 'like', $term))
                        ->orWhereHas('project', fn($p) => $p->where('code', 'like', $term)->orWhere('name', 'like', $term));
                });
            })
            ->when($this->mode, fn($q) => $q->where('mode', $this->mode))
            ->orderByDesc('paid_on')
            ->orderByDesc('id');

        $allMatching = (clone $query)->get();
        $totalAmount = (float)$allMatching->sum('amount');
        $totalTds = (float)$allMatching->sum('tds');

        return view('livewire.payments.payment-index', [
            'payments' => $query->paginate(15),
            'totalAmount' => $totalAmount,
            'totalTds' => $totalTds,
        ])->layoutData([
            'header' => 'Payments',
            'subtitle' => 'Every receipt — with mode and reference',
        ]);
    }
}
