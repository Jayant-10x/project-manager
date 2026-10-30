<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Services\AuditNotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $auditService = app(AuditNotificationService::class);
        $user = $request->user();
        $user->fill($request->validated());

        $structuredChanges = $auditService->computeChanges($user, ['updated_at', 'created_at', 'password', 'remember_token']);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if (! empty($structuredChanges)) {
            $details = [];
            foreach ($structuredChanges as $f => $d) {
                $details[] = "{$f}: {$d['old']} -> {$d['new']}";
            }
            $diffText = ! empty($details) ? ' ('.implode(', ', $details).')' : '';

            $auditService->log(
                $user->id,
                'Updated Profile',
                'User',
                $user->id,
                (string) $user->id,
                "User {$user->name} updated profile information{$diffText}",
                $structuredChanges
            );
        }

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();
        $userId = $user->id;
        $userName = $user->name;
        $userEmail = $user->email;

        app(AuditNotificationService::class)->log(
            $userId,
            'Deleted Account',
            'User',
            $userId,
            (string) $userId,
            "User {$userName} ({$userEmail}) deleted their account"
        );

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
