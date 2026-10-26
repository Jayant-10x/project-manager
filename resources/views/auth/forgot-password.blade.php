<x-guest-layout title="Forgot Password">
    <div class="login-wrap">
        <div class="login-card">
            <div class="login-logo-plate">
                <img class="login-logo" src="{{ asset('assets/logo.png') }}" alt="10X Agency">
            </div>

            <div class="login-title">Reset your password</div>
            <div class="login-sub">Forgot your password</div>

            @if (session('status'))
                <div class="alert-box ok">
                    <svg viewBox="0 0 24 24">
                        <path d="M20 6L9 17l-5-5"/>
                    </svg>
                    <span>{{ session('status') }}</span>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert-box">
                    <svg viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="9"/>
                        <path d="M12 8v5M12 16.5v.5"/>
                    </svg>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <div style="font-size:12.6px;color:var(--text2);line-height:1.6;margin-top:20px">
                Enter your registered email address. Your request will be forwarded to the system administrator for temporary credential provisioning.
            </div>

            <form method="POST" action="{{ route('password.email') }}">
                @csrf

                <label class="f" style="display:block;margin-top:16px">
                    <span>Email address</span>
                    <input class="i"
                           id="email"
                           type="email"
                           name="email"
                           value="{{ old('email') }}"
                           placeholder="you@the10xagency.in"
                           required
                           autofocus>
                </label>

                <button type="submit"
                        class="btn btn-primary"
                        style="width:100%;justify-content:center;margin-top:16px;padding:11px">
                    <svg viewBox="0 0 24 24" style="width:15px;height:15px">
                        <rect x="2" y="4" width="20" height="16" rx="2"/>
                        <path d="M22 6l-10 7L2 6"/>
                    </svg>
                    Request Password Reset
                </button>
            </form>

            <div class="login-hint">
                <a href="{{ route('login') }}" class="link">Back to sign in</a>
            </div>
        </div>
    </div>
</x-guest-layout>
