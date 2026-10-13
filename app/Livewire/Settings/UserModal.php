<?php

namespace App\Livewire\Settings;

use App\Models\User;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;

class UserModal extends Component {
    public ?int $userId = null;
    public string $name = '';
    public string $email = '';
    public string $phone = '';
    public string $role = 'Coordinator';
    public string $designation = 'Project Coordinator';
    public string $city = '';
    public string $status = 'Active';
    public string $password = '';

    #[On('open-user-modal')]
    public function openModal(?int $id = null): void {
        $this->resetValidation();
        $this->reset();
        $this->userId = $id;

        if ($id) {
            $user = User::findOrFail($id);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone ?? '';
            $this->role = $user->role ?? 'Coordinator';
            $this->designation = $user->designation ?? 'Project Coordinator';
            $this->city = $user->city ?? '';
            $this->status = $user->status ?? 'Active';
        } else {
            $this->role = 'Coordinator';
            $this->designation = 'Project Coordinator';
            $this->status = 'Active';
        }

        $this->dispatch('open-modal', 'user-modal');
    }

    protected function rules(): array {
        $passwordRules = ['nullable', 'string', 'min:8', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'];

        return [
            'name' => 'required|string|max:80',
            'email' => [
                'required',
                'email',
                'max:160',
                Rule::unique('users', 'email')->ignore($this->userId),
            ],
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:Coordinator,Admin',
            'designation' => 'nullable|string|max:60',
            'city' => 'nullable|string|max:60',
            'status' => 'required|in:Active,Inactive',
            'password' => $passwordRules,
        ];
    }

    protected function messages(): array {
        return [
            'password.min' => 'Temporary password must be at least 8 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ];
    }

    public function updated(string $propertyName): void {
        $this->validateOnly($propertyName);
    }

    public function save(): void {
        if (auth()->user()->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Only administrators can manage users.', 'kind' => 'err']);
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
                    $this->dispatch('toast', ['msg' => 'Cannot demote or deactivate the last active Admin.', 'kind' => 'err']);
                    return;
                }
            }

            if (!empty($validated['password'])) {
                $user->password = $validated['password'];
                $user->must_change = true;
            }

            unset($validated['password']);
            $user->update($validated);
        } else {
            $tempPass = !empty($validated['password']) ? $validated['password'] : 'Welcome@10x';
            $validated['password'] = $tempPass;
            $validated['must_change'] = true;
            $validated['joined'] = now()->toDateString();
            User::create($validated);
        }

        $this->reset();
        $this->dispatch('user-saved');
        $this->dispatch('close-modal', 'user-modal');
        $this->dispatch('toast', ['msg' => 'User saved successfully', 'kind' => 'ok']);
        $this->redirect(route('settings.index'), navigate: true);
    }

    public function render() {
        return view('livewire.settings.user-modal');
    }
}
