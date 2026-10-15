<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\SalesPerson;
use App\Services\CodeGeneratorService;
use Livewire\Component;
use Livewire\WithFileUploads;

class CustomerImportModal extends Component {
    use WithFileUploads;

    public $csvFile;
    public string $pastedData = '';
    public ?int $defaultSalesPersonId = null;

    public function import(CodeGeneratorService $codeService): void {
        $raw = trim($this->pastedData);

        if ($this->csvFile) {
            $raw = file_get_contents($this->csvFile->getRealPath());
        }

        if (empty($raw)) {
            $this->addError('pastedData', 'Please paste CSV data or choose a file.');
            return;
        }

        $lines = preg_split('/\r?\n/', $raw);
        $lines = array_filter($lines, fn($l) => !empty(trim($l)));

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
            $company = trim($row[$headerMap['company'] ?? 0] ?? '');
            if (empty($company)) continue;

            // Prevent duplicate insertion
            if (Customer::whereRaw('LOWER(company) = ?', [strtolower($company)])->exists()) {
                continue;
            }

            Customer::create([
                'code' => $codeService->generate('customer', 'CUS-'),
                'company' => $company,
                'contact_name' => trim($row[$headerMap['contact'] ?? $headerMap['contactname'] ?? -1] ?? ''),
                'phone' => trim($row[$headerMap['phone'] ?? $headerMap['mobile'] ?? -1] ?? ''),
                'email' => trim($row[$headerMap['email'] ?? -1] ?? ''),
                'city' => trim($row[$headerMap['city'] ?? -1] ?? ''),
                'state' => trim($row[$headerMap['state'] ?? -1] ?? ''),
                'gst_number' => strtoupper(trim($row[$headerMap['gst_number'] ?? $headerMap['gst'] ?? -1] ?? '')),
                'source' => trim($row[$headerMap['source'] ?? -1] ?? ''),
                'sales_person' => $this->defaultSalesPersonId,
                'status' => 'Active',
                'created_by' => auth()->id(),
            ]);
            $created++;
        }

        $this->reset();
        $this->dispatch('close-modal');
        $this->redirect(route('customers.index'), navigate: true);
    }

    public function render() {
        return view('livewire.customers.customer-import-modal', [
            'salesPeople' => SalesPerson::where('status', 'Active')->orderBy('name')->get()
        ]);
    }
}
