<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\AuditNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class Login extends Component
{
    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    protected function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'email.required' => 'The email address is required.',
            'email.email' => 'Please enter a valid email address.',
            'password.required' => 'The password is required.',
        ];
    }

    public function updated(string $propertyName): void
    {
        $this->validateOnly($propertyName);
    }

    public function authenticate()
    {
        $this->validate();

        $throttleKey = $this->throttleKey();

        $user = User::where('email', $this->email)->first();

        // Account Lockout Check
        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $seconds = now()->diffInSeconds($user->locked_until);
            $minutes = max(1, (int) ceil($seconds / 60));

            $this->addError('email', "Account locked due to multiple failed logins. Try again in {$minutes} minute(s).");

            return;
        }

        // IP Rate Limiting Check
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = ceil($seconds / 60);

            $this->addError('email', trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => $minutes,
            ]));

            return;
        }

        // Attempt Authentication
        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->remember)) {
            RateLimiter::hit($throttleKey);

            if ($user) {
                $user->fail_count = (int) $user->fail_count + 1;

                if ($user->fail_count >= 5) {
                    $user->fail_count = 0;
                    $user->locked_until = now()->addMinutes(30);
                    $user->save();

                    $auditService = app(AuditNotificationService::class);
                    $auditService->log(
                        $user->id,
                        'Account Locked',
                        'Auth',
                        $user->id,
                        '',
                        "Account {$user->email} locked for 30 minutes after 5 failed login attempts from IP ".request()->ip()
                    );

                    $auditService->notifyUplines(
                        $user,
                        'Account Locked Alert',
                        "Account Locked: {$user->name}",
                        "Account for {$user->name} ({$user->email}) was locked for 30 minutes after 5 consecutive failed login attempts from IP ".request()->ip().'.',
                        'settings'
                    );

                    $this->addError('email', 'Account locked for 30 minutes due to 5 failed attempts.');

                    return;
                }

                $user->save();
                $left = 5 - $user->fail_count;

                app(AuditNotificationService::class)->log(
                    $user->id,
                    'Failed Login Attempt',
                    'Auth',
                    $user->id,
                    '',
                    "Failed login attempt for {$user->email} from IP ".request()->ip()." ({$user->fail_count}/5 attempts)"
                );

                $this->addError('email', "Invalid email or password. {$left} attempt(s) left.");

                return;
            }

            $this->addError('email', trans('auth.failed'));

            return;
        }

        // Successful Authentication
        session()->regenerate();
        RateLimiter::clear($throttleKey);

        $authenticatedUser = Auth::user();
        if ($authenticatedUser) {
            $authenticatedUser->update([
                'fail_count' => 0,
                'locked_until' => null,
                'is_online' => true,
                'last_seen' => now(),
            ]);

            app(AuditNotificationService::class)->log(
                $authenticatedUser->id,
                'User Login',
                'Auth',
                $authenticatedUser->id,
                '',
                "User {$authenticatedUser->name} logged in from ".request()->ip()
            );

            if ($authenticatedUser->must_change) {
                return $this->redirect(route('password.change'), navigate: true);
            }
        }

        return $this->redirectIntended(default: route('dashboard', absolute: false), navigate: true);
    }

    protected function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->email).'|'.request()->ip());
    }

    public function render()
    {
        return view('livewire.auth.login')->layoutData([
            'title' => 'Sign In',
        ]);
    }
}
