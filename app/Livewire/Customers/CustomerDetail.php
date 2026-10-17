<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class CustomerDetail extends Component
{
    public Customer $customer;

    public string $activeTab = 'overview';

    #[On('payment-saved')]
    #[On('followup-saved')]
    public function refreshDetails(): void
    {
        $this->customer->load(['payments', 'followups']);
    }

    public function mount(Customer $customer): void
    {
        $this->customer = $customer;
    }

    public function deleteCustomer(): void
    {
        if (auth()->user()->role !== 'Admin') {
            abort(403, 'Only administrators can delete customers.');
        }

        if ($this->customer->projects()->exists()) {
            $this->dispatch('toast', [
                'msg' => 'Cannot delete customer with active projects. Delete or reassign projects first.',
                'type' => 'err',
            ]);

            return;
        }

        // Clean up files on disk
        foreach ($this->customer->documents as $doc) {
            Storage::disk('public')->delete($doc->stored_as);
        }

        $this->customer->delete();
        $this->redirect(route('customers.index'), navigate: true);
    }

    public function deletePayment(int $paymentId): void
    {
        $pay = Payment::where('customer_id', $this->customer->id)->findOrFail($paymentId);
        $termId = $pay->term_id;
        $pay->delete();

        if ($termId) {
            $term = PaymentTerm::find($termId);
            if ($term) {
                $remPaid = (float) Payment::where('term_id', $termId)->sum('amount');
                $remTds = (float) Payment::where('term_id', $termId)->sum('tds');
                $term->received = $remPaid;
                $term->tds = $remTds;
                $term->status = ($remPaid + $remTds >= $term->amount - 0.01) ? 'Paid' : ($remPaid > 0 ? 'Partial' : 'Pending');
                $term->save();
            }
        }
    }

    public function deleteDocument(int $docId): void
    {
        $doc = $this->customer->documents()->findOrFail($docId);
        Storage::disk('public')->delete($doc->stored_as);
        $doc->delete();
    }

    public function deleteFollowup(int $fuId): void
    {
        $this->customer->followups()->findOrFail($fuId)->delete();
    }

    public function render()
    {
        $user = auth()->user();

        $projects = $this->customer->projects()
            ->forUser($user)
            ->with(['terms', 'payments', 'milestones'])
            ->get();

        $projectIds = $projects->pluck('id');

        // Customer financial totals across assigned projects
        $sched = (float) $projects->sum(fn ($p) => $p->money['scheduled']);
        $rec = (float) $projects->sum(fn ($p) => $p->money['received']);
        $tds = (float) $projects->sum(fn ($p) => $p->money['tds']);
        $overdue = (float) $projects->sum(fn ($p) => $p->money['overdue']);
        $due = round(max(0, $sched - $rec - $tds), 2);
        $collRate = $sched > 0 ? min(100, round(($rec / $sched) * 100)) : 0;

        $terms = PaymentTerm::whereIn('project_id', $projectIds)
            ->with('project')
            ->orderBy('due_date')
            ->get();

        $payments = Payment::where('customer_id', $this->customer->id)
            ->with('project')
            ->orderByDesc('paid_on')
            ->get();

        return view('livewire.customers.customer-detail', [
            'projects' => $projects,
            'terms' => $terms,
            'payments' => $payments,
            'documents' => $this->customer->documents()->orderByDesc('id')->get(),
            'followups' => $this->customer->followups()->orderByDesc('happened_on')->get(),
            'money' => [
                'scheduled' => $sched,
                'received' => $rec,
                'due' => $due,
                'overdue' => $overdue,
                'rate' => $collRate,
            ],
        ])->layoutData([
            'header' => $this->customer->company,
            'subtitle' => $this->customer->code.($this->customer->city ? ' · '.$this->customer->city : '').($this->customer->salesPerson ? ' · onboarded by '.$this->customer->salesPerson->name : ''),
        ]);
    }
}
