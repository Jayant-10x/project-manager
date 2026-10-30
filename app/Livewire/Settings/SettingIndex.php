<?php

namespace App\Livewire\Settings;

use App\Mail\TemporaryPasswordMail;
use App\Models\PasswordResetRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\PasswordGeneratorService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
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
    /** @var array<int> */
    public array $reminderDays = [7, 3, 1];

    public ?int $newReminderDay = null;

    // Retained for backwards-compatibility
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
        if (auth()->user()->role !== 'Admin') {
            abort(403, 'Unauthorized: Only administrators have access to system settings.');
        }

        $this->reminderDays = Setting::getPaymentReminderDays();
        $this->paymentAlertDays = ! empty($this->reminderDays) ? $this->reminderDays[0] : 7;
        $this->amcAlertDays = (int) Setting::getValue('amc_alert_days', 7);
        $this->followupAlertDays = (int) Setting::getValue('followup_alert_days', 1);
        $this->companyName = (string) Setting::getValue('company_name', '10X Agency');
        $this->displayCurrency = (string) Setting::getValue('display_currency', 'INR');
    }

    public function addReminderDay(): void
    {
        if ($this->newReminderDay === null || $this->newReminderDay === '') {
            $this->addError('newReminderDay', 'Please enter a valid day count (0–180).');

            return;
        }

        $day = (int) $this->newReminderDay;
        if ($day < 0 || $day > 180) {
            $this->addError('newReminderDay', 'Reminder days must be between 0 (on due date) and 180.');

            return;
        }

        if (in_array($day, $this->reminderDays, true)) {
            $this->addError('newReminderDay', "A reminder threshold for {$day} day(s) is already configured.");

            return;
        }

        $this->reminderDays[] = $day;
        rsort($this->reminderDays);
        $this->reminderDays = array_values($this->reminderDays);
        $this->newReminderDay = null;
        $this->resetValidation(['newReminderDay', 'reminderDays']);
    }

    public function removeReminderDay(int $day): void
    {
        $this->reminderDays = array_values(array_filter($this->reminderDays, fn ($d) => $d !== $day));

        if (empty($this->reminderDays)) {
            $this->addError('reminderDays', 'At least one payment reminder threshold is required.');
        } else {
            $this->resetValidation('reminderDays');
        }
    }

    public function applyPreset(string $preset): void
    {
        $this->reminderDays = match ($preset) {
            'standard' => [7, 3, 1, 0],
            'intensive' => [14, 7, 3, 1, 0],
            'minimal' => [3, 0],
            'single' => [7],
            default => [7, 3, 1],
        };

        rsort($this->reminderDays);
        $this->reminderDays = array_values($this->reminderDays);
        $this->resetValidation(['newReminderDay', 'reminderDays']);
    }

    #[On('user-saved')]
    #[On('profile-updated')]
    public function refreshUsers(): void
    {
        // Re-renders the component when users or profile change
    }

    protected function rules(): array
    {
        return [
            'reminderDays' => 'required|array|min:1',
            'reminderDays.*' => 'integer|min:0|max:180',
            'companyName' => 'required|string|max:120',
            'displayCurrency' => 'required|in:INR,USD',
        ];
    }

    public function saveSettings(): void
    {
        if (auth()->user()->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Only administrators can update system settings.', 'type' => 'err']);

            return;
        }

        if (empty($this->reminderDays)) {
            $this->addError('reminderDays', 'At least one payment reminder threshold must be configured.');

            return;
        }

        $validated = $this->validate();

        rsort($this->reminderDays);
        $this->reminderDays = array_values(array_unique(array_map('intval', $this->reminderDays)));

        // Store as JSON array string for multi-value persistence
        Setting::query()->updateOrCreate(
            ['key' => 'payment_alert_days'],
            ['value' => json_encode($this->reminderDays)]
        );

        Setting::query()->updateOrCreate(
            ['key' => 'company_name'],
            ['value' => trim($validated['companyName'])]
        );

        Setting::query()->updateOrCreate(
            ['key' => 'display_currency'],
            ['value' => $validated['displayCurrency']]
        );

        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Updated Settings',
            'Setting',
            null,
            '',
            'Updated payment reminder thresholds to: '.implode(', ', $this->reminderDays).' days'
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
        $currentUser = auth()->user();
        if ($currentUser->role !== 'Admin') {
            $this->dispatch('toast', ['msg' => 'Only administrators can reset user credentials.', 'type' => 'err']);

            return;
        }

        $user = User::with('reportingTo')->findOrFail($id);

        if ($user->status !== 'Active') {
            $this->dispatch('toast', ['msg' => 'Cannot reset credentials for an inactive user. Activate the account first.', 'type' => 'err']);

            return;
        }

        // Generate a cryptographically secure random password without dictionary words
        $temporaryPassword = PasswordGeneratorService::generate(16);

        $user->password = $temporaryPassword;
        $user->must_change = true;
        $user->fail_count = 0;
        $user->locked_until = null;
        $user->save();

        // Mark any pending reset requests for this user as resolved
        PasswordResetRequest::where('user_id', $user->id)
            ->where('status', 'Pending')
            ->update([
                'status' => 'Resolved',
                'resolved_by' => $currentUser->id,
                'resolved_at' => now(),
            ]);

        app(AuditNotificationService::class)->log(
            $currentUser->id,
            'Reset User Password',
            'User',
            $user->id,
            '',
            "{$currentUser->role} {$currentUser->name} generated temporary password for {$user->name} ({$user->role})"
        );

        app(AuditNotificationService::class)->notify(
            $user->id,
            'Password Reset by Administrator',
            'Password Reset by Administrator',
            'Your password was reset by an administrator/manager. Please log in with your temporary credentials and set a new password.',
            'settings'
        );

        // Role-differentiated Email Dispatch:
        // Admin: email sent to Admin
        // Manager: email sent to Admin and Manager
        // Coordinator / Sales: No email sent (in-app only)
        if ($user->role === 'Admin') {
            try {
                Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword, 'Admin'));
            } catch (\Throwable) {
                // Safe failover if SMTP is unconfigured
            }
        } elseif ($user->role === 'Manager') {
            try {
                Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword, 'Manager'));
                $adminEmails = User::where('role', 'Admin')->where('status', 'Active')->pluck('email');
                foreach ($adminEmails as $admEmail) {
                    if ($admEmail !== $user->email) {
                        Mail::to($admEmail)->send(new TemporaryPasswordMail($user, $temporaryPassword, 'Admin'));
                    }
                }
            } catch (\Throwable) {
                // Safe failover
            }
        }

        $loginUrl = url('/login');
        $phone = preg_replace('/[^0-9]/', '', $user->phone ?? '');
        $whatsappText = "Project Manager 10X Agency Login Credentials:\nURL: {$loginUrl}\nEmail: {$user->email}\nTemporary Password: {$temporaryPassword}\nNote: You will be prompted to set your personal password upon sign-in.";

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
            'mailtoUrl' => "mailto:{$user->email}?subject=".rawurlencode('Project Manager 10X Agency — Your Temporary Password').'&body='.rawurlencode($whatsappText),
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

            app(AuditNotificationService::class)->log(
                $user->id,
                'User Logout',
                'Auth',
                $user->id,
                '',
                "User {$user->name} logged out"
            );
        }

        Auth::logout();
        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }
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

        // Pending password reset requests for user's scope
        $pendingResetRequestsQuery = PasswordResetRequest::with('user')
            ->where('status', 'Pending');

        if ($currentUser->role === 'Manager') {
            $pendingResetRequestsQuery->whereIn('user_id', $currentUser->getDownlineUserIds());
        } elseif ($currentUser->role !== 'Admin') {
            $pendingResetRequestsQuery->where('user_id', $currentUser->id);
        }

        $pendingResetRequests = $pendingResetRequestsQuery->orderByDesc('id')->get();

        return view('livewire.settings.setting-index', [
            'users' => $users,
            'pendingResetRequests' => $pendingResetRequests,
            'me' => $currentUser,
        ])->layoutData([
            'header' => 'Settings',
            'subtitle' => 'Alert days, company profile and your password',
        ]);
    }
}
