@php
    $code = $code ?? '404';
    $title = $title ?? 'Page Not Found';
    $heading = $heading ?? $title;
    $message = $message ?? 'The requested page or resource could not be found.';
    $badgeClass = $badgeClass ?? 'bg-amber-50 text-amber-800 border border-amber-200';
@endphp

@if(auth()->check())
    <x-app-layout :header="$title" :subtitle="$heading">
        <div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <div class="card p-8 sm:p-12 text-center shadow-sm rounded-2xl border border-[#e2e8f3] bg-white">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider {{ $badgeClass }} mb-6">
                    <span class="inline-block w-2 h-2 rounded-full bg-current opacity-75"></span>
                    {{ $code }} · {{ $title }}
                </div>

                <div class="my-4 flex justify-center text-gray-400">
                    @yield('icon')
                </div>

                <h1 class="text-2xl sm:text-3xl font-bold text-[#0d1b34] tracking-tight mb-3">
                    {{ $heading }}
                </h1>

                <p class="text-[#4a5875] text-sm sm:text-base max-w-lg mx-auto mb-8 leading-relaxed">
                    {{ $message }}
                </p>

                <div class="flex flex-wrap items-center justify-center gap-3 pt-2">
                    @yield('actions')
                </div>
            </div>
        </div>
    </x-app-layout>
@else
    <x-guest-layout :title="$title">
        <div class="min-h-screen flex flex-col justify-center items-center p-6 bg-[#f5f7fb]">
            <div class="w-full max-w-md card p-8 sm:p-10 shadow-md rounded-2xl border border-[#e2e8f3] bg-white text-center">
                <div class="mb-6 flex justify-center">
                    <img src="{{ asset('assets/logo.png') }}" alt="{{ config('app.name', 'Project Manager 10X Agency') }}" class="h-9 w-auto">
                </div>

                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold uppercase tracking-wider {{ $badgeClass }} mb-5">
                    <span class="inline-block w-2 h-2 rounded-full bg-current opacity-75"></span>
                    {{ $code }} · {{ $title }}
                </div>

                <h1 class="text-xl font-bold text-[#0d1b34] mb-2">
                    {{ $heading }}
                </h1>

                <p class="text-[#4a5875] text-sm mb-6 leading-relaxed">
                    {{ $message }}
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="{{ route('login') }}" class="btn btn-primary w-full sm:w-auto">Return to Sign In</a>
                    <button onclick="window.history.back()" type="button" class="btn btn-ghost w-full sm:w-auto">Go Back</button>
                </div>
            </div>
        </div>
    </x-guest-layout>
@endif
