<?php

namespace App\Livewire\Customers;

use App\Models\Customer;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\CodeGeneratorService;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class CustomerModal extends Component
{
    public ?int $customerId = null;

    #[Validate]
    public ?string $company = null;

    #[Validate]
    public string $contact_name = '';

    #[Validate]
    public string $email = '';

    #[Validate]
    public string $phone = '';

    #[Validate]
    public string $alt_phone = '';

    #[Validate]
    public string $city = '';

    #[Validate]
    public string $state = '';

    #[Validate]
    public string $address = '';

    #[Validate]
    public string $gst_number = '';

    #[Validate]
    public string $pan_number = '';

    #[Validate]
    public string $billing_name = '';

    #[Validate]
    public string $billing_address = '';

    #[Validate]
    public string $source = '';

    #[Validate]
    public string $industry = '';

    #[Validate]
    public ?int $sales_person_id = null;

    #[Validate]
    public string $status = 'Active';

    // Added #[Validate] attribute
    #[Validate]
    public string $internal_notes = '';

    // Optional but recommended for dynamic rules: guarantees validateOnly on change
    public function updated($propertyName): void
    {
        if ($propertyName === 'company' && is_string($this->company) && trim($this->company) === '') {
            $this->company = null;
        }

        $this->validateOnly($propertyName);
    }

    public function openModal(int|array|null $id = null): void
    {
        if (is_array($id)) {
            $id = isset($id['id']) ? (int) $id['id'] : null;
        }

        $this->loadCustomer($id);
    }

    #[On('open-customer-modal')]
    public function loadCustomer(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset();
        $this->customerId = $id;

        if ($id) {
            $customer = Customer::findOrFail($id);
            $this->company = $customer->company ?? '';
            $this->contact_name = $customer->contact_name ?? '';
            $this->email = $customer->email ?? '';
            $this->phone = $customer->phone ?? '';
            $this->alt_phone = $customer->alt_phone ?? '';
            $this->city = $customer->city ?? '';
            $this->state = $customer->state ?? '';
            $this->address = $customer->address ?? '';
            $this->gst_number = $customer->gst_number ?? '';
            $this->pan_number = $customer->pan_number ?? '';
            $this->billing_name = $customer->billing_name ?? '';
            $this->billing_address = $customer->billing_address ?? '';
            $this->source = $customer->source ?? '';
            $this->industry = $customer->industry ?? '';
            $this->sales_person_id = $customer->sales_person_id ?? null;
            $this->status = $customer->status ?? 'Active';
            $this->internal_notes = $customer->internal_notes ?? '';
        } else {
            if (auth()->check() && auth()->user()->role === 'Sales') {
                $this->sales_person_id = auth()->id();
            }
        }

        $this->dispatch('open-modal', 'customer-modal');
    }

    protected function rules(): array
    {
        return [
            'company' => 'nullable|string|min:2|max:160|unique:customers,company,'.$this->customerId.',id',
            'contact_name' => 'required|string|min:2|max:80',
            'email' => 'nullable|email|min:2|max:160|unique:customers,email,'.$this->customerId,
            'phone' => 'required|integer|digits_between:10,30|unique:customers,phone,'.$this->customerId,
            'alt_phone' => 'nullable|integer|digits_between:10,30',
            'city' => 'nullable|string|min:2|max:60',
            'state' => 'required|string|in:'.implode(',', array_keys(config('constants.states'))),
            'address' => 'nullable|string|min:5|max:1000',
            'gst_number' => [
                'nullable', 'string', 'size:15',
                'regex:/^[0-9]{2}[A-Z]{5}[0-9]{4}[A-Z]{1}[1-9A-Z]{1}Z[0-9A-Z]{1}$/',
                'unique:customers,gst_number,'.$this->customerId,
            ],
            'pan_number' => [
                'nullable', 'string', 'size:10',
                'regex:/^[A-Z]{5}[0-9]{4}[A-Z]$/',
                'unique:customers,pan_number,'.$this->customerId,
            ],
            'billing_name' => 'nullable|string|min:2|max:160',
            'billing_address' => 'nullable|string|min:2|max:1000',
            'source' => 'nullable|string|min:2|max:60',
            'industry' => 'nullable|string|min:2|max:60',
            'sales_person_id' => (auth()->check() && auth()->user()->role === 'Sales') ? 'nullable|integer' : 'required|integer|exists:users,id',
            'status' => 'required|in:Active,On Hold,Completed,Churned',
            'internal_notes' => 'nullable|string|min:2|max:4000',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'company' => 'Company Name',
            'contact_name' => 'Contact Person',
            'email' => 'Email',
            'alt_phone' => 'Alternate Phone',
            'sales_person_id' => 'Salesperson',
        ];
    }

    public function save(): void
    {
        if (auth()->check() && auth()->user()->role === 'Sales') {
            $this->sales_person_id = auth()->id();
        }

        // Normalize empty select values
        if (empty($this->sales_person_id)) {
            $this->sales_person_id = null;
        }

        if (is_string($this->company) && trim($this->company) === '') {
            $this->company = null;
        }

        $validated = $this->validate();

        if (auth()->check() && auth()->user()->role === 'Sales') {
            $validated['sales_person_id'] = auth()->id();
        }

        foreach (['company', 'email', 'alt_phone', 'gst_number', 'pan_number', 'billing_name', 'billing_address', 'source', 'industry', 'internal_notes', 'address', 'city'] as $field) {
            if (array_key_exists($field, $validated) && ($validated[$field] === '' || $validated[$field] === null)) {
                $validated[$field] = null;
            }
        }

        $codeService = app(CodeGeneratorService::class);
        $isEdit = (bool) $this->customerId;

        $auditService = app(AuditNotificationService::class);

        if ($this->customerId) {
            $customer = Customer::findOrFail($this->customerId);
            $customer->fill($validated);
            $structuredChanges = $auditService->computeChanges($customer);
            $customer->save();

            $label = $customer->company ?: ($customer->contact_name ?: $customer->customer_code);
            $diffSummaries = [];
            foreach ($structuredChanges as $field => $diff) {
                $diffSummaries[] = "{$field}: {$diff['old']} -> {$diff['new']}";
            }
            $diffText = ! empty($diffSummaries) ? ' ('.implode(', ', $diffSummaries).')' : '';

            $auditService->log(
                auth()->id(),
                'Updated Customer',
                'Customer',
                $customer->id,
                $customer->customer_code,
                "Updated customer {$label}{$diffText}",
                $structuredChanges
            );
        } else {
            $validated['customer_code'] = $codeService->generate('customer', 'CUS-');
            $validated['created_by'] = auth()->id();
            $customer = Customer::create($validated);
            $label = $customer->company ?: ($customer->contact_name ?: $customer->customer_code);
            $auditService->log(auth()->id(), 'Created Customer', 'Customer', $customer->id, $customer->customer_code, "Created customer {$label}");
        }

        $this->reset();
        $this->dispatch('customer-saved');
        $this->dispatch('close-modal', 'customer-modal');
        $this->dispatch('toast', ['msg' => 'Customer saved successfully', 'type' => 'ok']);

        if (! $isEdit) {
            $this->redirect(route('customers.show', $customer->id), navigate: true);
        }
    }

    public function render()
    {
        return view('livewire.customers.customer-modal', [
            'salesPeople' => User::where('role', 'Sales')->where('status', 'Active')->orderBy('name')->get(),
        ]);
    }
}
