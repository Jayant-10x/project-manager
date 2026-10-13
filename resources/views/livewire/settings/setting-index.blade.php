<div>
    <!-- 1. Change Password Card -->
    <div class="card mb-4">
        <div class="card-head">
            <div>
                <h3 class="font-bold text-sm text-[#0d1b34]">Change my password</h3>
                <div class="text-xs text-[#8894ac]">At least 8 characters, one capital letter, one number</div>
            </div>
        </div>
        <div class="card-body">
            <form wire:submit="updatePassword" novalidate>
                <div class="form-grid">
                    <label class="f">
                        <span>Current password *</span>
                        <input wire:model.blur="current_password"
                               type="password"
                               class="i @error('current_password') !border-red-500 !bg-red-50/20 @enderror"
                               placeholder="Current password">
                        @error('current_password')
                        <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="f">
                        <span>New password *</span>
                        <input wire:model.blur="new_password"
                               type="password"
                               class="i @error('new_password') !border-red-500 !bg-red-50/20 @enderror"
                               placeholder="New password">
                        @error('new_password')
                        <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="f full">
                        <span>Confirm new password *</span>
                        <input wire:model.blur="new_password_confirmation"
                               type="password"
                               class="i @error('new_password_confirmation') !border-red-500 !bg-red-50/20 @enderror"
                               placeholder="Repeat the new password">
                        @error('new_password_confirmation')
                        <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <div class="flex justify-end mt-4">
                    <button type="submit" class="btn btn-primary btn-sm">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <circle cx="8" cy="15" r="4"/>
                            <path d="M10.8 12.2L20 3M17 6l3 3M14 9l2 2"/>
                        </svg>
                        Update password
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2. Split Grid: Alert Settings & Profile vs Users -->
    <div class="grid-2e">
        <div>
            <!-- Operational Alert Settings (Admin Only) -->
            @if(auth()->user()->role === 'Admin')
                <div class="card mb-4">
                    <div class="card-head">
                        <div>
                            <h3 class="font-bold text-sm text-[#0d1b34]">Alert Settings</h3>
                            <div class="text-xs text-[#8894ac]">How many days before an alert</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form wire:submit="saveSettings" novalidate>
                            <div class="form-grid">
                                <label class="f">
                                    <span>Payment due alert (days)</span>
                                    <input wire:model.blur="paymentAlertDays"
                                           type="number" min="1" max="180"
                                           class="i @error('paymentAlertDays') !border-red-500 !bg-red-50/20 @enderror">
                                    @error('paymentAlertDays')
                                    <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="f">
                                    <span>AMC / renewal alert (days)</span>
                                    <input wire:model.blur="amcAlertDays"
                                           type="number" min="1" max="180"
                                           class="i @error('amcAlertDays') !border-red-500 !bg-red-50/20 @enderror">
                                    @error('amcAlertDays')
                                    <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="f">
                                    <span>Follow-up alert (days)</span>
                                    <input wire:model.blur="followupAlertDays"
                                           type="number" min="1" max="180"
                                           class="i @error('followupAlertDays') !border-red-500 !bg-red-50/20 @enderror">
                                    @error('followupAlertDays')
                                    <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="f">
                                    <span>Company name</span>
                                    <input wire:model.blur="companyName"
                                           class="i @error('companyName') !border-red-500 !bg-red-50/20 @enderror">
                                    @error('companyName')
                                    <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="f full">
                                    <span>Display currency</span>
                                    <select wire:model.blur="displayCurrency"
                                            class="i @error('displayCurrency') !border-red-500 !bg-red-50/20 @enderror">
                                        <option value="INR">INR (₹)</option>
                                        <option value="USD">USD ($)</option>
                                    </select>
                                    @error('displayCurrency')
                                    <span class="text-xs text-red-500 mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                    <div class="hint text-xs text-[#8894ac] mt-1">
                                        Amounts are stored in INR. USD is only a display conversion.
                                    </div>
                                </label>
                            </div>

                            <div class="flex justify-end mt-4">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                    Save settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif

            <!-- My Profile Card -->
            <div class="card">
                <div class="card-head">
                    <div>
                        <h3 class="font-bold text-sm text-[#0d1b34]">My Profile</h3>
                        <div class="text-xs text-[#8894ac]">How you appear in the workspace</div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="avatar lg" style="background:#0b3d91">
                            {{ strtoupper(substr($me->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="font-bold text-base text-[#0d1b34]">{{ $me->name }}</div>
                            <div class="text-xs text-[#8894ac]">{{ $me->designation ?? $me->role }}</div>
                            <div class="mt-1">
                                <span class="badge {{ $me->role === 'Admin' ? 'b-navy' : 'b-green' }}">
                                    {{ $me->role }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="field mb-4">
                        <div class="text-xs font-bold text-gray-400 uppercase">Email</div>
                        <div class="font-semibold text-sm text-[#0d1b34]">{{ $me->email }}</div>
                    </div>

                    <button type="button" wire:click="logout" class="btn btn-danger btn-sm">
                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                            <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
                        </svg>
                        Sign out
                    </button>
                </div>
            </div>
        </div>

        <!-- 3. User Accounts Management List (Right Column) -->
        <div class="card">
            <div class="card-head">
                <div>
                    <h3 class="font-bold text-sm text-[#0d1b34]">Users</h3>
                    <div class="text-xs text-[#8894ac]">{{ $users->count() }} accounts</div>
                </div>
                <div class="right">
                    @if(auth()->user()->role === 'Admin')
                        <button type="button" class="btn btn-primary btn-sm"
                                @click="Livewire.dispatch('open-user-modal', { id: null })">
                            <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                <path d="M12 5v14M5 12h14"/>
                            </svg>
                            Add user
                        </button>
                    @endif
                </div>
            </div>
            <div class="card-body p-0">
                @foreach($users as $u)
                    <div
                        class="mini-item flex items-center gap-3 p-3.5 border-b last:border-b-0 border-[#eef2f9] hover:bg-slate-50 transition">
                        <div class="avatar" style="background: {{ $u->role === 'Admin' ? '#0b3d91' : '#5aa236' }}">
                            {{ strtoupper(substr($u->name, 0, 2)) }}
                        </div>

                        <div style="flex:1;min-width:0">
                            <div class="t font-semibold text-sm text-[#0d1b34]">{{ $u->name }}</div>
                            <div class="s text-xs text-[#8894ac] mt-0.5">
                                {{ $u->email }} · {{ $u->designation ?? $u->role }}
                            </div>
                        </div>

                        <span class="badge {{ $u->status === 'Active' ? 'b-green' : 'b-grey' }} text-xs">
                            {{ $u->status }}
                        </span>

                        <span class="badge {{ $u->role === 'Admin' ? 'b-navy' : 'b-grey' }} text-xs">
                            {{ $u->role }}
                        </span>

                        @if(auth()->user()->role === 'Admin')
                            <div class="flex items-center gap-1.5 flex-shrink-0">
                                <button type="button" class="mini-btn"
                                        @click="Livewire.dispatch('open-user-modal', { id: {{ $u->id }} })"
                                        title="Edit user">
                                    <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                        <path d="M12 20h9"/>
                                        <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                    </svg>
                                </button>
                                @if($u->id !== auth()->id())
                                    <button type="button" class="mini-btn danger"
                                            wire:confirm="Remove this user login permanently?"
                                            wire:click="deleteUser({{ $u->id }})"
                                            title="Remove">
                                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                            <path
                                                d="M3 6h18M8 6V4a1 1 0 011-1h6a1 1 0 011 1v2M19 6l-1 14a2 2 0 01-2 2H8a2 2 0 01-2-2L5 6"/>
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <!-- User Modal Component -->
    <livewire:settings.user-modal/>
</div>
