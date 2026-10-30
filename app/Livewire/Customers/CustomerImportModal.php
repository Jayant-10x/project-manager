<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\CodeGeneratorService;
use Livewire\Component;
use Livewire\WithFileUploads;

class CustomerImportModal extends Component
{
    use WithFileUploads;

    public $csvFile;

    public string $pastedData = '';

    public ?int $defaultSalesPersonId = null;

    public function import(CodeGeneratorService $codeService): void
    {
        $raw = trim($this->pastedData);

        if ($this->csvFile) {
            $raw = file_get_contents($this->csvFile->getRealPath());
        }

        if (empty($raw)) {
            $this->addError('pastedData', 'Please paste CSV data or choose a file.');

            return;
        }

        $lines = preg_split('/\r?\n/', $raw);
        $lines = array_filter($lines, fn ($l) => ! empty(trim($l)));

        if (count($lines) < 2) {
            $this->addError('pastedData', 'At least one header row and one data row are required.');

            return;
        }

        $headers = str_getcsv(array_shift($lines));
        $headerMap = [];
        foreach ($headers as $idx => $h) {
            $clean = strtolower(preg_replace('/[^a-z]/', '', $h));
            $headerMap[$clean] = $idx;
        }

        $created = 0;
        foreach ($lines as $line) {
            $row = str_getcsv($line);
            $company = trim($row[$headerMap['company'] ?? -1] ?? '');
            $contactName = trim($row[$headerMap['contact'] ?? $headerMap['contactname'] ?? -1] ?? '');
            $phone = trim($row[$headerMap['phone'] ?? $headerMap['mobile'] ?? -1] ?? '');

            // At least company or contact name must be present
            if (empty($company) && empty($contactName)) {
                continue;
            }

            // Prevent duplicate insertion
            if (! empty($company) && Customer::whereRaw('LOWER(company) = ?', [strtolower($company)])->exists()) {
                continue;
            }

            if (! empty($phone) && Customer::where('phone', $phone)->exists()) {
                continue;
            }

            $isSales = auth()->check() && auth()->user()->role === 'Sales';

            Customer::create([
                'customer_code' => $codeService->generate('customer', 'CUS-'),
                'company' => ! empty($company) ? $company : null,
                'contact_name' => $contactName ?: null,
                'phone' => $phone ?: null,
                'email' => trim($row[$headerMap['email'] ?? -1] ?? '') ?: null,
                'city' => trim($row[$headerMap['city'] ?? -1] ?? '') ?: null,
                'state' => trim($row[$headerMap['state'] ?? -1] ?? '') ?: null,
                'gst_number' => strtoupper(trim($row[$headerMap['gstnumber'] ?? $headerMap['gst'] ?? -1] ?? '')) ?: null,
                'pan_number' => strtoupper(trim($row[$headerMap['pannumber'] ?? $headerMap['pan'] ?? -1] ?? '')) ?: null,
                'address' => trim($row[$headerMap['address'] ?? -1] ?? '') ?: null,
                'industry' => trim($row[$headerMap['industry'] ?? -1] ?? '') ?: null,
                'source' => trim($row[$headerMap['source'] ?? -1] ?? '') ?: null,
                'internal_notes' => trim($row[$headerMap['internalnotes'] ?? $headerMap['notes'] ?? -1] ?? '') ?: null,
                'sales_person_id' => $isSales ? (auth()->id() ?? $this->defaultSalesPersonId) : $this->defaultSalesPersonId,
                'status' => 'Active',
                'approval_status' => $isSales ? 'Pending' : 'Approved',
                'created_by' => auth()->id(),
            ]);
            $created++;
        }

        if ($created > 0) {
            $auditService = app(AuditNotificationService::class);
            $auditService->log(
                auth()->id(),
                'Imported Customers',
                'Customer',
                null,
                '',
                "Imported {$created} customer(s) from CSV"
            );

            $isSales = auth()->check() && auth()->user()->role === 'Sales';
            if ($isSales) {
                $auditService->notifyUplines(
                    auth()->user(),
                    'Customer Approval Required',
                    'Bulk Customers Approval Required',
                    auth()->user()->name." imported {$created} customer(s) via CSV. Approval required.",
                    'customer'
                );
            } else {
                $auditService->notifyUplines(
                    auth()->user(),
                    'Customers Imported',
                    'Bulk Customers Imported',
                    auth()->user()->name." imported {$created} customer(s) via CSV.",
                    'customer'
                );
            }
        }

        $this->reset();
        $this->dispatch('close-modal');
        $this->dispatch('customer-saved');
        $this->dispatch('toast', ['msg' => "Successfully imported {$created} customer(s).", 'type' => 'ok']);
        $this->redirect(route('customers.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.customers.customer-import-modal', [
            'salesPeople' => User::where('role', 'Sales')->where('status', 'Active')->orderBy('name')->get(),
        ]);
    }
}
