<div>
    <div class="page-head">
        <span class="info">
            @if(auth()->user()->role === 'Sales')
                {{ $customers->total() }} customer {{ \Illuminate\Support\Str::plural('account', $customers->total()) }} acquired
            @else
                {{ $customers->total() }} customer{{ $customers->total() === 1 ? '' : 's' }} · total due <b><x-money
                        :value="$totalDue"/></b>
            @endif
        </span>
        <span class="spacer"></span>
        {{-- [Temporarily hidden - Export & Import buttons]
        <button type="button" class="btn btn-ghost btn-sm" wire:click="exportCsv">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <path d="M7 10l5 5 5-5"/>
                <path d="M12 15V3"/>
            </svg>
            Export CSV
        </button>
        <button type="button" class="btn btn-ghost btn-sm" @click="$dispatch('open-modal', 'customer-import-modal')">
            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                <path d="M21 15v4a2 2 0 01-2 2H5a2 2 0 01-2-2v-4"/>
                <path d="M7 10l5-5 5 5"/>
                <path d="M12 5v12"/>
            </svg>
            Bulk Import
        </button>
        --}}
        @if(auth()->user()->hasPermission('customers', 'add'))
            <button type="button" class="btn btn-primary btn-sm"
                    @click="Livewire.dispatch('open-customer-modal', { id: null })">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M12 5v14M5 12h14"/>
                </svg>
                New Customer
            </button>
        @endif
    </div>

    <!-- Filterbar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="filterbar">
                <div class="gsearch" style="width:240px">
                    <svg viewBox="0 0 24 24">
                        <circle cx="11" cy="11" r="7"/>
                        <path d="M21 21l-4.3-4.3"/>
                    </svg>
                    <input wire:model.live.debounce.300ms="search" placeholder="Company, contact, phone, GST...">
                </div>

                <select wire:model.live="sales" class="i" style="width:auto">
                    <option value="">All salespeople</option>
                    @foreach($salesPeople as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                    @endforeach
                </select>

                <select wire:model.live="state" class="i" style="width:auto">
                    <option value="">All states</option>
                    @foreach($states as $code => $name)
                        <option value="{{ $code }}">{{ $name }}</option>
                    @endforeach
                </select>

                @if(auth()->user()->hasPermission('payments', 'view'))
                    <select wire:model.live="pay" class="i" style="width:auto">
                        <option value="">Any payment status</option>
                        <option value="due">Payment pending</option>
                        <option value="over">Overdue</option>
                        <option value="clear">Fully paid</option>
                    </select>
                @endif

                <select wire:model.live="status" class="i" style="width:auto">
                    <option value="">Any status</option>
                    <option value="Active">Active</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Completed">Completed</option>
                    <option value="Churned">Churned</option>
                </select>

                @if($search !== '' || $sales !== '' || $state !== '' || $pay !== '' || $status !== '')
                    <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearFilters">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M18 6L6 18M6 6l12 12"/>
                        </svg>
                        Clear
                    </button>
                @endif
            </div>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card">
        <div class="table-wrap">
            <table>
                <thead>
                <tr>
                    <th>Customer</th>
                    <th>Onboarded By</th>
                    <th>Projects</th>
                    @if(auth()->user()->hasPermission('payments', 'view'))
                        <th class="num">Value</th>
                        <th class="num">Received</th>
                        <th class="num">Due</th>
                        <th>Payment</th>
                    @endif
                    <th>Status</th>
                    <th></th>
                </tr>
                </thead>
                <tbody>
                @forelse($customers as $customer)
                    @php
                        $scheduledAmount = (float) $customer->projects->sum(fn ($proj) => $proj->money['scheduled']);
                        $receivedAmount = (float) $customer->projects->sum(fn ($proj) => $proj->money['received']);
                        $dueAmount = (float) $customer->projects->sum(fn ($proj) => $proj->money['due']);
                        $overdueAmount = (float) $customer->projects->sum(fn ($proj) => $proj->money['overdue']);
                        $collectionRate = $scheduledAmount > 0 ? min(100, round(($receivedAmount / $scheduledAmount) * 100)) : 0;
                        $paymentClass = $overdueAmount > 0 ? 'b-red' : ($dueAmount > 0 ? 'b-amber' : ($scheduledAmount > 0 ? 'b-green' : 'b-grey'));
                        $paymentLabel = $overdueAmount > 0 ? 'Overdue' : ($dueAmount > 0 ? 'Pending' : ($scheduledAmount > 0 ? 'Cleared' : '—'));
                    @endphp
                    <tr class="clickable" onclick="window.location='{{ route('customers.show', $customer) }}'">
                        <td>
                            <div
                                class="strong">{{ $customer->display_name }}</div>
                            <div class="muted" style="font-size:10.9px">
                                {{ $customer->code }}
                                @if($customer->company && $customer->contact_name)
                                    · {{ $customer->contact_name }}
                                @endif
                                @if($customer->city)
                                    · {{ $customer->city }}
                                @endif
                            </div>
                        </td>
                        <td>
                            @if($customer->salesPerson)
                                <span class="pill">{{ $customer->salesPerson->name }}</span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td>{{ $customer->projects->count() }}</td>
                        @if(auth()->user()->hasPermission('payments', 'view'))
                            <td class="num">
                                <x-money :value="$scheduledAmount"/>
                            </td>
                            <td class="num" style="color:#3d7a24">
                                <x-money :value="$receivedAmount"/>
                            </td>
                            <td class="num font-bold" style="color:{{ $dueAmount > 0 ? '#96660f' : 'inherit' }}">
                                <x-money :value="$dueAmount"/>
                            </td>
                            <td style="min-width:120px">
                                <div
                                    style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px">
                                    <span class="badge dot {{ $paymentClass }}">{{ $paymentLabel }}</span>
                                    <span class="text-xs text-muted font-semibold"
                                          style="font-size:11px;color:var(--muted);font-weight:600">{{ $collectionRate }}%</span>
                                </div>
                                <div class="bar" style="height:5px">
                                    <i style="width:{{ $collectionRate }}%"></i>
                                </div>
                            </td>
                        @endif
                        <td>
                            <span
                                class="badge {{ status_badge_class($customer->status) }}">
                                {{ $customer->status }}
                            </span>
                            @if($customer->isPendingApproval())
                                <div class="mt-1">
                                    <span class="badge bg-amber-50 text-amber-700 border border-amber-300 text-[10px] leading-tight">Pending Approval</span>
                                </div>
                            @elseif($customer->isRejected())
                                <div class="mt-1">
                                    <span class="badge bg-rose-50 text-rose-700 border border-rose-300 text-[10px] leading-tight">Rejected</span>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="row-actions flex items-center justify-end gap-1.5">
                                @if($customer->canBeApprovedBy(auth()->user()))
                                    <button type="button" class="btn btn-xs btn-outline-success"
                                            title="Approve Customer"
                                            @click.stop="$dispatch('confirm-dialog', {
                                                title: 'Approve Customer?',
                                                message: 'Are you sure you want to approve customer \'{{ addslashes($customer->name) }}\'?',
                                                confirmText: 'Approve',
                                                cancelText: 'Cancel',
                                                type: 'success',
                                                wire: $wire,
                                                action: 'approveCustomer',
                                                params: [{{ $customer->id }}]
                                            })">
                                        <svg viewBox="0 0 24 24" fill="none"
                                             xmlns="http://www.w3.org/2000/svg">
                                            <path
                                                d="M20.9751 12.1852L20.2361 12.0574L20.9751 12.1852ZM20.2696 16.265L19.5306 16.1371L20.2696 16.265ZM6.93776 20.4771L6.19055 20.5417H6.19055L6.93776 20.4771ZM6.1256 11.0844L6.87281 11.0198L6.1256 11.0844ZM13.9949 5.22142L14.7351 5.34269V5.34269L13.9949 5.22142ZM13.3323 9.26598L14.0724 9.38725V9.38725L13.3323 9.26598ZM6.69813 9.67749L6.20854 9.10933H6.20854L6.69813 9.67749ZM8.13687 8.43769L8.62646 9.00585H8.62646L8.13687 8.43769ZM10.518 4.78374L9.79207 4.59542L10.518 4.78374ZM10.9938 2.94989L11.7197 3.13821L11.7197 3.13821L10.9938 2.94989ZM12.6676 2.06435L12.4382 2.77841L12.4382 2.77841L12.6676 2.06435ZM12.8126 2.11093L13.0419 1.39687L13.0419 1.39687L12.8126 2.11093ZM9.86194 6.46262L10.5235 6.81599V6.81599L9.86194 6.46262ZM13.9047 3.24752L13.1787 3.43584V3.43584L13.9047 3.24752ZM11.6742 2.13239L11.3486 1.45675L11.3486 1.45675L11.6742 2.13239ZM20.2361 12.0574L19.5306 16.1371L21.0086 16.3928L21.7142 12.313L20.2361 12.0574ZM13.245 21.25H8.59634V22.75H13.245V21.25ZM7.68497 20.4125L6.87281 11.0198L5.37839 11.149L6.19055 20.5417L7.68497 20.4125ZM19.5306 16.1371C19.0238 19.0677 16.3813 21.25 13.245 21.25V22.75C17.0712 22.75 20.3708 20.081 21.0086 16.3928L19.5306 16.1371ZM13.2548 5.10015L12.5921 9.14472L14.0724 9.38725L14.7351 5.34269L13.2548 5.10015ZM7.18772 10.2456L8.62646 9.00585L7.64728 7.86954L6.20854 9.10933L7.18772 10.2456ZM11.244 4.97206L11.7197 3.13821L10.2678 2.76157L9.79207 4.59542L11.244 4.97206ZM12.4382 2.77841L12.5832 2.82498L13.0419 1.39687L12.897 1.3503L12.4382 2.77841ZM10.5235 6.81599C10.8354 6.23198 11.0777 5.61339 11.244 4.97206L9.79207 4.59542C9.65572 5.12107 9.45698 5.62893 9.20041 6.10924L10.5235 6.81599ZM12.5832 2.82498C12.8896 2.92342 13.1072 3.16009 13.1787 3.43584L14.6306 3.05921C14.4252 2.26719 13.819 1.64648 13.0419 1.39687L12.5832 2.82498ZM11.7197 3.13821C11.7547 3.0032 11.8522 2.87913 11.9998 2.80804L11.3486 1.45675C10.8166 1.71309 10.417 2.18627 10.2678 2.76157L11.7197 3.13821ZM11.9998 2.80804C12.1345 2.74311 12.2931 2.73181 12.4382 2.77841L12.897 1.3503C12.3872 1.18655 11.8312 1.2242 11.3486 1.45675L11.9998 2.80804ZM14.1537 10.9842H19.3348V9.4842H14.1537V10.9842ZM14.7351 5.34269C14.8596 4.58256 14.824 3.80477 14.6306 3.0592L13.1787 3.43584C13.3197 3.97923 13.3456 4.54613 13.2548 5.10016L14.7351 5.34269ZM8.59634 21.25C8.12243 21.25 7.726 20.887 7.68497 20.4125L6.19055 20.5417C6.29851 21.7902 7.34269 22.75 8.59634 22.75V21.25ZM8.62646 9.00585C9.30632 8.42 10.0391 7.72267 10.5235 6.81599L9.20041 6.10924C8.85403 6.75767 8.30249 7.30493 7.64728 7.86954L8.62646 9.00585ZM21.7142 12.313C21.9695 10.8365 20.8341 9.4842 19.3348 9.4842V10.9842C19.9014 10.9842 20.3332 11.4959 20.2361 12.0574L21.7142 12.313ZM12.5921 9.14471C12.4344 10.1076 13.1766 10.9842 14.1537 10.9842V9.4842C14.1038 9.4842 14.0639 9.43901 14.0724 9.38725L12.5921 9.14471ZM6.87281 11.0198C6.84739 10.7258 6.96474 10.4378 7.18772 10.2456L6.20854 9.10933C5.62021 9.61631 5.31148 10.3753 5.37839 11.149L6.87281 11.0198Z"
                                                fill="currentColor"/>
                                            <path opacity="0.5"
                                                  d="M3.9716 21.4709L3.22439 21.5355L3.9716 21.4709ZM3 10.2344L3.74721 10.1698C3.71261 9.76962 3.36893 9.46776 2.96767 9.48507C2.5664 9.50239 2.25 9.83274 2.25 10.2344L3 10.2344ZM4.71881 21.4063L3.74721 10.1698L2.25279 10.299L3.22439 21.5355L4.71881 21.4063ZM3.75 21.5129V10.2344H2.25V21.5129H3.75ZM3.22439 21.5355C3.2112 21.383 3.33146 21.2502 3.48671 21.2502V22.7502C4.21268 22.7502 4.78122 22.1281 4.71881 21.4063L3.22439 21.5355ZM3.48671 21.2502C3.63292 21.2502 3.75 21.3686 3.75 21.5129H2.25C2.25 22.1954 2.80289 22.7502 3.48671 22.7502V21.2502Z"
                                                  fill="currentColor"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-danger"
                                            title="Reject Customer"
                                            @click.stop="$dispatch('confirm-dialog', {
                                                title: 'Reject Customer?',
                                                message: 'Please provide a reason for rejecting customer \'{{ addslashes($customer->name) }}\':',
                                                confirmText: 'Reject',
                                                cancelText: 'Cancel',
                                                type: 'danger',
                                                hasInput: true,
                                                inputPlaceholder: 'Enter rejection reason (optional)...',
                                                onConfirm: (reason) => {
                                                    $wire.rejectCustomer({{ $customer->id }}, reason);
                                                }
                                            })">
                                        <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                            <path d="M20.9751 11.8148L20.2361 11.9426L20.9751 11.8148ZM20.2696 7.73505L19.5306 7.86285L20.2696 7.73505ZM6.93776 3.52293L6.19055 3.45832H6.19055L6.93776 3.52293ZM6.1256 12.9156L6.87281 12.9802L6.1256 12.9156ZM13.9949 18.7786L14.7351 18.6573V18.6573L13.9949 18.7786ZM13.3323 14.734L14.0724 14.6128V14.6128L13.3323 14.734ZM6.69813 14.3225L6.20854 14.8907H6.20854L6.69813 14.3225ZM8.13687 15.5623L8.62646 14.9942H8.62646L8.13687 15.5623ZM10.518 19.2163L9.79207 19.4046L10.518 19.2163ZM10.9938 21.0501L11.7197 20.8618L11.7197 20.8618L10.9938 21.0501ZM12.6676 21.9356L12.4382 21.2216L12.4382 21.2216L12.6676 21.9356ZM12.8126 21.8891L13.0419 22.6031L13.0419 22.6031L12.8126 21.8891ZM9.86194 17.5374L10.5235 17.184V17.184L9.86194 17.5374ZM13.9047 20.7525L13.1787 20.5642V20.5642L13.9047 20.7525ZM11.6742 21.8676L11.3486 22.5433L11.3486 22.5433L11.6742 21.8676ZM20.2361 11.9426L19.5306 7.86285L21.0086 7.60724L21.7142 11.687L20.2361 11.9426ZM13.245 2.75H8.59634V1.25H13.245V2.75ZM7.68497 3.58754L6.87281 12.9802L5.37839 12.851L6.19055 3.45832L7.68497 3.58754ZM19.5306 7.86285C19.0238 4.93226 16.3813 2.75 13.245 2.75V1.25C17.0712 1.25 20.3708 3.91895 21.0086 7.60724L19.5306 7.86285ZM13.2548 18.8998L12.5921 14.8553L14.0724 14.6128L14.7351 18.6573L13.2548 18.8998ZM7.18772 13.7544L8.62646 14.9942L7.64728 16.1305L6.20854 14.8907L7.18772 13.7544ZM11.244 19.0279L11.7197 20.8618L10.2678 21.2384L9.79207 19.4046L11.244 19.0279ZM12.4382 21.2216L12.5832 21.175L13.0419 22.6031L12.897 22.6497L12.4382 21.2216ZM10.5235 17.184C10.8354 17.768 11.0777 18.3866 11.244 19.0279L9.79207 19.4046C9.65572 18.8789 9.45698 18.3711 9.20041 17.8908L10.5235 17.184ZM12.5832 21.175C12.8896 21.0766 13.1072 20.8399 13.1787 20.5642L14.6306 20.9408C14.4252 21.7328 13.819 22.3535 13.0419 22.6031L12.5832 21.175ZM11.7197 20.8618C11.7547 20.9968 11.8522 21.1209 11.9998 21.192L11.3486 22.5433C10.8166 22.2869 10.417 21.8137 10.2678 21.2384L11.7197 20.8618ZM11.9998 21.192C12.1345 21.2569 12.2931 21.2682 12.4382 21.2216L12.897 22.6497C12.3872 22.8135 11.8312 22.7758 11.3486 22.5433L11.9998 21.192ZM14.1537 13.0158H19.3348V14.5158H14.1537V13.0158ZM14.7351 18.6573C14.8596 19.4174 14.824 20.1952 14.6306 20.9408L13.1787 20.5642C13.3197 20.0208 13.3456 19.4539 13.2548 18.8998L14.7351 18.6573ZM8.59634 2.75C8.12243 2.75 7.726 3.11302 7.68497 3.58754L6.19055 3.45832C6.29851 2.20975 7.34269 1.25 8.59634 1.25V2.75ZM8.62646 14.9942C9.30632 15.58 10.0391 16.2773 10.5235 17.184L9.20041 17.8908C8.85403 17.2423 8.30249 16.6951 7.64728 16.1305L8.62646 14.9942ZM21.7142 11.687C21.9695 13.1635 20.8341 14.5158 19.3348 14.5158V13.0158C19.9014 13.0158 20.3332 12.5041 20.2361 11.9426L21.7142 11.687ZM12.5921 14.8553C12.4344 13.8924 13.1766 13.0158 14.1537 13.0158V14.5158C14.1038 14.5158 14.0639 14.561 14.0724 14.6128L12.5921 14.8553ZM6.87281 12.9802C6.84739 13.2742 6.96474 13.5622 7.18772 13.7544L6.20854 14.8907C5.62021 14.3837 5.31148 13.6247 5.37839 12.851L6.87281 12.9802Z" fill="currentColor"/>
                                            <path opacity="0.5" d="M3.9716 2.52911L3.22439 2.4645L3.9716 2.52911ZM3 13.7656L3.74721 13.8302C3.71261 14.2304 3.36893 14.5322 2.96767 14.5149C2.5664 14.4976 2.25 14.1673 2.25 13.7656L3 13.7656ZM4.71881 2.59372L3.74721 13.8302L2.25279 13.701L3.22439 2.4645L4.71881 2.59372ZM3.75 2.48709V13.7656H2.25V2.48709H3.75ZM3.22439 2.4645C3.2112 2.61704 3.33146 2.74983 3.48671 2.74983V1.24983C4.21268 1.24983 4.78122 1.87192 4.71881 2.59372L3.22439 2.4645ZM3.48671 2.74983C3.63292 2.74983 3.75 2.63139 3.75 2.48709H2.25C2.25 1.80457 2.80289 1.24983 3.48671 1.24983V2.74983Z" fill="currentColor"/>
                                        </svg>
                                    </button>
                                @endif
                                <a href="{{ route('customers.show', $customer) }}" wire:navigate
                                   class="btn btn-outline-primary btn-xs"
                                   title="View Customer Profile">
                                    <svg viewBox="0 0 24 24" class="w-3 h-3">
                                        <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z"/>
                                        <circle cx="12" cy="12" r="3"/>
                                    </svg>
                                </a>
                                @if(auth()->user()->hasPermission('customers', 'edit'))
                                    <button type="button" class="btn btn-outline-secondary btn-xs" title="Edit Customer"
                                            onclick="event.stopPropagation();"
                                            @click="Livewire.dispatch('open-customer-modal', { id: {{ $customer->id }} })">
                                        <svg viewBox="0 0 24 24" class="w-3 h-3">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ auth()->user()->hasPermission('payments', 'view') ? 9 : 5 }}">
                            <div class="empty">
                                 <span class="flex items-center justify-center">
                                    <svg viewBox="0 0 24 24">
                                        <path d="M16 21v-2a4 4 0 00-4-4H6a4 4 0 00-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                    </svg>
                                 </span>
                                <p>No customers found</p>
                                <div class="sm">Add a customer or change the filters</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="p-4 border-t border-[#e2e8f3]">{{ $customers->links() }}</div>
    </div>

    {{-- [Temporarily hidden - Bulk Import Modal]
    <livewire:customers.customer-import-modal/>
    --}}
</div>
