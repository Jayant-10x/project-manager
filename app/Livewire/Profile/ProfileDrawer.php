<?php

namespace App\Livewire\Profile;

use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\PasswordGeneratorService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProfileDrawer extends Component
{
    use WithFileUploads;

    public bool $isOpen = false;

    public string $name = '';

    public string $email = '';

    public ?string $phone = null;

    public ?string $city = null;

    public ?string $role = '';

    public ?string $designation = null;

    public ?string $supervisorName = null;

    public ?string $supervisorRole = null;

    /** @var mixed */
    public $avatarFile = null;

    public ?string $existingAvatarUrl = null;

    public bool $removeAvatar = false;

    public bool $showPasswordSection = false;

    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    #[On('open-profile-drawer')]
    public function openDrawer(): void
    {
        $this->loadUserData();
        $this->isOpen = true;
    }

    public function closeDrawer(): void
    {
        $this->isOpen = false;
        $this->resetValidation();
        $this->reset([
            'avatarFile',
            'current_password',
            'new_password',
            'new_password_confirmation',
            'showPasswordSection',
            'removeAvatar',
        ]);
    }

    public function loadUserData(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->name = $user->name ?? '';
        $this->email = $user->email ?? '';
        $this->phone = $user->phone ?? '';
        $this->city = $user->city ?? '';
        $this->role = $user->role ?? '';
        $this->designation = $user->designation ?? '';
        $this->existingAvatarUrl = $user->avatar_url;
        $this->removeAvatar = false;
        $this->avatarFile = null;

        if ($user->reportingTo) {
            $this->supervisorName = $user->reportingTo->name;
            $this->supervisorRole = $user->reportingTo->role;
        } else {
            $this->supervisorName = null;
            $this->supervisorRole = null;
        }
    }

    public function markRemoveAvatar(): void
    {
        $this->removeAvatar = true;
        $this->avatarFile = null;
        $this->existingAvatarUrl = null;
    }

    public function togglePasswordSection(): void
    {
        $this->showPasswordSection = ! $this->showPasswordSection;
        if (! $this->showPasswordSection) {
            $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
            $this->resetValidation(['current_password', 'new_password', 'new_password_confirmation']);
        }
    }

    public function generateRandomPassword(): void
    {
        $password = PasswordGeneratorService::generate(16);
        $this->new_password = $password;
        $this->new_password_confirmation = $password;
    }

    public function save(): void
    {
        /** @var User|null $user */
        $user = Auth::user();
        if (! $user) {
            return;
        }

        $this->name = trim($this->name);
        $this->phone = trim($this->phone ?? '');
        $this->city = trim($this->city ?? '');

        $rules = [
            'name' => ['required', 'string', 'max:160'],
            'phone' => ['nullable', 'string', 'max:30', Rule::unique('users', 'phone')->ignore($user->id)],
            'city' => ['nullable', 'string', 'max:60'],
            'avatarFile' => ['nullable', 'image', 'max:2048', 'mimes:jpeg,jpg,png,webp'],
        ];

        if ($this->showPasswordSection || ! empty($this->new_password) || ! empty($this->current_password)) {
            $rules['current_password'] = [
                'required',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    if (! Hash::check((string) $value, $user->getAuthPassword())) {
                        $fail('The current password provided is incorrect.');
                    }
                },
            ];
            $rules['new_password'] = [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'confirmed',
                'different:current_password',
            ];
        }

        $messages = [
            'new_password.min' => 'Password must be at least 8 characters.',
            'new_password.regex' => 'Password must contain at least 1 uppercase letter, 1 lowercase letter, and 1 number.',
            'new_password.different' => 'The new password must be different from the current password.',
        ];

        $this->validate($rules, $messages);

        $auditService = app(AuditNotificationService::class);

        $user->name = $this->name;
        $user->phone = ! empty($this->phone) ? $this->phone : null;
        $user->city = ! empty($this->city) ? $this->city : null;

        $structuredChanges = $auditService->computeChanges($user, ['updated_at', 'created_at', 'password', 'remember_token', 'fail_count', 'locked_until']);

        // Avatar handling
        $avatarChanged = false;
        if ($this->removeAvatar) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = null;
            $avatarChanged = true;
        } elseif ($this->avatarFile) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $nameWithoutExt = pathinfo($this->avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $this->avatarFile->getClientOriginalExtension() ?: 'png';
            $safeName = Str::slug($nameWithoutExt) ?: 'avatar';
            $filename = "{$safeName}_".time().".{$extension}";
            $user->avatar = $this->avatarFile->storeAs('avatars', $filename, 'public');
            $avatarChanged = true;
        }

        // Password change handling
        $passwordChanged = false;
        if (! empty($this->new_password)) {
            $user->password = $this->new_password;
            $user->must_change = false;
            $user->fail_count = 0;
            $user->locked_until = null;
            $passwordChanged = true;
        }

        $user->save();

        if ($passwordChanged) {
            $auditService->log(
                $user->id,
                'Changed Password',
                'User',
                $user->id,
                (string) $user->id,
                "User {$user->name} changed their password from the profile drawer"
            );
        }

        if (! empty($structuredChanges) || $avatarChanged) {
            $details = [];
            foreach ($structuredChanges as $f => $d) {
                $details[] = "{$f}: {$d['old']} -> {$d['new']}";
            }
            if ($this->avatarFile) {
                $details[] = 'avatar: uploaded new photo';
            } elseif ($this->removeAvatar) {
                $details[] = 'avatar: removed photo';
            }
            $diffText = ! empty($details) ? ' ('.implode(', ', $details).')' : '';

            $auditService->log(
                $user->id,
                'Updated Profile',
                'User',
                $user->id,
                (string) $user->id,
                "User {$user->name} updated profile details{$diffText}",
                $structuredChanges
            );
        }

        $this->dispatch('toast', ['msg' => 'Profile updated successfully!', 'type' => 'ok']);
        $this->dispatch('profile-updated');
        $this->closeDrawer();
    }

    public function render(): View
    {
        return view('livewire.profile.profile-drawer');
    }
}
