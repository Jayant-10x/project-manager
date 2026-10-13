<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    <title>{{ config('app.name', '10X Agency CRM') }}</title>

    <link rel="icon" type="image/png" href="{{ asset('assets/logo.png') }}">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/crm-style.css') }}">
    @livewireStyles

    <script>
        document.addEventListener('alpine:init', () => {
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
                        return '$' + n.toLocaleString('en-US', {maximumFractionDigits: n < 100 ? 2 : 0});
                    }
                    if (short && Math.abs(n) >= 100000) return '₹' + (n / 100000).toFixed(n % 100000 === 0 ? 0 : 1) + 'L';
                    return '₹' + n.toLocaleString('en-IN', {maximumFractionDigits: 0});
                }
            });
        });
    </script>
</head>
<body class="bg-[#f5f7fb] text-[#0d1b34] font-sans antialiased"
      x-data="{
          collapsed: localStorage.getItem('pjt_sidebar_collapsed') === 'true',
          mobileOpen: false,
          toggleSidebar() {
              if (window.innerWidth <= 860) {
                  this.mobileOpen = !this.mobileOpen;
              } else {
                  this.collapsed = !this.collapsed;
                  localStorage.setItem('pjt_sidebar_collapsed', this.collapsed);
              }
          }
      }">

<div class="app">
    @include('layouts.partials.sidebar')

    <div class="main">
        <header class="topbar">
            <button class="icon-sq" id="burger" type="button" @click="toggleSidebar()" title="Toggle Sidebar">
                <svg viewBox="0 0 24 24">
                    <path d="M3 12h18M3 6h18M3 18h18"/>
                </svg>
            </button>

            <div>
                <div class="page-title">{{ $header ?? 'Dashboard' }}</div>
                <div class="page-sub">{{ $subtitle ?? '' }}</div>
            </div>

            <div class="top-actions">
                <livewire:components.global-search/>
                <livewire:components.notification-bell/>

                <button type="button"
                        class="btn btn-ghost btn-sm"
                        @click="$store.currency.toggle()"
                        x-text="$store.currency.cur"
                        title="Currency Toggle">
                </button>

                <button type="button"
                        class="btn btn-primary"
                        @click="$dispatch('open-customer-modal')">
                    <svg viewBox="0 0 24 24" class="w-4 h-4">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    New Customer
                </button>
            </div>
        </header>

        <main class="content" id="content">
            {{ $slot }}
        </main>
    </div>
</div>

<livewire:customers.customer-modal/>
<livewire:projects.project-modal/>
<livewire:payments.payment-modal/>

@livewireScripts
</body>
</html>
