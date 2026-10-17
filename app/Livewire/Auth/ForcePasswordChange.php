<?php

namespace App\Livewire\Auth;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.guest')]
class ForcePasswordChange extends Component
{
    public string $current_password = '';

    public string $new_password = '';

    public string $new_password_confirmation = '';

    protected function rules(): array
    {
        return [
            'current_password' => ['required', 'string'],
            'new_password' => [
                'required',
                'string',
                'min:8',
                'regex:/[A-Z]/',
                'regex:/[a-z]/',
                'regex:/[0-9]/',
                'confirmed',
            ],
            'new_password_confirmation' => ['required', 'string'],
        ];
    }

    protected function messages(): array
    {
        return [
            'new_password.min' => 'Password must be at least 8 characters.',
            'new_password.regex' => 'Password must contain at least 1 uppercase letter, 1 lowercase letter, and 1 number.',
        ];
    }

    public function updatePassword()
    {
        $this->validate();

        $user = auth()->user();

        if (! Hash::check($this->current_password, $user->getAuthPassword())) {
            $this->addError('current_password', 'The current password is incorrect.');

            return;
        }

        if ($this->current_password === $this->new_password) {
            $this->addError('new_password', 'The new password must be different from the current password.');

            return;
        }

        $user->password = $this->new_password;
        $user->must_change = false;
        $user->save();

        session()->flash('status', 'Password set successfully.');

        return $this->redirect(route('dashboard'), navigate: true);
    }

    public function logout()
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return $this->redirect(route('login'), navigate: true);
    }

    public function render()
    {
        return view('livewire.auth.force-password-change');
    }
}
