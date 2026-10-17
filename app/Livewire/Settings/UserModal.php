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

    #[On('open-user-modal')]
    public function openModal(?int $id = null): void
    {
        $this->resetValidation();
        $this->reset();
        $this->userId = $id;

        if ($id) {
            $user = User::findOrFail($id);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone ?? null;
            $this->role = $user->role ?? 'Sales';
            $this->designation = $user->designation ?? 'Sales Person';
            $this->city = $user->city ?? null;
            $this->status = $user->status ?? 'Inactive';
            $this->reporting_to = $user->reporting_to;
        } else {
            $this->role = 'Sales';
            $this->designation = 'Sales Person';
            $this->status = 'Active';
            $this->reporting_to = null;
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
            'status' => 'required|string|in:Active,Inactive',
            'password' => [$this->userId ? 'nullable' : 'required', 'string', 'min:8', 'max:200', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
        ];
    }

    protected function messages(): array
    {
        return [
            'password.min' => 'Temporary password must be at least 8 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ];
    }

    public function updated(string $propertyName): void
    {
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

        $validated = $this->validate();

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
        $this->dispatch('close-modal', 'user-modal');
        $this->dispatch('toast', ['msg' => 'User saved successfully', 'type' => 'ok']);
        $this->redirect(route('settings.index'), navigate: true);
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
