<?php

namespace App\Livewire\Sales;

use App\Models\Customer;
use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class SalesIndex extends Component
{
    #[On('sales-saved')]
    public function refreshList(): void
    {
        // Re-renders the component
    }

    public function exportCsv()
    {
        $user = auth()->user();
        $salesPeople = User::where('role', 'Sales')
            ->whereIn('id', $user->role === 'Admin' ? User::where('role', 'Sales')->pluck('id') : $user->getDownlineUserIds())
            ->with([
                'salesProjects' => fn ($q) => $q->forUser($user)->with(['terms', 'payments', 'customer']),
            ])->orderBy('name')->get();

        $filename = 'sales-report-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($salesPeople) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($handle, ['Sales Person', 'Customers', 'Projects', 'Total Value', 'Received', 'Overdue']);

            foreach ($salesPeople as $s) {
                $projs = $s->salesProjects;
                $val = (float) $projs->sum('value');
                $rec = (float) $projs->flatMap->payments->sum('amount');
                $over = (float) $projs->flatMap->terms->where('status', 'Overdue')->sum(fn ($t) => max(0, $t->amount - $t->received - $t->tds));

                fputcsv($handle, [
                    $s->name,
                    $projs->pluck('customer_id')->unique()->count(),
                    $projs->count(),
                    $val,
                    $rec,
                    $over,
                ]);
            }
            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=utf-8']);
    }

    public function deleteSales(int $id): void
    {
        if (auth()->user()->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Only an Admin can remove a salesperson.', 'type' => 'err']);

            return;
        }

        $sp = User::where('role', 'Sales')->findOrFail($id);

        if (Customer::where('sales_person_id', $id)->exists()) {
            $sp->status = 'Inactive';
            $sp->save();
            $this->dispatch('toast', ['msg' => 'Salesperson marked Inactive to preserve customer attribution', 'type' => 'ok']);
        } else {
            $sp->delete();
            $this->dispatch('toast', ['msg' => 'Salesperson removed', 'type' => 'ok']);
        }
    }

    public function render()
    {
        $user = auth()->user();

        $salesPeople = User::where('role', 'Sales')
            ->whereIn('id', $user->role === 'Admin' ? User::where('role', 'Sales')->pluck('id') : $user->getDownlineUserIds())
            ->with([
                'salesProjects' => fn ($q) => $q->forUser($user)->with(['terms', 'payments', 'customer']),
            ])->orderBy('name')->get();

        $rows = [];
        foreach ($salesPeople as $sp) {
            $projs = $sp->salesProjects;
            $val = (float) $projs->sum('value');
            $rec = (float) $projs->flatMap->payments->sum('amount');
            $over = (float) $projs->flatMap->terms->where('status', 'Overdue')->sum(fn ($t) => max(0, $t->amount - $t->received - $t->tds));
            $rate = $val > 0 ? min(100, round(($rec / $val) * 100)) : 0;

            $rows[] = [
                'salesPerson' => $sp,
                'customers' => $projs->pluck('customer_id')->unique()->count(),
                'projects' => $projs->count(),
                'value' => $val,
                'received' => $rec,
                'overdue' => $over,
                'rate' => $rate,
            ];
        }

        usort($rows, fn ($a, $b) => $b['value'] <=> $a['value']);

        $totalOnboarded = Customer::whereNotNull('sales_person_id')->count();

        return view('livewire.sales.sales-index', [
            'rows' => $rows,
            'salesCount' => count($rows),
            'totalOnboarded' => $totalOnboarded,
        ])->layoutData([
            'header' => 'Sales Team',
            'subtitle' => 'Who onboarded how many customers, and how much was collected',
        ]);
    }
}
