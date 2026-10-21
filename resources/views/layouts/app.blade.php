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
          collapsed: window.innerWidth > 860 ? (localStorage.getItem('pjt_sidebar_collapsed') === 'true') : false,
          mobileOpen: false,
          toggleSidebar() {
              if (window.innerWidth <= 860) {
                  this.mobileOpen = !this.mobileOpen;
              } else {
                  this.collapsed = !this.collapsed;
                  localStorage.setItem('pjt_sidebar_collapsed', this.collapsed);
              }
          }
      }"
      @notification-updated.window="if ($event.detail && typeof $event.detail.unreadCount !== 'undefined') $store.notifications.setCount($event.detail.unreadCount)"
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
        });

        Alpine.store('notifications', {
            unread: {{ \App\Models\Notification::where('user_id', auth()->id())->where('is_read', false)->count() }},
            setCount(val) {
                this.unread = parseInt(val, 10) || 0;
            }
        });
      ">

<div class="app">
    <!-- Mobile Sidebar Backdrop Overlay -->
    <div class="sidebar-backdrop"
         x-show="mobileOpen"
         x-transition:enter="transition-opacity ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition-opacity ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         @click="mobileOpen = false"
         style="display: none;"></div>

    <!-- Complete Responsive Sidebar -->
    @include('layouts.partials.sidebar')

    <!-- Main View Content Stream -->
    <div class="main">
        <header class="topbar">
            <!-- Burger Button: Toggles 70px icon-rail on desktop or off-canvas drawer on mobile -->
            <button class="icon-sq" id="burger" type="button" @click.stop="toggleSidebar()" title="Toggle Sidebar">
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
                        @click="$dispatch('open-customer-modal', { id: null })">
                    <svg viewBox="0 0 24 24" class="w-4 h-4">
                        <path d="M12 5v14M5 12h14"/>
                    </svg>
                    <span class="btn-lbl">New Customer</span>
                </button>
            </div>
        </header>

        <!-- Main Page View Slot -->
        <main class="content" id="content">
            {{ $slot }}
        </main>
    </div>
</div>

<!-- Global Slide-Over Notification Drawer -->
<livewire:components.notification-drawer/>

<!-- Global Application Modals -->
<livewire:customers.customer-modal/>
<livewire:projects.project-modal/>
<livewire:payments.payment-modal/>
<livewire:followups.followup-modal/>
<livewire:documents.document-preview-modal/>
<livewire:settings.user-modal/>
<x-confirm-modal/>

@livewireScripts

<!-- Native Desktop Browser Notification Poller -->
<div x-data="browserNotifier({{ auth()->id() }})" x-init="initNotifier()"></div>

<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('browserNotifier', (userId) => ({
            pollInterval: 5000, // 30 seconds
            timer: null,
            storageKey: `crm_last_seen_notif_${userId}`,
            lastSeenId: 0,
            alertedIds: new Set(),

            initNotifier() {
                const stored = localStorage.getItem(this.storageKey);
                this.lastSeenId = stored !== null ? parseInt(stored, 10) : 0;

                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }

                this.poll();
                this.timer = setInterval(() => this.poll(), this.pollInterval);
            },

            async poll() {
                try {
                    const response = await fetch(`{{ route('api.notifications.poll') }}?after_id=${this.lastSeenId}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) return;

                    const data = await response.json();

                    // Synchronize global Alpine notification store with actual unread count
                    if (typeof data.unread_count !== 'undefined') {
                        if (window.Alpine && Alpine.store('notifications')) {
                            Alpine.store('notifications').setCount(data.unread_count);
                        }
                    }

                    // If backend instructs cursor reset (first load or cleared database)
                    if (data.reset_cursor) {
                        this.lastSeenId = data.latest_id;
                        localStorage.setItem(this.storageKey, this.lastSeenId);
                        return;
                    }

                    // Process new unread notifications
                    if (data.items && data.items.length > 0) {
                        data.items.forEach(item => {
                            if (!this.alertedIds.has(item.id)) {
                                this.alertedIds.add(item.id);
                                this.showDesktopNotification(item);
                            }
                            if (item.id > this.lastSeenId) {
                                this.lastSeenId = item.id;
                            }
                        });

                        localStorage.setItem(this.storageKey, this.lastSeenId);

                        // Broadcast notification-updated event with latest unread count
                        window.dispatchEvent(new CustomEvent('notification-updated', {
                            detail: {unreadCount: data.unread_count}
                        }));
                    } else if (data.latest_id > this.lastSeenId) {
                        this.lastSeenId = data.latest_id;
                        localStorage.setItem(this.storageKey, this.lastSeenId);
                    }
                } catch (err) {
                    // Silently bypass intermittent connection interruptions
                }
            },

            showDesktopNotification(item) {
                if (!('Notification' in window) || Notification.permission !== 'granted') {
                    return;
                }

                const notification = new Notification(item.title, {
                    body: item.body,
                    icon: '{{ asset("assets/logo.png") }}',
                    tag: `crm-notif-${item.id}` // Prevent duplicate operating system banners
                });

                notification.onclick = () => {
                    window.focus();
                    notification.close();

                    if (item.link_type === 'project' && item.link_id) {
                        window.location.href = `/project/${item.link_id}`;
                    } else if (item.link_type === 'customer' && item.link_id) {
                        window.location.href = `/customer/${item.link_id}`;
                    } else {
                        window.location.href = `/notifications/${item.id}`;
                    }
                };
            }
        }));
    });
</script>
</body>
</html>
