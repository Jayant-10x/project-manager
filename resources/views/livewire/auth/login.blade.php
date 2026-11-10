<div class="login-wrap">
    <div class="login-card">
        <!-- Brand Logo Plate -->
        <div class="login-logo-plate">
            <img class="login-logo" src="{{ asset('assets/logo.png') }}" alt="10X Agency">
        </div>

        <div class="login-title">Project Coordinator CRM</div>
        <div class="login-sub">10X Agency</div>

        <!-- Lockout / Rate-Limit Alert Box -->
        @if ($errors->has('email') && (str_contains($errors->first('email'), 'Account locked') || str_contains($errors->first('email'), 'Too many login attempts')))
            <div class="alert-box">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="9"/>
                    <path d="M12 8v5M12 16.5v.5"/>
                </svg>
                <span>{{ $errors->first('email') }}</span>
            </div>
        @endif

        @if (session('status'))
            <div class="alert-box ok">
                <svg viewBox="0 0 24 24">
                    <path d="M20 6L9 17l-5-5"/>
                </svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        <!-- Livewire Form Submission -->
        <form wire:submit="authenticate" novalidate>
            <label class="f" style="display:block;margin-top:22px">
                <span>Email address</span>
                <input class="i text-sm @error('email') border-red-500! bg-red-50/20! @enderror"
                       id="email"
                       type="email"
                       wire:model.blur="email"
                       placeholder="you@the10xagency.in"
                       autofocus
                       autocomplete="username">
                @error('email')
                    <span class="text-xs text-red-500! block mt-1">{{ $message }}</span>
                @enderror
            </label>

            <label class="f" style="display:block;margin-top:14px">
                <span>Password</span>
                <input class="i text-sm @error('password') border-red-500! bg-red-50/20! @enderror"
                       id="password"
                       type="password"
                       wire:model.blur="password"
                       placeholder="Your password"
                       autocomplete="current-password">
                @error('password')
                    <span class="text-xs text-red-500! block mt-1">{{ $message }}</span>
                @enderror
            </label>

            <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
                <label class="checkline" style="margin:0">
                    <input type="checkbox" wire:model="remember" id="remember_me">
                    <span style="font-size:12px;color:var(--text2)">Remember me</span>
                </label>

                @if (Route::has('password.request'))
                    <a class="link" href="{{ route('password.request') }}" style="font-size:12px">
                        Forgot password?
                    </a>
                @endif
            </div>

            <button type="submit"
                    class="btn btn-primary w-full justify-center mt-4 py-2.5"
                    wire:loading.attr="disabled"
                    wire:target="authenticate">
                <span wire:loading.remove wire:target="authenticate" class="inline-flex items-center justify-center gap-1.5">
                    <svg viewBox="0 0 24 24" class="w-4 h-4">
                        <rect x="4" y="10" width="16" height="11" rx="2"/>
                        <path d="M8 10V7a4 4 0 018 0v3"/>
                    </svg>
                    <span>Sign in</span>
                </span>
                <span wire:loading.inline-flex wire:target="authenticate" class="items-center justify-center gap-1.5">
                    <svg class="animate-spin w-4 h-4" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                    <span>Signing in...</span>
                </span>
            </button>
        </form>

    </div>
</div>
