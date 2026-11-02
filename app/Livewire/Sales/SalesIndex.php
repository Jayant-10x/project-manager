<?php

namespace App\Livewire\Sales;

use App\Models\Customer;
use App\Models\User;
use App\Services\AuditNotificationService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class SalesIndex extends Component
{
    public function mount(): void
    {
        $user = auth()->user();
        if (! $user || $user->role === 'Sales' || $user->isTechPerson() || (! $user->isAdmin() && ! $user->hasPermission('team', 'view'))) {
            abort(403, 'Unauthorized access to Sales Team module.');
        }
    }

    #[On('sales-saved')]
    public function refreshList(): void
    {
        // Re-renders the component
    }

    public function exportCsv()
    {
        $user = auth()->user();
        $salesPeople = User::where('role', 'Sales')
            ->where('status', 'Active')
            ->whereIn('id', $user->role === 'Admin' ? User::where('role', 'Sales')->where('status', 'Active')->pluck('id') : current_user_downline_ids())
            ->with([
                'salesProjects' => fn ($projectQuery) => $projectQuery->forUser($user)->with(['terms', 'payments', 'customer']),
            ])->orderBy('name')->get();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Exported CSV',
            'Sales',
            null,
            '',
            "Exported sales report for {$salesPeople->count()} team members to CSV"
        );

        $filename = 'sales-report-'.now()->toDateString().'.csv';

        return response()->streamDownload(function () use ($salesPeople) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF"); // BOM for Excel
            fputcsv($handle, ['Sales Person', 'Customers', 'Projects', 'Total Value', 'Received', 'Overdue']);

            foreach ($salesPeople as $salesPerson) {
                $projs = $salesPerson->salesProjects;
                $val = (float) $projs->sum('value');
                $rec = (float) $projs->flatMap->payments->sum('amount');
                $over = (float) $projs->flatMap->terms->where('status', 'Overdue')->sum(fn ($term) => max(0, $term->amount - $term->received - $term->tds));

                fputcsv($handle, [
                    $salesPerson->name,
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

        $salesPerson = User::where('role', 'Sales')->findOrFail($id);
        $auditService = app(AuditNotificationService::class);

        if (Customer::where('sales_person_id', $id)->exists()) {
            $salesPerson->status = 'Inactive';
            $salesPerson->save();

            $auditService->log(
                auth()->id(),
                'Deactivated Salesperson',
                'User',
                $salesPerson->id,
                (string) $salesPerson->id,
                "Salesperson {$salesPerson->name} marked Inactive to preserve customer attribution"
            );

            $auditService->notifyUplines(
                $salesPerson,
                'Salesperson Deactivated',
                "Salesperson Deactivated: {$salesPerson->name}",
                "Salesperson {$salesPerson->name} ({$salesPerson->email}) was marked Inactive by ".auth()->user()->name.'.',
                'sales'
            );

            $this->dispatch('toast', ['msg' => 'Salesperson marked Inactive to preserve customer attribution', 'type' => 'ok']);
        } else {
            $salesName = $salesPerson->name;
            $salesEmail = $salesPerson->email;
            $salesPerson->delete();

            $auditService->log(
                auth()->id(),
                'Deleted Salesperson',
                'User',
                $id,
                (string) $id,
                "Deleted salesperson {$salesName} ({$salesEmail})"
            );

            $auditService->notifyUplines(
                $salesPerson,
                'Salesperson Deleted',
                "Salesperson Deleted: {$salesName}",
                "Salesperson {$salesName} ({$salesEmail}) was deleted by ".auth()->user()->name.'.',
                'sales'
            );

            $this->dispatch('toast', ['msg' => 'Salesperson removed', 'type' => 'ok']);
        }
    }

    public function render()
    {
        $user = auth()->user();

        $salesPeople = User::where('role', 'Sales')
            ->where('status', 'Active')
            ->whereIn('id', $user->role === 'Admin' ? User::where('role', 'Sales')->where('status', 'Active')->pluck('id') : current_user_downline_ids())
            ->with([
                'salesProjects' => fn ($projectQuery) => $projectQuery->forUser($user)->with(['terms', 'payments', 'customer']),
            ])->orderBy('name')->get();

        $rows = [];
        foreach ($salesPeople as $salesPerson) {
            $projs = $salesPerson->salesProjects;
            $val = (float) $projs->sum('value');
            $rec = (float) $projs->flatMap->payments->sum('amount');
            $over = (float) $projs->flatMap->terms->where('status', 'Overdue')->sum(fn ($term) => max(0, $term->amount - $term->received - $term->tds));
            $rate = $val > 0 ? min(100, round(($rec / $val) * 100)) : 0;

            $rows[] = [
                'salesPerson' => $salesPerson,
                'customers' => $projs->pluck('customer_id')->unique()->count(),
                'projects' => $projs->count(),
                'value' => $val,
                'received' => $rec,
                'overdue' => $over,
                'rate' => $rate,
            ];
        }

        usort($rows, fn ($firstRow, $secondRow) => $secondRow['value'] <=> $firstRow['value']);

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
