<?php

namespace App\Livewire\Profile;

use App\Models\User;
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
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $numbers = '23456789';
        $symbols = '@#$%&*!';

        $pass = '';
        $pass .= $upper[random_int(0, strlen($upper) - 1)];
        $pass .= $lower[random_int(0, strlen($lower) - 1)];
        $pass .= $numbers[random_int(0, strlen($numbers) - 1)];
        $pass .= $symbols[random_int(0, strlen($symbols) - 1)];

        $all = $upper.$lower.$numbers.$symbols;
        for ($i = 0; $i < 8; $i++) {
            $pass .= $all[random_int(0, strlen($all) - 1)];
        }

        $shuffled = str_shuffle($pass);
        $this->new_password = $shuffled;
        $this->new_password_confirmation = $shuffled;
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
            $rules['new_password'] = ['required', 'string', 'min:8', 'confirmed'];
        }

        $this->validate($rules);

        $user->name = $this->name;
        $user->phone = ! empty($this->phone) ? $this->phone : null;
        $user->city = ! empty($this->city) ? $this->city : null;

        // Avatar handling
        if ($this->removeAvatar) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $user->avatar = null;
        } elseif ($this->avatarFile) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $nameWithoutExt = pathinfo($this->avatarFile->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $this->avatarFile->getClientOriginalExtension() ?: 'png';
            $safeName = Str::slug($nameWithoutExt) ?: 'avatar';
            $filename = "{$safeName}_".time().".{$extension}";
            $user->avatar = $this->avatarFile->storeAs('avatars', $filename, 'public');
        }

        // Password change handling
        if (! empty($this->new_password)) {
            $user->password = $this->new_password;
            $user->must_change = false;
            $user->fail_count = 0;
            $user->locked_until = null;
        }

        $user->save();

        $this->dispatch('toast', ['msg' => 'Profile updated successfully!', 'type' => 'ok']);
        $this->dispatch('profile-updated');
        $this->closeDrawer();
    }

    public function render(): View
    {
        return view('livewire.profile.profile-drawer');
    }
}
