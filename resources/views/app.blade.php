<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name', '10X Agency CRM') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}">

    <!-- Scripts & Styles via Vite -->
    @vite(['resources/css/app.css'])
    <link rel="stylesheet" href="{{ asset('css/crm-style.css') }}">
    @livewireStyles
</head>
<body class="bg-[#f5f7fb] text-[#0d1b34] font-sans antialiased"
      x-data
      x-init="
        Alpine.store('currency', {
            cur: localStorage.getItem('pjt_cur') || 'INR',
            rate: {{ app(\App\Services\CurrencyRateService::class)->getUsdInrRate() }},
            toggle() {
                this.cur = this.cur === 'INR' ? 'USD' : 'INR';
                localStorage.setItem('pjt_cur', this.cur);
            },
            format(val, short = false) {
                let n = Number(val || 0);
                if (this.cur === 'USD') {
                    n = n / this.rate;
                    if (short && Math.abs(n) >= 1000) return '$' + (n / 1000).toFixed(n % 1000 === 0 ? 0 : 1) + 'K';
                    return '$' + n.toLocaleString('en-US', { maximumFractionDigits: n < 100 ? 2 : 0 });
                }
                if (short && Math.abs(n) >= 100000) return '₹' + (n / 100000).toFixed(n % 100000 === 0 ? 0 : 1) + 'L';
                return '₹' + n.toLocaleString('en-IN', { maximumFractionDigits: 0 });
            }
        })
      ">

<div class="app flex min-h-screen">
    <!-- Collapsible Sidebar -->
    @include('layouts.partials.sidebar')

    <!-- Main Content Stream -->
    <div class="main flex-1 flex flex-col min-w-0">
        <!-- Topbar -->
        <header
            class="topbar sticky top-0 z-40 bg-white/95 backdrop-blur border-b border-[#e2e8f3] px-6 py-3 flex items-center gap-4">
            <button class="icon-sq md:hidden" @click="$dispatch('toggle-sidebar')">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
            </button>

            <div>
                <h1 class="page-title text-lg font-bold text-[#0d1b34]">{{ $header ?? 'Dashboard' }}</h1>
                <p class="page-sub text-xs text-[#8894ac]">{{ $subtitle ?? '' }}</p>
            </div>

            <div class="top-actions ml-auto flex items-center gap-3">
                <!-- Inline Global Search -->
                @livewire('components.global-search')

                <!-- Notification Bell (Polls every 60s) -->
                @livewire('components.notification-bell')

                <!-- Currency Switcher -->
                <button type="button"
                        class="btn btn-ghost btn-sm font-semibold"
                        @click="$store.currency.toggle()"
                        x-text="$store.currency.cur">
                </button>

                <button type="button"
                        class="btn btn-primary btn-sm"
                        @click="$dispatch('open-modal', 'create-customer')">
                    + New Customer
                </button>
            </div>
        </header>

        <!-- Page Body Slot -->
        <main class="content flex-1 p-6">
            {{ $slot }}
        </main>
    </div>
</div>

<!-- Global Modals Root -->
@livewire('customers.customer-modal')
@livewire('projects.project-modal')
@livewire('payments.payment-modal')
@vite(['resources/js/app.js'])
@livewireScripts
</body>
</html>
