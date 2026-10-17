<?php

namespace App\Livewire\Sales;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class SalesModal extends Component
{
    public ?int $salesId = null;

    public string $name = '';

    public string $phone = '';

    public string $email = '';

    public ?float $target = 0.0;

    public string $status = 'Active';

    #[On('open-sales-modal')]
    public function openModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset();
        $this->salesId = $id;

        if ($id) {
            $sp = User::where('role', 'Sales')->findOrFail($id);
            $this->name = $sp->name;
            $this->phone = $sp->phone ?? '';
            $this->email = $sp->email ?? '';
            $this->target = $sp->target !== null ? (float) $sp->target : 0.0;
            $this->status = $sp->status ?? 'Active';
        } else {
            $this->target = 0.0;
            $this->status = 'Active';
        }

        $this->dispatch('open-modal', 'sales-modal');
    }

    protected function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:80',
            ],
            'phone' => 'nullable|string|max:30',
            'email' => [
                'nullable',
                'email',
                'max:160',
                Rule::unique('users', 'email')->ignore($this->salesId),
            ],
            'target' => 'required|numeric|min:0',
            'status' => 'required|in:Active,Inactive',
        ];
    }

    public function updated(string $propertyName): void
    {
        if ($propertyName === 'target') {
            $this->target = ($this->target === null || $this->target === '') ? 0.0 : (float) $this->target;
        }

        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        $this->name = trim($this->name);
        $this->phone = trim($this->phone);
        $this->email = trim($this->email);
        $this->target = ($this->target === null || $this->target === '') ? 0.0 : (float) $this->target;

        $validated = $this->validate();

        if ($this->salesId) {
            $sp = User::where('role', 'Sales')->findOrFail($this->salesId);
            $sp->update([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?: null,
                'email' => $validated['email'] ?: null,
                'target' => $validated['target'],
                'status' => $validated['status'],
            ]);
        } else {
            $email = $validated['email'] ?: 'sales.'.Str::slug($this->name).'.'.rand(100, 999).'@internal.local';
            User::create([
                'name' => $validated['name'],
                'phone' => $validated['phone'] ?: null,
                'email' => $email,
                'role' => 'Sales',
                'designation' => 'Sales Representative',
                'target' => $validated['target'],
                'status' => $validated['status'],
                'password' => Hash::make(Str::random(16)),
            ]);
        }

        $this->reset();
        $this->dispatch('sales-saved');
        $this->dispatch('close-modal', 'sales-modal');
        $this->dispatch('toast', ['msg' => 'Salesperson saved successfully', 'type' => 'ok']);
        $this->redirect(route('sales.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.sales.sales-modal');
    }
}
