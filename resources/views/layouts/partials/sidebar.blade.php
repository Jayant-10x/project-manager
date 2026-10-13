@php
    $user = auth()->user();
    $assignedProjectsCount = \App\Models\Project::forUser($user)->count();
    $totalCustomersCount = \App\Models\Customer::count();
    $openTasksCount = \App\Models\Task::where('assigned_to_id', $user->id)->where('done', false)->count();

    $assignedProjectIds = \App\Models\Project::forUser($user)->pluck('id');
    $overdueCount = \App\Models\PaymentTerm::whereIn('project_id', $assignedProjectIds)->where('status', 'Overdue')->count();
    $followupAlertDays = (int) \App\Models\Setting::getValue('followup_alert_days', 1);
    $fupCount = \App\Models\Followup::whereNotNull('next_date')
        ->where('next_date', '<=', now()->addDays($followupAlertDays)->toDateString())
        ->count();
    $todayCount = $overdueCount + $fupCount;
    $unreadNotifications = \App\Models\Notification::where('user_id', $user->id)->where('is_read', false)->count();
@endphp

<aside class="sidebar"
       id="sb"
       :class="{ 'collapsed': collapsed, 'open': mobileOpen }"
       @click.outside="if (window.innerWidth <= 860) mobileOpen = false">

    <div class="sb-head">
        <div class="logo-plate">
            <img src="{{ asset('assets/logo.png') }}" alt="10X Agency">
        </div>
    </div>
    <div class="sb-name">PJT CRM</div>

    <nav class="nav" id="nav">
        <!-- 1. Overview -->
        <div class="nav-label">Overview</div>
        <a href="{{ route('dashboard') }}" wire:navigate
           class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}" title="Dashboard">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><path
                        d="M3 13h8V3H3v10zm10 8h8V11h-8v10zM3 21h8v-6H3v6zm10-12h8V3h-8v6z"/></svg></span>
            <span class="lbl">Dashboard</span>
        </a>
        <a href="{{ route('today.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('today.*') ? 'active' : '' }}" title="Today's Work">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><path
                        d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/><path d="M4 22v-7"/></svg></span>
            <span class="lbl">Today's Work</span>
            @if($todayCount > 0)
                <span class="nav-count warn">{{ $todayCount }}</span>
            @endif
        </a>

        <!-- 2. Customers -->
        <div class="nav-label">Customers</div>
        <a href="{{ route('customers.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('customers.*') ? 'active' : '' }}" title="Customers">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/><circle
                        cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 00-3-3.87"/></svg></span>
            <span class="lbl">Customers</span>
            <span class="nav-count">{{ $totalCustomersCount }}</span>
        </a>
        <a href="{{ route('projects.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('projects.*') ? 'active' : '' }}" title="Projects">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><path
                        d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/></svg></span>
            <span class="lbl">Projects</span>
            <span class="nav-count">{{ $assignedProjectsCount }}</span>
        </a>
        <a href="{{ route('tasks.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('tasks.*') ? 'active' : '' }}" title="My Tasks">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/></svg></span>
            <span class="lbl">My Tasks</span>
            @if($openTasksCount > 0)
                <span class="nav-count">{{ $openTasksCount }}</span>
            @endif
        </a>

        <!-- 3. Money -->
        <div class="nav-label">Money</div>
        <a href="{{ route('payments.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('payments.*') ? 'active' : '' }}" title="Payments">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><rect x="2" y="6" width="20" height="12" rx="2"/><circle
                        cx="12" cy="12" r="2.5"/><path d="M6 12h.01M18 12h.01"/></svg></span>
            <span class="lbl">Payments</span>
        </a>
        <a href="{{ route('schedule.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('schedule.*') ? 'active' : '' }}" title="Payment Schedule">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2"/><path
                        d="M16 2v4M8 2v4M3 10h18"/></svg></span>
            <span class="lbl">Payment Schedule</span>
        </a>
        <a href="{{ route('reports.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('reports.*') ? 'active' : '' }}" title="Reports">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><rect x="7" y="11" width="3"
                                                                                       height="6"/><rect x="12" y="7"
                                                                                                         width="3"
                                                                                                         height="10"/><rect
                        x="17" y="13" width="3" height="4"/></svg></span>
            <span class="lbl">Reports</span>
        </a>

        <!-- 4. Records -->
        <div class="nav-label">Records</div>
        <a href="{{ route('documents.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('documents.*') ? 'active' : '' }}" title="Documents">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><path
                        d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg></span>
            <span class="lbl">Documents</span>
        </a>
        <a href="{{ route('followups.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('followups.*') ? 'active' : '' }}" title="Follow-ups">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><path
                        d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/></svg></span>
            <span class="lbl">Follow-ups</span>
        </a>
        <a href="{{ route('sales.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('sales.*') ? 'active' : '' }}" title="Sales Team">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle
                        cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/></svg></span>
            <span class="lbl">Sales Team</span>
        </a>

        <!-- 5. System -->
        <div class="nav-label">System</div>
        <a href="{{ route('notifications.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('notifications.*') ? 'active' : '' }}" title="Notifications">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 10-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path
                        d="M13.7 21a2 2 0 01-3.4 0"/></svg></span>
            <span class="lbl">Notifications</span>
            @if($unreadNotifications > 0)
                <span class="nav-count warn">{{ $unreadNotifications }}</span>
            @endif
        </a>
        <a href="{{ route('audit.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('audit.*') ? 'active' : '' }}" title="Activity Log">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
            <span class="lbl">Activity Log</span>
        </a>
        <a href="{{ route('settings.index') }}" wire:navigate
           class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}" title="Settings">
            <span class="nav-ico"><svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path
                        d="M12 2v3M12 19v3M2 12h3M19 12h3M4.9 4.9l2.1 2.1M17 17l2.1 2.1M19.1 4.9L17 7M7 17l-2.1 2.1"/></svg></span>
            <span class="lbl">Settings</span>
        </a>
    </nav>

    <div class="sb-foot">
        <div class="me">
            <div class="avatar" style="background:#0b3d91">{{ strtoupper(substr($user->name, 0, 2)) }}</div>
            <div class="me-txt">
                <strong>{{ $user->name }}</strong>
                <span>{{ $user->designation ?? $user->role }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="inline">
                @csrf
                <button type="submit" class="sb-btn" title="Sign out">
                    <svg viewBox="0 0 24 24">
                        <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4"/>
                        <path d="M16 17l5-5-5-5"/>
                        <path d="M21 12H9"/>
                    </svg>
                </button>
            </form>
        </div>
    </div>
</aside>
