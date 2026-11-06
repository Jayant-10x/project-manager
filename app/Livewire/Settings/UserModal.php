<?php

namespace App\Livewire\Settings;

use App\Constants\Permissions;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\PasswordGeneratorService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

class UserModal extends Component
{
    use WithFileUploads;

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
    public ?float $target = null;

    public ?float $currentAchieved = null;

    public ?int $currentDealsCount = null;

    public $avatarFile = null;

    public ?string $existingAvatarUrl = null;

    public array $userPermissions = [];

    public bool $customPermissions = false;

    public int $permKey = 1;

    public bool $showPass = false;

    public bool $openPerms = false;

    #[On('open-user-modal')]
    public function openModal(int|array|null $id = null, ?string $role = null): void
    {
        if (is_array($id)) {
            $role = $id['role'] ?? $role;
            $id = isset($id['id']) && $id['id'] !== null ? (int) $id['id'] : null;
        }

        $this->resetValidation();
        $this->reset();
        $this->showPass = false;
        $this->openPerms = false;
        $this->userId = $id;

        if ($id) {
            $user = User::findOrFail($id);
            $this->name = $user->name;
            $this->email = $user->email;
            $this->phone = $user->phone ?? '';
            $this->role = $user->role ?? '';
            $this->designation = $user->designation ?? '';
            $this->city = $user->city ?? '';
            $this->status = $user->status ?? '';
            $this->reporting_to = $user->reporting_to;
            $this->target = $user->target !== null ? (float) $user->target : null;
            $this->existingAvatarUrl = $user->avatar_url;

            if ($user->role === 'Sales') {
                $this->currentAchieved = (float) $user->salesProjects()->sum('value');
                $this->currentDealsCount = $user->salesProjects()->count();
            } else {
                $this->currentAchieved = null;
                $this->currentDealsCount = null;
            }

            if (! empty($user->permissions) && is_array($user->permissions)) {
                $this->userPermissions = $this->getNormalizedPermissionsForRole($this->role, $user->permissions);
                $this->customPermissions = true;
            } else {
                $this->userPermissions = $this->getNormalizedPermissionsForRole($this->role);
                $this->customPermissions = false;
            }
        } else {
            $this->role = $role ?? '';
            $this->designation = $this->role === 'Sales' ? 'Sales Person' : '';
            $this->status = '';
            $this->reporting_to = null;
            $this->target = null;
            $this->currentAchieved = null;
            $this->currentDealsCount = null;
            $this->existingAvatarUrl = null;
            $this->userPermissions = $this->getNormalizedPermissionsForRole($this->role);
            $this->customPermissions = false;
        }

        $this->permKey++;
        $this->dispatch('open-modal', 'user-modal');
    }

    public function togglePasswordVisibility(): void
    {
        $this->showPass = ! $this->showPass;
    }

    public function togglePermissionsDropdown(): void
    {
        $this->openPerms = ! $this->openPerms;
    }

    public function updatedRole(): void
    {
        if ($this->role === 'Admin') {
            $this->reporting_to = null;
        } elseif ($this->reporting_to) {
            $allowedSupervisorIds = $this->supervisors->pluck('id')->map(fn ($id) => (int) $id)->toArray();
            if (! in_array((int) $this->reporting_to, $allowedSupervisorIds, true)) {
                $this->reporting_to = null;
            }
        }

        if (! $this->customPermissions) {
            $this->userPermissions = $this->getNormalizedPermissionsForRole($this->role);
            $this->permKey++;
        }
    }

    public function updatedUserPermissions(): void
    {
        $this->customPermissions = true;
    }

    public function togglePermission(string $module, string $action): void
    {
        $current = (bool) ($this->userPermissions[$module][$action] ?? false);
        $this->userPermissions[$module][$action] = ! $current;
        $this->customPermissions = true;
    }

    public function resetPermissionsToRoleDefaults(): void
    {
        $this->userPermissions = $this->getNormalizedPermissionsForRole($this->role);
        $this->customPermissions = false;
        $this->permKey++;
        $this->dispatch('toast', ['msg' => "Permissions reset to {$this->role} defaults", 'type' => 'ok']);
    }

    public function getNormalizedPermissionsForRole(string $role, ?array $customOverrides = null): array
    {
        $roleDefaults = Permissions::forRole($role);
        $normalized = [];
        $modules = ['customers', 'projects', 'schedules', 'payments', 'today', 'reports', 'users', 'team', 'settings'];
        $actions = ['view', 'add', 'edit', 'delete', 'export'];

        foreach ($modules as $mod) {
            $normalized[$mod] = [];
            foreach ($actions as $act) {
                if ($customOverrides !== null && isset($customOverrides[$mod]) && array_key_exists($act, $customOverrides[$mod])) {
                    $normalized[$mod][$act] = (bool) $customOverrides[$mod][$act];
                } else {
                    $normalized[$mod][$act] = (bool) ($roleDefaults[$mod][$act] ?? false);
                }
            }
        }

        return $normalized;
    }

