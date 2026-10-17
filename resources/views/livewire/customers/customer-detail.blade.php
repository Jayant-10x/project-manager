<div>
    <div class="mb-4">
        <a href="{{ route('customers.index') }}" wire:navigate
           class="btn btn-ghost btn-sm inline-flex items-center gap-1.5">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M19 12H5M12 19l-7-7 7-7"/>
            </svg>
            Back
        </a>
    </div>

    <!-- Customer Identity & Financial Header -->
    <div class="card mb-4">
        <div class="card-body">
            <div style="display:flex;align-items:flex-start;gap:14px;flex-wrap:wrap">
                <div
                    style="width:54px;height:54px;border-radius:14px;background:linear-gradient(135deg,#0b3d91,#5aa236);display:grid;place-items:center;color:#fff;font-size:18px;font-weight:750">
                    {{ strtoupper(substr($customer->company, 0, 2)) }}
                </div>
                <div style="flex:1;min-width:240px">
                    <h2 style="font-size:19px;font-weight:700;letter-spacing:-.3px">{{ $customer->company }}</h2>
                    <div class="muted" style="font-size:12.5px;margin-top:3px">
                        {{ $customer->code }}
                        @if($customer->contact_name)
                            · {{ $customer->contact_name }}
                        @endif
                        @if($customer->phone)
                            · {{ $customer->phone }}
                        @endif
                        @if($customer->email)
                            · {{ $customer->email }}
                        @endif
                    </div>
                    <div style="margin-top:8px;display:flex;gap:7px;flex-wrap:wrap">
                        <span
                            class="badge {{ $customer->status === 'Active' ? 'b-green' : ($customer->status === 'Churned' ? 'b-red' : 'b-grey') }}">
                            {{ $customer->status }}
                        </span>
                        @if($customer->source)
                            <span class="pill">{{ $customer->source }}</span>
                        @endif
                        @if($customer->gst_number)
                            <span class="pill">GST {{ $customer->gst_number }}</span>
                        @endif
                        @if($customer->salesPerson)
                            <span class="pill">Sales: {{ $customer->salesPerson->name }}</span>
                        @endif
                    </div>
                </div>
                <div style="display:flex;gap:8px;flex-wrap:wrap">
                    <button type="button" class="btn btn-ghost btn-sm"
                            @click="$dispatch('open-customer-modal', { id: {{ $customer->id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                        </svg>
                        Edit
                    </button>
                    <button type="button" class="btn btn-ghost btn-sm"
                            @click="Livewire.dispatch('open-followup-modal', { customerId: {{ $customer->id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                        </svg>
                        Log Follow-up
                    </button>
                    <button type="button" class="btn btn-primary btn-sm"
                            @click="$dispatch('open-modal', 'project-modal')">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        New Project
                    </button>
                    @if(auth()->user()->role === 'Admin')
                        <button type="button" class="btn btn-ghost btn-sm text-red-600 hover:bg-red-50"
                                wire:confirm="Are you sure? This will delete the customer and all related projects and documents permanently."
                                wire:click="deleteCustomer" title="Delete customer">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path
                                    d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            </svg>
                        </button>
                    @endif
                </div>
            </div>

            <div class="divider"></div>

            <!-- Financial Strip -->
            <div class="money-grid">
                <div class="money-cell cursor-pointer hover:bg-gray-50 transition rounded-xl p-1.5"
                     wire:click="$set('activeTab', 'projects')" title="View projects">
                    <div class="k">Total Value</div>
                    <div class="v">
                        <x-money :value="$money['contract'] > 0 ? $money['contract'] : $money['scheduled']"/>
                    </div>
                    @if($money['contract'] > 0 && abs((float)$money['contract'] - (float)$money['scheduled']) > 0.01)
                        <div class="text-[11px] text-[#8894ac] mt-0.5">
                            Sched: <x-money :value="$money['scheduled']"/>
                        </div>
                    @endif
                </div>
                <div class="money-cell rec cursor-pointer hover:bg-green-50/50 transition rounded-xl p-1.5"
                     wire:click="$set('activeTab', 'payments')" title="View payments">
                    <div class="k">Received</div>
                    <div class="v text-[#5aa236]">
                        <x-money :value="$money['received']"/>
                    </div>
                </div>
                <div class="money-cell due cursor-pointer hover:bg-amber-50/50 transition rounded-xl p-1.5"
                     wire:click="$set('activeTab', 'payments')" title="View installments due">
                    <div class="k">Due</div>
                    <div class="v text-[#e0a13a]">
                        <x-money :value="$money['due']"/>
                    </div>
                </div>
                <div class="money-cell over cursor-pointer hover:bg-red-50/50 transition rounded-xl p-1.5"
                     wire:click="$set('activeTab', 'payments')" title="View overdue installments">
                    <div class="k">Overdue</div>
                    <div class="v text-[#d94b4b]">
                        <x-money :value="$money['overdue']"/>
                    </div>
                </div>
            </div>

            <div style="margin-top:14px">
                <div class="progress-row">
                    <span>Collection progress</span>
                    <b>{{ $money['rate'] }}%</b>
                </div>
                <div class="bar {{ $money['rate'] >= 80 ? 'green' : ($money['rate'] >= 40 ? '' : 'amber') }}"
                     style="height:8px">
                    <i style="width:{{ $money['rate'] }}%"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabbed Information Panes -->
    <div class="card mb-4">
        <div class="tabs">
            <button wire:click="$set('activeTab', 'overview')"
                    class="tab {{ $activeTab === 'overview' ? 'active' : '' }}">Overview
            </button>
            <button wire:click="$set('activeTab', 'projects')"
                    class="tab {{ $activeTab === 'projects' ? 'active' : '' }}">
                Projects <span class="n">{{ $projects->count() }}</span>
            </button>
            <button wire:click="$set('activeTab', 'payments')"
                    class="tab {{ $activeTab === 'payments' ? 'active' : '' }}">
                Payments <span class="n">{{ $payments->count() }}</span>
            </button>
            <button wire:click="$set('activeTab', 'documents')"
                    class="tab {{ $activeTab === 'documents' ? 'active' : '' }}">
                Documents <span class="n">{{ $documents->count() }}</span>
            </button>
            <button wire:click="$set('activeTab', 'followups')"
                    class="tab {{ $activeTab === 'followups' ? 'active' : '' }}">
                Follow-ups <span class="n">{{ $followups->count() }}</span>
            </button>
        </div>

        <div class="card-body">
            <!-- 1. OVERVIEW TAB -->
            @if($activeTab === 'overview')
                <div class="grid-3" style="gap:14px">
                    <div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Contact person</div>
                            <div class="v font-semibold">{{ $customer->contact_name ?: '—' }}</div>
                        </div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Phone</div>
                            <div class="v font-semibold">{{ $customer->phone ?: '—' }} @if($customer->alt_phone)
                                    / {{ $customer->alt_phone }}
                                @endif</div>
                        </div>
                        <div class="field">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Email</div>
                            <div class="v font-semibold">{{ $customer->email ?: '—' }}</div>
                        </div>
                    </div>
                    <div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">City / State</div>
                            <div
                                class="v font-semibold">{{ collect([$customer->city, $customer->state])->filter()->join(', ') ?: '—' }}</div>
                        </div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">GSTIN</div>
                            <div class="v font-semibold">{{ $customer->gst_number ?: '—' }}</div>
                        </div>
                        <div class="field">
                            <div class="k text-xs font-bold text-gray-400 uppercase">PAN</div>
                            <div class="v font-semibold">{{ $customer->pan_number ?: '—' }}</div>
                        </div>
                    </div>
                    <div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Source</div>
                            <div class="v font-semibold">{{ $customer->source ?: '—' }}</div>
                        </div>
                        <div class="field mb-3">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Industry</div>
                            <div class="v font-semibold">{{ $customer->industry ?: '—' }}</div>
                        </div>
                        <div class="field">
                            <div class="k text-xs font-bold text-gray-400 uppercase">Onboarded by</div>
                            <div class="v font-semibold">{{ $customer->salesPerson?->name ?: '—' }}</div>
                        </div>
                    </div>
                </div>

                @if($customer->address)
                    <div class="divider"></div>
                    <div class="field">
                        <div class="k text-xs font-bold text-gray-400 uppercase">Address</div>
                        <div class="v mt-0.5">{{ $customer->address }}</div>
                    </div>
                @endif

                @if($customer->billing_name || $customer->billing_address)
                    <div class="divider"></div>
                    <div class="field">
                        <div class="k text-xs font-bold text-gray-400 uppercase">Billing</div>
                        <div class="v mt-0.5">{{ $customer->billing_name }}<br>{{ $customer->billing_address }}</div>
                    </div>
                @endif

                @if($customer->internal_notes)
                    <div class="divider"></div>
                    <div
                        class="notice warn flex items-start gap-2.5 p-3 rounded-xl bg-amber-50 text-amber-900 border border-amber-200">
                        <svg viewBox="0 0 24 24" class="w-4 h-4 text-amber-600 flex-shrink-0 mt-0.5">
                            <rect x="4" y="10" width="16" height="11" rx="2"/>
                            <path d="M8 10V7a4 4 0 018 0v3"/>
                        </svg>
                        <div class="text-xs">
                            <b>Internal notes</b> (not visible to customer)<br>
                            {{ $customer->internal_notes }}
                        </div>
                    </div>
                @endif

                <!-- 2. PROJECTS TAB -->
            @elseif($activeTab === 'projects')
                @if($projects->isEmpty())
                    <div class="empty">
                         <span class="flex items-center justify-center">
                            <svg viewBox="0 0 24 24">
                                <path d="M3 7a2 2 0 012-2h4l2 2h8a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V7z"/>
                            </svg>
                         </span>
                        <p>No projects yet</p>
                        <div class="sm">Click New Project to get started</div>
                    </div>
                @else
                    <div class="grid-3">
                        @foreach($projects as $p)
                            @php
                                $doneMs = $p->milestones->where('done', true)->count();
                                $totMs = $p->milestones->count();
                            @endphp
                            <div class="card clickable p-4"
                                 onclick="window.location='{{ route('projects.show', $p) }}'">
                                <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:10px">
                                    <div style="flex:1">
                                        <div class="muted"
                                             style="font-size:10.8px;font-weight:750;letter-spacing:.5px">{{ $p->code }}</div>
                                        <div
                                            style="font-size:14.6px;font-weight:660;margin-top:3px">{{ $p->name }}</div>
                                    </div>
                                    <x-health-badge :health="$p->health"/>
                                </div>
                                <div class="money-grid" style="grid-template-columns:1fr 1fr;gap:9px">
                                    <div class="money-cell" style="padding:9px 11px">
                                        <div class="k">Value</div>
                                        <div class="v" style="font-size:14.5px">
                                            <x-money :value="$p->money['scheduled']"/>
                                        </div>
                                    </div>
                                    <div class="money-cell due" style="padding:9px 11px">
                                        <div class="k">Due</div>
                                        <div class="v text-[#e0a13a]" style="font-size:14.5px">
                                            <x-money :value="$p->money['due']"/>
                                        </div>
                                    </div>
                                </div>
                                <div style="margin-top:11px">
                                    <div class="progress-row">
                                        <span>Milestones</span>
                                        <b>{{ $doneMs }}/{{ $totMs }}</b>
                                    </div>
                                    <div class="bar {{ $totMs && $doneMs === $totMs ? 'green' : '' }}"
                                         style="height:6px">
                                        <i style="width:{{ $totMs > 0 ? round(($doneMs / $totMs) * 100) : 0 }}%"></i>
                                    </div>
                                </div>
                                <div style="display:flex;gap:10px;margin-top:10px;font-size:11.4px;color:var(--text2)">
                                    <span>Due {{ $p->delivery_date?->format('d M') ?? '—' }}</span>
                                    <span>·</span>
                                    <span>{{ $p->status }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- 3. PAYMENTS TAB -->
            @elseif($activeTab === 'payments')
                <div class="card-head" style="padding:0 0 12px;border:none">
                    <div>
                        <h3>Payment schedule</h3>
                        <div class="sub">{{ $terms->count() }} installments</div>
                    </div>
                </div>
                @forelse($terms as $t)
                    @php $bal = max(0, $t->amount - $t->received - $t->tds); @endphp
                    <div class="term {{ $t->status === 'Paid' ? 'paid' : ($t->status === 'Overdue' ? 'over' : '') }}">
                        <div class="term-no">{{ $t->seq }}</div>
                        <div class="term-body">
                            <div class="term-title">{{ $t->project?->code }} — {{ $t->label }}</div>
                            <div class="term-meta">
                                <span>{{ ucfirst($t->trigger_type) }}</span>
                                <span>Due {{ $t->due_date?->format('d M') ?? '—' }}</span>
                                <span
                                    class="badge {{ $t->status === 'Paid' ? 'b-green' : ($t->status === 'Overdue' ? 'b-red' : 'b-amber') }}">{{ $t->status }}</span>
                            </div>
                        </div>
                        <div class="term-amt">
                            <div class="a">
                                <x-money :value="$t->amount"/>
                            </div>
                            <div class="b text-xs">{{ $bal > 0 ? 'Remaining' : 'Full Paid' }}</div>
                        </div>
                    </div>
                @empty
                    <div class="empty">
                        <p>No installments scheduled yet</p>
                    </div>
                @endforelse

                <div class="divider"></div>

                <div class="card-head" style="padding:0 0 12px;border:none">
                    <div>
                        <h3>Receipts</h3>
                        <div class="sub">{{ $payments->count() }} {{ \Illuminate\Support\Str::plural('payment', $payments->count()) }} ·
                            <x-money :value="$payments->sum('amount')"/>
                        </div>
                    </div>
                </div>
                @if($payments->isNotEmpty())
                    <div class="table-wrap">
                        <table>
                            <thead>
                            <tr>
                                <th>Receipt</th>
                                <th>Date</th>
                                <th>Project</th>
                                <th class="num">Amount</th>
                                <th>Mode</th>
                                <th>Reference</th>
                                <th></th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($payments as $pay)
                                <tr>
                                    <td class="strong">{{ $pay->code }}</td>
                                    <td class="nowrap">{{ $pay->paid_on->format('d M Y') }}</td>
                                    <td class="muted">{{ $pay->project?->code }}</td>
                                    <td class="num font-bold text-[#5aa236]">
                                        <x-money :value="$pay->amount"/>
                                    </td>
                                    <td><span class="pill">{{ $pay->mode }}</span></td>
                                    <td class="muted"
                                        style="font-size:11.4px">{{ $pay->reference ?: '—' }} @if($pay->bank)
                                            · {{ $pay->bank }}
                                        @endif</td>
                                    <td>
                                        <div class="row-actions">
                                            <button type="button" class="mini-btn danger"
                                                    wire:confirm="Remove this receipt entry?"
                                                    wire:click="deletePayment({{ $pay->id }})"
                                                    title="Delete payment" aria-label="Delete payment">
                                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                                    <path
                                                        d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty">
                        <p>No receipts recorded yet</p>
                    </div>
                @endif

                <!-- 4. DOCUMENTS TAB -->
            @elseif($activeTab === 'documents')
                <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
                    <button type="button" class="btn btn-primary btn-sm"
                            @click="Livewire.dispatch('open-document-upload-modal', { customerId: {{ $customer->id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 19V5M5 12l7-7 7 7"/>
                        </svg>
                        Upload document
                    </button>
                </div>
                @forelse($documents as $doc)
                    <div class="doc flex items-center justify-between p-3 border-b border-[#eef2f9]">
                        <span class="doc-ico">{{ strtoupper(pathinfo($doc->file_name, PATHINFO_EXTENSION)) }}</span>
                        <div style="flex:1;min-width:0;margin-left:10px">
                            <div class="doc-n font-bold text-sm">{{ $doc->title }}</div>
                            <div class="doc-m text-xs text-[#8894ac]">{{ $doc->file_name }} · {{ $doc->size_kb }} KB
                                · {{ $doc->created_at->format('d M H:i') }}</div>
                        </div>
                        <span class="pill mr-2">{{ $doc->category }}</span>
                        @php
                            $fileUrl = asset('storage/' . $doc->stored_as);
                        @endphp
                        <button type="button"
                                class="mini-btn mr-1"
                                @click="Livewire.dispatch('open-document-preview', {
                                    data: {
                                        title: '{{ addslashes($doc->title) }}',
                                        name: '{{ addslashes($doc->file_name) }}',
                                        sizeKb: '{{ $doc->size_kb }}',
                                        category: '{{ $doc->category }}',
                                        url: '{{ $fileUrl }}',
                                        mime: '{{ $doc->mime }}'
                                    }
                                })"
                                title="Preview">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                <circle cx="12" cy="12" r="3"/>
                            </svg>
                        </button>
                        <a href="{{ $fileUrl }}" target="_blank" download
                           class="mini-btn mr-1" title="Download">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4M7 10l5 5 5-5M12 15V3"/>
                            </svg>
                        </a>
                        <button type="button" class="mini-btn danger" wire:confirm="Remove this document?"
                                wire:click="deleteDocument({{ $doc->id }})" title="Remove">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path
                                    d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            </svg>
                        </button>
                    </div>
                @empty
                    <div class="empty">
                         <span class="flex items-center justify-center">
                            <svg viewBox="0 0 24 24">
                                <path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/>
                            </svg>
                         </span>
                        <p>No documents yet</p>
                        <div class="sm">Upload a contract, KYC or invoice</div>
                    </div>
                @endforelse

                <!-- 5. FOLLOWUPS TAB -->
            @elseif($activeTab === 'followups')
                <div style="display:flex;justify-content:flex-end;margin-bottom:12px">
                    <button type="button" class="btn btn-primary btn-sm"
                            @click="Livewire.dispatch('open-followup-modal', { customerId: {{ $customer->id }} })">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        Log Follow-up
                    </button>
                </div>
                @forelse($followups as $fu)
                    <div class="fu flex items-start gap-3 p-3 border-b border-[#eef2f9]">
                        <span class="fu-ico">{{ substr($fu->type, 0, 1) }}</span>
                        <div style="flex:1;min-width:0">
                            <div class="fu-s font-bold text-sm">{{ $fu->summary }}</div>
                            @if($fu->detail)
                                <div class="fu-t text-xs text-gray-500 mt-0.5">{{ $fu->detail }}</div>
                            @endif
                            <div class="fu-t text-xs text-[#8894ac] mt-1">{{ $fu->type }}
                                · {{ $fu->happened_on->format('d M H:i') }}</div>
                            @if($fu->next_date)
                                <div class="mt-1">
                                    <span
                                        class="badge b-navy text-xs">Next: {{ $fu->next_date->format('d M Y') }} @if($fu->next_note)
                                            — {{ $fu->next_note }}
                                        @endif</span>
                                </div>
                            @endif
                        </div>
                        <button type="button" class="mini-btn danger" wire:confirm="Remove this follow-up?"
                                wire:click="deleteFollowup({{ $fu->id }})">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path
                                    d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                            </svg>
                        </button>
                    </div>
                @empty
                    <div class="empty">
                         <span class="flex items-center justify-center">
                            <svg viewBox="0 0 24 24">
                                <path d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z"/>
                            </svg>
                         </span>
                        <p>No follow-ups yet</p>
                        <div class="sm">Log your first conversation</div>
                    </div>
                @endforelse
            @endif
        </div>
    </div>

    <!-- Modals -->
    <livewire:documents.document-upload-modal/>
</div>
