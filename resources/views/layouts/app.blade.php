<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="robots" content="noindex, nofollow">
    @php
        $pageTitle = trim($__env->yieldContent('title'));
        if (empty($pageTitle)) {
            $pageTitle = $title ?? ($header ?? null);
        }
        $appName = config('app.name', 'Project Manager 10X Agency');
        if ($pageTitle && str_contains($pageTitle, $appName)) {
            $fullTitle = $pageTitle;
        } else {
            $fullTitle = $pageTitle ? "{$pageTitle} | {$appName}" : $appName;
        }
    @endphp
    <title>{{ $fullTitle }}</title>

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

        window.ModalManager = {
            openModals: new Set(),
            open(id) {
                if (id) this.openModals.add(id);
                document.body.classList.add('overflow-hidden');
            },
            close(id) {
                if (id) this.openModals.delete(id);
                if (this.openModals.size === 0) {
                    document.body.classList.remove('overflow-hidden');
                }
                setTimeout(() => this.sync(), 120);
            },
            sync() {
                const visibleModals = Array.from(document.querySelectorAll('[data-modal], [data-confirm-modal]')).filter(el => {
                    if (!el.isConnected) return false;
                    if (el.style.display === 'none') return false;
                    try {
                        if (window.getComputedStyle(el).display === 'none') return false;
                    } catch (e) {}
                    if (el._x_dataStack && el._x_dataStack[0] && el._x_dataStack[0].show === false) return false;
                    return true;
                });

                if (visibleModals.length === 0) {
                    this.openModals.clear();
                    document.body.classList.remove('overflow-hidden');
                } else {
                    document.body.classList.add('overflow-hidden');
                }
            }
        };

        window.addEventListener('livewire:navigated', () => {
            window.ModalManager.openModals.clear();
            document.body.classList.remove('overflow-hidden');
        });

        window.addEventListener('close-modal', (e) => {
            const name = e.detail?.name || (Array.isArray(e.detail) ? e.detail[0] : e.detail);
            if (name) {
                window.ModalManager.close(name);
            } else {
                window.ModalManager.openModals.clear();
                document.body.classList.remove('overflow-hidden');
            }
            setTimeout(() => window.ModalManager.sync(), 100);
        });

        // First Error Field Auto-Scroll & Focus Engine
        window.scrollToFirstError = function(scope) {
            let root = (typeof scope === 'string' ? document.querySelector(scope) : scope) || null;
            if (!root || !root.isConnected) {
                const activeModal = Array.from(document.querySelectorAll('[data-modal], [data-confirm-modal], .modal')).find(el => {
                    if (!el.isConnected) return false;
                    if (el.style.display === 'none') return false;
                    try {
                        if (window.getComputedStyle(el).display === 'none') return false;
                    } catch (e) {}
                    if (el._x_dataStack && el._x_dataStack[0] && el._x_dataStack[0].show === false) return false;
                    return true;
                });

                const activeDrawer = document.querySelector('.drawer.open, [data-drawer].open');
                root = activeModal || activeDrawer || document;
            }

            const candidates = Array.from(root.querySelectorAll(
                'input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]'
            ));

            let firstErrorField = null;

            for (const input of candidates) {
                const classStr = input.className || '';
                const isDirectlyInvalid = input.getAttribute('aria-invalid') === 'true' ||
                    classStr.includes('border-red') ||
                    classStr.includes('is-invalid') ||
                    classStr.includes('bg-red');

                if (isDirectlyInvalid) {
                    firstErrorField = input;
                    break;
                }

                const parent = input.closest('label, .f, .form-group, .form-row, td, tr');
                if (parent) {
                    const errorEl = Array.from(parent.querySelectorAll('[class*="text-red"], .err, .invalid-feedback, [role="alert"]')).find(el => {
                        const txt = el.textContent.trim();
                        return txt.length > 0 && txt !== '*' && txt !== '•' && !el.closest('span:has(> input)');
                    });
                    if (errorEl) {
                        firstErrorField = input;
                        break;
                    }
                }
            }

            if (!firstErrorField) {
                const errorSpans = Array.from(root.querySelectorAll(
                    '.err, [class*="text-red-500"], [class*="text-red-600"], .invalid-feedback, [role="alert"]'
                )).filter(el => {
                    const txt = el.textContent.trim();
                    return txt.length > 0 && txt !== '*' && txt !== '•';
                });

                if (errorSpans.length > 0) {
                    const firstMsg = errorSpans[0];
                    const container = firstMsg.closest('label, .f, .form-group, .form-row, td, tr, div');
                    if (container) {
                        firstErrorField = container.querySelector('input:not([type="hidden"]):not([disabled]), select:not([disabled]), textarea:not([disabled])') || firstMsg;
                    } else {
                        firstErrorField = firstMsg;
                    }
                }
            }

            if (firstErrorField) {
                if (firstErrorField.offsetParent === null) {
                    const hiddenTab = firstErrorField.closest('[data-tab-content], [data-tab-pane], [id^="tab-"]');
                    if (hiddenTab) {
                        const tabId = hiddenTab.getAttribute('data-tab') || hiddenTab.id;
                        const trigger = document.querySelector(`[data-tab-target="${tabId}"], button[href="#${tabId}"]`);
                        if (trigger) trigger.click();
                    }
                }

                firstErrorField.scrollIntoView({
                    behavior: 'smooth',
                    block: 'center',
                    inline: 'nearest'
                });

                setTimeout(() => {
                    if (typeof firstErrorField.focus === 'function') {
                        try {
                            firstErrorField.focus({ preventScroll: true });
                        } catch (e) {
                            firstErrorField.focus();
                        }
                    }

                    firstErrorField.classList.add('error-focused');
                    setTimeout(() => {
                        firstErrorField.classList.remove('error-focused');
                    }, 1000);
                }, 100);

                return true;
            }

            return false;
        };

        let isFormSubmitting = false;

        document.addEventListener('submit', () => {
            isFormSubmitting = true;
            setTimeout(() => {
                window.scrollToFirstError();
            }, 80);
        }, true);

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('button[type="submit"], button[wire\\:click*="save"], button[wire\\:click*="submit"], button[wire\\:click*="store"], button[wire\\:click*="update"], button[wire\\:click*="import"], button.btn-primary');
            if (btn) {
                isFormSubmitting = true;
            }
        }, true);

        function setupLivewireErrorScroller() {
            if (!window.Livewire || typeof window.Livewire.hook !== 'function') return;

            window.Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                const hasFormAction = Array.isArray(commit.calls) && commit.calls.some(c => {
                    const m = (c.method || '').toLowerCase();
                    return m.includes('save') || m.includes('submit') || m.includes('store') || m.includes('update') || m.includes('create') || m.includes('import');
                });

                if (isFormSubmitting || hasFormAction) {
                    succeed(() => {
                        isFormSubmitting = false;
                        requestAnimationFrame(() => {
                            setTimeout(() => {
                                window.scrollToFirstError(component?.el);
                            }, 70);
                        });
                    });
                }
            });

            window.Livewire.hook('morph.updated', ({ el, component }) => {
                if (isFormSubmitting) {
                    requestAnimationFrame(() => {
                        setTimeout(() => {
                            window.scrollToFirstError(component?.el || el);
                        }, 70);
                    });
                }
            });
        }

        if (window.Livewire) {
            setupLivewireErrorScroller();
        } else {
            document.addEventListener('livewire:init', setupLivewireErrorScroller);
        }

        window.addEventListener('scroll-to-first-error', (e) => {
            const targetScope = e.detail?.scope || (typeof e.detail === 'string' ? document.querySelector(e.detail) : null);
            window.scrollToFirstError(targetScope);
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
                <livewire:components.notification-bell/>

                <button type="button"
                        class="btn btn-outline-secondary btn-sm font-bold"
                        @click="$store.currency.toggle()"
                        x-text="$store.currency.cur"
                        title="Currency Toggle">
                </button>

                @if(auth()->user()?->hasPermission('customers', 'add'))
                    <button type="button"
                            class="btn btn-primary"
                            @click="$dispatch('open-customer-modal', { id: null })"
                            title="Add Customer">
                        <svg viewBox="0 0 24 24" class="w-4 h-4">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        <span class="btn-lbl">Add Customer</span>
                    </button>
                @endif
            </div>
        </header>

        <!-- Main Page View Slot -->
        <main class="content" id="content">
            {{ $slot ?? '' }}
            @yield('content')
        </main>
    </div>
</div>

<!-- Global Slide-Over Notification Drawer -->
<livewire:components.notification-drawer/>

<!-- Global Profile Slide-Over Drawer -->
@auth
    <livewire:profile.profile-drawer/>
@endauth

<!-- Global Application Modals -->
@if(auth()->check() && auth()->user()->hasPermission('users', 'add/edit'))
    <livewire:settings.user-modal/>
@endif

<livewire:customers.customer-modal/>
<livewire:projects.project-modal/>
<livewire:payments.payment-modal/>
<livewire:projects.milestone-chase-modal/>
<livewire:documents.document-preview-modal/>

<livewire:followups.followup-modal/>
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
                this.isInitialized = stored !== null;
                this.lastSeenId = stored !== null ? parseInt(stored, 10) : 0;

                if ('Notification' in window && Notification.permission === 'default') {
                    Notification.requestPermission();
                }

                this.poll();
                this.timer = setInterval(() => this.poll(), this.pollInterval);
            },

            async poll() {
                try {
                    let url = `{{ route('api.notifications.poll') }}?after_id=${this.lastSeenId}`;
                    if (!this.isInitialized) {
                        url += '&init=1';
                    }

                    const response = await fetch(url, {
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
                        this.isInitialized = true;
                        localStorage.setItem(this.storageKey, this.lastSeenId);
                        return;
                    }

                    this.isInitialized = true;

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
                    window.location.href = `/notifications/${item.id}`;
                };
            }
        }));
    });
</script>
</body>
</html>
