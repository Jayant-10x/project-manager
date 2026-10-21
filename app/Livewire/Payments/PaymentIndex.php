<?php

namespace App\Livewire\Payments;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class PaymentIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $mode = '';

    #[Url]
    public string $customer_id = '';

    #[Url]
    public string $date_from = '';

    #[Url]
    public string $date_to = '';

    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || $user->role === 'Sales') {
            abort(403, 'Unauthorized access to payment ledger.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingMode(): void
    {
        $this->resetPage();
    }

    public function updatingCustomerId(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'mode', 'customer_id', 'date_from', 'date_to']);
        $this->resetPage();
    }

    #[On('payment-saved')]
    public function refreshPayments(): void
    {
        $this->resetPage();
    }

    public function deletePayment(int $id): void
    {
        $user = auth()->user();
        $payment = Payment::query()
            ->whereHas('project', fn ($q) => $q->forUser($user))
            ->findOrFail($id);

        // Coordinators can only delete their own entries unless Admin
        if ($user->role !== 'Admin' && $payment->created_by !== $user->id) {
            $this->dispatch('toast', ['msg' => 'You can only remove receipts you entered.', 'type' => 'err']);

            return;
        }

        $termId = $payment->term_id;
        $payment->delete();

        if ($termId) {
            $term = PaymentTerm::find($termId);
            if ($term) {
                $remPaid = (float) Payment::where('term_id', $termId)->sum('amount');
                $remTds = (float) Payment::where('term_id', $termId)->sum('tds');
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

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Payment',
            'Payment',
            $payment->id,
            $payment->code,
            "Deleted payment receipt {$payment->code} of ₹".number_format($payment->amount, 2)
        );

        $this->dispatch('payment-deleted');
    }

    public function exportCsv()
    {
        $user = auth()->user();
        $payments = Payment::query()
            ->whereHas('project', fn ($q) => $q->forUser($user))
            ->with(['project.customer', 'customer', 'term', 'enteredBy'])
            ->when($user->role === 'Coordinator', function ($q) {
                $q->whereHas('project.terms', function ($t) {
                    $t->whereIn('status', ['Pending', 'Partial', 'Overdue']);
                });
            })
            ->when($this->mode, fn ($builder) => $builder->where('mode', $this->mode))
            ->when($this->customer_id, fn ($builder) => $builder->where('customer_id', $this->customer_id))
            ->when($this->date_from, fn ($builder) => $builder->whereDate('paid_on', '>=', $this->date_from))
            ->when($this->date_to, fn ($builder) => $builder->whereDate('paid_on', '<=', $this->date_to))
            ->when($this->search, function ($builder) {
                $searchTerm = '%'.trim($this->search).'%';
                $builder->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('code', 'like', $searchTerm)
                        ->orWhere('reference', 'like', $searchTerm)
                        ->orWhere('bank', 'like', $searchTerm)
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('company', 'like', $searchTerm)->orWhere('contact_name', 'like', $searchTerm))
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', $searchTerm)->orWhere('project_code', 'like', $searchTerm));
                });
            })
            ->orderByDesc('paid_on')
            ->get();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Exported CSV',
            'Payment',
            null,
            '',
            "Exported {$payments->count()} payment receipts to CSV"
        );

        $filename = 'payments-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($payments) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($handle, ['Receipt', 'Date', 'Customer', 'Project Code', 'Project', 'Amount', 'TDS', 'Mode', 'Reference', 'Bank', 'Entered By', 'Note']);

            foreach ($payments as $payment) {
                fputcsv($handle, [
                    $payment->code,
                    $payment->paid_on?->format('Y-m-d') ?? '',
                    $payment->customer?->display_name ?? '',
                    $payment->project?->code ?? '',
                    $payment->project?->name ?? '',
                    $payment->amount,
                    $payment->tds,
                    $payment->mode,
                    $payment->reference,
                    $payment->bank,
                    $payment->enteredBy?->name ?? '',
                    $payment->note,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function render()
    {
        $user = auth()->user();

        $query = Payment::query()
            ->whereHas('project', fn ($projectQuery) => $projectQuery->forUser($user))
            ->with(['project.customer', 'customer', 'term', 'enteredBy'])
            ->when($user->role === 'Coordinator', function ($coordinatorQuery) {
                // Coordinators only see payments with active due balances
                $coordinatorQuery->whereHas('project.terms', function ($termQuery) {
                    $termQuery->whereIn('status', ['Pending', 'Partial', 'Overdue']);
                });
            })
            ->when($this->mode, fn ($builder) => $builder->where('mode', $this->mode))
            ->when($this->customer_id, fn ($builder) => $builder->where('customer_id', $this->customer_id))
            ->when($this->date_from, fn ($builder) => $builder->whereDate('paid_on', '>=', $this->date_from))
            ->when($this->date_to, fn ($builder) => $builder->whereDate('paid_on', '<=', $this->date_to))
            ->when($this->search, function ($builder) {
                $searchTerm = '%'.trim($this->search).'%';
                $builder->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('code', 'like', $searchTerm)
                        ->orWhere('reference', 'like', $searchTerm)
                        ->orWhere('bank', 'like', $searchTerm)
                        ->orWhereHas('customer', fn ($customerQuery) => $customerQuery->where('company', 'like', $searchTerm)->orWhere('contact_name', 'like', $searchTerm))
                        ->orWhereHas('project', fn ($projectQuery) => $projectQuery->where('name', 'like', $searchTerm)->orWhere('project_code', 'like', $searchTerm));
                });
            })
            ->orderByDesc('paid_on')
            ->orderByDesc('id');

        $allMatching = (clone $query)->get();
        $totalAmount = (float) $allMatching->sum('amount');
        $totalTds = (float) $allMatching->sum('tds');

        $customers = Customer::query()
            ->whereHas('projects', fn ($projectQuery) => $projectQuery->forUser($user))
            ->orderBy('company')
            ->get();

        return view('livewire.payments.payment-index', [
            'payments' => $query->paginate(15),
            'customers' => $customers,
            'totalAmount' => $totalAmount,
            'totalTds' => $totalTds,
        ])->layoutData([
            'header' => 'Payments',
            'subtitle' => 'Every receipt — with mode and reference',
        ]);
    }
}
