<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\TemporaryPasswordMail;
use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\PasswordGeneratorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user) {
            return back()->with('status', 'If an active account exists with that email address, a password reset request has been logged.');
        }

        // Active/Inactive status verification
        if ($user->status !== 'Active') {
            return back()->withErrors([
                'email' => 'Your account is currently inactive. Please contact your system administrator.',
            ]);
        }

        $auditService = app(AuditNotificationService::class);

        // Role-differentiated reset behavior:
        // Admin: Temporary password emailed directly to Admin (admin->admin)
        if ($user->role === 'Admin') {
            $temporaryPassword = PasswordGeneratorService::generate(16);

            $user->password = $temporaryPassword;
            $user->must_change = true;
            $user->fail_count = 0;
            $user->locked_until = null;
            $user->save();

            PasswordResetRequest::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'status' => 'Resolved',
                'resolved_by' => $user->id,
                'resolved_at' => now(),
            ]);

            try {
                Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword, 'Admin'));
            } catch (\Throwable $exception) {
                Log::error('Admin password reset mail error: '.$exception->getMessage(), ['exception' => $exception]);
            }

            // In-app alert for all active Admins (including self)
            $adminIds = User::where('role', 'Admin')->where('status', 'Active')->pluck('id');
            foreach ($adminIds as $adminId) {
                $auditService->notify(
                    $adminId,
                    'Password Reset Requested',
                    'Admin Password Reset Alert',
                    $adminId === $user->id
                        ? 'You requested a password reset. Temporary credentials have been emailed to you.'
                        : "Administrator {$user->name} ({$user->email}) requested a password reset. Temporary credentials were sent.",
                    'settings',
                    $user->id
                );
            }

            $auditService->log(
                $user->id,
                'Self Password Reset',
                'User',
                $user->id,
                '',
                "Admin {$user->email} requested password reset; temporary password sent via email."
            );

            return back()->with('status', 'A temporary password has been emailed to you. Please check your inbox to sign in.');
        }

        // Manager: Temporary password emailed to Manager AND all active Admins (manager->admin,manager)
        if ($user->role === 'Manager') {
            $temporaryPassword = PasswordGeneratorService::generate(16);

            $user->password = $temporaryPassword;
            $user->must_change = true;
            $user->fail_count = 0;
            $user->locked_until = null;
            $user->save();

            PasswordResetRequest::create([
                'user_id' => $user->id,
                'ip_address' => $request->ip(),
                'status' => 'Resolved',
                'resolved_by' => $user->id,
                'resolved_at' => now(),
            ]);

            try {
                Mail::to($user->email)->send(new TemporaryPasswordMail($user, $temporaryPassword, 'Manager'));

                $adminEmails = User::where('role', 'Admin')->where('status', 'Active')->pluck('email');
                foreach ($adminEmails as $adminEmail) {
                    if ($adminEmail !== $user->email) {
                        Mail::to($adminEmail)->send(new TemporaryPasswordMail($user, $temporaryPassword, 'Admin'));
                    }
                }
            } catch (\Throwable $exception) {
                Log::error('Manager password reset mail error: '.$exception->getMessage(), ['exception' => $exception]);
            }

            // In-app alert for all active Admins
            $adminIds = User::where('role', 'Admin')->where('status', 'Active')->pluck('id');
            foreach ($adminIds as $adminId) {
                $auditService->notify(
                    $adminId,
                    'Manager Password Reset',
                    'Manager Password Reset',
                    "Manager {$user->name} requested a password reset. Temporary credentials have been emailed.",
                    'settings',
                    $user->id
                );
            }

            $auditService->log(
                $user->id,
                'Self Password Reset',
                'User',
                $user->id,
                '',
                "Manager {$user->email} requested password reset; temporary password emailed to manager and active administrators."
            );

            return back()->with('status', 'A temporary password has been emailed to you and your administrator. Please check your inbox to sign in.');
        }

        // Coordinator & Sales: NO email sent; in-app notification to all active Admins and direct Manager
        PasswordResetRequest::updateOrCreate(
            ['user_id' => $user->id, 'status' => 'Pending'],
            ['ip_address' => $request->ip(), 'resolved_by' => null, 'resolved_at' => null]
        );

        // 1. In-app notification to all active Admins
        $adminIds = User::where('role', 'Admin')->where('status', 'Active')->pluck('id');
        foreach ($adminIds as $adminId) {
            $auditService->notify(
                $adminId,
                'Password Reset Requested',
                'Password Reset Requested',
                "{$user->name} ({$user->role}) requested a password reset.",
                'settings',
                $user->id
            );
        }

        // 2. In-app notification to user's direct Manager (if any)
        if ($user->reporting_to) {
            $manager = User::find($user->reporting_to);
            if ($manager && $manager->status === 'Active' && $manager->role === 'Manager') {
                $auditService->notify(
                    $manager->id,
                    'Password Reset Requested',
                    'Team Password Reset Request',
                    "Your team member {$user->name} ({$user->role}) requested a password reset.",
                    'team',
                    $user->id
                );
            }
        }

        // 3. In-app confirmation for requesting user
        $auditService->notify(
            $user->id,
            'Password Reset Requested',
            'Password Reset Request Logged',
            'Your password reset request has been logged and sent to your manager/administrator for review.',
            'settings',
            $user->id
        );

        $auditService->log(
            $user->id,
            'Password Reset Requested',
            'User',
            $user->id,
            '',
            "User {$user->email} requested password reset from IP {$request->ip()}"
        );

        return back()->with('status', 'Password reset request has been logged. Your manager or administrator will issue your temporary credentials.');
    }
}
