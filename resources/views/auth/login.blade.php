<x-guest-layout title="Sign In">
    <div class="login-wrap">
        <div class="login-card">
            <!-- Brand Logo Plate -->
            <div class="login-logo-plate">
                <img class="login-logo" src="{{ asset('assets/logo.png') }}" alt="10X Agency">
            </div>

            <div class="login-title">Project Coordinator CRM</div>
            <div class="login-sub">10X Agency</div>

            <!-- Validation & Error Banner -->
            @if ($errors->any())
                <div class="alert-box">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16.5v.5"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
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

            <!-- Standard Laravel Breeze Form Submission -->
            <form method="POST" action="{{ route('login') }}">
                @csrf

                <label class="f" style="display:block;margin-top:22px">
                    <span>Email address</span>
                    <input class="i"
                           id="email"
                           type="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="you@the10xagency.in"
                           required
                           autofocus
                           autocomplete="username">
                </label>

                <label class="f" style="display:block;margin-top:14px">
                    <span>Password</span>
                    <input class="i"
                           id="password"
                           type="password"
                           name="password"
                           placeholder="Your password"
                           required
                           autocomplete="current-password">
                </label>

                <div style="display:flex;justify-content:space-between;align-items:center;margin-top:12px">
                    <label class="checkline" style="margin:0">
                        <input type="checkbox" name="remember" id="remember_me">
                        <span style="font-size:12px;color:var(--text2)">Remember me</span>
                    </label>

                    @if (Route::has('password.request'))
                        <a class="link" href="{{ route('password.request') }}" style="font-size:12.3px">
                            Forgot password?
                        </a>
                    @endif
                </div>

                <button type="submit"
                        class="btn btn-primary"
                        style="width:100%;justify-content:center;margin-top:16px;padding:11px">
                    <svg viewBox="0 0 24 24" style="width:15px;height:15px">
                        <rect x="4" y="10" width="16" height="11" rx="2"/>
                        <path d="M8 10V7a4 4 0 018 0v3"/>
                    </svg>
                    Sign in
                </button>
            </form>

        </div>
    </div>
</x-guest-layout>
