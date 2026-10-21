<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Models\User;
use App\Services\AuditNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingIndex extends Component
{
    #[Url]
    public ?int $highlight_user = null;

    // Alert & System Settings
    public ?int $paymentAlertDays = 7;

    public ?int $amcAlertDays = 7;

    public ?int $followupAlertDays = 1;

    public string $companyName = '10X Agency';

    public string $displayCurrency = 'INR';

    // Password Change Fields
    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    public function mount(): void
    {
        $this->paymentAlertDays = (int) Setting::getValue('payment_alert_days', 7);
        $this->amcAlertDays = (int) Setting::getValue('amc_alert_days', 7);
        $this->followupAlertDays = (int) Setting::getValue('followup_alert_days', 1);
        $this->companyName = (string) Setting::getValue('company_name', '10X Agency');
        $this->displayCurrency = (string) Setting::getValue('display_currency', 'INR');
    }

    #[On('user-saved')]
    public function refreshUsers(): void
    {
        // Re-renders the user account table
    }

    protected function rules(): array
    {
        return [
            'paymentAlertDays' => 'required|integer|min:1|max:180',
            'amcAlertDays' => 'required|integer|min:1|max:180',
            'followupAlertDays' => 'required|integer|min:1|max:180',
            'companyName' => 'required|string|max:120',
            'displayCurrency' => 'required|in:INR,USD',
        ];
    }

    public function updated(string $propertyName): void
    {
        if (in_array($propertyName, ['paymentAlertDays', 'amcAlertDays', 'followupAlertDays'])) {
            $this->{$propertyName} = ($this->{$propertyName} === null || $this->{$propertyName} === '') ? null : (int) $this->{$propertyName};
        }

        $this->validateOnly($propertyName);
    }

    public function saveSettings(): void
    {
        if (auth()->user()->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Only administrators can update system settings.', 'type' => 'err']);

            return;
        }

        $this->paymentAlertDays = ($this->paymentAlertDays === null || $this->paymentAlertDays === '') ? 7 : (int) $this->paymentAlertDays;
        $this->amcAlertDays = ($this->amcAlertDays === null || $this->amcAlertDays === '') ? 7 : (int) $this->amcAlertDays;
        $this->followupAlertDays = ($this->followupAlertDays === null || $this->followupAlertDays === '') ? 1 : (int) $this->followupAlertDays;

        $validated = $this->validate();

        Setting::query()->updateOrCreate(['key' => 'payment_alert_days'], ['value' => $validated['paymentAlertDays']]);
        Setting::query()->updateOrCreate(['key' => 'amc_alert_days'], ['value' => $validated['amcAlertDays']]);
        Setting::query()->updateOrCreate(['key' => 'followup_alert_days'], ['value' => $validated['followupAlertDays']]);
        Setting::query()->updateOrCreate(['key' => 'company_name'], ['value' => trim($validated['companyName'])]);
        Setting::query()->updateOrCreate(['key' => 'display_currency'], ['value' => $validated['displayCurrency']]);

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Updated Settings',
            'Setting',
            null,
            '',
            'Updated system alert days and company settings'
        );

        $this->dispatch('toast', ['msg' => 'Settings saved successfully', 'type' => 'ok']);
    }

    public function updatePassword(): void
    {
        $user = auth()->user();

        $this->validate([
            'current_password' => 'required|string',
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'confirmed',
            ],
            'new_password_confirmation' => 'required|string',
        ], [
            'new_password.min' => 'Password must be at least 8 characters.',
            'new_password.regex' => 'Password must contain at least one uppercase letter, one lowercase letter, and one number.',
        ]);

        if (! Hash::check($this->current_password, $user->getAuthPassword())) {
            $this->addError('current_password', 'Your current password is incorrect.');

            return;
        }

        if ($this->current_password === $this->new_password) {
            $this->addError('new_password', 'The new password must be different from the current password.');

            return;
        }

        $user->password = $this->new_password;
        $user->must_change = false;
        $user->save();

        app(AuditNotificationService::class)->log(
            $user->id,
            'Changed Password',
            'User',
            $user->id,
            '',
            "User {$user->name} changed their password"
        );

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->resetValidation();
        $this->dispatch('toast', ['msg' => 'Password updated successfully', 'type' => 'ok']);
    }

    public ?array $tempCredentials = null;

    public function resetUserPassword(int $id): void
    {
        if (auth()->user()->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Only administrators can reset user credentials.', 'type' => 'err']);

            return;
        }

        $user = User::with('reportingTo')->findOrFail($id);

        // Generate a secure alphanumeric temporary password
        $randomPart = Str::random(6);
        $temporaryPassword = 'Temp#'.ucfirst($randomPart).rand(10, 99);

        $user->password = $temporaryPassword;
        $user->must_change = true;
        $user->fail_count = 0;
        $user->locked_until = null;
        $user->save();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Reset User Password',
            'User',
            $user->id,
            '',
            'Admin '.auth()->user()->name." generated temporary password for {$user->name}"
        );

        app(AuditNotificationService::class)->notify(
            $user->id,
            'auth',
            'Password Reset by Administrator',
            'Your password was reset by an administrator. Please log in with your temporary password and set a new personal password.',
            'settings'
        );

        $loginUrl = url('/login');
        $phone = preg_replace('/[^0-9]/', '', $user->phone ?? '');
        $whatsappText = "10X Agency CRM Login Credentials:\nURL: {$loginUrl}\nEmail: {$user->email}\nTemporary Password: {$temporaryPassword}\nNote: You will be prompted to set your personal password upon sign-in.";

        $this->tempCredentials = [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? '',
            'role' => $user->role,
            'designation' => $user->designation ?? $user->role,
            'supervisor' => $user->reportingTo?->name,
            'password' => $temporaryPassword,
            'loginUrl' => $loginUrl,
            'fullText' => $whatsappText,
            'whatsappUrl' => $phone ? "https://wa.me/{$phone}?text=".rawurlencode($whatsappText) : null,
            'mailtoUrl' => "mailto:{$user->email}?subject=".rawurlencode('10X Agency CRM — Your Temporary Password').'&body='.rawurlencode($whatsappText),
        ];

        $this->dispatch('open-modal', 'temp-credentials-modal');
        $this->dispatch('toast', ['msg' => 'Temporary password generated successfully', 'type' => 'ok']);
    }

    public function closeTempCredentialsModal(): void
    {
        $this->tempCredentials = null;
        $this->dispatch('close-modal', 'temp-credentials-modal');
    }

    public function deleteUser(int $id): void
    {
        if (auth()->user()->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Only administrators can manage user accounts.', 'type' => 'err']);

            return;
        }

        if (auth()->id() === $id) {
            $this->dispatch('toast', ['msg' => 'You cannot delete your own account.', 'type' => 'err']);

            return;
        }

        $target = User::findOrFail($id);

        if ($target->role === 'Admin' && $target->status === 'Active') {
            $activeAdminCount = User::where('role', 'Admin')->where('status', 'Active')->count();
            if ($activeAdminCount <= 1) {
                $this->dispatch('toast', ['msg' => 'Cannot delete the last active Admin account.', 'type' => 'err']);

                return;
            }
        }

        $name = $target->name;
        $role = $target->role;

        $target->delete();

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Deleted User',
            'User',
            $id,
            '',
            "Deleted user {$name} ({$role})"
        );

        $this->dispatch('toast', ['msg' => 'User removed', 'type' => 'ok']);
    }

    public function logout(): void
    {
        $user = Auth::user();
        if ($user) {
            $user->update([
                'is_online' => false,
                'last_seen' => now(),
            ]);
        }

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        $currentUser = auth()->user();

        if ($currentUser->role === 'Admin') {
            $users = User::with('reportingTo')
                ->orderByRaw("CASE role WHEN 'Admin' THEN 1 WHEN 'Manager' THEN 2 WHEN 'Coordinator' THEN 3 WHEN 'Sales' THEN 4 ELSE 5 END")
                ->orderBy('name')
                ->get();
        } elseif (in_array($currentUser->role, ['Manager', 'Coordinator'])) {
            $users = User::whereIn('id', $currentUser->getDownlineUserIds())
                ->with('reportingTo')
                ->orderByRaw("CASE role WHEN 'Admin' THEN 1 WHEN 'Manager' THEN 2 WHEN 'Coordinator' THEN 3 WHEN 'Sales' THEN 4 ELSE 5 END")
                ->orderBy('name')
                ->get();
        } else {
            $users = collect();
        }

        return view('livewire.settings.setting-index', [
            'users' => $users,
            'me' => $currentUser,
        ])->layoutData([
            'header' => 'Settings',
            'subtitle' => 'Alert days, company profile and your password',
        ]);
    }
}
