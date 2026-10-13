<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\SalesPerson;
use App\Services\CodeGeneratorService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class CustomerModal extends Component {
    public ?int $customerId = null;
    public string $company = '';
    public string $contact_name = '';
    public string $email = '';
    public string $phone = '';
    public string $alt_phone = '';
    public string $city = '';
    public string $state = '';
    public string $address = '';
    public string $gstin = '';
    public string $pan = '';
    public string $billing_name = '';
    public string $billing_addr = '';
    public string $source = '';
    public string $industry = '';
    public ?int $sales_person_id = null;
    public string $status = 'Active';
    public string $internal_notes = '';

    #[On('open-customer-modal')]
    public function loadCustomer(?int $id = null): void {
        $this->resetValidation();
        $this->reset();
        $this->customerId = $id;

        if ($id) {
            $c = Customer::findOrFail($id);
            $this->company = $c->company ?? '';
            $this->contact_name = $c->contact_name ?? '';
            $this->email = $c->email ?? '';
            $this->phone = $c->phone ?? '';
            $this->alt_phone = $c->alt_phone ?? '';
            $this->city = $c->city ?? '';
            $this->state = $c->state ?? '';
            $this->address = $c->address ?? '';
            $this->gstin = $c->gstin ?? '';
            $this->pan = $c->pan ?? '';
            $this->billing_name = $c->billing_name ?? '';
            $this->billing_addr = $c->billing_addr ?? '';
            $this->source = $c->source ?? '';
            $this->industry = $c->industry ?? '';
            $this->sales_person_id = $c->sales_person_id;
            $this->status = $c->status ?? 'Active';
            $this->internal_notes = $c->internal_notes ?? '';
        }

        $this->dispatch('open-modal', 'customer-modal');
    }

    protected function rules(): array {
        return [
            'company' => [
                'required',
                'string',
                'max:160',
                Rule::unique('customers', 'company')->ignore($this->customerId),
            ],
            'contact_name' => 'nullable|string|max:80',
            'email' => 'nullable|email|max:160',
            'phone' => 'nullable|string|max:30',
            'alt_phone' => 'nullable|string|max:30',
            'city' => 'nullable|string|max:60',
            'state' => 'nullable|string|max:60',
            'address' => 'nullable|string|max:1000',
            'gstin' => 'nullable|string|max:20',
            'pan' => 'nullable|string|max:15',
            'billing_name' => 'nullable|string|max:160',
            'billing_addr' => 'nullable|string|max:1000',
            'source' => 'nullable|string|max:60',
            'industry' => 'nullable|string|max:60',
            'sales_person_id' => 'nullable|integer|exists:sales_persons,id',
            'status' => 'required|in:Active,On Hold,Completed,Churned',
            'internal_notes' => 'nullable|string|max:4000',
        ];
    }

    public function save(): void {
        // Normalize empty select values
        if (empty($this->sales_person_id)) {
            $this->sales_person_id = null;
        }

        $validated = $this->validate();
        $codeService = app(CodeGeneratorService::class);

        if ($this->customerId) {
            $customer = Customer::findOrFail($this->customerId);
            $customer->update($validated);
        } else {
            $validated['code'] = $codeService->generate('customer', 'CUS-');
            $validated['created_by_id'] = auth()->id();
            Customer::create($validated);
        }

        $this->reset();
        $this->dispatch('customer-saved');
        $this->dispatch('close-modal');
        $this->redirect(route('customers.index'), navigate: true);
    }

    public function render() {
        return view('livewire.customers.customer-modal', [
            'salesPeople' => SalesPerson::where('status', 'Active')->orderBy('name')->get()
        ]);
    }
}