    public function generateRandomPassword(): void
    {
        $this->password = PasswordGeneratorService::generate(16);
    }

    public function getSupervisorsProperty()
    {
        $allowedRoles = match ($this->role) {
            'Admin' => ['Admin'],
            'Manager' => ['Admin'],
            'Coordinator', 'TechPerson' => ['Manager', 'Admin'],
            'Sales' => ['Coordinator'],
            default => ['Admin', 'Manager', 'Coordinator'],
        };

        return User::where('status', 'Active')
            ->when($this->userId, fn ($userQuery) => $userQuery->where('id', '!=', $this->userId))
            ->whereIn('role', $allowedRoles)
            ->orderByRaw("CASE role WHEN 'Admin' THEN 1 WHEN 'Manager' THEN 2 WHEN 'Coordinator' THEN 3 ELSE 4 END")
            ->orderBy('name')
            ->get();
    }

    protected function rules(): array
    {
        return [
            'name' => 'required|string|min:2|max:200',
            'email' => ['required', 'email', 'min:2', 'max:160', Rule::unique('users', 'email')->ignore($this->userId)],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($this->userId)],
            'role' => 'required|string|in:Admin,Manager,Coordinator,Sales,TechPerson',
            'reporting_to' => 'nullable|integer|exists:users,id',
            'designation' => 'nullable|string|min:2|max:60',
            'city' => 'nullable|string|min:2|max:60',
            'target' => 'nullable|numeric|min:0',
            'status' => 'required|string|in:Active,Inactive',
            'password' => ['nullable', 'string', 'min:8', 'max:200', 'regex:/[A-Z]/', 'regex:/[a-z]/', 'regex:/[0-9]/'],
            'avatarFile' => 'nullable|image|max:2048',
        ];
    }

    protected function messages(): array
    {
        return [
            'password.min' => 'Temporary password must be at least 8 characters.',
            'password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
            'avatarFile.image' => 'The avatar must be a valid image file (jpg, png, webp).',
            'avatarFile.max' => 'The avatar image size must not exceed 2MB.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'target' => 'monthly target',
            'reporting_to' => 'supervisor',
            'avatarFile' => 'profile picture',
        ];
    }

    public function updated(string $propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function save(): void
    {
        $action = $this->userId ? 'edit' : 'add';
        if (auth()->user()->role !== 'Admin' && ! auth()->user()->hasPermission('users', $action)) {
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

        if ($this->avatarFile) {
            $nameWithoutExt = pathinfo($this->avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $this->avatarFile->getClientOriginalExtension() ?: 'png';
            $safeName = Str::slug($nameWithoutExt) ?: 'avatar';
            $filename = "{$safeName}_".time().".{$extension}";
            $path = $this->avatarFile->storeAs('avatars', $filename, 'public');
            $validated['avatar'] = $path;

            if ($this->userId) {
                $oldUser = User::find($this->userId);
                if ($oldUser && $oldUser->avatar && Storage::disk('public')->exists($oldUser->avatar)) {
                    Storage::disk('public')->delete($oldUser->avatar);
                }
            }
        }
        unset($validated['avatarFile']);

        $validated['permissions'] = $this->customPermissions ? $this->userPermissions : null;

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
            $user->fill($validated);
            $structuredChanges = app(AuditNotificationService::class)->computeChanges($user, ['updated_at', 'created_at', 'password', 'remember_token', 'fail_count', 'locked_until']);
            $user->save();

            $diffSummaries = [];
            foreach ($structuredChanges as $field => $diff) {
                if ($field === 'permissions') {
                    $diffSummaries[] = 'permissions updated';
                } else {
                    $diffSummaries[] = "{$field}: {$diff['old']} -> {$diff['new']}";
                }
            }
            $diffText = ! empty($diffSummaries) ? ' ('.implode(', ', $diffSummaries).')' : '';

            app(AuditNotificationService::class)->log(
                auth()->id(),
                'Updated User',
                'User',
                $user->id,
                '',
                "Updated user {$user->name} ({$user->role}){$diffText}",
                $structuredChanges
            );

            if (isset($structuredChanges['reporting_to']) && $user->reporting_to && $user->reporting_to !== auth()->id()) {
                app(AuditNotificationService::class)->notify(
                    $user->reporting_to,
                    'Team Member Assigned',
                    "Team Member Assigned: {$user->name}",
                    "{$user->name} ({$user->role}) has been reassigned to report to you.",
                    'team',
                    $user->id
                );
            }
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

            if ($newUser->reporting_to && $newUser->reporting_to !== auth()->id()) {
                app(AuditNotificationService::class)->notify(
                    $newUser->reporting_to,
                    'Team Member Assigned',
                    "New Team Member: {$newUser->name}",
                    "{$newUser->name} ({$newUser->role}) has been added to your team.",
                    'team',
                    $newUser->id
                );
            }
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
            'supervisors' => $this->supervisors,
        ]);
    }
}
