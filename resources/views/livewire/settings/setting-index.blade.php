<div>
    <!-- My Profile Card -->
    <div class="card">
        <div class="card-head justify-between">
            <div>
                <h3 class="font-bold text-sm text-[#0d1b34]">My Profile</h3>
                <div class="text-xs text-[#8894ac]">How you appear in the workspace</div>
            </div>
            <button type="button"
                    @click="$dispatch('confirm-dialog', {
                        title: 'Sign Out?',
                        message: 'Are you sure you want to sign out of your workspace session?',
                        confirmText: 'Sign Out',
                        cancelText: 'Stay',
                        type: 'logout',
                        danger: true,
                        wire: $wire,
                        action: 'logout'
                    })"
                    class="btn btn-danger btn-sm">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
                Sign out
            </button>
        </div>
        <div class="card-body">
            <div class="grid-2e">
                <div class="flex items-center gap-3 mb-4 border-e border-black">
                    <div class="avatar lg" style="background:#0b3d91">
                        {{ strtoupper(substr($me->name, 0, 2)) }}
                    </div>
                    <div>
                        <div class="font-bold text-base text-[#0d1b34]">{{ $me->name }}</div>
                        <div class="font-semibold text-sm text-gray-600">{{ $me->email }}</div>
                        <div class="text-xs text-[#8894ac]">{{ $me->designation ?? $me->role }}</div>
                        <div class="mt-1">
                                <span class="badge {{ $me->role === 'Admin' ? 'b-navy' : 'b-green' }}">
                                    {{ $me->role }}
                                </span>
                        </div>
                    </div>
                </div>
                <!-- 1. Change Password Card -->
                <form wire:submit="updatePassword" novalidate>
                    <div class="form-grid">
                        <label class="f">
                            <span>Current password<span class="text-red-500"> *</span></span>
                            <input wire:model.blur="current_password"
                                   type="password"
                                   class="i @error('current_password') border-red-500! bg-red-50/20! @enderror"
                                   placeholder="Current password">
                            @error('current_password')
                            <span class="text-xs text-red-500! mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="f">
                            <span>New password<span class="text-red-500"> *</span></span>
                            <input wire:model.blur="new_password"
                                   type="password"
                                   class="i @error('new_password') border-red-500! bg-red-50/20! @enderror"
                                   placeholder="New password">
                            @error('new_password')
                            <span class="text-xs text-red-500! mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </label>

                        <label class="f">
                            <span>Confirm new password<span class="text-red-500"> *</span></span>
                            <input wire:model.blur="new_password_confirmation"
                                   type="password"
                                   class="i @error('new_password_confirmation') border-red-500! bg-red-50/20! @enderror"
                                   placeholder="Repeat the new password">
                            @error('new_password_confirmation')
                            <span class="text-xs text-red-500! mt-1 block font-medium">{{ $message }}</span>
                            @enderror
                        </label>

                        <div class="flex justify-end mt-4 pt-4 @error('new_password_confirmation') my-8 py-8 @enderror">
                            <button type="submit" class="btn btn-primary btn-sm">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                    <circle cx="8" cy="15" r="4"/>
                                    <path d="M10.8 12.2L20 3M17 6l3 3M14 9l2 2"/>
                                </svg>
                                Update password
                            </button>
                        </div>
                    </div>


                </form>
            </div>
        </div>
    </div>
    <!-- 2. Split Grid: Alert Settings & Profile vs Users -->
    <div class="grid-2e mt-3">
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
                                           class="i @error('paymentAlertDays') border-red-500! bg-red-50/20! @enderror">
                                    @error('paymentAlertDays')
                                    <span class="text-xs text-red-500! mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="f">
                                    <span>AMC / renewal alert (days)</span>
                                    <input wire:model.blur="amcAlertDays"
                                           type="number" min="1" max="180"
                                           class="i @error('amcAlertDays') border-red-500! bg-red-50/20! @enderror">
                                    @error('amcAlertDays')
                                    <span class="text-xs text-red-500! mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="f">
                                    <span>Follow-up alert (days)</span>
                                    <input wire:model.blur="followupAlertDays"
                                           type="number" min="1" max="180"
                                           class="i @error('followupAlertDays') border-red-500! bg-red-50/20! @enderror">
                                    @error('followupAlertDays')
                                    <span class="text-xs text-red-500! mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="f">
                                    <span>Company name</span>
                                    <input wire:model.blur="companyName"
                                           class="i @error('companyName') border-red-500! bg-red-50/20! @enderror">
                                    @error('companyName')
                                    <span class="text-xs text-red-500! mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="f full">
                                    <span>Display currency</span>
                                    <select wire:model.blur="displayCurrency"
                                            class="i @error('displayCurrency') border-red-500! bg-red-50/20! @enderror">
                                        <option value="INR">INR (₹)</option>
                                        <option value="USD">USD ($)</option>
                                    </select>
                                    @error('displayCurrency')
                                    <span class="text-xs text-red-500! mt-1 block font-medium">{{ $message }}</span>
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
        </div>

        <!-- 3. User Accounts Management List (Right Column) -->
        @if(auth()->user()->role !== 'Sales')
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
                    @foreach($users as $userAccount)
                        @php
                            $isHighlighted = ($highlight_user && (int)$highlight_user === $userAccount->id);
                            $roleBadge = match($userAccount->role) {
                                'Admin' => 'b-navy',
                                'Manager' => 'b-violet',
                                'Sales' => 'b-amber',
                                default => 'b-green',
                            };
                            $avatarBg = match($userAccount->role) {
                                'Admin' => '#0b3d91',
                                'Manager' => '#7a5af8',
                                'Sales' => '#e0a13a',
                                default => '#5aa236',
                            };
                        @endphp
                        <div id="user-row-{{ $userAccount->id }}"
                             x-data="{ highlighted: @js($isHighlighted) }"
                             x-init="if (highlighted) { $el.scrollIntoView({ behavior: 'smooth', block: 'center' }); }"
                             class="mini-item flex items-center gap-3 p-3.5 last:border-b-0 border-[#eef2f9] transition"
                             :style="highlighted ? 'background-color: #fef7ee;' : ''"
                             :class="{ 'hover:bg-[#fdf0dc]': highlighted, 'hover:bg-slate-50': !highlighted }">
                            <div class="relative flex-shrink-0">
                                <div class="avatar" style="background: {{ $avatarBg }}">
                                    {{ strtoupper(substr($userAccount->name, 0, 2)) }}
                                </div>
                                @if($userAccount->is_online)
                                    <span
                                        class="absolute bottom-0 right-0 w-2.5 h-2.5 bg-green-500 border-2 border-white rounded-full"
                                        title="Online now"></span>
                                @endif
                            </div>

                            <div style="flex:1;min-width:0">
                                <div class="t font-semibold text-sm text-[#0d1b34]">{{ $userAccount->name }}</div>
                                <div class="s text-xs text-[#8894ac] mt-0.5">
                                    {{ $userAccount->email }} · {{ $userAccount->designation ?? $userAccount->role }}
                                    @if($userAccount->reportingTo)
                                        · <span
                                            class="text-indigo-600 font-medium">Reports to {{ $userAccount->reportingTo->name }}</span>
                                    @endif
                                </div>
                            </div>

                            <span class="badge {{ $userAccount->status === 'Active' ? 'b-green' : 'b-grey' }} text-xs">
                                {{ $userAccount->status }}
                            </span>

                            <span class="badge {{ $roleBadge }} text-xs">
                                {{ $userAccount->role }}
                            </span>

                            @if(auth()->user()->role === 'Admin')
                                <div class="flex items-center gap-1.5 shrink-0">
                                    <button type="button"
                                            class="btn btn-sm gap-1.5 font-semibold transition"
                                            :class="highlighted ? 'btn-amber shadow-sm' : 'btn-amber-soft'"
                                            :style="highlighted ? 'box-shadow: 0 0 0 2px #fff, 0 0 0 4px #e0a13a;' : ''"
                                            @click="$dispatch('confirm-dialog', {
                                                title: 'Reset User Password?',
                                                message: 'Generate a secure temporary password for {{ addslashes($userAccount->name) }}? Any active lockout will be cleared and the user will be forced to change their password on next sign-in.',
                                                confirmText: 'Generate Password',
                                                cancelText: 'Cancel',
                                                type: 'warning',
                                                danger: false,
                                                wire: $wire,
                                                action: 'resetUserPassword',
                                                params: {{ $userAccount->id }}
                                            })"
                                            title="Reset Password" aria-label="Reset Password">
                                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 fill-none stroke-current stroke-2">
                                            <circle cx="8" cy="15" r="4"/>
                                            <path d="M10.8 12.2L20 3M17 6l3 3M14 9l2 2"/>
                                        </svg>
                                        <span>Reset Password</span>
                                    </button>
                                    <button type="button" class="mini-btn"
                                            @click="Livewire.dispatch('open-user-modal', { id: {{ $userAccount->id }} })"
                                            title="Edit user">
                                        <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/>
                                        </svg>
                                    </button>
                                    @if($userAccount->id !== auth()->id())
                                        <button type="button" class="mini-btn danger"
                                                @click="$dispatch('confirm-dialog', {
                                                    title: 'Remove User Account?',
                                                    message: 'Permanently remove this user login ({{ addslashes($userAccount->name) }})? They will immediately lose workspace access.',
                                                    confirmText: 'Remove User',
                                                    cancelText: 'Cancel',
                                                    danger: true,
                                                    wire: $wire,
                                                    action: 'deleteUser',
                                                    params: {{ $userAccount->id }}
                                                })"
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
        @endif
    </div>

    <!-- Temporary Credentials Share Modal -->
    <x-modal name="temp-credentials-modal" title="Temporary Password Generated" sub="Share these temporary credentials with the staff member" :wide="false">
        @if($tempCredentials)
            <div x-data="{
                copiedPass: false,
                copiedAll: false,
                copyPassword() {
                    navigator.clipboard.writeText('{{ $tempCredentials['password'] }}');
                    this.copiedPass = true;
                    setTimeout(() => this.copiedPass = false, 2000);
                },
                copyAll() {
                    navigator.clipboard.writeText(`{{ addslashes($tempCredentials['fullText']) }}`);
                    this.copiedAll = true;
                    setTimeout(() => this.copiedAll = false, 2000);
                }
            }">
                <!-- Staff Info Strip -->
                <div class="p-3 bg-slate-50 rounded-xl border border-[#e2e8f3] mb-4 text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-[#8894ac] font-medium">Staff Member:</span>
                        <span class="font-bold text-[#0d1b34]">{{ $tempCredentials['name'] }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-[#8894ac] font-medium">Email:</span>
                        <span class="font-semibold text-indigo-600">{{ $tempCredentials['email'] }}</span>
                    </div>
                    @if(!empty($tempCredentials['phone']))
                        <div class="flex justify-between">
                            <span class="text-[#8894ac] font-medium">Phone:</span>
                            <span class="font-medium text-[#0d1b34]">{{ $tempCredentials['phone'] }}</span>
                        </div>
                    @endif
                    <div class="flex justify-between">
                        <span class="text-[#8894ac] font-medium">Role / Position:</span>
                        <span class="font-medium text-[#0d1b34]">{{ $tempCredentials['designation'] }} ({{ $tempCredentials['role'] }})</span>
                    </div>
                    @if(!empty($tempCredentials['supervisor']))
                        <div class="flex justify-between">
                            <span class="text-[#8894ac] font-medium">Reporting To:</span>
                            <span class="font-medium text-[#0d1b34]">{{ $tempCredentials['supervisor'] }}</span>
                        </div>
                    @endif
                </div>

                <!-- Credential Display Box -->
                <div class="p-4 bg-amber-50/70 border border-amber-200 rounded-xl mb-4">
                    <div class="text-xs font-semibold text-amber-900 mb-1">Temporary Password</div>
                    <div class="flex items-center justify-between gap-3">
                        <div class="font-mono text-lg font-bold text-slate-800 tracking-wider select-all">
                            {{ $tempCredentials['password'] }}
                        </div>
                        <button type="button"
                                @click="copyPassword()"
                                class="btn btn-sm btn-ghost bg-white border border-amber-300 text-amber-900 hover:bg-amber-100 flex items-center gap-1.5 shrink-0">
                            <template x-if="!copiedPass">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                            </template>
                            <template x-if="copiedPass">
                                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5 text-green-600"><polyline points="20 6 9 17 4 12"/></svg>
                            </template>
                            <span x-text="copiedPass ? 'Copied!' : 'Copy Password'"></span>
                        </button>
                    </div>
                    <div class="text-[11px] text-amber-800/80 mt-2">
                        * The user will be required to choose a new password immediately upon first sign-in.
                    </div>
                </div>

                <!-- Action / Sharing Buttons -->
                <div class="space-y-2">
                    <button type="button"
                            @click="copyAll()"
                            class="btn btn-primary w-full justify-center gap-2 py-2.5">
                        <template x-if="!copiedAll">
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/></svg>
                        </template>
                        <template x-if="copiedAll">
                            <svg viewBox="0 0 24 24" class="w-4 h-4 text-green-300"><polyline points="20 6 9 17 4 12"/></svg>
                        </template>
                        <span x-text="copiedAll ? 'Full Credentials Copied!' : 'Copy Full Credentials'"></span>
                    </button>

                    <div class="grid grid-cols-2 gap-2">
                        @if($tempCredentials['whatsappUrl'])
                            <a href="{{ $tempCredentials['whatsappUrl'] }}" target="_blank" rel="noopener noreferrer"
                               class="btn btn-ghost bg-[#25D366]/10 text-[#128C7E] hover:bg-[#25D366]/20 border border-[#25D366]/30 justify-center gap-1.5 py-2 text-xs">
                                <svg viewBox="0 0 24 24" class="w-4 h-4 fill-current"><path d="M12.031 6.172c-3.181 0-5.767 2.586-5.768 5.766-.001 1.298.38 2.27 1.019 3.287l-.711 2.598 2.669-.699c.969.586 1.761.884 2.791.884h.001c3.181 0 5.767-2.586 5.768-5.766.001-3.181-2.585-5.77-5.769-5.77zm0 10.455h-.001c-.907 0-1.745-.252-2.484-.71l-.178-.105-1.848.485.494-1.802-.115-.184c-.502-.801-.767-1.464-.766-2.368.001-2.585 2.104-4.688 4.691-4.688 2.586 0 4.689 2.103 4.69 4.688 0 2.586-2.103 4.684-4.688 4.684zm2.571-3.513c-.141-.07-.833-.411-.962-.458-.13-.047-.224-.07-.319.07-.094.141-.365.458-.448.552-.082.094-.165.106-.306.035-.141-.07-.597-.22-1.137-.701-.42-.375-.704-.838-.786-.979-.082-.141-.009-.217.062-.287.064-.063.141-.165.212-.247.07-.082.094-.141.141-.235.047-.094.024-.176-.012-.247-.035-.07-.319-.768-.437-1.052-.115-.276-.232-.239-.319-.243l-.271-.004c-.094 0-.247.035-.377.176-.13.141-.494.482-.494 1.176s.506 1.364.577 1.458c.07.094.996 1.52 2.413 2.132.337.146.6.233.806.299.339.108.647.093.891.056.272-.041.833-.34 1.047-.667.214-.327.214-.608.15-.667-.064-.059-.206-.094-.347-.165z"/></svg>
                                Send on WhatsApp
                            </a>
                        @endif
                        <a href="{{ $tempCredentials['mailtoUrl'] }}"
                           class="btn btn-ghost bg-indigo-50 text-indigo-700 hover:bg-indigo-100 border border-indigo-200 justify-center gap-1.5 py-2 text-xs {{ !$tempCredentials['whatsappUrl'] ? 'col-span-2' : '' }}">
                            <svg viewBox="0 0 24 24" class="w-4 h-4"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="M22 6l-10 7L2 6"/></svg>
                            Send via Email
                        </a>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-t border-[#e2e8f3] flex justify-end">
                    <button type="button" wire:click="closeTempCredentialsModal" class="btn btn-ghost btn-sm">
                        Dismiss
                    </button>
                </div>
            </div>
        @endif
    </x-modal>
</div>
