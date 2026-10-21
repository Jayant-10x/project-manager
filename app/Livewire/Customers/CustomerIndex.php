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
    public string $city = '';

    #[Url]
    public string $pay = '';

    #[Url]
    public string $status = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingSales(): void
    {
        $this->resetPage();
    }

    public function updatingCity(): void
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
        $this->reset(['search', 'sales', 'city', 'pay', 'status']);
        $this->resetPage();
    }

    #[On('customer-saved')]
    public function refreshCustomers(): void
    {
        $this->resetPage();
    }

    public function exportCsv()
    {
        $user = auth()->user();
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
                        ->orWhere('gst_number', 'like', $searchTerm);
                });
            })
            ->when($this->sales, fn ($builder) => $builder->where('sales_person_id', $this->sales))
            ->when($this->city, fn ($builder) => $builder->where('city', $this->city))
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

        app(AuditNotificationService::class)->log(
            auth()->id(),
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
            fputcsv($handle, ['Code', 'Company', 'Contact', 'Phone', 'Email', 'City', 'GSTIN', 'Source', 'Onboarded By', 'Status', 'Projects', 'Total Value', 'Received', 'Due']);

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
                        ->orWhere('gst_number', 'like', $searchTerm);
                });
            })
            ->when($this->sales, fn ($builder) => $builder->where('sales_person_id', $this->sales))
            ->when($this->city, fn ($builder) => $builder->where('city', $this->city))
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

        $cities = Customer::distinct()->whereNotNull('city')->where('city', '!=', '')->pluck('city')->sort();

        return view('livewire.customers.customer-index', [
            'customers' => $query->paginate(15),
            'salesPeople' => User::where('role', 'Sales')->where('status', 'Active')->orderBy('name')->get(),
            'cities' => $cities,
            'totalDue' => $totalDue,
        ])->layoutData([
            'header' => 'Customers',
            'subtitle' => 'Onboarded customers, their projects and payment status',
        ]);
    }
}
