<div>
    <!-- Top Welcome Header & Scope Bar -->
    <div class="mb-6">
        <div class="flex items-center justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-bold text-[#0d1b34] tracking-tight">
                    Welcome back, {{ $user->name }} 👋
                </h1>
                <p class="text-sm text-[#8894ac] mt-1 flex items-center gap-2 flex-wrap">
                    <span
                        class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold bg-blue-50 text-[#0b3d91] border border-blue-200">
                        {{ $role }}
                    </span>
                    <span>&middot;</span>
                    <span>{{ now()->format('l, d F Y') }}</span>
                    <span>&middot;</span>
                    <span class="text-slate-500">
                        @switch($role)
                            @case('Admin') Company-wide Executive Governance @break
                            @case('Manager') Operations & Team Delivery Overview @break
                            @case('Coordinator') My Active Projects & Delivery Schedule @break
                            @case('TechPerson') My Assigned Projects & Delivery Schedule @break
                            @case('Sales') My Deals Pipeline & Customer Accounts @break
                        @endswitch
                    </span>
                </p>
            </div>
            <div class="flex items-center gap-2">
                @if($user->hasPermission('payments', 'view') || $user->hasPermission('schedules', 'view'))
                    <a href="{{ route('calendar.index') }}" wire:navigate
                       class="btn btn-outline-primary btn-sm flex items-center gap-1.5 shadow-xs"
                       title="Open Datewise Payment Calendar">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                        <span>Payment Calendar</span>
                    </a>
                @endif
                @if($user->hasPermission('today', 'view'))
                    <a href="{{ route('today.index') }}" wire:navigate
                       class="btn btn-outline-primary btn-sm flex items-center gap-1.5 shadow-xs"
                       title="Open Today's Work Queue">
                        <svg viewBox="0 0 24 24" class="w-4 h-4">
                            <path d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z"/>
                            <path d="M4 22v-7"/>
                        </svg>
                        <span>Today's Work</span>
                        <livewire:components.dynamic-counter type="today" :warn="true" :hideZero="true"/>
                    </a>
                @endif
                @if($role === 'Sales')
                    <button type="button"
                            class="btn btn-outline-primary btn-sm flex items-center gap-1.5 shadow-xs"
                            @click="$dispatch('open-customer-modal', { id: null })"
                            title="Add Customer">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        <span>Add Customer</span>
                    </button>
                    <button type="button"
                            class="btn btn-primary btn-sm flex items-center gap-1.5 shadow-xs"
                            @click="$dispatch('open-project-modal', { id: null })"
                            title="New Project">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        <span>New Project</span>
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Dashboard Unified Control & Date Filter Bar -->
    <div class="card p-3 mb-6 bg-slate-50/70 border border-slate-200/80 flex items-center justify-between flex-wrap gap-3">
        <div class="flex items-center gap-3 flex-wrap">
            {{-- Date Range Filter Dropdown & Dual Calendar Picker --}}
            <div x-data="{
                openDropdown: false,
                viewMode: '{{ $datePreset === 'custom' ? 'custom' : 'presets' }}',
                customFrom: '{{ $dateFrom ?? now()->toDateString() }}',
                customTo: '{{ $dateTo ?? now()->toDateString() }}',
                hoverDate: null,
                calMonth1: null,
                calMonth2: null,
                init() {
                    let base = this.customFrom ? new Date(this.customFrom + 'T00:00:00') : new Date();
                    this.calMonth1 = new Date(base.getFullYear(), base.getMonth(), 1);
                    this.calMonth2 = new Date(base.getFullYear(), base.getMonth() + 1, 1);
                },
                prevMonth() {
                    this.calMonth1 = new Date(this.calMonth1.getFullYear(), this.calMonth1.getMonth() - 1, 1);
                    this.calMonth2 = new Date(this.calMonth2.getFullYear(), this.calMonth2.getMonth() - 1, 1);
                },
                nextMonth() {
                    this.calMonth1 = new Date(this.calMonth1.getFullYear(), this.calMonth1.getMonth() + 1, 1);
                    this.calMonth2 = new Date(this.calMonth2.getFullYear(), this.calMonth2.getMonth() + 1, 1);
                },
                getMonthName(dateObj) {
                    if (!dateObj) return '';
                    return dateObj.toLocaleDateString('en-US', { month: 'short', year: 'numeric' });
                },
                getDays(dateObj) {
                    if (!dateObj) return [];
                    let year = dateObj.getFullYear();
                    let month = dateObj.getMonth();
                    let firstDayIndex = new Date(year, month, 1).getDay();
                    let daysInMonth = new Date(year, month + 1, 0).getDate();
                    let list = [];
                    for (let i = 0; i < firstDayIndex; i++) {
                        list.push({ day: null, dateStr: '' });
                    }
                    for (let d = 1; d <= daysInMonth; d++) {
                        let mStr = String(month + 1).padStart(2, '0');
                        let dStr = String(d).padStart(2, '0');
                        list.push({ day: d, dateStr: `${year}-${mStr}-${dStr}` });
                    }
                    return list;
                },
                selectDay(dateStr) {
                    if (!dateStr) return;
                    if (!this.customFrom || (this.customFrom && this.customTo)) {
                        this.customFrom = dateStr;
                        this.customTo = '';
                    } else if (this.customFrom && !this.customTo) {
                        if (dateStr < this.customFrom) {
                            this.customTo = this.customFrom;
                            this.customFrom = dateStr;
                        } else {
                            this.customTo = dateStr;
                        }
                    }
                },
                isInRange(dateStr) {
                    if (!dateStr || !this.customFrom) return false;
                    let end = this.customTo || this.hoverDate;
                    if (!end) return dateStr === this.customFrom;
                    let s = this.customFrom <= end ? this.customFrom : end;
                    let e = this.customFrom <= end ? end : this.customFrom;
                    return dateStr >= s && dateStr <= e;
                },
                isStart(dateStr) {
                    return dateStr && dateStr === this.customFrom;
                },
                isEnd(dateStr) {
                    return dateStr && dateStr === this.customTo;
                },
                applyCustom() {
                    if (!this.customFrom) return;
                    let from = this.customFrom;
                    let to = this.customTo || this.customFrom;
                    if (from > to) { [from, to] = [to, from]; }
                    $wire.applyCustomRange(from, to);
                    this.openDropdown = false;
                }
            }" class="relative inline-block text-left" @click.outside="openDropdown = false">
                <button type="button"
                        @click="openDropdown = !openDropdown"
                        class="inline-flex items-center gap-2 px-3 py-1.5 rounded-lg border border-slate-300 bg-white text-xs font-medium text-slate-700 hover:bg-slate-50 hover:border-blue-400 shadow-2xs transition-all focus:outline-hidden focus:ring-2 focus:ring-blue-100">
                    <svg class="w-4 h-4 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                    <span class="text-slate-500 font-normal">Period:</span>
                    <span class="font-bold text-slate-800">{{ $periodLabel }}</span>
                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                        @if($datePreset === 'all_time')
                            All Time
                        @elseif($startDate && $endDate)
                            @if($startDate === $endDate)
                                {{ \Carbon\Carbon::parse($startDate)->format('d M Y') }}
                            @else
                                {{ \Carbon\Carbon::parse($startDate)->format('d M') }} – {{ \Carbon\Carbon::parse($endDate)->format('d M Y') }}
                            @endif
                        @endif
                    </span>
                    <svg class="w-3.5 h-3.5 text-slate-400 transition-transform duration-200" :class="{ 'rotate-180': openDropdown }" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                    </svg>
                </button>

                <!-- Popover dropdown -->
                <div x-show="openDropdown"
                     x-transition:enter="transition ease-out duration-150"
                     x-transition:enter-start="opacity-0 translate-y-1 scale-98"
                     x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                     x-transition:leave-end="opacity-0 translate-y-1 scale-98"
                     class="absolute left-0 mt-2 z-50 bg-white rounded-xl shadow-2xl border border-slate-200 text-xs overflow-hidden"
                     style="display: none; min-width: 320px;">
                    
                    <div class="flex flex-col md:flex-row divide-y md:divide-y-0 md:divide-x divide-slate-200">
                        <!-- Presets Column -->
                        <div class="p-3 w-full md:w-64 shrink-0 bg-slate-50/60 flex flex-col justify-between">
                            <div class="space-y-3">
                                <!-- Quick Picks -->
                                <div>
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1">
                                        Quick Picks
                                    </div>
                                    <div class="space-y-0.5">
                                        <button type="button" wire:click="selectPreset('today')" @click="openDropdown = false"
                                                class="w-full text-left px-2.5 py-1.5 rounded-md flex items-center justify-between {{ $datePreset === 'today' ? 'bg-blue-600 text-white font-semibold shadow-2xs' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            <span>Today</span>
                                            @if($datePreset === 'today')
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            @endif
                                        </button>
                                        <button type="button" wire:click="selectPreset('yesterday')" @click="openDropdown = false"
                                                class="w-full text-left px-2.5 py-1.5 rounded-md flex items-center justify-between {{ $datePreset === 'yesterday' ? 'bg-blue-600 text-white font-semibold shadow-2xs' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            <span>Yesterday</span>
                                            @if($datePreset === 'yesterday')
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            @endif
                                        </button>
                                        <button type="button" wire:click="selectPreset('last_7_days')" @click="openDropdown = false"
                                                class="w-full text-left px-2.5 py-1.5 rounded-md flex items-center justify-between {{ $datePreset === 'last_7_days' ? 'bg-blue-600 text-white font-semibold shadow-2xs' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            <span>Last 7 Days</span>
                                            @if($datePreset === 'last_7_days')
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            @endif
                                        </button>
                                        <button type="button" wire:click="selectPreset('last_30_days')" @click="openDropdown = false"
                                                class="w-full text-left px-2.5 py-1.5 rounded-md flex items-center justify-between {{ $datePreset === 'last_30_days' ? 'bg-blue-600 text-white font-semibold shadow-2xs' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            <span>Last 30 Days</span>
                                            @if($datePreset === 'last_30_days')
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            @endif
                                        </button>
                                    </div>
                                </div>

                                <!-- Calendar Periods -->
                                <div class="pt-2 border-t border-slate-200">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1">
                                        Calendar Periods
                                    </div>
                                    <div class="grid grid-cols-2 gap-1">
                                        <button type="button" wire:click="selectPreset('this_week')" @click="openDropdown = false"
                                                class="text-left px-2 py-1.5 rounded-md {{ $datePreset === 'this_week' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            This Week
                                        </button>
                                        <button type="button" wire:click="selectPreset('last_week')" @click="openDropdown = false"
                                                class="text-left px-2 py-1.5 rounded-md {{ $datePreset === 'last_week' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            Last Week
                                        </button>
                                        <button type="button" wire:click="selectPreset('this_month')" @click="openDropdown = false"
                                                class="text-left px-2 py-1.5 rounded-md {{ $datePreset === 'this_month' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            This Month
                                        </button>
                                        <button type="button" wire:click="selectPreset('last_month')" @click="openDropdown = false"
                                                class="text-left px-2 py-1.5 rounded-md {{ $datePreset === 'last_month' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            Last Month
                                        </button>
                                        <button type="button" wire:click="selectPreset('this_quarter')" @click="openDropdown = false"
                                                class="text-left px-2 py-1.5 rounded-md {{ $datePreset === 'this_quarter' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            This Quarter
                                        </button>
                                        <button type="button" wire:click="selectPreset('last_quarter')" @click="openDropdown = false"
                                                class="text-left px-2 py-1.5 rounded-md {{ $datePreset === 'last_quarter' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            Last Quarter
                                        </button>
                                        <button type="button" wire:click="selectPreset('ytd')" @click="openDropdown = false"
                                                class="text-left px-2 py-1.5 rounded-md {{ $datePreset === 'ytd' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            Year to Date (YTD)
                                        </button>
                                        <button type="button" wire:click="selectPreset('last_year')" @click="openDropdown = false"
                                                class="text-left px-2 py-1.5 rounded-md {{ $datePreset === 'last_year' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            Last Year
                                        </button>
                                    </div>
                                </div>

                                <!-- Everything -->
                                <div class="pt-2 border-t border-slate-200">
                                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-2 mb-1">
                                        Everything
                                    </div>
                                    <div class="space-y-0.5">
                                        <button type="button" wire:click="selectPreset('all_time')" @click="openDropdown = false"
                                                class="w-full text-left px-2.5 py-1.5 rounded-md flex items-center justify-between {{ $datePreset === 'all_time' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            <span>All Time</span>
                                            @if($datePreset === 'all_time')
                                                <svg class="w-3.5 h-3.5" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                            @endif
                                        </button>
                                        <button type="button" @click="viewMode = 'custom'"
                                                class="w-full text-left px-2.5 py-1.5 rounded-md flex items-center justify-between {{ $datePreset === 'custom' ? 'bg-blue-600 text-white font-semibold' : 'text-slate-700 hover:bg-slate-200/70' }}">
                                            <span>Custom Range...</span>
                                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="9 18 15 12 9 6"/></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Dual Calendar View Column -->
                        <div class="p-3 bg-white flex flex-col justify-between w-full md:w-auto" style="min-width: 480px;">
                            <div>
                                <!-- Calendar Navigation Header -->
                                <div class="flex items-center justify-between pb-2 mb-2 border-b border-slate-100">
                                    <button type="button" @click="prevMonth()" class="btn btn-ghost btn-xs p-1" title="Previous Month">
                                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M15 18l-6-6 6-6"/></svg>
                                    </button>
                                    <div class="flex items-center gap-12 font-semibold text-xs text-slate-800">
                                        <span x-text="getMonthName(calMonth1)"></span>
                                        <span x-text="getMonthName(calMonth2)"></span>
                                    </div>
                                    <button type="button" @click="nextMonth()" class="btn btn-ghost btn-xs p-1" title="Next Month">
                                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 18l6-6-6-6"/></svg>
                                    </button>
                                </div>

                                <!-- Dual Calendars Grid -->
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <!-- Left Month Calendar -->
                                    <div>
                                        <div class="grid grid-cols-7 text-center text-[10px] font-semibold text-slate-400 mb-1">
                                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                                        </div>
                                        <div class="grid grid-cols-7 gap-y-0.5 text-center text-xs">
                                            <template x-for="(d, idx) in getDays(calMonth1)" :key="'m1-' + idx">
                                                <div class="h-6 flex items-center justify-center">
                                                    <template x-if="d.day">
                                                        <button type="button"
                                                                @click="selectDay(d.dateStr)"
                                                                @mouseenter="if(customFrom && !customTo) hoverDate = d.dateStr"
                                                                :class="{
                                                                    'bg-blue-600 text-white font-bold rounded-full': isStart(d.dateStr) || isEnd(d.dateStr),
                                                                    'bg-blue-100 text-blue-900 rounded-none': isInRange(d.dateStr) && !isStart(d.dateStr) && !isEnd(d.dateStr),
                                                                    'text-slate-700 hover:bg-slate-100 rounded-full': !isInRange(d.dateStr)
                                                                }"
                                                                class="w-6 h-6 flex items-center justify-center text-[11px] transition-colors"
                                                                x-text="d.day"></button>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <!-- Right Month Calendar -->
                                    <div>
                                        <div class="grid grid-cols-7 text-center text-[10px] font-semibold text-slate-400 mb-1">
                                            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                                        </div>
                                        <div class="grid grid-cols-7 gap-y-0.5 text-center text-xs">
                                            <template x-for="(d, idx) in getDays(calMonth2)" :key="'m2-' + idx">
                                                <div class="h-6 flex items-center justify-center">
                                                    <template x-if="d.day">
                                                        <button type="button"
                                                                @click="selectDay(d.dateStr)"
                                                                @mouseenter="if(customFrom && !customTo) hoverDate = d.dateStr"
                                                                :class="{
                                                                    'bg-blue-600 text-white font-bold rounded-full': isStart(d.dateStr) || isEnd(d.dateStr),
                                                                    'bg-blue-100 text-blue-900 rounded-none': isInRange(d.dateStr) && !isStart(d.dateStr) && !isEnd(d.dateStr),
                                                                    'text-slate-700 hover:bg-slate-100 rounded-full': !isInRange(d.dateStr)
                                                                }"
                                                                class="w-6 h-6 flex items-center justify-center text-[11px] transition-colors"
                                                                x-text="d.day"></button>
                                                    </template>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Controls: Date Inputs & Apply Button -->
                            <div class="pt-3 mt-3 border-t border-slate-100 flex items-center justify-between gap-2 flex-wrap">
                                <div class="flex items-center gap-2">
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 uppercase block">Start Date</label>
                                        <input type="date" x-model="customFrom" class="i text-xs py-1 px-2 rounded border-slate-300 w-32">
                                    </div>
                                    <span class="text-slate-400 mt-3">&rarr;</span>
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 uppercase block">End Date</label>
                                        <input type="date" x-model="customTo" class="i text-xs py-1 px-2 rounded border-slate-300 w-32">
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 mt-3 sm:mt-0">
                                    <button type="button" @click="openDropdown = false" class="btn btn-outline-secondary btn-xs py-1 px-2.5">
                                        Cancel
                                    </button>
                                    <button type="button" @click="applyCustom()" class="btn btn-primary btn-xs py-1 px-3 shadow-xs">
                                        Apply Range
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Team Member Filter (if accessible users exist) --}}
            @if($accessibleUsers->isNotEmpty())
                <div class="flex items-center gap-2 flex-wrap">
                    <span class="text-xs font-bold text-slate-700 uppercase tracking-wide flex items-center gap-1.5">
                        <svg class="w-4 h-4 text-slate-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                        <span class="hidden sm:inline">Team Member:</span>
                    </span>
                    <select wire:model.live="selectedUserId"
                            class="i text-xs py-1.5 px-3 rounded-lg border-slate-300 bg-white"
                            style="width: auto; min-width: 200px;">
                        <option value="">Entire Organization / Team Portfolio</option>
                        @foreach($accessibleUsers as $au)
                            <option value="{{ $au->id }}">{{ $au->name }} ({{ $au->role }})</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        {{-- Active Filter Tags & Quick Resets --}}
        <div class="flex items-center gap-2 flex-wrap">
            @if($datePreset !== 'today')
                <button type="button" wire:click="selectPreset('today')"
                        class="btn btn-outline-secondary btn-sm text-xs py-1 px-2.5 flex items-center gap-1"
                        title="Reset date filter to Today">
                    <svg class="w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/>
                        <path d="M3 3v5h5"/>
                    </svg>
                    <span>Reset to Today</span>
                </button>
            @endif

            @if($selectedUserId)
                <button type="button" wire:click="$set('selectedUserId', null)"
                        class="btn btn-outline-danger btn-sm text-xs py-1 px-2.5">
                    Reset Team Filter
                </button>
            @endif

            @if($selectedUserId && $activeViewingUser && $activeViewingUser->id !== $user->id)
                <div class="text-xs text-blue-700 bg-blue-50 px-2.5 py-1 rounded-md border border-blue-200 font-medium flex items-center gap-1.5">
                    <span class="inline-block w-2 h-2 rounded-full bg-blue-600 animate-pulse"></span>
                    <span>Filtered View: <strong>{{ $activeViewingUser->name }}</strong> ({{ $activeViewingUser->role }})</span>
                </div>
            @endif
        </div>
    </div>

    <!-- 1. Role-Tailored KPI Row -->
    @switch($role)
        @case('Admin')
            <!-- Admin Financial Governance Section Card -->
            <div class="card mb-6">
                <div class="card-head flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="6" width="20" height="12" rx="2"/>
                                <circle cx="12" cy="12" r="2.5"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Payments &amp; Financial Governance</h3>
                            <div class="text-xs text-[#8894ac]">Company-wide billing, realized revenue &amp; outstanding
                                collections
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        @if($user->hasPermission('schedules', 'view'))
                            <a href="{{ route('schedule.index') }}" wire:navigate
                               class="text-xs font-semibold text-[#0b3d91] hover:underline flex items-center gap-1">
                                <span>All Schedules</span>
                                <span>&rarr;</span>
                            </a>
                        @endif
                        @if($user->hasPermission('payments', 'view'))
                            <span class="text-slate-300">|</span>
                            <a href="{{ route('payments.index') }}" wire:navigate
                               class="text-xs font-semibold text-emerald-700 hover:underline flex items-center gap-1">
                                <span>All Receipts</span>
                                <span>&rarr;</span>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4 sm:p-5 bg-slate-50/40">
                    <div class="kpis mb-0">
                        <x-kpi-card label="Total Receivable" :value="$m['scheduled']" sub="All portfolio projects"
                                    icon="cash"
                                    :ismoney="true" color="#0b3d91" bg="#e8eefc"
                                    :href="$user->hasPermission('schedules', 'view') ? route('schedule.index') : null"/>
                        <x-kpi-card label="Received" :value="$m['received']"
                                    :sub="$m['tds'] > 0
                                    ? ('Realized Revenue · ' . view('components.money', ['value' => $m['cash']])->render() . ' Cash + ' . view('components.money', ['value' => $m['tds']])->render() . ' TDS Credit (' . $m['collectionRate'] . '% settled)')
                                    : ('Realized Revenue · ' . $m['collectionRate'] . '% settled to date')"
                                    icon="check" :ismoney="true" color="#5aa236" bg="#eef7e8"
                                    :href="$user->hasPermission('payments', 'view') ? route('payments.index') : null"/>
                        <x-kpi-card label="Due" :value="$m['due']" sub="Balance Due · Net pending collection"
                                    icon="clock"
                                    color="#e0a13a"
                                    :ismoney="true" bg="#fdf3e2"
                                    :href="$user->hasPermission('schedules', 'view') ? route('schedule.index') : null"/>
                        <x-kpi-card label="Overdue" :value="$m['overdue']"
                                    :sub="$overdueCount . ' ' . \Illuminate\Support\Str::plural('installment', $overdueCount) . ' late'"
                                    icon="alert"
                                    :ismoney="true" color="#d94b4b" bg="#fdeaea"
                                    :href="$user->hasPermission('today', 'view') ? route('today.index') : null"/>
                        <x-kpi-card label="This Month" :value="$m['thisMonth']" sub="Realized this calendar month"
                                    icon="cal"
                                    color="#2e75b6"
                                    :ismoney="true" bg="#e8f1fa"
                                    :href="$user->hasPermission('payments', 'view') ? route('payments.index') : null"/>
                    </div>
                </div>
            </div>
            <!-- ADMIN VIEW: Macro Portfolio Health & Cash Realization -->
            <div class="grid-2 mb-6">
                <!-- Project Health Breakdown -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Project Health</h3>
                            <div class="text-xs text-[#8894ac]">{{ $totalProjectsCount }} total projects across company
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;gap:14px;flex-wrap:wrap">
                            @php
                                $colors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                            @endphp
                            @foreach($healthCounts as $label => $count)
                                @php
                                    $percentage = $totalProjectsCount > 0 ? round(($count / $totalProjectsCount) * 100) : 0;
                                    $colorCode = $colors[$label];
                                @endphp
                                <a href="{{ route('projects.index', ['health' => $label]) }}" wire:navigate
                                   class="block p-3 rounded-xl transition hover:bg-gray-50 border border-gray-100 hover:border-gray-200 cursor-pointer"
                                   style="flex:1;min-width:110px;text-decoration:none;color:inherit"
                                   title="Filter {{ $label }} projects">
                                    <div
                                        style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:6px">
                                        <i style="width:8px;height:8px;border-radius:50%;background:{{ $colorCode }};display:inline-block"></i>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <div style="display:flex;align-items:baseline;justify-content:space-between">
                                        <span
                                            style="font-size:22px;font-weight:750;letter-spacing:-.6px">{{ $count }}</span>
                                        <span
                                            style="font-size:11px;font-weight:600;color:var(--muted)">{{ $percentage }}%</span>
                                    </div>
                                    <div class="bar" style="height:5px;margin-top:8px">
                                        <i style="width:{{ $percentage }}%;background:{{ $colorCode }}"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- 6-Month Realized Revenue Trend -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Collection Trend</h3>
                            <div class="text-xs text-[#8894ac]">Monthly revenue realized (Last 6 months)</div>
                        </div>
                    </div>
                    <div class="card-body">
                        @php $maxVal = max(array_merge([1], array_column($trend, 'value'))); @endphp
                        <div class="spark-chart" style="display:flex;gap:8px;align-items:flex-end">
                            @foreach($trend as $point)
                                @php $barHeight = max(4, round(($point['value'] / $maxVal) * 100)); @endphp
                                <div class="spark-col">
                                    <div class="spark-val"
                                         x-text="$store.currency.format({{ (float)$point['value'] }}, true)"></div>
                                    <div class="spark-bar-wrap">
                                        <i style="height:{{ $barHeight }}%"
                                           :title="'{{ $point['label'] }}: ' + $store.currency.format({{ (float)$point['value'] }}, false)"></i>
                                    </div>
                                    <div class="spark-label">{{ $point['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sales Attribution Performance Table -->
            @if(!empty($salesRows))
                <div class="card mb-6">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-amber-50 text-[#e0a13a] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="8" r="7"/>
                                <polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Sales-wise Performance</h3>
                            <div class="text-xs text-[#8894ac]">Contract booking value and collection rate by
                                salesperson
                            </div>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Salesperson</th>
                                <th>Customers</th>
                                <th>Projects</th>
                                <th class="num">Contract Value</th>
                                <th class="num">Received</th>
                                <th>Collection Rate</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($salesRows as $salesRow)
                                <tr>
                                    <td class="strong">{{ $salesRow['name'] }}</td>
                                    <td>{{ $salesRow['customers'] }}</td>
                                    <td>{{ $salesRow['projects'] }}</td>
                                    <td class="num font-semibold">
                                        <x-money :value="$salesRow['value']"/>
                                    </td>
                                    <td class="num font-semibold text-emerald-700">
                                        <x-money :value="$salesRow['received']"/>
                                    </td>
                                    <td style="min-width:130px">
                                        <div
                                            class="bar {{ $salesRow['rate'] >= 80 ? 'green' : ($salesRow['rate'] >= 40 ? 'amber' : '') }}">
                                            <i style="width:{{ $salesRow['rate'] }}%"></i>
                                        </div>
                                        <span class="muted" style="font-size:11px">{{ $salesRow['rate'] }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <!-- Payment Ageing Breakdown -->
            <div class="card">
                <div class="card-head flex items-center gap-3">
                    <div class="w-8 h-8 rounded-lg bg-rose-50 text-[#d94b4b] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 14 14"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Overdue Payment Timeline</h3>
                        <div class="text-xs text-[#8894ac]">Breakdown of overdue payments by days late</div>
                    </div>
                </div>
                <div class="card-body">
                    @php
                        $ageing = $ageing ?? ['0-30' => 0.0, '31-60' => 0.0, '61-90' => 0.0, '90+' => 0.0];
                        $maxAgeVal = max(array_merge([1], array_values($ageing)));
                        $ageColors = ['0-30' => '#5aa236', '31-60' => '#2e75b6', '61-90' => '#e0a13a', '90+' => '#d94b4b'];
                    @endphp
                    <div style="display:flex;flex-direction:column;gap:14px">
                        @foreach($ageing as $bracket => $amt)
                            @php
                                $barPct = round(($amt / $maxAgeVal) * 100, 1);
                                $col = $ageColors[$bracket];
                            @endphp
                            <div>
                                <div
                                    style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px">
                                    <span style="font-weight:600">{{ $bracket }} days late</span>
                                    <span class="muted" style="font-weight:650"><x-money :value="$amt"/></span>
                                </div>
                                <div class="bar" style="height:8px">
                                    <i style="width:{{ $barPct }}%;background:{{ $col }}"></i>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @break
        @case('Manager')
            <!-- Manager Revenue & Collections Section Card -->
            <div class="card mb-6">
                <div class="card-head flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="6" width="20" height="12" rx="2"/>
                                <circle cx="12" cy="12" r="2.5"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Team Revenue &amp; Collections</h3>
                            <div class="text-xs text-[#8894ac]">Realized collections and pending contract dues across
                                managed team
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        @if($user->hasPermission('schedules', 'view'))
                            <a href="{{ route('schedule.index') }}" wire:navigate
                               class="text-xs font-semibold text-[#0b3d91] hover:underline flex items-center gap-1">
                                <span>Team Schedules</span>
                                <span>&rarr;</span>
                            </a>
                        @endif
                        @if($user->hasPermission('payments', 'view'))
                            <span class="text-slate-300">|</span>
                            <a href="{{ route('payments.index') }}" wire:navigate
                               class="text-xs font-semibold text-emerald-700 hover:underline flex items-center gap-1">
                                <span>Collections</span>
                                <span>&rarr;</span>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4 sm:p-5 bg-slate-50/40">
                    <div class="kpis mb-0">
                        <x-kpi-card label="Total Receivable" :value="$m['scheduled']"
                                    sub="Team Bookings · Total contract value"
                                    icon="cash"
                                    :ismoney="true" color="#0b3d91" bg="#e8eefc"
                                    :href="$user->hasPermission('schedules', 'view') ? route('schedule.index') : route('projects.index')"/>
                        <x-kpi-card label="Received" :value="$m['received']"
                                    :sub="$m['tds'] > 0
                                    ? ('Team Collections · ' . view('components.money', ['value' => $m['cash']])->render() . ' Cash + ' . view('components.money', ['value' => $m['tds']])->render() . ' TDS Credit (' . $m['collectionRate'] . '% settled)')
                                    : ('Team Collections · ' . $m['collectionRate'] . '% team realization')"
                                    icon="check" :ismoney="true" color="#5aa236" bg="#eef7e8"
                                    :href="$user->hasPermission('payments', 'view') ? route('payments.index') : route('projects.index')"/>
                        <x-kpi-card label="Due" :value="$m['due']" sub="Balance Due · Net pending collection"
                                    icon="clock"
                                    color="#e0a13a"
                                    :ismoney="true" bg="#fdf3e2"
                                    :href="$user->hasPermission('schedules', 'view') ? route('schedule.index') : route('projects.index')"/>
                        <x-kpi-card label="Overdue" :value="$m['overdue']"
                                    :sub="$overdueCount . ' ' . \Illuminate\Support\Str::plural('installment', $overdueCount) . ' late'"
                                    icon="alert"
                                    :ismoney="true" color="#d94b4b" bg="#fdeaea"
                                    :href="$user->hasPermission('today', 'view') ? route('today.index') : route('projects.index')"/>
                        <x-kpi-card label="This Month" :value="$m['thisMonth']" sub="Realized this calendar month"
                                    icon="cal"
                                    color="#2e75b6"
                                    :ismoney="true" bg="#e8f1fa"
                                    :href="$user->hasPermission('payments', 'view') ? route('payments.index') : route('projects.index')"/>
                    </div>
                </div>
            </div>
            <!-- MANAGER VIEW: Team Delivery & Coordinator Workload -->
            <div class="grid-2 mb-6">
                <!-- Team Project Health -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Team Project Health</h3>
                            <div class="text-xs text-[#8894ac]">{{ $totalProjectsCount }} projects managed by your team
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;gap:14px;flex-wrap:wrap">
                            @php
                                $colors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                            @endphp
                            @foreach($healthCounts as $label => $count)
                                @php
                                    $percentage = $totalProjectsCount > 0 ? round(($count / $totalProjectsCount) * 100) : 0;
                                    $colorCode = $colors[$label];
                                @endphp
                                <a href="{{ route('projects.index', ['health' => $label]) }}" wire:navigate
                                   class="block p-3 rounded-xl transition hover:bg-gray-50 border border-gray-100 hover:border-gray-200 cursor-pointer"
                                   style="flex:1;min-width:110px;text-decoration:none;color:inherit">
                                    <div
                                        style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:6px">
                                        <i style="width:8px;height:8px;border-radius:50%;background:{{ $colorCode }};display:inline-block"></i>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <div style="display:flex;align-items:baseline;justify-content:space-between">
                                        <span
                                            style="font-size:22px;font-weight:750;letter-spacing:-.6px">{{ $count }}</span>
                                        <span
                                            style="font-size:11px;font-weight:600;color:var(--muted)">{{ $percentage }}%</span>
                                    </div>
                                    <div class="bar" style="height:5px;margin-top:8px">
                                        <i style="width:{{ $percentage }}%;background:{{ $colorCode }}"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Team Collection Trend -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                                <polyline points="17 6 23 6 23 12"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Team Revenue Trend</h3>
                            <div class="text-xs text-[#8894ac]">{{ $totalProjectsCount }} realized collections from team
                                accounts
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        @php $maxVal = max(array_merge([1], array_column($trend, 'value'))); @endphp
                        <div class="spark-chart" style="display:flex;gap:8px;align-items:flex-end">
                            @foreach($trend as $point)
                                @php $barHeight = max(4, round(($point['value'] / $maxVal) * 100)); @endphp
                                <div class="spark-col">
                                    <div class="spark-val"
                                         x-text="$store.currency.format({{ (float)$point['value'] }}, true)"></div>
                                    <div class="spark-bar-wrap">
                                        <i style="height:{{ $barHeight }}%"
                                           :title="'{{ $point['label'] }}: ' + $store.currency.format({{ (float)$point['value'] }}, false)"></i>
                                    </div>
                                    <div class="spark-label">{{ $point['label'] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            <!-- Coordinator Workload & Delivery Velocity -->
            <div class="card mb-6">
                <div class="card-head flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-lg bg-indigo-50 text-[#7a5af8] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Coordinator Workload &amp; Delivery Velocity</h3>
                        <div class="text-xs text-[#8894ac]">Active projects and health distribution by coordinator</div>
                    </div>
                </div>
                <div class="table-wrap">
                    <table>
                        <thead>
                        <tr>
                            <th>Coordinator</th>
                            <th>Active Projects</th>
                            <th>On Track</th>
                            <th>Delayed / At Risk</th>
                            <th>Delivery Status</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($teamCoordinators as $coordinator)
                            <tr>
                                <td>
                                    <div class="font-semibold text-[#0d1b34]">{{ $coordinator->name }}</div>
                                    <div class="text-xs text-[#8894ac]">{{ $coordinator->email }}</div>
                                </td>
                                <td class="font-bold text-slate-800">{{ $coordinator->active_projects_count }}</td>
                                <td class="font-semibold text-emerald-600">{{ $coordinator->on_track_projects_count }}</td>
                                <td class="font-semibold {{ $coordinator->delayed_projects_count > 0 ? 'text-red-600' : 'text-slate-400' }}">
                                    {{ $coordinator->delayed_projects_count }}
                                </td>
                                <td>
                                    @if($coordinator->delayed_projects_count > 0)
                                        <span class="badge b-red">Attention Needed</span>
                                    @elseif($coordinator->active_projects_count > 0)
                                        <span class="badge b-green">Healthy</span>
                                    @else
                                        <span class="badge b-gray">Available</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-6 text-gray-400 text-xs">
                                    No coordinators currently assigned to your team.
                                </td>
                            </tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Team Sales Performance -->
            @if(!empty($salesRows))
                <div class="card">
                    <div class="card-head">
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Team Sales Performance</h3>
                            <div class="text-xs text-[#8894ac]">Bookings and cash realization across supervised sales
                                team
                            </div>
                        </div>
                    </div>
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Salesperson</th>
                                <th>Customers</th>
                                <th>Projects</th>
                                <th class="num">Contract Value</th>
                                <th class="num">Received</th>
                                <th>Collection</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($salesRows as $salesRow)
                                <tr>
                                    <td class="strong">{{ $salesRow['name'] }}</td>
                                    <td>{{ $salesRow['customers'] }}</td>
                                    <td>{{ $salesRow['projects'] }}</td>
                                    <td class="num font-semibold">
                                        <x-money :value="$salesRow['value']"/>
                                    </td>
                                    <td class="num font-semibold text-emerald-700">
                                        <x-money :value="$salesRow['received']"/>
                                    </td>
                                    <td style="min-width:130px">
                                        <div
                                            class="bar {{ $salesRow['rate'] >= 80 ? 'green' : ($salesRow['rate'] >= 40 ? 'amber' : '') }}">
                                            <i style="width:{{ $salesRow['rate'] }}%"></i>
                                        </div>
                                        <span class="muted" style="font-size:11px">{{ $salesRow['rate'] }}%</span>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
            @break
        @case('Coordinator')
            <!-- 1. Payments & Invoicing Overview Section Card -->
            <div class="card mb-6">
                <div class="card-head flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="6" width="20" height="12" rx="2"/>
                                <circle cx="12" cy="12" r="2.5"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Payments &amp; Invoicing Overview</h3>
                            <div class="text-xs text-[#8894ac]">Payment milestones, realization rate &amp; pending dues
                                on assigned accounts
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-3 text-xs">
                        @if($user->hasPermission('schedules', 'view'))
                            <a href="{{ route('schedule.index') }}" wire:navigate
                               class="text-xs font-semibold text-[#0b3d91] hover:underline flex items-center gap-1">
                                <span>View Schedules</span>
                                <span>&rarr;</span>
                            </a>
                        @endif
                        @if($user->hasPermission('payments', 'view'))
                            <span class="text-slate-300">|</span>
                            <a href="{{ route('payments.index') }}" wire:navigate
                               class="text-xs font-semibold text-emerald-700 hover:underline flex items-center gap-1">
                                <span>Payments</span>
                                <span>&rarr;</span>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="card-body p-4 sm:p-5 bg-slate-50/40">
                    <div class="kpis mb-0">
                        <x-kpi-card label="Total Receivable" :value="$m['scheduled']"
                                    sub="Assigned projects · Contract value" icon="cash"
                                    :ismoney="true" color="#0b3d91" bg="#e8eefc"
                                    :href="$user->hasPermission('schedules', 'view') ? route('schedule.index') : route('projects.index')"/>
                        <x-kpi-card label="Received" :value="$m['received']"
                                    :sub="$m['tds'] > 0
                                    ? ('Realized Revenue · ' . view('components.money', ['value' => $m['cash']])->render() . ' Cash + ' . view('components.money', ['value' => $m['tds']])->render() . ' TDS (' . $m['collectionRate'] . '% settled)')
                                    : ('Realized Revenue · ' . $m['collectionRate'] . '% collected to date')"
                                    icon="check" :ismoney="true" color="#5aa236" bg="#eef7e8"
                                    :href="$user->hasPermission('payments', 'view') ? route('payments.index') : route('projects.index')"/>
                        <x-kpi-card label="Due" :value="$m['due']" sub="Balance Due · Net pending collection"
                                    icon="clock"
                                    color="#e0a13a" :ismoney="true" bg="#fdf3e2"
                                    :href="$user->hasPermission('schedules', 'view') ? route('schedule.index') : route('projects.index')"/>
                        <x-kpi-card label="Overdue" :value="$m['overdue']"
                                    :sub="$overdueCount . ' ' . \Illuminate\Support\Str::plural('installment', $overdueCount) . ' late on accounts'"
                                    icon="alert" :ismoney="true" color="#d94b4b" bg="#fdeaea"
                                    :href="$user->hasPermission('today', 'view') ? route('today.index') : route('projects.index')"/>
                        <x-kpi-card label="This Month" :value="$m['thisMonth']" sub="Realized this calendar month"
                                    icon="cal"
                                    color="#2e75b6" :ismoney="true" bg="#e8f1fa"
                                    :href="$user->hasPermission('payments', 'view') ? route('payments.index') : route('projects.index')"/>
                    </div>
                </div>
            </div>

            <!-- 2. Projects & Delivery Execution Section Card -->
            <div class="card mb-6">
                <div class="card-head flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Projects &amp; Delivery Execution</h3>
                            <div class="text-xs text-[#8894ac]">Active project status, health indicators &amp; milestone
                                velocity
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('projects.index') }}" wire:navigate
                           class="text-xs font-semibold text-[#0b3d91] hover:underline flex items-center gap-1">
                            <span>All Projects</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
                <div class="card-body p-4 sm:p-5 bg-slate-50/40">
                    <div class="kpis four mb-0">
                        <x-kpi-card label="Active Projects" :value="$coordinatorData['activeProjectsCount']"
                                    sub="My Active Projects · In-progress" icon="proj"
                                    :ismoney="false" color="#0b3d91" bg="#e8eefc"
                                    :href="route('projects.index', ['status' => 'In Progress'])"/>
                        <x-kpi-card label="Milestones"
                                    :value="$coordinatorData['completedMilestones'] . ' / ' . $coordinatorData['totalMilestones']"
                                    :sub="'Milestones Progress · ' . $coordinatorData['milestoneRate'] . '% completed'"
                                    icon="check"
                                    :ismoney="false" color="#5aa236" bg="#eef7e8" :href="route('schedule.index')"/>
                        <x-kpi-card label="On Track" :value="$healthCounts['On Track']"
                                    sub="Projects progressing on schedule"
                                    icon="check" :ismoney="false" color="#5aa236" bg="#eef7e8"
                                    :href="route('projects.index', ['health' => 'On Track'])"/>
                        <x-kpi-card label="Delayed / At Risk"
                                    :value="$healthCounts['Delayed'] + $healthCounts['At Risk']"
                                    sub="Projects requiring attention" icon="alert"
                                    :ismoney="false" color="#d94b4b" bg="#fdeaea"
                                    :href="route('projects.index', ['health' => 'Delayed'])"/>
                    </div>
                </div>
            </div>
            <!-- COORDINATOR VIEW: Milestones & Project Deliveries -->
            <div class="grid-2 mb-6">
                <!-- My Projects Health -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">My Projects Health</h3>
                            <div class="text-xs text-[#8894ac]">{{ $totalProjectsCount }} projects assigned to you</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;gap:14px;flex-wrap:wrap">
                            @php
                                $colors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                            @endphp
                            @foreach($healthCounts as $label => $count)
                                @php
                                    $percentage = $totalProjectsCount > 0 ? round(($count / $totalProjectsCount) * 100) : 0;
                                    $colorCode = $colors[$label];
                                @endphp
                                <a href="{{ route('projects.index', ['health' => $label]) }}" wire:navigate
                                   class="block p-3 rounded-xl transition hover:bg-gray-50 border border-gray-100 hover:border-gray-200 cursor-pointer"
                                   style="flex:1;min-width:110px;text-decoration:none;color:inherit">
                                    <div
                                        style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:6px">
                                        <i style="width:8px;height:8px;border-radius:50%;background:{{ $colorCode }};display:inline-block"></i>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <div style="display:flex;align-items:baseline;justify-content:space-between">
                                        <span
                                            style="font-size:22px;font-weight:750;letter-spacing:-.6px">{{ $count }}</span>
                                        <span
                                            style="font-size:11px;font-weight:600;color:var(--muted)">{{ $percentage }}%</span>
                                    </div>
                                    <div class="bar" style="height:5px;margin-top:8px">
                                        <i style="width:{{ $percentage }}%;background:{{ $colorCode }}"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Upcoming Project Deliveries (Next 30 Days) -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 14 14"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Upcoming Deliveries</h3>
                            <div class="text-xs text-[#8894ac]">Projects scheduled for completion in next 30 days</div>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($coordinatorData['upcomingDeliveries'] as $project)
                            @php
                                $delDate = $project->delivery_date ? \Carbon\Carbon::parse($project->delivery_date) : null;
                                $daysLeft = $delDate ? (int) round(\Carbon\Carbon::today()->startOfDay()->diffInDays($delDate->startOfDay(), false)) : 0;
                            @endphp
                            <div
                                class="mini-item clickable flex items-center justify-between p-2.5 rounded-xl border border-gray-100 hover:bg-gray-50/70 transition mb-2"
                                @click="Livewire.navigate('{{ route('projects.show', $project->id) }}')">
                                <div class="flex-1 min-w-0 pr-3">
                                    <div class="font-semibold text-sm text-[#0d1b34]">{{ $project->name }}</div>
                                    <div class="text-xs text-[#8894ac] flex items-center gap-1.5 mt-0.5">
                                        <span>{{ $project->customer?->display_name ?? '—' }}</span>
                                        <span>&middot;</span>
                                        <span>Due {{ $delDate ? $delDate->format('d M') : '—' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                <span class="badge {{ $daysLeft <= 7 ? 'b-red' : 'b-amber' }}">
                                    {{ $daysLeft == 0 ? 'Today' : ($daysLeft < 0 ? abs($daysLeft) . 'd late' : $daysLeft . 'd left') }}
                                </span>
                                    <x-health-badge :health="$proj->health"/>
                                </div>
                            </div>
                        @empty
                            <div class="empty py-6 text-center text-gray-400">
                            <span class="flex items-center justify-center mb-1 text-emerald-500">
                                <svg viewBox="0 0 24 24" class="w-7 h-7 stroke-current fill-none"><path
                                        d="M20 6L9 17l-5-5"/></svg>
                            </span>
                                <p class="text-xs font-semibold text-gray-500">No project deliveries due in next 30
                                    days</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- 6-Month Realized Revenue Trend -->
            <div class="card">
                <div class="card-head flex items-center gap-3">
                    <div
                        class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                        <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                            <polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/>
                            <polyline points="17 6 23 6 23 12"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">Project Cash Flow Trend</h3>
                        <div class="text-xs text-[#8894ac]">Collections recorded on your assigned accounts</div>
                    </div>
                </div>
                <div class="card-body">
                    @php $maxVal = max(array_merge([1], array_column($trend, 'value'))); @endphp
                    <div class="spark-chart" style="display:flex;gap:8px;align-items:flex-end">
                        @foreach($trend as $point)
                            @php $barHeight = max(4, round(($point['value'] / $maxVal) * 100)); @endphp
                            <div class="spark-col">
                                <div class="spark-val"
                                     x-text="$store.currency.format({{ (float)$point['value'] }}, true)"></div>
                                <div class="spark-bar-wrap">
                                    <i style="height:{{ $barHeight }}%"
                                       :title="'{{ $point['label'] }}: ' + $store.currency.format({{ (float)$point['value'] }}, false)"></i>
                                </div>
                                <div class="spark-label">{{ $point['label'] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            @break
        @case('TechPerson')
            <!-- Projects & Delivery Execution Section Card -->
            <div class="card mb-6">
                <div class="card-head flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Project Execution &amp; Delivery Progress</h3>
                            <div class="text-xs text-[#8894ac]">Active project assignments, health indicators &amp; milestone velocity
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('projects.index') }}" wire:navigate
                           class="text-xs font-semibold text-[#0b3d91] hover:underline flex items-center gap-1">
                            <span>My Projects</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
                <div class="card-body p-4 sm:p-5 bg-slate-50/40">
                    <div class="kpis four mb-0">
                        <x-kpi-card label="Active Projects" :value="$coordinatorData['activeProjectsCount']"
                                    sub="My Active Projects · In-progress" icon="proj"
                                    :ismoney="false" color="#0b3d91" bg="#e8eefc"
                                    :href="route('projects.index', ['status' => 'In Progress'])"/>
                        <x-kpi-card label="Milestones Done"
                                    :value="$coordinatorData['completedMilestones'] . ' / ' . $coordinatorData['totalMilestones']"
                                    :sub="'Milestones Progress · ' . $coordinatorData['milestoneRate'] . '% completed'"
                                    icon="check"
                                    :ismoney="false" color="#5aa236" bg="#eef7e8" :href="route('projects.index')"/>
                        <x-kpi-card label="On Track" :value="$healthCounts['On Track']"
                                    sub="Projects progressing on schedule"
                                    icon="check" :ismoney="false" color="#5aa236" bg="#eef7e8"
                                    :href="route('projects.index', ['health' => 'On Track'])"/>
                        <x-kpi-card label="Delayed / At Risk"
                                    :value="$healthCounts['Delayed'] + $healthCounts['At Risk']"
                                    sub="Projects requiring attention" icon="alert"
                                    :ismoney="false" color="#d94b4b" bg="#fdeaea"
                                    :href="route('projects.index', ['health' => 'Delayed'])"/>
                    </div>
                </div>
            </div>

            <!-- TECH PERSON VIEW: Assigned Projects Health & Deliveries -->
            <div class="grid-2 mb-6">
                <!-- My Projects Health -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Assigned Projects Health</h3>
                            <div class="text-xs text-[#8894ac]">{{ $totalProjectsCount }} projects assigned to you for execution</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;gap:14px;flex-wrap:wrap">
                            @php
                                $colors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                            @endphp
                            @foreach($healthCounts as $label => $count)
                                @php
                                    $percentage = $totalProjectsCount > 0 ? round(($count / $totalProjectsCount) * 100) : 0;
                                    $colorCode = $colors[$label];
                                @endphp
                                <a href="{{ route('projects.index', ['health' => $label]) }}" wire:navigate
                                   class="block p-3 rounded-xl transition hover:bg-gray-50 border border-gray-100 hover:border-gray-200 cursor-pointer"
                                   style="flex:1;min-width:110px;text-decoration:none;color:inherit">
                                    <div
                                        style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:6px">
                                        <i style="width:8px;height:8px;border-radius:50%;background:{{ $colorCode }};display:inline-block"></i>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <div style="display:flex;align-items:baseline;justify-content:space-between">
                                        <span
                                            style="font-size:22px;font-weight:750;letter-spacing:-.6px">{{ $count }}</span>
                                        <span
                                            style="font-size:11px;font-weight:600;color:var(--muted)">{{ $percentage }}%</span>
                                    </div>
                                    <div class="bar" style="height:5px;margin-top:8px">
                                        <i style="width:{{ $percentage }}%;background:{{ $colorCode }}"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Upcoming Project Deliveries (Next 30 Days) -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <circle cx="12" cy="12" r="10"/>
                                <polyline points="12 6 12 12 14 14"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Upcoming Project Deadlines</h3>
                            <div class="text-xs text-[#8894ac]">Projects scheduled for completion in next 30 days</div>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($coordinatorData['upcomingDeliveries'] as $project)
                            @php
                                $delDate = $project->delivery_date ? \Carbon\Carbon::parse($project->delivery_date) : null;
                                $daysLeft = $delDate ? (int) round(\Carbon\Carbon::today()->startOfDay()->diffInDays($delDate->startOfDay(), false)) : 0;
                            @endphp
                            <div
                                class="mini-item clickable flex items-center justify-between p-2.5 rounded-xl border border-gray-100 hover:bg-gray-50/70 transition mb-2"
                                @click="Livewire.navigate('{{ route('projects.show', $project->id) }}')">
                                <div class="flex-1 min-w-0 pr-3">
                                    <div class="font-semibold text-sm text-[#0d1b34]">{{ $project->name }}</div>
                                    <div class="text-xs text-[#8894ac] flex items-center gap-1.5 mt-0.5">
                                        <span>{{ $project->customer?->display_name ?? '—' }}</span>
                                        <span>&middot;</span>
                                        <span>Due {{ $delDate ? $delDate->format('d M') : '—' }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <span class="badge {{ $daysLeft <= 7 ? 'b-red' : 'b-amber' }}">
                                        {{ $daysLeft == 0 ? 'Today' : ($daysLeft < 0 ? abs($daysLeft) . 'd late' : $daysLeft . 'd left') }}
                                    </span>
                                    <x-health-badge :health="$proj->health"/>
                                </div>
                            </div>
                        @empty
                            <div class="empty py-6 text-center text-gray-400">
                                <span class="flex items-center justify-center mb-1 text-emerald-500">
                                    <svg viewBox="0 0 24 24" class="w-7 h-7 stroke-current fill-none"><path
                                            d="M20 6L9 17l-5-5"/></svg>
                                </span>
                                <p class="text-xs font-semibold text-gray-500">No project deliveries due in next 30
                                    days</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @break
        @case('Sales')
            @if($salesData['target'] > 0)
                <!-- Quota Progress Card for Sales -->
                <div class="card mb-6">
                    <div class="card-head">
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Sales Quota Target</h3>
                            <div class="text-xs text-[#8894ac]">Closed revenue vs assigned sales quota</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="flex items-baseline justify-between mb-2">
                            <div class="font-bold text-lg text-[#0d1b34]">
                                <x-money :value="$salesData['totalDealsValue']"/>
                                <span class="text-sm font-normal text-[#8894ac]">booked of</span>
                                <x-money :value="$salesData['target']"/>
                                <span class="text-sm font-normal text-[#8894ac]">quota</span>
                            </div>
                            <div
                                class="font-bold text-base {{ $salesData['targetProgress'] >= 80 ? 'text-emerald-600' : 'text-blue-600' }}">
                                {{ $salesData['targetProgress'] }}% Achieved
                            </div>
                        </div>
                        <div class="bar" style="height:10px">
                            <i style="width:{{ $salesData['targetProgress'] }}%;background:{{ $salesData['targetProgress'] >= 80 ? '#5aa236' : '#0b3d91' }}"></i>
                        </div>
                    </div>
                </div>
            @endif
            <!-- 1. Revenue & Collections Section Card -->
            <div class="card mb-6">
                <div class="card-head flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="2" y="6" width="20" height="12" rx="2"/>
                                <circle cx="12" cy="12" r="2.5"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Revenue &amp; Collections</h3>
                            <div class="text-xs text-[#8894ac]">Closed deal bookings, cash inflows &amp; balance dues
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('projects.index') }}" wire:navigate
                           class="text-xs font-semibold text-[#0b3d91] hover:underline flex items-center gap-1">
                            <span>My Deals</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
                <div class="card-body p-4 sm:p-5 bg-slate-50/40">
                    <div class="kpis mb-0">
                        <x-kpi-card label="Total Receivable" :value="$salesData['totalDealsValue']"
                                    :sub="'Total Closed Bookings · ' . $salesData['dealsCount'] . ' deals closed'"
                                    icon="cash"
                                    :ismoney="true" color="#0b3d91" bg="#e8eefc" :href="route('projects.index')"/>
                        <x-kpi-card label="Received" :value="$salesData['grossRealized']"
                                    :sub="$salesData['tdsRealized'] > 0
                                    ? ('Cash Inflow Realized · ' . view('components.money', ['value' => $salesData['cashRealized']])->render() . ' Cash + ' . view('components.money', ['value' => $salesData['tdsRealized']])->render() . ' TDS Credit (' . $salesData['realizationRate'] . '% settled)')
                                    : ('Cash Inflow Realized · ' . $salesData['realizationRate'] . '% cash collected')"
                                    icon="check" color="#5aa236"
                                    :ismoney="true" bg="#eef7e8"
                                    :href="auth()->user()->hasPermission('payments', 'view') ? route('payments.index') : route('projects.index')"/>
                        <x-kpi-card label="Due" :value="$salesData['dueAmount']"
                                    sub="Balance Due · Pending collection on deals" icon="clock"
                                    color="#e0a13a" :ismoney="true" bg="#fdf3e2"
                                    :href="auth()->user()->hasPermission('schedules', 'view') ? route('schedule.index') : route('projects.index')"/>
                        <x-kpi-card label="Overdue" :value="$salesData['overdueAmount']"
                                    :sub="$salesData['overdueCount'] . ' late on customer accounts'" icon="alert"
                                    :ismoney="true" color="#d94b4b" bg="#fdeaea"
                                    :href="auth()->user()->hasPermission('today', 'view') ? route('today.index') : route('projects.index')"/>
                        @if($salesData['target'] > 0)
                            <x-kpi-card label="Target" :value="$salesData['target']"
                                        :sub="'Quota Target · ' . $salesData['targetProgress'] . '% quota achieved'"
                                        icon="target"
                                        :ismoney="true" color="#2e75b6" bg="#e8f1fa" :href="route('projects.index')"/>
                        @else
                            <x-kpi-card label="Avg Deal Value" :value="$salesData['avgDealSize']"
                                        sub="Per closed project contract"
                                        icon="chart"
                                        :ismoney="true" color="#2e75b6" bg="#e8f1fa" :href="route('projects.index')"/>
                        @endif
                    </div>
                </div>
            </div>

            <!-- 2. Customer Accounts & Portfolio Section Card -->
            <div class="card mb-6">
                <div class="card-head flex items-center justify-between flex-wrap gap-2">
                    <div class="flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 00-3-3.87"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Customer Accounts &amp; Portfolio</h3>
                            <div class="text-xs text-[#8894ac]">Acquired customer accounts, execution pipeline &amp;
                                monthly velocity
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('customers.index') }}" wire:navigate
                            class="text-xs font-semibold text-[#0b3d91] hover:underline flex items-center gap-1">
                            <span>All Customers</span>
                            <span>&rarr;</span>
                        </a>
                    </div>
                </div>
                <div class="card-body p-4 sm:p-5 bg-slate-50/40">
                    <div class="kpis four mb-0">
                        <x-kpi-card label="Customers" :value="$salesData['customersAcquired']"
                                    sub="Customer accounts acquired"
                                    icon="cust" color="#0b3d91"
                                    :ismoney="false" bg="#e8eefc" :href="route('customers.index')"/>
                        <x-kpi-card label="Projects" :value="$totalProjectsCount" sub="Active customer projects"
                                    icon="proj"
                                    color="#2e75b6"
                                    :ismoney="false" bg="#e8f1fa" :href="route('projects.index')"/>
                        <x-kpi-card label="This Month" :value="$salesData['thisMonthCollections']"
                                    sub="Collections this month"
                                    icon="cal" color="#5aa236"
                                    :ismoney="true" bg="#eef7e8"
                                    :href="auth()->user()->hasPermission('payments', 'view') ? route('payments.index') : route('projects.index')"/>
                        <x-kpi-card label="Avg Deal Value" :value="$salesData['avgDealSize']"
                                    sub="Average contract size"
                                    icon="chart" color="#7a5af8"
                                    :ismoney="true" bg="#f0ecff" :href="route('projects.index')"/>
                    </div>
                </div>
            </div>
            <!-- SALES VIEW: Deal Origination, Quota Progress & Customer Projects -->
            <div class="grid-2">
                <!-- Customer Projects Fulfillment Status -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-blue-50 text-[#0b3d91] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Customer Projects Fulfillment</h3>
                            <div class="text-xs text-[#8894ac]">Execution status of projects sold by you</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="display:flex;gap:14px;flex-wrap:wrap">
                            @php
                                $colors = ['On Track' => '#5aa236', 'At Risk' => '#e0a13a', 'Delayed' => '#d94b4b', 'Completed' => '#2e75b6'];
                            @endphp
                            @foreach($healthCounts as $label => $count)
                                @php
                                    $percentage = $totalProjectsCount > 0 ? round(($count / $totalProjectsCount) * 100) : 0;
                                    $colorCode = $colors[$label];
                                @endphp
                                <a href="{{ route('projects.index', ['health' => $label]) }}" wire:navigate
                                   class="block p-3 rounded-xl transition hover:bg-gray-50 border border-gray-100 hover:border-gray-200 cursor-pointer"
                                   style="flex:1;min-width:110px;text-decoration:none;color:inherit">
                                    <div
                                        style="display:flex;align-items:center;gap:6px;font-size:12px;font-weight:650;margin-bottom:6px">
                                        <i style="width:8px;height:8px;border-radius:50%;background:{{ $colorCode }};display:inline-block"></i>
                                        <span>{{ $label }}</span>
                                    </div>
                                    <div style="display:flex;align-items:baseline;justify-content:space-between">
                                        <span
                                            style="font-size:22px;font-weight:750;letter-spacing:-.6px">{{ $count }}</span>
                                        <span
                                            style="font-size:11px;font-weight:600;color:var(--muted)">{{ $percentage }}%</span>
                                    </div>
                                    <div class="bar" style="height:5px;margin-top:8px">
                                        <i style="width:{{ $percentage }}%;background:{{ $colorCode }}"></i>
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>

                <!-- Recent Closed Deals & Accounts -->
                <div class="card">
                    <div class="card-head flex items-center gap-3">
                        <div
                            class="w-8 h-8 rounded-lg bg-emerald-50 text-[#5aa236] flex items-center justify-center shrink-0">
                            <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                                <circle cx="9" cy="7" r="4"/>
                                <path d="M22 21v-2a4 4 0 00-3-3.87"/>
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Recent Closed Deals</h3>
                            <div class="text-xs text-[#8894ac]">Latest customer accounts originated</div>
                        </div>
                    </div>
                    <div class="card-body">
                        @forelse($salesData['recentDeals'] as $deal)
                            <div
                                class="mini-item clickable flex items-center justify-between p-2.5 rounded-xl border border-gray-100 hover:bg-gray-50/70 transition mb-2"
                                @click="Livewire.navigate('{{ route('projects.show', $deal->id) }}')">
                                <div class="flex-1 min-w-0 pr-3">
                                    <div class="font-semibold text-sm text-[#0d1b34]">{{ $deal->name }}</div>
                                    <div class="text-xs text-[#8894ac] flex items-center gap-1.5 mt-0.5">
                                        <span>{{ $deal->customer?->display_name ?? '—' }}</span>
                                        <span>&middot;</span>
                                        <span class="font-semibold text-emerald-700"><x-money
                                                :value="$deal->value"/></span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2 shrink-0">
                                    <x-health-badge :health="$deal->health"/>
                                </div>
                            </div>
                        @empty
                            <div class="empty py-6 text-center text-gray-400">
                                <p class="text-xs font-semibold text-gray-500">No deals originated yet</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
            @break
    @endswitch
</div>
