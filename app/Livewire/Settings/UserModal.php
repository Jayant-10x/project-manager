<?php

namespace App\Livewire\Settings;

use App\Models\User;
use App\Services\AuditNotificationService;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;

class UserModal extends Component
{
    public ?int $userId = null;

    #[Validate]
    public string $name = '';

    #[Validate]
    public string $email = '';

    #[Validate]
    public string $phone = '';

    #[Validate]
    public string $role = '';

    #[Validate]
    public string $designation = '';

    #[Validate]
    public string $city = '';

    #[Validate]
    public string $status = '';

    #[Validate]
    public string $password = '';

    #[Validate]
    public ?int $reporting_to = null;

    #[Validate]
    public ?float $target = 0.0;

    #[On('open-user-modal')]
    public function openModal(?int $id = null, ?string $role = null): void
    {
        $this->resetValidation();
        $this->reset();
        $this->userId = $id;

        if ($id) {
            $user = User::findOrFail($id);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone ?? '';
            $this->role = $user->role ?? 'Sales';
            $this->designation = $user->designation ?? 'Sales Person';
            $this->city = $user->city ?? '';
            $this->status = $user->status ?? 'Active';
            $this->reporting_to = $user->reporting_to;
            $this->target = $user->target !== null ? (float) $user->target : 0.0;
        } else {
            $this->role = $role ?? 'Sales';
            $this->designation = $this->role === 'Sales' ? 'Sales Person' : '';
            $this->status = 'Active';
            $this->reporting_to = null;
            $this->target = 0.0;
        }

        $this->dispatch('open-modal', 'user-modal');
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:200',
            'email' => ['required', 'email', 'min:2', 'max:160', Rule::unique('users', 'email')->ignore($this->userId)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($this->userId)],
            'role' => 'required|string|in:Admin,Manager,Coordinator,Sales',
            'reporting_to' => 'nullable|integer|exists:users,id',
            'designation' => 'nullable|string|min:2|max:60',
            'city' => 'nullable|string|min:2|max:60',
            'target' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:Active,Inactive',
            'password' => ['nullable', 'string', 'min:8', 'max:200', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ];
    }

    protected function messages(): array
    {
        return [
            'password.min' => 'Temporary password must be at least 8 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'target' => 'monthly target',
            'reporting_to' => 'supervisor',
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
        if (auth()->user()->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Only administrators can manage users.', 'type' => 'err']);

            return;
        }

        $this->name = trim($this->name);
        $this->email = trim($this->email);
        $this->phone = trim($this->phone);
        $this->designation = trim($this->designation);
        $this->city = trim($this->city);
        if ($this->role === 'Sales') {
            $this->target = ($this->target === null || $this->target === '') ? 0.0 : (float) $this->target;
        } else {
            $this->target = 0.0;
        }

        $validated = $this->validate();

        $validated['phone'] = ! empty($validated['phone']) ? $validated['phone'] : null;
        $validated['city'] = ! empty($validated['city']) ? $validated['city'] : null;
        $validated['designation'] = ! empty($validated['designation']) ? $validated['designation'] : null;
        $validated['reporting_to'] = ! empty($validated['reporting_to']) ? (int) $validated['reporting_to'] : null;

        if ($this->role === 'Sales') {
            $validated['target'] = ($this->target === null || $this->target === '') ? 0.0 : (float) $this->target;
        } else {
            $validated['target'] = 0.0;
        }

        if ($this->userId) {
            $user = User::findOrFail($this->userId);

            // Prevent demoting last active admin
            if ($user->role === 'Admin' && $user->status === 'Active' && ($validated['role'] !== 'Admin' || $validated['status'] !== 'Active')) {
                $activeAdminCount = User::where('role', 'Admin')->where('status', 'Active')->count();
                if ($activeAdminCount <= 1) {
                    $this->dispatch('toast', ['msg' => 'Cannot demote or deactivate the last active Admin.', 'type' => 'err']);

                    return;
                }
            }

            if (! empty($validated['password'])) {
                $user->password = $validated['password'];
                $user->must_change = true;
                $user->fail_count = 0;
                $user->locked_until = null;
            }

            if (($validated['status'] ?? '') === 'Active') {
                $user->fail_count = 0;
                $user->locked_until = null;
            }

            unset($validated['password']);
            $user->update($validated);

            app(AuditNotificationService::class)->log(
                auth()->id(),
                'Updated User',
                'User',
                $user->id,
                '',
                "Updated user {$user->name} ({$user->role})"
            );
        } else {
            $tempPass = ! empty($validated['password']) ? $validated['password'] : 'Welcome@10x';
            $validated['password'] = $tempPass;
            $validated['must_change'] = true;
            $validated['fail_count'] = 0;
            $validated['locked_until'] = null;
            $validated['created_by'] = auth()->id();
            $newUser = User::create($validated);

            app(AuditNotificationService::class)->log(
                auth()->id(),
                'Created User',
                'User',
                $newUser->id,
                '',
                "Created user {$newUser->name} ({$newUser->role})"
            );
        }

        $this->reset();
        $this->dispatch('user-saved');
        $this->dispatch('sales-saved');
        $this->dispatch('close-modal', 'user-modal');
        $this->dispatch('toast', ['msg' => 'User saved successfully', 'type' => 'ok']);
    }

    public function render()
    {
        return view('livewire.settings.user-modal', [
            'supervisors' => User::where('status', 'Active')
                ->when($this->userId, fn ($q) => $q->where('id', '!=', $this->userId))
                ->whereIn('role', ['Admin', 'Manager', 'Coordinator'])
                ->orderBy('name')
                ->get(),
        ]);
    }
}
