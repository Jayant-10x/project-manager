<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AuthController extends Controller {
    public function __construct(
        protected AuditNotificationService $auditService
    ) {
    }

    public function ping(): JsonResponse {
        return response()->json([
            'ok' => true,
            'message' => 'PJT CRM API is running',
            'driver' => 'laravel-mysql',
            'login' => 'email+password',
            'time' => Carbon::now()->toIso8601String(),
        ]);
    }

    public function login(Request $request): JsonResponse {
        $email = strtolower(trim((string)$request->input('email', '')));
        $password = (string)$request->input('password', '');

        if (empty($email) || empty($password)) {
            return response()->json(['ok' => false, 'error' => 'Enter both email and password.']);
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'Invalid email or password.']);
        }

        if ($user->status !== 'Active') {
            return response()->json(['ok' => false, 'error' => 'This account is inactive. Contact your admin.']);
        }

        if ($user->locked_until && Carbon::parse($user->locked_until)->isFuture()) {
            $mins = max(1, (int)ceil(Carbon::now()->diffInMinutes(Carbon::parse($user->locked_until))));
            return response()->json([
                'ok' => false,
                'error' => "Too many failed attempts. The account is locked for {$mins} minute(s)."
            ]);
        }

        if (!Hash::check($password, $user->pass_hash)) {
            $failCount = $user->fail_count + 1;
            $maxFails = config('crm.max_login_fails', 5);
            $lockMinutes = config('crm.lock_minutes', 30);

            if ($failCount >= $maxFails) {
                $user->update([
                    'fail_count' => 0,
                    'locked_until' => Carbon::now()->addMinutes($lockMinutes),
                ]);
                return response()->json([
                    'ok' => false,
                    'error' => "Too many failed attempts. The account has been locked for {$lockMinutes} minutes."
                ]);
            }

            $user->update(['fail_count' => $failCount]);
            return response()->json([
                'ok' => false,
                'error' => 'Invalid email or password. ' . ($maxFails - $failCount) . ' attempt(s) left.'
            ]);
        }

        $token = bin2hex(random_bytes(24));
        $user->update([
            'token' => $token,
            'token_at' => Carbon::now(),
            'online' => true,
            'last_seen' => Carbon::now(),
            'fail_count' => 0,
            'locked_until' => null,
        ]);

        $this->auditService->log($user->name, 'login', 'user', $user->id, '', 'Signed in');

        return response()->json([
            'ok' => true,
            'token' => $token,
            'mustChange' => (int)$user->must_change,
            'user' => [
                'id' => (int)$user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'designation' => $user->designation,
            ]
        ]);
    }

    public function logout(Request $request): JsonResponse {
        $user = $request->user();
        if ($user) {
            $user->update([
                'token' => null,
                'token_at' => null,
                'online' => false,
            ]);
        }

        return response()->json(['ok' => true]);
    }

    public function forgotPassword(Request $request): JsonResponse {
        $email = strtolower(trim((string)$request->input('email', '')));
        if (empty($email)) {
            return response()->json(['ok' => false, 'error' => 'Enter your email.']);
        }

        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();
        if (!$user || $user->status !== 'Active') {
            return response()->json([
                'ok' => true,
                'sent' => true,
                'message' => 'If this email is registered, a reset link has been sent.'
            ]);
        }

        $rawToken = bin2hex(random_bytes(32));
        $ttlSeconds = config('crm.reset_ttl', 7200);

        $user->update([
            'reset_hash' => hash('sha256', $rawToken),
            'reset_expires' => Carbon::now()->addSeconds($ttlSeconds),
            'reset_used' => false,
        ]);

        $resetUrl = rtrim(config('crm.app_url'), '/') . '/?reset=' . $rawToken;
        $appShort = config('crm.app_short', 'PJT CRM');
        $validHours = (int)($ttlSeconds / 3600);

        $html = '<div style="font-family:Segoe UI,Arial,sans-serif;max-width:520px;margin:0 auto;color:#0d1b34">'
            . '<div style="background:linear-gradient(135deg,#0a2a5e,#0b3d91);padding:22px 24px;border-radius:14px 14px 0 0">'
            . '<h2 style="color:#fff;margin:0;font-size:18px">' . htmlspecialchars($appShort) . '</h2>'
            . '<div style="color:#b9cbef;font-size:12px;margin-top:3px">Password reset</div></div>'
            . '<div style="border:1px solid #e2e8f3;border-top:none;border-radius:0 0 14px 14px;padding:24px">'
            . '<p style="font-size:14px">Hi <b>' . htmlspecialchars($user->name) . '</b>,</p>'
            . '<p style="font-size:14px">Click the button below to reset your password:</p>'
            . '<p style="text-align:center;margin:26px 0"><a href="' . $resetUrl . '" style="background:#0b3d91;color:#fff;text-decoration:none;padding:12px 26px;border-radius:10px;font-weight:600;display:inline-block">Set new password</a></p>'
            . '<p style="font-size:12.5px;color:#4a5875">This link is valid for <b>' . $validHours . ' hour(s)</b> and can be used only once.</p>'
            . '<p style="font-size:12px;color:#8894ac">If you did not request this, you can ignore this email.</p>'
            . '<div style="border-top:1px solid #eef2f9;margin-top:20px;padding-top:14px;font-size:11.5px;color:#8894ac;word-break:break-all">' . $resetUrl . '</div>'
            . '</div></div>';

        try {
            Mail::html($html, function ($message) use ($user, $appShort) {
                $message->to($user->email)
                    ->subject("Reset your {$appShort} password");
            });
            $sent = true;
        } catch (\Throwable) {
            $sent = false;
        }

        return response()->json([
            'ok' => true,
            'sent' => $sent,
            'message' => $sent
                ? 'Reset link sent. Check your inbox and spam folder.'
                : 'The email could not be sent. Ask your admin to reset your password.'
        ]);
    }

    public function checkReset(Request $request): JsonResponse {
        $raw = (string)$request->input('reset', '');
        if (empty($raw)) {
            return response()->json(['ok' => false, 'error' => 'Invalid reset link.']);
        }

        $user = User::query()->where('reset_hash', hash('sha256', $raw))->first();
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'This reset link is not valid. Request a new one.']);
        }
        if ($user->reset_used) {
            return response()->json(['ok' => false, 'error' => 'This link has already been used.']);
        }
        if (Carbon::parse($user->reset_expires)->isPast()) {
            return response()->json(['ok' => false, 'error' => 'This link has expired. Request a new one.']);
        }

        return response()->json(['ok' => true, 'name' => $user->name, 'email' => $user->email]);
    }

    public function resetPassword(Request $request): JsonResponse {
        $raw = (string)$request->input('reset', '');
        $password = (string)$request->input('password', '');

        if (empty($raw)) {
            return response()->json(['ok' => false, 'error' => 'Invalid reset link.']);
        }

        if ($problem = $this->validatePasswordStrength($password)) {
            return response()->json(['ok' => false, 'error' => $problem]);
        }

        $user = User::query()->where('reset_hash', hash('sha256', $raw))->first();
        if (!$user) {
            return response()->json(['ok' => false, 'error' => 'This reset link is not valid.']);
        }
        if ($user->reset_used) {
            return response()->json(['ok' => false, 'error' => 'This link has already been used.']);
        }
        if (Carbon::parse($user->reset_expires)->isPast()) {
            return response()->json(['ok' => false, 'error' => 'This link has expired. Request a new one.']);
        }

        $user->update([
            'pass_hash' => Hash::make($password),
            'reset_used' => true,
            'reset_hash' => null,
            'token' => null,
            'token_at' => null,
            'must_change' => false,
            'fail_count' => 0,
            'locked_until' => null,
        ]);

        $this->auditService->log($user->name, 'reset', 'user', $user->id, '', 'Password reset via email link');

        return response()->json(['ok' => true, 'message' => 'Password set. Sign in with your new password.']);
    }

    public function changePassword(Request $request): JsonResponse {
        $user = $request->user();
        $old = (string)$request->input('oldPassword', '');
        $new = (string)$request->input('newPassword', '');

        if ($problem = $this->validatePasswordStrength($new)) {
            return response()->json(['ok' => false, 'error' => $problem]);
        }

        if (!Hash::check($old, $user->pass_hash)) {
            return response()->json(['ok' => false, 'error' => 'Your current password is incorrect.']);
        }

        if ($old === $new) {
            return response()->json(['ok' => false, 'error' => 'The new password must be different from the current one.']);
        }

        $user->update([
            'pass_hash' => Hash::make($new),
            'must_change' => false,
        ]);

        $this->auditService->log($user->name, 'update', 'user', $user->id, '', 'Changed own password');

        return response()->json(['ok' => true, 'message' => 'Password changed.']);
    }

    public function me(Request $request): JsonResponse {
        $user = $request->user();
        return response()->json([
            'ok' => true,
            'user' => [
                'id' => (int)$user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'designation' => $user->designation,
            ]
        ]);
    }

    private function validatePasswordStrength(string $password): ?string {
        if (strlen($password) < 8) return 'Password must be at least 8 characters.';
        if (!preg_match('/[A-Z]/', $password)) return 'Password must contain a capital letter.';
        if (!preg_match('/[a-z]/', $password)) return 'Password must contain a lowercase letter.';
        if (!preg_match('/[0-9]/', $password)) return 'Password must contain a number.';
        return null;
    }
}
