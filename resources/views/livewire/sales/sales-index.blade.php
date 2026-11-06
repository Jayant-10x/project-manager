<div>
    <!-- Page Header with Summary & Actions -->
    <div class="page-head mb-4">
        <span class="info text-sm text-[#8894ac]">
            {{ $salesCount }} salesperson{{ $salesCount === 1 ? '' : 's' }} · {{ $totalOnboarded }} onboarded customer{{ $totalOnboarded === 1 ? '' : 's' }}
        </span>
        <span class="spacer"></span>
        <button type="button" class="btn btn-outline-secondary btn-sm" wire:click="exportCsv">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <path d="M7 10l5 5 5-5"/>
                <path d="M12 15V3"/>
            </svg>
            Export CSV
        </button>
        @if(auth()->user()->role === 'Admin')
            <button type="button" class="btn btn-primary btn-sm"
                    @click="Livewire.dispatch('open-user-modal', { id: null, role: 'Sales' })">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                Add Salesperson
            </button>
        @endif
    </div>

    <!-- 1. Grid of Salesperson Cards -->
    <div class="grid-3 mb-6" style="gap:15px">
        @forelse($rows as $row)
            @php
                $salesPerson = $row['salesPerson'];
                $roleColors = [
                    'Admin' => ['badge' => 'bg-indigo-50 text-indigo-700 border-indigo-200', 'avatar' => '#4338ca'],
                    'Manager' => ['badge' => 'bg-purple-50 text-purple-700 border-purple-200', 'avatar' => '#7e22ce'],
                    'Coordinator' => ['badge' => 'bg-amber-50 text-amber-700 border-amber-200', 'avatar' => '#d97706'],
                    'TechPerson' => ['badge' => 'bg-cyan-50 text-cyan-700 border-cyan-200', 'avatar' => '#0891b2'],
                    'Sales' => ['badge' => 'bg-emerald-50 text-emerald-700 border-emerald-200', 'avatar' => '#059669'],
                ];
                $cfg = $roleColors[array_rand($roleColors)];
                @endphp
            <div class="card p-4">
                <div class="flex items-center gap-3 mb-3">
                    @if($salesPerson->avatar_url)
                        <img src="{{ $salesPerson->avatar_url }}" alt="{{ $salesPerson->name }}"
                             class="w-11 h-11 rounded-xl object-cover shrink-0 shadow-2xs border border-gray-200">
                    @else
                        <div
                            class="w-11 h-11 rounded-xl flex items-center justify-center text-white font-bold text-sm shrink-0 shadow-xs"
                            style="background:{{ $cfg['avatar'] }}">
                            {{ strtoupper(substr($salesPerson->name, 0, 2)) }}
                        </div>
                    @endif
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-base text-[#0d1b34]">{{ $salesPerson->name }}</div>
                        <div class="text-xs text-[#8894ac]">{{ $salesPerson->phone ?: ($salesPerson->email ?: '—') }}</div>
                        <div class="mt-1">
                            <span
                                class="badge {{ status_badge_class($salesPerson->status) }}">{{ $salesPerson->status }}</span>
                        </div>
                    </div>
                </div>

                <div class="money-grid grid-cols-2 gap-2 my-2">
                    <a href="{{ route('customers.index', ['sales' => $salesPerson->id]) }}" wire:navigate
                       class="money-cell p-2 block transition hover:bg-blue-50/50 cursor-pointer rounded-xl"
                       style="text-decoration:none;color:inherit"
                       title="View customers onboarded by {{ $salesPerson->name }}">
                        <div class="k text-[10px]">Onboarded</div>
                        <div class="v text-base text-[#0b3d91] font-bold">{{ $row['customers'] }}</div>
                    </a>
                    <a href="{{ route('projects.index', ['sales_person_id' => $salesPerson->id]) }}" wire:navigate
                       class="money-cell p-2 block transition hover:bg-blue-50/50 cursor-pointer rounded-xl"
                       style="text-decoration:none;color:inherit"
                       title="View projects">
                        <div class="k text-[10px]">Projects</div>
                        <div class="v text-base font-bold">{{ $row['projects'] }}</div>
                    </a>
                </div>

                <div class="mt-3">
                    <div class="progress-row text-xs flex justify-between mb-1">
                        <span class="text-[#8894ac]">Collected</span>
                        <b>
                            <x-money :value="$row['received']"/>
                            /
                            <x-money :value="$row['value']"/>
                        </b>
                    </div>
                    <div class="bar {{ $row['rate'] >= 80 ? 'green' : ($row['rate'] >= 40 ? '' : 'amber') }}"
                         style="height:6px">
                        <i style="width:{{ $row['rate'] }}%"></i>
                    </div>
                </div>

                @if($row['overdue'] > 0)
                    <div class="mt-2.5">
                        <span class="badge b-red text-xs"><x-money :value="$row['overdue']"/> overdue</span>
                    </div>
                @endif

                @if($salesPerson->target > 0)
                    <div class="mt-2 text-xs text-[#8894ac]">
                        Target:
                        <x-money :value="$salesPerson->target"/>
                    </div>
                @endif

                @if(auth()->user()->role === 'Admin')
                    <div class="row-actions mt-3 justify-start flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-xs"
                                @click="Livewire.dispatch('open-user-modal', { id: {{ $salesPerson->id }} })"
                                title="Edit Salesperson">
                            <svg viewBox="0 0 24 24" class="w-3 h-3">
                                <path d="M12 20h9"/>
                                <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                            </svg>
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-xs"
                                @click="$dispatch('confirm-dialog', {
                                    title: 'Remove Salesperson?',
                                    message: 'Remove {{ addslashes($salesPerson->name) }}? Past attributed customers and projects will remain intact.',
                                    confirmText: 'Remove Salesperson',
                                    cancelText: 'Cancel',
                                    danger: true,
                                    wire: $wire,
                                    action: 'deleteSales',
                                    params: {{ $salesPerson->id }}
                                })"
                                title="Remove Salesperson">
                            <svg viewBox="0 0 24 24" class="w-3 h-3">
                                <path
                                    d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            </svg>
                        </button>
                    </div>
                @endif
            </div>
        @empty
            <div class="col-span-3">
                <div class="empty py-8 text-center text-gray-400">
                    <span class="flex items-center justify-center mb-2">
                        <svg viewBox="0 0 24 24" class="w-10 h-10 stroke-current fill-none">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 00-3-3.87"/>
                        </svg>
                    </span>
                    <p class="font-medium text-gray-600">No salespeople registered</p>
                    <div class="text-xs">Add a salesperson to start tracking lead attribution</div>
                </div>
            </div>
        @endforelse
    </div>

    <!-- 2. Collection by Salesperson Table -->
    <div class="card">
        <div class="card-head p-4 border-b border-[#eef2f9]">
            <div>
                <h3 class="font-bold text-sm text-[#0d1b34]">Collection by salesperson</h3>
                <div class="text-xs text-[#8894ac]">Revenue and collection progress attribution</div>
            </div>
        </div>
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Salesperson</th>
                    <th>Customers</th>
                    <th>Projects</th>
                    <th class="num">Total Value</th>
                    <th class="num">Received</th>
                    <th class="num">Overdue</th>
                    <th>Collection</th>
                </tr>
                </thead>
                <tbody>
                @forelse($rows as $row)
                    <tr>
                        <td class="strong">{{ $row['salesPerson']->name }}</td>
                        <td>{{ $row['customers'] }}</td>
                        <td>{{ $row['projects'] }}</td>
                        <td class="num">
                            <x-money :value="$row['value']"/>
                        </td>
                        <td class="num font-bold text-[#3d7a24]">
                            <x-money :value="$row['received']"/>
                        </td>
                        <td class="num" style="color:{{ $row['overdue'] > 0 ? '#d94b4b' : 'inherit' }}">
                            <x-money :value="$row['overdue']"/>
                        </td>
                        <td style="min-width:130px">
                            <div class="bar {{ $row['rate'] >= 80 ? 'green' : ($row['rate'] >= 40 ? '' : 'amber') }}">
                                <i style="width:{{ $row['rate'] }}%"></i>
                            </div>
                            <span class="muted" style="font-size:11px">{{ $row['rate'] }}%</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="empty py-6 text-center text-gray-400">
                                <p>No collection records yet</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
