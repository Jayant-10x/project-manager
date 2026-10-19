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
            ->with(['projects' => fn ($q) => $q->forUser($user)->with(['terms', 'payments']), 'salesPerson'])
            ->when($this->search, function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('company', 'like', $term)
                        ->orWhere('customer_code', 'like', $term)
                        ->orWhere('contact_name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('gst_number', 'like', $term);
                });
            })
            ->when($this->sales, fn ($q) => $q->where('sales_person_id', $this->sales))
            ->when($this->city, fn ($q) => $q->where('city', $this->city))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderBy('company');

        $customers = $query->get();

        if ($this->pay) {
            $customers = $customers->filter(function ($c) {
                $due = $c->projects->sum(fn ($p) => $p->money['due']);
                $overdue = $c->projects->sum(fn ($p) => $p->money['overdue']);
                $sched = $c->projects->sum(fn ($p) => $p->money['scheduled']);

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

            foreach ($customers as $c) {
                $sched = (float) $c->projects->sum(fn ($p) => $p->money['scheduled']);
                $rec = (float) $c->projects->sum(fn ($p) => $p->money['received']);
                $due = (float) $c->projects->sum(fn ($p) => $p->money['due']);

                fputcsv($handle, [
                    $c->customer_code,
                    $c->company,
                    $c->contact_name,
                    $c->phone,
                    $c->email,
                    $c->city,
                    $c->gst_number,
                    $c->source,
                    $c->salesPerson?->name ?? '',
                    $c->status,
                    $c->projects->count(),
                    $sched,
                    $rec,
                    $due,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function render()
    {
        $user = auth()->user();

        $query = Customer::query()
            ->with([
                'projects' => fn ($q) => $q->forUser($user)->with(['terms', 'payments']),
                'salesPerson',
            ])
            ->when($this->search, function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('company', 'like', $term)
                        ->orWhere('customer_code', 'like', $term)
                        ->orWhere('contact_name', 'like', $term)
                        ->orWhere('phone', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('city', 'like', $term)
                        ->orWhere('gst_number', 'like', $term);
                });
            })
            ->when($this->sales, fn ($q) => $q->where('sales_person_id', $this->sales))
            ->when($this->city, fn ($q) => $q->where('city', $this->city))
            ->when($this->status, fn ($q) => $q->where('status', $this->status))
            ->orderBy('company');

        $allMatching = (clone $query)->get();

        // Filter by payment status if specified
        if ($this->pay) {
            $filteredIds = $allMatching->filter(function ($c) {
                $due = $c->projects->sum(fn ($p) => $p->money['due']);
                $overdue = $c->projects->sum(fn ($p) => $p->money['overdue']);
                $sched = $c->projects->sum(fn ($p) => $p->money['scheduled']);

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
            $totalDue = $allMatching->whereIn('id', $filteredIds)->sum(fn ($c) => $c->projects->sum(fn ($p) => $p->money['due']));
        } else {
            $totalDue = $allMatching->sum(fn ($c) => $c->projects->sum(fn ($p) => $p->money['due']));
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
