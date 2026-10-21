<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Services\AuditNotificationService;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class CustomerDetail extends Component
{
    public Customer $customer;

    #[Url(as: 'tab')]
    public string $activeTab = 'overview';

    #[On('payment-saved')]
    #[On('followup-saved')]
    #[On('customer-saved')]
    #[On('document-saved')]
    public function refreshDetails(): void
    {
        $this->customer->refresh();
        $this->customer->load(['payments', 'followups', 'projects', 'documents']);
    }

    public function updatedActiveTab(string $value): void
    {
        if (auth()->user()?->role === 'Sales' && $value === 'payments') {
            $this->activeTab = 'overview';
        }
    }

    public function mount(Customer $customer): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(403, 'Unauthorized.');
        }

        if ($user->role !== 'Admin') {
            $isAllowed = Customer::query()->forUser($user)->where('id', $customer->id)->exists();
            if (! $isAllowed) {
                abort(403, 'Unauthorized access to unassigned customer.');
            }
        }

        if ($user->role === 'Sales' && $this->activeTab === 'payments') {
            $this->activeTab = 'overview';
        }

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

        $code = $this->customer->customer_code;
        $company = $this->customer->display_name;
        $id = $this->customer->id;

        $this->customer->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Customer',
            'Customer',
            $id,
            $code,
            "Deleted customer {$company} and associated records"
        );

        $this->redirect(route('customers.index'), navigate: true);
    }

    public function deletePayment(int $paymentId): void
    {
        $pay = Payment::where('customer_id', $this->customer->id)->findOrFail($paymentId);
        $termId = $pay->term_id;
        $payCode = $pay->code;
        $payAmt = $pay->amount;
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

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Payment',
            'Payment',
            $paymentId,
            $payCode ?: '',
            "Deleted payment receipt {$payCode} of ₹".number_format($payAmt, 2)." from customer {$this->customer->display_name}"
        );

        $this->dispatch('payment-deleted');
    }

    public function deleteDocument(int $docId): void
    {
        $doc = $this->customer->documents()->findOrFail($docId);
        $docName = $doc->file_name;
        if (Storage::disk('uploads')->exists($doc->stored_as)) {
            Storage::disk('uploads')->delete($doc->stored_as);
        }
        Storage::disk('public')->delete($doc->stored_as);
        $doc->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Document',
            'Document',
            $docId,
            $this->customer->customer_code,
            "Deleted document '{$docName}' from customer {$this->customer->display_name}"
        );
    }

    public function deleteFollowup(int $fuId): void
    {
        $fu = $this->customer->followups()->findOrFail($fuId);
        $summary = $fu->summary;
        $fu->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Follow-up',
            'Followup',
            $fuId,
            $this->customer->customer_code,
            "Deleted follow-up '{$summary}' for customer {$this->customer->display_name}"
        );
    }

    public function render()
    {
        $user = auth()->user();

        $projects = $this->customer->projects()
            ->forUser($user)
            ->with(['terms', 'payments', 'milestones'])
            ->get();

        $projectIds = $projects->pluck('id');

        $contractVal = (float) $projects->sum('value');
        $sched = (float) $projects->sum(fn ($project) => $project->money['scheduled']);
        $rec = (float) $projects->sum(fn ($project) => $project->money['received']);
        $tds = (float) $projects->sum(fn ($project) => $project->money['tds']);
        $overdue = (float) $projects->sum(fn ($project) => $project->money['overdue']);
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
                'contract' => $contractVal,
                'scheduled' => $sched,
                'received' => $rec,
                'due' => $due,
                'overdue' => $overdue,
                'rate' => $collRate,
            ],
        ])->layoutData([
            'header' => $this->customer->display_name,
            'subtitle' => $this->customer->code.($this->customer->city ? ' · '.$this->customer->city : '').($this->customer->salesPerson ? ' · onboarded by '.$this->customer->salesPerson->name : ''),
        ]);
    }
}
