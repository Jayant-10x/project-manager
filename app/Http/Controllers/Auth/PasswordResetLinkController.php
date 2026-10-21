<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
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

        $user = User::where('email', $request->email)->where('status', 'Active')->first();

        if ($user) {
            $adminIds = User::where('role', 'Admin')->where('status', 'Active')->pluck('id');
            $auditService = app(AuditNotificationService::class);

            foreach ($adminIds as $adminId) {
                $auditService->notify(
                    $adminId,
                    'auth',
                    'Password Reset Requested',
                    "{$user->name} ({$user->email}) requested a password reset.",
                    'settings',
                    $user->id
                );
            }

            $auditService->log(
                $user->id,
                'Password Reset Requested',
                'User',
                $user->id,
                '',
                "User {$user->email} requested password reset from IP {$request->ip()}"
            );
        }

        return back()->with('status', 'Password reset request has been sent to the admin. Please ask or wait for your temporary credentials.');
    }
}
