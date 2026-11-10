<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Services\AuditNotificationService;
use Illuminate\Support\Facades\DB;
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

    #[Url(as: 'doc_search')]
    public string $docSearch = '';

    #[Url(as: 'doc_cat')]
    public string $docCategory = '';

    #[Url(as: 'doc_pjt')]
    public string $docProject = '';

    public function clearDocFilters(): void
    {
        $this->reset(['docSearch', 'docCategory', 'docProject']);
    }

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
        if ((! auth()->user()?->hasPermission('payments', 'view') || auth()->user()?->role === 'Sales') && $value === 'payments') {
            $this->activeTab = 'overview';
        }
        if ($value === 'followups') {
            $this->activeTab = 'overview';
        }
    }

    public function mount(Customer $customer): void
    {
        $user = auth()->user();
        if (! $user || $user->isTechPerson() || ! $user->hasPermission('customers', 'view')) {
            abort(403, 'Unauthorized access to customer module.');
        }

        if ($user->role !== 'Admin') {
            $isAllowed = Customer::query()->forUser($user)->where('id', $customer->id)->exists();
            if (! $isAllowed) {
                abort(403, 'Unauthorized access to unassigned customer.');
            }
        }

        if ((! $user->hasPermission('payments', 'view') || $user->role === 'Sales') && $this->activeTab === 'payments') {
            $this->activeTab = 'overview';
        }

        if ($this->activeTab === 'followups') {
            $this->activeTab = 'overview';
        }

        $this->customer = $customer;
    }

    public function deleteCustomer(): void
    {
        if (auth()->user()->role !== 'Admin') {
            abort(403, 'Only administrators can delete customers.');
        }

        $projectCount = $this->customer->projects()->count();
        if ($projectCount > 0) {
            $this->dispatch('toast', [
                'msg' => "Cannot delete customer with {$projectCount} active project(s). Delete or reassign projects first.",
                'type' => 'err',
            ]);

            return;
        }

        $code = $this->customer->customer_code;
        $company = $this->customer->display_name;
        $id = $this->customer->id;

        $filesToDelete = $this->customer->documents->pluck('stored_as')->filter()->values()->all();
        $auditService = app(AuditNotificationService::class);

        DB::transaction(function () use ($id, $code, $company, $auditService, $filesToDelete) {
            $auditService->notifyCustomerStakeholders(
                $this->customer,
                'Customer Deleted',
                "Customer Deleted: {$company}",
                "Customer {$company} ({$code}) was permanently deleted by ".auth()->user()->name.'.',
                'customer',
                $id
            );

            $this->customer->delete();

            $auditService->log(
                auth()->id(),
                'Deleted Customer',
                'Customer',
                $id,
                $code,
                "Deleted customer {$company} and associated records"
            );

            DB::afterCommit(function () use ($filesToDelete) {
                foreach ($filesToDelete as $storedAs) {
                    if (Storage::disk('documents')->exists($storedAs)) {
                        Storage::disk('documents')->delete($storedAs);
                    }
                    if (Storage::disk('public')->exists($storedAs)) {
                        Storage::disk('public')->delete($storedAs);
                    }
                }
            });
        });

        $this->redirect(route('customers.index'), navigate: true);
    }

    public function approveCustomer(): void
    {
        $user = auth()->user();
        if (! $this->customer->canBeApprovedBy($user)) {
            abort(403, 'Unauthorized to approve this customer.');
        }

        $this->customer->update([
            'approval_status' => 'Approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $auditService = app(AuditNotificationService::class);
        $label = $this->customer->company ?: ($this->customer->contact_name ?: $this->customer->customer_code);

        $auditService->log(
            $user->id,
            'Approved Customer',
            'Customer',
            $this->customer->id,
            $this->customer->customer_code,
            "Approved customer {$label}"
        );

        $creatorId = $this->customer->created_by ?: $this->customer->sales_person_id;
        if ($creatorId && (int) $creatorId !== (int) $user->id) {
            $auditService->notify(
                $creatorId,
                'Customer Approved',
                "Customer Approved: {$label}",
                "Your submitted customer {$label} ({$this->customer->customer_code}) was approved by {$user->name}.",
                'customer',
                $this->customer->id
            );
        }

        $this->refreshDetails();
        $this->dispatch('toast', ['msg' => 'Customer approved successfully.', 'type' => 'ok']);
    }

    public function rejectCustomer(?string $reason = null): void
    {
        $user = auth()->user();
        if (! $this->customer->canBeApprovedBy($user)) {
            abort(403, 'Unauthorized to reject this customer.');
        }

        $cleanReason = trim((string) $reason);

        $this->customer->update([
            'approval_status' => 'Rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => $cleanReason !== '' ? $cleanReason : null,
        ]);

        $auditService = app(AuditNotificationService::class);
        $label = $this->customer->company ?: ($this->customer->contact_name ?: $this->customer->customer_code);
        $reasonText = $cleanReason !== '' ? " (Reason: {$cleanReason})" : '';

        $auditService->log(
            $user->id,
            'Rejected Customer',
            'Customer',
            $this->customer->id,
            $this->customer->customer_code,
            "Rejected customer {$label}{$reasonText}"
        );

        $creatorId = $this->customer->created_by ?: $this->customer->sales_person_id;
        if ($creatorId && (int) $creatorId !== (int) $user->id) {
            $auditService->notify(
                $creatorId,
                'Customer Rejected',
                "Customer Rejected: {$label}",
                "Your submitted customer {$label} ({$this->customer->customer_code}) was rejected by {$user->name}.".($cleanReason !== '' ? " Reason: {$cleanReason}" : ''),
                'customer',
                $this->customer->id
            );
        }

        $this->refreshDetails();
        $this->dispatch('toast', ['msg' => 'Customer rejected.', 'type' => 'warning']);
    }

    public function deletePayment(int $paymentId): void
    {
        $user = auth()->user();
        if ($user->role !== 'Admin' && ! $user->hasPermission('payments', 'delete')) {
            $this->dispatch('toast', ['msg' => 'Only Admin can delete payments.', 'type' => 'err']);

            return;
        }

        $pay = Payment::with('project')->where('customer_id', $this->customer->id)->findOrFail($paymentId);
        $termId = $pay->term_id;
        $payCode = $pay->code;
        $payAmt = $pay->amount;
        $project = $pay->project;
        $pay->delete();

        if ($termId) {
            $term = PaymentTerm::find($termId);
            $term?->recalculateStatus();
        }

        $auditService = app(AuditNotificationService::class);
        $auditService->log(
            auth()->id(),
            'Deleted Payment',
            'Payment',
            $paymentId,
            $payCode ?: '',
            "Deleted payment receipt {$payCode} of ₹".number_format($payAmt, 2)." from customer {$this->customer->display_name}"
        );

        if ($project) {
            $auditService->notifyProjectStakeholders(
                $project,
                'Payment Deleted',
                "Payment Deleted: {$payCode}",
                "Payment receipt {$payCode} of ₹".number_format($payAmt, 2)." for project {$project->name} was deleted by ".auth()->user()->name.'.',
                'project',
                $project->id
            );
        } else {
            $auditService->notifyCustomerStakeholders(
                $this->customer,
                'Payment Deleted',
                "Payment Deleted: {$payCode}",
                "Payment receipt {$payCode} of ₹".number_format($payAmt, 2).' was deleted by '.auth()->user()->name.'.',
                'customer',
                $this->customer->id
            );
        }

        $this->dispatch('payment-deleted');
    }

    public function deleteDocument(int $docId): void
    {
        $user = auth()->user();
        $doc = $this->customer->documents()->findOrFail($docId);

        if ($user->role !== 'Admin' && $doc->uploaded_by !== $user->id) {
            $this->dispatch('toast', ['msg' => 'You can only remove files you uploaded.', 'type' => 'err']);

            return;
        }

        $docName = $doc->file_name;
        if (Storage::disk('documents')->exists($doc->stored_as)) {
            Storage::disk('documents')->delete($doc->stored_as);
        }
        if (Storage::disk('public')->exists($doc->stored_as)) {
            Storage::disk('public')->delete($doc->stored_as);
        }
        $doc->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Document',
            'Document',
            $docId,
            $this->customer->customer_code,
            "Deleted document '{$docName}' from customer {$this->customer->display_name}"
        );

        $this->dispatch('toast', ['msg' => 'Document removed', 'type' => 'ok']);
    }

    public function deleteFollowup(int $followupId): void
    {
        $user = auth()->user();
        $followup = $this->customer->followups()->findOrFail($followupId);

        if ($user->role !== 'Admin' && $followup->created_by !== $user->id) {
            $this->dispatch('toast', ['msg' => 'You can only remove follow-ups you logged.', 'type' => 'err']);

            return;
        }

        $summary = $followup->summary;
        $followup->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted Follow-up',
            'Followup',
            $followupId,
            $this->customer->customer_code,
            "Deleted follow-up '{$summary}' for customer {$this->customer->display_name}"
        );

        $this->dispatch('toast', ['msg' => 'Follow-up removed', 'type' => 'ok']);
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
        $cash = (float) $projects->sum(fn ($project) => $project->money['cash']);
        $tds = (float) $projects->sum(fn ($project) => $project->money['tds']);
        $rec = round($cash + $tds, 2); // Option B: Gross Realized Revenue (Cash + TDS)
        $overdue = (float) $projects->sum(fn ($project) => $project->money['overdue']);
        $due = round(max(0, $sched - $cash - $tds), 2);
        $collRate = $sched > 0 ? min(100, round(($rec / $sched) * 100)) : 0;

        $terms = PaymentTerm::whereIn('project_id', $projectIds)
            ->with(['project', 'milestone'])
            ->orderBy('due_date')
            ->get();

        $payments = Payment::where('customer_id', $this->customer->id)
            ->with(['project', 'term.milestone', 'enteredBy', 'document'])
            ->orderByDesc('paid_on')
            ->get();

        $documentsQuery = $this->customer->documents()
            ->with(['project', 'uploadedBy'])
            ->when($this->docSearch !== '', function ($builder) {
                $term = '%'.trim($this->docSearch).'%';
                $builder->where(function ($subQuery) use ($term) {
                    $subQuery->where('title', 'like', $term)
                        ->orWhere('file_name', 'like', $term);
                });
            })
            ->when($this->docCategory !== '', fn ($builder) => $builder->where('category', $this->docCategory))
            ->when($this->docProject !== '', function ($builder) {
                if ($this->docProject === 'general') {
                    $builder->whereNull('project_id');
                } else {
                    $builder->where('project_id', $this->docProject);
                }
            })
            ->orderByDesc('id');

        $availableDocCategories = $this->customer->documents()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->pluck('category')
            ->sort()
            ->values();

        if ($availableDocCategories->isEmpty()) {
            $availableDocCategories = collect(['Contract', 'KYC/GST', 'Invoice', 'Proposal', 'Quotation', 'Delivery Proof', 'Other']);
        }

        return view('livewire.customers.customer-detail', [
            'projects' => $projects,
            'terms' => $terms,
            'payments' => $payments,
            'documents' => $documentsQuery->get(),
            'docCategories' => $availableDocCategories,
            'customerProjects' => $projects,
            'followups' => $this->customer->followups()->orderByDesc('happened_on')->get(),
            'money' => [
                'contract' => $contractVal,
                'scheduled' => $sched,
                'received' => $rec,
                'cash' => $cash,
                'tds' => $tds,
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
