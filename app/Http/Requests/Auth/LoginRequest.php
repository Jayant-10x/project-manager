<?php

namespace App\Http\Requests\Auth;

use App\Models\User;
use App\Services\AuditNotificationService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = User::where('email', $this->string('email'))->first();

        if (! Auth::attempt($this->only('email', 'password'), $this->boolean('remember'))) {
            RateLimiter::hit($this->throttleKey());

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
                        "Account {$user->email} locked for 30 minutes after 5 failed login attempts from IP {$this->ip()}"
                    );

                    $auditService->notifyUplines(
                        $user,
                        'Account Locked Alert',
                        "Account Locked: {$user->name}",
                        "Account for {$user->name} ({$user->email}) was locked for 30 minutes after 5 consecutive failed login attempts from IP {$this->ip()}.",
                        'settings',
                        $user->id
                    );

                    throw ValidationException::withMessages([
                        'email' => 'Account locked for 30 minutes due to 5 failed attempts.',
                    ]);
                }

                $user->save();
                $left = 5 - $user->fail_count;

                app(AuditNotificationService::class)->log(
                    $user->id,
                    'Failed Login Attempt',
                    'Auth',
                    $user->id,
                    '',
                    "Failed login attempt for {$user->email} from IP {$this->ip()} ({$user->fail_count}/5 attempts)"
                );

                throw ValidationException::withMessages([
                    'email' => "Invalid email or password. {$left} attempt(s) left.",
                ]);
            }

            throw ValidationException::withMessages([
                'email' => trans('auth.failed'),
            ]);
        }

        if ($user) {
            $user->update([
                'fail_count' => 0,
                'locked_until' => null,
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Ensure the login request is not rate limited.
     *
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        $user = User::where('email', $this->string('email'))->first();

        if ($user && $user->locked_until && $user->locked_until->isFuture()) {
            $seconds = now()->diffInSeconds($user->locked_until);
            $minutes = max(1, (int) ceil($seconds / 60));

            throw ValidationException::withMessages([
                'email' => "Account locked due to multiple failed logins. Try again in {$minutes} minute(s).",
            ]);
        }

        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'email' => trans('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => ceil($seconds / 60),
            ]),
        ]);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')).'|'.$this->ip());
    }
}
