<div class="space-y-5">
    <!-- Header: Title, Quick Actions & Month Navigation -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="p-2 rounded-lg bg-blue-50 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <svg viewBox="0 0 24 24" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                        <line x1="16" y1="2" x2="16" y2="6"/>
                        <line x1="8" y1="2" x2="8" y2="6"/>
                        <line x1="3" y1="10" x2="21" y2="10"/>
                    </svg>
                </span>
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-white flex items-center gap-2">
                        Payment Calendar
                        <span class="text-sm font-normal text-slate-500 dark:text-slate-400">
                            ({{ $currentMonth->format('F Y') }})
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400">
                        Date wise tracking of scheduled installment dues and payment receipts
                    </p>
                </div>
            </div>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('dashboard') }}" wire:navigate
               class="btn btn-outline-primary btn-sm flex items-center gap-1.5 shadow-xs">
                <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                    <rect x="3" y="3" width="7" height="7"/>
                    <rect x="14" y="3" width="7" height="7"/>
                    <rect x="14" y="14" width="7" height="7"/>
                    <rect x="3" y="14" width="7" height="7"/>
                </svg>
                <span>Dashboard</span>
            </a>

            <!-- Month Navigator Controls -->
            <div
                class="inline-flex items-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-lg p-0.5 shadow-xs">
                <button type="button"
                        wire:click="prevMonth"
                        class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-600 dark:text-slate-300"
                        title="Previous Month">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M15 18l-6-6 6-6"/>
                    </svg>
                </button>

                <button type="button"
                        wire:click="goToToday"
                        class="px-2.5 py-1 text-xs font-semibold text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition-colors"
                        title="Jump to Current Month">
                    Today
                </button>

                <button type="button"
                        wire:click="nextMonth"
                        class="p-1.5 hover:bg-slate-100 dark:hover:bg-slate-800 rounded text-slate-600 dark:text-slate-300"
                        title="Next Month">
                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M9 18l6-6-6-6"/>
                    </svg>
                </button>
            </div>

            <input type="month"
                   wire:model.live="month"
                   class="i text-xs py-1.5 px-2.5 w-auto bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-lg shadow-xs"
                   title="Pick Specific Month & Year">
        </div>
    </div>

    <!-- Monthly KPI Cards Strip -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
        <div class="card p-3.5 border-l-4 border-l-blue-600 shadow-xs">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Scheduled Due</span>
            <div class="text-lg font-bold text-slate-900 dark:text-white mt-0.5">
                <x-money :value="$monthlyScheduled"/>
            </div>
            <span class="text-2xs text-slate-400 dark:text-slate-500">In {{ $currentMonth->format('M Y') }}</span>
        </div>

        <div class="card p-3.5 border-l-4 border-l-emerald-600 shadow-xs">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Received</span>
            <div class="text-lg font-bold text-emerald-600 dark:text-emerald-400 mt-0.5">
                <x-money :value="$monthlyReceived"/>
            </div>
            <span class="text-2xs text-slate-400 dark:text-slate-500">In {{ $currentMonth->format('M Y') }}</span>
        </div>

        <div class="card p-3.5 border-l-4 border-l-amber-500 shadow-xs">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Due (Pending)</span>
            <div class="text-lg font-bold text-amber-600 dark:text-amber-400 mt-0.5">
                <x-money :value="$monthlyPending"/>
            </div>
            <span class="text-2xs text-slate-400 dark:text-slate-500">Awaiting collection</span>
        </div>

        <div class="card p-3.5 border-l-4 border-l-red-500 shadow-xs">
            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">Total Overdue</span>
            <div class="text-lg font-bold text-red-600 dark:text-red-400 mt-0.5">
                <x-money :value="$monthlyOverdue"/>
            </div>
            <span class="text-2xs text-slate-400 dark:text-slate-500">Past due date</span>
        </div>
    </div>

    <!-- Filter Bar: Type Filter & Downline Person Filter -->
    <div class="card p-3 shadow-xs">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <!-- Type Filter Tabs -->
            <div class="flex items-center gap-1.5">
                <button type="button"
                        wire:click="$set('typeFilter', 'all')"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors btn {{ $typeFilter === 'all' ? 'btn-secondary' : 'btn-outline-secondary' }}">
                    All Records
                </button>
                <button type="button"
                        wire:click="$set('typeFilter', 'schedules')"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors btn {{ $typeFilter === 'schedules' ? 'btn-danger' : 'btn-outline-danger' }}">
                    Scheduled Dues Only
                </button>
                <button type="button"
                        wire:click="$set('typeFilter', 'receipts')"
                        class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-colors btn {{ $typeFilter === 'receipts' ? 'btn-green' : 'btn-outline-green' }}">
                    Received Payments Only
                </button>
                @if($selectedUserId && $activeViewingUser->id !== auth()->id())
                    <div
                        class="ms-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-between text-xs">
                <span
                    class="inline-flex items-center gap-1.5 text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/30 px-2 py-0.5 rounded-md font-medium">
                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                    Viewing Calendar for: <strong>{{ $activeViewingUser->name }}</strong> ({{ $activeViewingUser->role }})
                </span>
                    </div>
                @endif
            </div>

            <!-- Downline Team Member Selector -->
            @if($accessibleUsers->isNotEmpty())
                <div class="flex items-center gap-2">
                    <label for="calendar-user-filter"
                           class="text-xs font-semibold text-slate-600 dark:text-slate-300 whitespace-nowrap">
                        Team Member:
                    </label>
                    <select id="calendar-user-filter"
                            wire:model.live="selectedUserId"
                            class="i text-xs py-1.5 px-3 min-w-50 bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-800 rounded-lg">
                        <option value="">Entire Team / Portfolio</option>
                        @foreach($accessibleUsers as $u)
                            <option value="{{ $u->id }}">
                                {{ $u->name }} ({{ $u->role }})
                            </option>
                        @endforeach
                    </select>

                    @if($selectedUserId)
                        <button type="button" wire:click="$set('selectedUserId', null)" class="btn btn-outline-danger">
                            ✕ Clear Filter
                        </button>
                    @endif
                </div>
            @endif
        </div>
    </div>

    <!-- 7-Column Month Calendar Grid -->
    <div class="card overflow-hidden shadow-xs border border-slate-200 dark:border-slate-800">
        <!-- Day-of-Week Column Headers -->
        <div
            class="grid grid-cols-7 bg-slate-100 dark:bg-slate-800/80 text-center border-b border-slate-200 dark:border-slate-800 text-xs font-semibold text-slate-700 dark:text-slate-300">
            <div class="py-2.5 text-red-600 dark:text-red-400">Sun</div>
            <div class="py-2.5">Mon</div>
            <div class="py-2.5">Tue</div>
            <div class="py-2.5">Wed</div>
            <div class="py-2.5">Thu</div>
            <div class="py-2.5">Fri</div>
            <div class="py-2.5 text-blue-600 dark:text-blue-400">Sat</div>
        </div>

        <!-- Days Cells Grid -->
        <div class="grid grid-cols-7 gap-px bg-slate-200 dark:bg-slate-800">
            @foreach($days as $day)
                @php
                    $isSel = $selectedDate === $day['date'];
                    $showSchedules = $typeFilter !== 'receipts' && $day['scheduledCount'] > 0;
                    $showReceipts = $typeFilter !== 'schedules' && $day['receivedCount'] > 0;
                    $totalItems = ($showSchedules ? $day['scheduledCount'] : 0) + ($showReceipts ? $day['receivedCount'] : 0);
                @endphp
                <div wire:click="selectDate('{{ $day['date'] }}')"
                     class="min-h-27.5 sm:min-h-32.5 p-2 transition-all cursor-pointer relative flex flex-col justify-between
                        {{ $day['isCurrentMonth'] ? 'bg-white dark:bg-slate-900' : 'bg-slate-50/70 dark:bg-slate-900/40 text-slate-400 dark:text-slate-600' }}
                        {{ $day['isToday'] ? 'ring-2 ring-inset ring-blue-500 bg-blue-50/30 dark:bg-blue-950/20' : '' }}
                        {{ $isSel ? 'bg-blue-50/70 dark:bg-blue-900/40 ring-2 ring-inset ring-indigo-600' : 'hover:bg-slate-50/80 dark:hover:bg-slate-800/50' }}">

                    <!-- Top Row: Day Number & Today Tag -->
                    <div class="flex items-center justify-between mb-1.5">
                        <span
                            class="text-xs font-bold {{ $day['isToday'] ? 'text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/60 px-1.5 py-0.5 rounded-full' : ($day['isCurrentMonth'] ? 'text-slate-800 dark:text-slate-200' : 'text-slate-400') }}">
                            {{ $day['dayNumber'] }}
                        </span>

                        @if($day['isToday'])
                            <span
                                class="text-2xs font-semibold uppercase tracking-wider text-blue-600 dark:text-blue-400">
                                Today
                            </span>
                        @endif

                        @if($day['hasOverdue'] && $typeFilter !== 'receipts')
                            <span class="w-2 h-2 rounded-full bg-red-500" title="Contains overdue terms"></span>
                        @endif
                    </div>

                    <!-- Middle: Financial Aggregate Badges -->
                    <div class="space-y-1 my-auto">
                        @if($showSchedules)
                            <div
                                class="flex items-center justify-between text-2xs font-medium px-1.5 py-0.5 rounded bg-blue-50 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 border border-blue-200/60 dark:border-blue-800/60"
                                title="Scheduled Due: {{ $day['scheduledCount'] }} installment(s)">
                                <span class="flex items-center gap-1 truncate">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3 shrink-0" fill="none" stroke="currentColor"
                                         stroke-width="2">
                                        <circle cx="12" cy="12" r="10"/>
                                        <polyline points="12 6 12 12 16 14"/>
                                    </svg>
                                    <span>Due</span>
                                </span>
                                <x-money :value="number_format($day['scheduledAmount'])"/>
                            </div>
                        @endif

                        @if($showReceipts)
                            <div
                                class="flex items-center justify-between text-2xs font-medium px-1.5 py-0.5 rounded bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300 border border-emerald-200/60 dark:border-emerald-800/60"
                                title="Payments Received: {{ $day['receivedCount'] }} receipt(s)">
                                <span class="flex items-center gap-1 truncate">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3 shrink-0" fill="none" stroke="currentColor"
                                         stroke-width="2">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                    <span>Paid</span>
                                </span>
                                <x-money :value="number_format($day['receivedAmount'])"/>
                            </div>
                        @endif
                    </div>

                    <!-- Bottom: Item summary count or dot -->
                    <div class="mt-1 pt-1 border-t border-slate-100 dark:border-slate-800/60 text-right">
                        @if($totalItems > 0)
                            <span class="text-3xs font-medium text-slate-500 dark:text-slate-400">
                                {{ $totalItems }} record{{ $totalItems === 1 ? '' : 's' }}
                            </span>
                        @else
                            <span class="text-3xs text-slate-300 dark:text-slate-700">—</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <!-- Date Details Modal (When a date is selected) -->
    @if($selectedDate)
        @php
            $carbonSelDate = \Carbon\Carbon::parse($selectedDate);
            $selScheduledSum = (float) $selectedDateTerms->sum('amount');
            $selReceivedSum = (float) $selectedDatePayments->sum('amount');
        @endphp
        <div class="fixed inset-0 z-50 overflow-y-auto"
             role="dialog"
             aria-modal="true"
             wire:keydown.escape.window="closeDateModal">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs transition-opacity"
                 wire:click="closeDateModal"></div>

            <div class="flex min-h-full items-center justify-center p-4 text-center sm:p-0">
                <div
                    class="relative transform overflow-hidden rounded-xl bg-white dark:bg-slate-900 text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-3xl border border-slate-200 dark:border-slate-800">
                    <!-- Modal Header -->
                    <div
                        class="bg-slate-50 dark:bg-slate-800/80 px-6 py-4 border-b border-slate-200 dark:border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-base font-bold text-slate-900 dark:text-white flex items-center gap-2">
                                <svg viewBox="0 0 24 24" class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none"
                                     stroke="currentColor" stroke-width="2">
                                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"/>
                                    <line x1="16" y1="2" x2="16" y2="6"/>
                                    <line x1="8" y1="2" x2="8" y2="6"/>
                                    <line x1="3" y1="10" x2="21" y2="10"/>
                                </svg>
                                Payments for {{ $carbonSelDate->format('l, F j, Y') }}
                            </h3>
                            <div class="flex items-center gap-3 mt-1 text-xs">
                                <span class="text-blue-600 dark:text-blue-400 font-semibold">
                                    Scheduled Due: <x-money :value="$selScheduledSum"/>
                                </span>
                                <span class="text-slate-300 dark:text-slate-700">|</span>
                                <span class="text-emerald-600 dark:text-emerald-400 font-semibold">
                                    Collected: <x-money :value="$selReceivedSum"/>
                                </span>
                            </div>
                        </div>
                        <button type="button"
                                wire:click="closeDateModal"
                                class="text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 p-1 rounded-lg">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M18 6L6 18M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal Body -->
                    <div class="p-6 space-y-6 max-h-[70vh] overflow-y-auto">
                        <!-- 1. Scheduled Terms Section -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                Scheduled Installments Due ({{ $selectedDateTerms->count() }})
                            </h4>

                            @if($selectedDateTerms->isEmpty())
                                <p class="text-xs text-slate-400 italic py-2">No scheduled terms due on this date.</p>
                            @else
                                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-lg">
                                    <table class="w-full text-left text-xs">
                                        <thead
                                            class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-semibold border-b border-slate-200 dark:border-slate-800">
                                        <tr>
                                            <th class="py-2 px-3">Term / Milestone</th>
                                            <th class="py-2 px-3">Customer & Project</th>
                                            <th class="py-2 px-3 text-right">Amount</th>
                                            <th class="py-2 px-3 text-right">Balance Due</th>
                                            <th class="py-2 px-3 text-center">Status</th>
                                            <th class="py-2 px-3 text-right">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        @foreach($selectedDateTerms as $term)
                                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                                <td class="py-2.5 px-3">
                                                    <div
                                                        class="font-semibold text-slate-900 dark:text-white">{{ $term->label }}</div>
                                                    <div class="text-2xs text-slate-400">{{ $term->trigger_type }}</div>
                                                </td>
                                                <td class="py-2.5 px-3">
                                                    <div
                                                        class="font-medium text-slate-800 dark:text-slate-200">{{ $term->project?->customer?->display_name ?? '—' }}</div>
                                                    <div class="text-2xs text-blue-600 dark:text-blue-400">
                                                        <a href="{{ route('projects.show', $term->project_id) }}"
                                                           wire:navigate class="hover:underline">
                                                            {{ $term->project?->code }} - {{ $term->project?->name }}
                                                        </a>
                                                    </div>
                                                </td>
                                                <td class="py-2.5 px-3 text-right font-medium">
                                                    <x-money :value="$term->amount"/>
                                                </td>
                                                <td class="py-2.5 px-3 text-right font-bold {{ $term->balance > 0 ? 'text-red-600 dark:text-red-400' : 'text-slate-400' }}">
                                                    <x-money :value="$term->balance"/>
                                                </td>
                                                <td class="py-2.5 px-3 text-center">
                                                    @php
                                                        $stClass = match($term->status) {
                                                            'Paid' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                                            'Overdue' => 'bg-red-50 text-red-700 dark:bg-red-900/40 dark:text-red-300',
                                                            'Partial' => 'bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                                            default => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300'
                                                        };
                                                    @endphp
                                                    <span
                                                        class="inline-block px-2 py-0.5 text-2xs font-semibold rounded-full {{ $stClass }}">
                                                            {{ $term->status }}
                                                        </span>
                                                </td>
                                                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                                    <a href="{{ route('projects.show', $term->project_id) }}"
                                                       wire:navigate
                                                       class="btn btn-outline-primary btn-xs">
                                                        View Project
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>

                        <!-- 2. Received Payments Section -->
                        <div>
                            <h4 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 mb-2 flex items-center gap-1.5">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                Payments Received on this Date ({{ $selectedDatePayments->count() }})
                            </h4>

                            @if($selectedDatePayments->isEmpty())
                                <p class="text-xs text-slate-400 italic py-2">No payments received on this date.</p>
                            @else
                                <div class="overflow-x-auto border border-slate-200 dark:border-slate-800 rounded-lg">
                                    <table class="w-full text-left text-xs">
                                        <thead
                                            class="bg-slate-50 dark:bg-slate-800/60 text-slate-600 dark:text-slate-300 font-semibold border-b border-slate-200 dark:border-slate-800">
                                        <tr>
                                            <th class="py-2 px-3">Receipt Code</th>
                                            <th class="py-2 px-3">Customer & Project</th>
                                            <th class="py-2 px-3">Mode & Bank</th>
                                            <th class="py-2 px-3 text-right">Amount</th>
                                            <th class="py-2 px-3 text-right">Action</th>
                                        </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                        @foreach($selectedDatePayments as $payment)
                                            <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40">
                                                <td class="py-2.5 px-3">
                                                    <div
                                                        class="font-bold text-slate-900 dark:text-white">{{ $payment->code }}</div>
                                                    <div
                                                        class="text-2xs text-slate-400">{{ $payment->reference ?: 'No reference' }}</div>
                                                </td>
                                                <td class="py-2.5 px-3">
                                                    <div
                                                        class="font-medium text-slate-800 dark:text-slate-200">{{ $payment->customer?->display_name ?? '—' }}</div>
                                                    <div class="text-2xs text-blue-600 dark:text-blue-400">
                                                        <a href="{{ route('projects.show', $payment->project_id) }}"
                                                           wire:navigate class="hover:underline">
                                                            {{ $payment->project?->code }}
                                                            - {{ $payment->project?->name }}
                                                        </a>
                                                    </div>
                                                </td>
                                                <td class="py-2.5 px-3">
                                                        <span
                                                            class="inline-block px-1.5 py-0.5 rounded text-2xs font-medium bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                                            {{ $payment->mode }}
                                                        </span>
                                                    @if($payment->bank)
                                                        <span class="text-2xs text-slate-400 ml-1">({{ $payment->bank }})</span>
                                                    @endif
                                                </td>
                                                <td class="py-2.5 px-3 text-right font-bold text-emerald-600 dark:text-emerald-400">
                                                    <x-money :value="$payment->amount"/>
                                                </td>
                                                <td class="py-2.5 px-3 text-right whitespace-nowrap">
                                                    <a href="{{ route('projects.show', $payment->project_id) }}"
                                                       wire:navigate
                                                       class="btn btn-outline-primary btn-xs">
                                                        View
                                                    </a>
                                                </td>
                                            </tr>
                                        @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Modal Footer -->
                    <div
                        class="bg-slate-50 dark:bg-slate-800/80 px-6 py-3 border-t border-slate-200 dark:border-slate-800 flex justify-end">
                        <button type="button"
                                wire:click="closeDateModal"
                                class="btn btn-outline-primary btn-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
