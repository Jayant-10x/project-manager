<?php

namespace App\Livewire\Settings;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class SettingIndex extends Component {
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

    public function mount(): void {
        $this->paymentAlertDays = (int)Setting::getValue('payment_alert_days', 7);
        $this->amcAlertDays = (int)Setting::getValue('amc_alert_days', 7);
        $this->followupAlertDays = (int)Setting::getValue('followup_alert_days', 1);
        $this->companyName = (string)Setting::getValue('company_name', '10X Agency');
        $this->displayCurrency = (string)Setting::getValue('display_currency', 'INR');
    }

    #[On('user-saved')]
    public function refreshUsers(): void {
        // Re-renders the user account table
    }

    protected function rules(): array {
        return [
            'paymentAlertDays' => 'required|integer|min:1|max:180',
            'amcAlertDays' => 'required|integer|min:1|max:180',
            'followupAlertDays' => 'required|integer|min:1|max:180',
            'companyName' => 'required|string|max:120',
            'displayCurrency' => 'required|in:INR,USD',
        ];
    }

    public function updated(string $propertyName): void {
        if (in_array($propertyName, ['paymentAlertDays', 'amcAlertDays', 'followupAlertDays'])) {
            $this->{$propertyName} = ($this->{$propertyName} === null || $this->{$propertyName} === '') ? null : (int)$this->{$propertyName};
        }

        $this->validateOnly($propertyName);
    }

    public function saveSettings(): void {
        if (auth()->user()->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Only administrators can update system settings.', 'kind' => 'err']);
            return;
        }

        $this->paymentAlertDays = ($this->paymentAlertDays === null || $this->paymentAlertDays === '') ? 7 : (int)$this->paymentAlertDays;
        $this->amcAlertDays = ($this->amcAlertDays === null || $this->amcAlertDays === '') ? 7 : (int)$this->amcAlertDays;
        $this->followupAlertDays = ($this->followupAlertDays === null || $this->followupAlertDays === '') ? 1 : (int)$this->followupAlertDays;

        $validated = $this->validate();

        Setting::query()->updateOrCreate(['k' => 'payment_alert_days'], ['v' => $validated['paymentAlertDays']]);
        Setting::query()->updateOrCreate(['k' => 'amc_alert_days'], ['v' => $validated['amcAlertDays']]);
        Setting::query()->updateOrCreate(['k' => 'followup_alert_days'], ['v' => $validated['followupAlertDays']]);
        Setting::query()->updateOrCreate(['k' => 'company_name'], ['v' => trim($validated['companyName'])]);
        Setting::query()->updateOrCreate(['k' => 'display_currency'], ['v' => $validated['displayCurrency']]);

        $this->dispatch('toast', ['msg' => 'Settings saved successfully', 'kind' => 'ok']);
    }

    public function updatePassword(): void {
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

        if (!Hash::check($this->current_password, $user->getAuthPassword())) {
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

        $this->reset(['current_password', 'new_password', 'new_password_confirmation']);
        $this->resetValidation();
        $this->dispatch('toast', ['msg' => 'Password updated successfully', 'kind' => 'ok']);
    }

    public function deleteUser(int $id): void {
        if (auth()->user()->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Only administrators can manage user accounts.', 'kind' => 'err']);
            return;
        }

        if (auth()->id() === $id) {
            $this->dispatch('toast', ['msg' => 'You cannot delete your own account.', 'kind' => 'err']);
            return;
        }

        $target = User::findOrFail($id);

        if ($target->role === 'Admin' && $target->status === 'Active') {
            $activeAdminCount = User::where('role', 'Admin')->where('status', 'Active')->count();
            if ($activeAdminCount <= 1) {
                $this->dispatch('toast', ['msg' => 'Cannot delete the last active Admin account.', 'kind' => 'err']);
                return;
            }
        }

        $target->delete();
        $this->dispatch('toast', ['msg' => 'User removed', 'kind' => 'ok']);
    }

    public function logout(): void {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();
        $this->redirect(route('login'), navigate: true);
    }

    public function render() {
        $users = User::orderByRaw('FIELD(role, "Admin", "Coordinator")')->orderBy('name')->get();

        return view('livewire.settings.setting-index', [
            'users' => $users,
            'me' => auth()->user(),
        ])->layoutData([
            'header' => 'Settings',
            'subtitle' => 'Alert days, company profile and your password',
        ]);
    }
}
