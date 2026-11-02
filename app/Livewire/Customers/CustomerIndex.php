<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\User;
use App\Services\AuditNotificationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class CustomerIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $sales = '';

    #[Url]
    public string $state = '';

    #[Url]
    public string $pay = '';

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        abort_if(! auth()->user() || auth()->user()->isTechPerson() || ! auth()->user()->hasPermission('customers', 'view'), 403, 'Unauthorized access to customer module.');
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSales(): void
    {
        $this->resetPage();
    }

    public function updatingState(): void
    {
        $this->resetPage();
    }

    public function updatingPay(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'sales', 'state', 'pay', 'status']);
        $this->resetPage();
    }

    #[On('customer-saved')]
    public function refreshCustomers(): void
    {
        $this->resetPage();
    }

    public function approveCustomer(int $id): void
    {
        $customer = Customer::findOrFail($id);
        $user = auth()->user();
        if (! $customer->canBeApprovedBy($user)) {
            abort(403, 'Unauthorized to approve this customer.');
        }

        $customer->update([
            'approval_status' => 'Approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => null,
        ]);

        $auditService = app(AuditNotificationService::class);
        $label = $customer->company ?: ($customer->contact_name ?: $customer->customer_code);

        $auditService->log(
            $user->id,
            'Approved Customer',
            'Customer',
            $customer->id,
            $customer->customer_code,
            "Approved customer {$label}"
        );

        $creatorId = $customer->created_by ?: $customer->sales_person_id;
        if ($creatorId && (int) $creatorId !== (int) $user->id) {
            $auditService->notify(
                $creatorId,
                'Customer Approved',
                "Customer Approved: {$label}",
                "Your submitted customer {$label} ({$customer->customer_code}) was approved by {$user->name}.",
                'customer',
                $customer->id
            );
        }

        $this->dispatch('toast', ['msg' => 'Customer approved successfully.', 'type' => 'ok']);
    }

    public function rejectCustomer(int $id, ?string $reason = null): void
    {
        $customer = Customer::findOrFail($id);
        $user = auth()->user();
        if (! $customer->canBeApprovedBy($user)) {
            abort(403, 'Unauthorized to reject this customer.');
        }

        $cleanReason = trim((string) $reason);

        $customer->update([
            'approval_status' => 'Rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
            'rejection_reason' => $cleanReason !== '' ? $cleanReason : null,
        ]);

        $auditService = app(AuditNotificationService::class);
        $label = $customer->company ?: ($customer->contact_name ?: $customer->customer_code);
        $reasonText = $cleanReason !== '' ? " (Reason: {$cleanReason})" : '';

        $auditService->log(
            $user->id,
            'Rejected Customer',
            'Customer',
            $customer->id,
            $customer->customer_code,
            "Rejected customer {$label}{$reasonText}"
        );

        $creatorId = $customer->created_by ?: $customer->sales_person_id;
        if ($creatorId && (int) $creatorId !== (int) $user->id) {
            $auditService->notify(
                $creatorId,
                'Customer Rejected',
                "Customer Rejected: {$label}",
                "Your submitted customer {$label} ({$customer->customer_code}) was rejected by {$user->name}.".($cleanReason !== '' ? " Reason: {$cleanReason}" : ''),
                'customer',
                $customer->id
            );
        }

        $this->dispatch('toast', ['msg' => 'Customer rejected.', 'type' => 'warning']);
    }

    public function exportCsv()
    {
        abort_if(! logged_in_user() || is_tech_person() || ! has_permission('customers', 'view'), 403, 'Unauthorized access to customer module.');

        $user = logged_in_user();
        $query = Customer::query()
            ->forUser($user)
            ->with(['projects' => fn ($projectQuery) => $projectQuery->forUser($user)->with(['terms', 'payments']), 'salesPerson'])
            ->when($this->search, function ($builder) {
                $searchTerm = '%'.trim($this->search).'%';
                $builder->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('company', 'like', $searchTerm)
                        ->orWhere('customer_code', 'like', $searchTerm)
                        ->orWhere('contact_name', 'like', $searchTerm)
                        ->orWhere('phone', 'like', $searchTerm)
                        ->orWhere('email', 'like', $searchTerm)
                        ->orWhere('city', 'like', $searchTerm)
                        ->orWhere('state', 'like', $searchTerm)
                        ->orWhere('gst_number', 'like', $searchTerm);
                });
            })
            ->when($this->sales, fn ($builder) => $builder->where('sales_person_id', $this->sales))
            ->when($this->state, fn ($builder) => $builder->where('state', $this->state))
            ->when($this->status, fn ($builder) => $builder->where('status', $this->status))
            ->orderBy('company');

        $customers = $query->get();

        if ($this->pay) {
            $customers = $customers->filter(function ($customer) {
                $due = $customer->projects->sum(fn ($project) => $project->money['due']);
                $overdue = $customer->projects->sum(fn ($project) => $project->money['overdue']);
                $sched = $customer->projects->sum(fn ($project) => $project->money['scheduled']);

                if ($this->pay === 'due') {
                    return $due > 0;
                }
                if ($this->pay === 'over') {
                    return $overdue > 0;
                }
                if ($this->pay === 'clear') {
                    return $due <= 0 && $sched > 0;
                }

                return true;
            });
        }

        audit_service()->log(
            logged_in_user_id(),
            'Exported CSV',
            'Customer',
            null,
            '',
            "Exported {$customers->count()} customers to CSV"
        );

        $filename = 'customers-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($customers) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($handle, ['Code', 'Company', 'Contact', 'Phone', 'Email', 'City', 'State', 'GSTIN', 'Source', 'Onboarded By', 'Status', 'Projects', 'Total Value', 'Received', 'Due']);

            foreach ($customers as $customer) {
                $scheduledAmount = (float) $customer->projects->sum(fn ($project) => $project->money['scheduled']);
                $receivedAmount = (float) $customer->projects->sum(fn ($project) => $project->money['received']);
                $dueAmount = (float) $customer->projects->sum(fn ($project) => $project->money['due']);

                fputcsv($handle, [
                    $customer->customer_code,
                    $customer->company,
                    $customer->contact_name,
                    $customer->phone,
                    $customer->email,
                    $customer->city,
                    $customer->state,
                    $customer->gst_number,
                    $customer->source,
                    $customer->salesPerson?->name ?? '',
                    $customer->status,
                    $customer->projects->count(),
                    $scheduledAmount,
                    $receivedAmount,
                    $dueAmount,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function render()
    {
        $user = auth()->user();

        $query = Customer::query()
            ->forUser($user)
            ->with([
                'projects' => fn ($projectQuery) => $projectQuery->forUser($user)->with(['terms', 'payments']),
                'salesPerson',
            ])
            ->when($this->search, function ($builder) {
                $searchTerm = '%'.trim($this->search).'%';
                $builder->where(function ($subQuery) use ($searchTerm) {
                    $subQuery->where('company', 'like', $searchTerm)
                        ->orWhere('customer_code', 'like', $searchTerm)
                        ->orWhere('contact_name', 'like', $searchTerm)
                        ->orWhere('phone', 'like', $searchTerm)
                        ->orWhere('email', 'like', $searchTerm)
                        ->orWhere('city', 'like', $searchTerm)
                        ->orWhere('state', 'like', $searchTerm)
                        ->orWhere('gst_number', 'like', $searchTerm);
                });
            })
            ->when($this->sales, fn ($builder) => $builder->where('sales_person_id', $this->sales))
            ->when($this->state, fn ($builder) => $builder->where('state', $this->state))
            ->when($this->status, fn ($builder) => $builder->where('status', $this->status))
            ->orderBy('company');

        $allMatching = (clone $query)->get();

        // Filter by payment status if specified
        if ($this->pay) {
            $filteredIds = $allMatching->filter(function ($customer) {
                $due = $customer->projects->sum(fn ($project) => $project->money['due']);
                $overdue = $customer->projects->sum(fn ($project) => $project->money['overdue']);
                $sched = $customer->projects->sum(fn ($project) => $project->money['scheduled']);

                if ($this->pay === 'due') {
                    return $due > 0;
                }
                if ($this->pay === 'over') {
                    return $overdue > 0;
                }
                if ($this->pay === 'clear') {
                    return $due <= 0 && $sched > 0;
                }

                return true;
            })->pluck('id');

            $query->whereIn('id', $filteredIds);
            $totalDue = $allMatching->whereIn('id', $filteredIds)->sum(fn ($customer) => $customer->projects->sum(fn ($project) => $project->money['due']));
        } else {
            $totalDue = $allMatching->sum(fn ($customer) => $customer->projects->sum(fn ($project) => $project->money['due']));
        }

        $presentStateCodes = Customer::query()
            ->forUser($user)
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->distinct()
            ->pluck('state')
            ->filter()
            ->values();

        $allStates = config('constants.states', []);
        $states = [];
        foreach ($presentStateCodes as $code) {
            $states[$code] = $allStates[$code] ?? $code;
        }
        asort($states);

        $salesPeopleQuery = User::where('role', 'Sales')->where('status', 'Active');
        if ($user->role === 'Manager') {
            $salesPeopleQuery->whereIn('id', current_user_downline_ids());
        } elseif ($user->role === 'Sales') {
            $salesPeopleQuery->where('id', $user->id);
        }
        $salesPeople = ($user->role === 'Sales') ? collect() : $salesPeopleQuery->orderBy('name')->get();

        return view('livewire.customers.customer-index', [
            'customers' => $query->paginate(15),
            'salesPeople' => $salesPeople,
            'states' => $states,
            'totalDue' => $totalDue,
        ])->layoutData([
            'header' => 'Customers',
            'subtitle' => 'Onboarded customers, their projects and payment status',
        ]);
    }
}
