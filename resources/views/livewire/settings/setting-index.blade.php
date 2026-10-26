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
                    class="btn btn-outline-danger btn-sm">
                <svg viewBox="0 0 24 24" class="w-3.5 h-3.5">
                    <path d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9"/>
                </svg>
                Sign out
            </button>
        </div>
        <div class="card-body">
            <div class="grid-2e">
                <div class="flex items-center gap-4 mb-4 pb-4 sm:mb-0 sm:pb-0 sm:border-e sm:border-[#eef2f9] sm:pr-6">
                    <button type="button"
                            @click="$dispatch('open-profile-drawer')"
                            class="relative shrink-0 group cursor-pointer focus:outline-hidden"
                            title="Click to view and update profile photo">
                        @if($me->avatar_url)
                            <img src="{{ $me->avatar_url }}" alt="{{ $me->name }}" class="w-14 h-14 rounded-2xl object-cover border-2 border-white shadow-xs group-hover:ring-2 group-hover:ring-[#0b3d91] transition">
                        @else
                            <div class="w-14 h-14 rounded-2xl font-bold text-white shadow-xs flex items-center justify-center text-lg group-hover:ring-2 group-hover:ring-[#0b3d91] transition" style="background:#0b3d91">
                                {{ strtoupper(substr($me->name, 0, 2)) }}
                            </div>
                        @endif
                        <span class="absolute -bottom-1 -right-1 w-5 h-5 rounded-lg bg-[#0b3d91] text-white flex items-center justify-center shadow-xs border border-white group-hover:scale-110 transition">
                            <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                                <circle cx="12" cy="13" r="4"/>
                            </svg>
                        </span>
                    </button>
                    <div class="min-w-0 flex-1">
                        <div class="font-bold text-base text-[#0d1b34] truncate">{{ $me->name }}</div>
                        <div class="font-medium text-xs text-gray-500 truncate mt-0.5">{{ $me->email }}</div>
                        <div class="text-xs text-[#8894ac] truncate mt-0.5">{{ $me->designation ?? $me->role }}</div>
                        <div class="mt-2 flex items-center gap-2">
                            <span class="badge {{ $me->role === 'Admin' ? 'b-navy' : 'b-green' }} text-[11px]">
                                {{ $me->role }}
                            </span>
                            <button type="button"
                                    @click="$dispatch('open-profile-drawer')"
                                    class="btn btn-outline-primary btn-xs">
                                Edit Profile
                            </button>
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
                            <h3 class="font-bold text-sm text-[#0d1b34]">Payment Alert Schedule</h3>
                            <div class="text-xs text-[#8894ac]">Notification intervals before milestone due dates</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <form wire:submit="saveSettings" novalidate>
                            <!-- Dynamic Threshold Manager -->
                            <div class="mb-5 p-4 bg-gray-50/70 rounded-2xl border border-gray-100 space-y-3">
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-gray-800">Active Reminder Thresholds</span>
                                    <span class="text-[11px] text-gray-500 font-medium">{{ count($reminderDays) }} active {{ count($reminderDays) === 1 ? 'interval' : 'intervals' }}</span>
                                </div>

                                <!-- Pill List -->
                                <div class="flex flex-wrap items-center gap-2 min-h-[36px]">
                                    @forelse($reminderDays as $day)
                                        @if($day === 0)
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-800 border border-amber-200 shadow-2xs">
                                                <svg class="w-3.5 h-3.5 text-amber-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 14 14"/>
                                                </svg>
                                                <span>Due Day</span>
                                                <button type="button"
                                                        wire:click="removeReminderDay(0)"
                                                        class="text-amber-500 hover:text-amber-800 transition font-bold ml-0.5 cursor-pointer"
                                                        title="Remove this threshold">
                                                    ✕
                                                </button>
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-blue-50 text-[#0b3d91] border border-blue-200 shadow-2xs">
                                                <svg class="w-3.5 h-3.5 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>
                                                </svg>
                                                <span>{{ $day }} {{ $day === 1 ? 'Day' : 'Days' }} Before</span>
                                                <button type="button"
                                                        wire:click="removeReminderDay({{ $day }})"
                                                        class="text-blue-400 hover:text-blue-700 transition font-bold ml-0.5 cursor-pointer"
                                                        title="Remove this threshold">
                                                    ✕
                                                </button>
                                            </span>
                                        @endif
                                    @empty
                                        <span class="text-xs text-red-500 font-medium">No reminder thresholds configured. Please add at least one interval.</span>
                                    @endforelse
                                </div>
                                @error('reminderDays')
                                <span class="text-xs text-red-500! font-medium block">{{ $message }}</span>
                                @enderror

                                <!-- Add Threshold Input Row -->
                                <div class="pt-2 border-t border-gray-200/60">
                                    <div class="flex items-center gap-2">
                                        <div class="relative flex-1 max-w-[240px]">
                                            <input wire:model="newReminderDay"
                                                   wire:keydown.enter.prevent="addReminderDay"
                                                   type="number" min="0" max="180"
                                                   class="i w-full h-[36px] text-xs @error('newReminderDay') border-red-500! bg-red-50/20! @enderror"
                                                   placeholder="Days (e.g. 14, 7, 0)">
                                        </div>
                                        <button type="button"
                                                wire:click="addReminderDay"
                                                class="btn btn-outline-primary inline-flex items-center gap-1 h-[36px] px-3 text-xs font-semibold cursor-pointer">
                                            <svg class="w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M12 5v14M5 12h14"/>
                                            </svg>
                                            <span>Add Day</span>
                                        </button>
                                    </div>
                                    @error('newReminderDay')
                                    <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                                    @enderror
                                    <span class="text-[11px] text-gray-500 block mt-1">Enter 0 for due day, or 1–180 for days in advance.</span>
                                </div>

                                <!-- Quick Presets -->
                                <div class="pt-2 flex items-center gap-1.5 flex-wrap">
                                    <span class="text-[11px] text-gray-500 font-semibold mr-1">Quick Presets:</span>
                                    <button type="button"
                                            wire:click="applyPreset('standard')"
                                            class="btn btn-outline-secondary btn-xs">
                                        Standard (7, 3, 1, Due Day)
                                    </button>
                                    <button type="button"
                                            wire:click="applyPreset('intensive')"
                                            class="btn btn-outline-secondary btn-xs">
                                        Intensive (14, 7, 3, 1, Due Day)
                                    </button>
                                    <button type="button"
                                            wire:click="applyPreset('minimal')"
                                            class="btn btn-outline-secondary btn-xs">
                                        Minimal (3, Due Day)
                                    </button>
                                </div>
                            </div>

                            <div class="form-grid">
                                <label class="f">
                                    <span>Company / Billing Name <span class="text-red-500">*</span></span>
                                    <input wire:model.blur="companyName"
                                           class="i @error('companyName') border-red-500! bg-red-50/20! @enderror">
                                    @error('companyName')
                                    <span class="text-xs text-red-500! mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                </label>

                                <label class="f">
                                    <span>Display Currency <span class="text-red-500">*</span></span>
                                    <select wire:model.blur="displayCurrency"
                                            class="i @error('displayCurrency') border-red-500! bg-red-50/20! @enderror">
                                        <option value="INR">INR (₹)</option>
                                        <option value="USD">USD ($)</option>
                                    </select>
                                    @error('displayCurrency')
                                    <span class="text-xs text-red-500! mt-1 block font-medium">{{ $message }}</span>
                                    @enderror
                                </label>

                                {{-- [Commented out legacy AMC & Follow-up alert days to streamline payment governance]
                                <label class="f">
                                    <span>AMC / renewal alert (days)</span>
                                    <input wire:model.blur="amcAlertDays"
                                           type="number" min="1" max="180"
                                           class="i @error('amcAlertDays') border-red-500! bg-red-50/20! @enderror">
                                </label>
                                <label class="f">
                                    <span>Follow-up alert (days)</span>
                                    <input wire:model.blur="followupAlertDays"
                                           type="number" min="1" max="180"
                                           class="i @error('followupAlertDays') border-red-500! bg-red-50/20! @enderror">
                                </label>
                                --}}
                            </div>

                            <div class="flex justify-end mt-5 pt-3 border-t border-[#eef2f9]">
                                <button type="submit" class="btn btn-primary inline-flex items-center gap-1.5">
                                    <svg viewBox="0 0 24 24" class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2">
                                        <path d="M20 6L9 17l-5-5"/>
                                    </svg>
                                    <span>Save Settings</span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        @if(auth()->user()->role !== 'Sales' && auth()->user()->hasPermission('users', 'view'))
        <!-- 3. User Accounts Management Shortcut (Right Column) -->
        <div class="space-y-4">
            <div class="card p-6 bg-white rounded-2xl border border-gray-100 shadow-2xs">
                <div class="flex items-start gap-3.5 mb-4">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 text-[#0b3d91] flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 00-3-3.87"/><path d="M16 3.13a4 4 0 010 7.75"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-bold text-[#0d1b34] text-sm">User & Team Access</h4>
                        <p class="text-xs text-gray-500 mt-1 leading-relaxed">
                            Manage staff accounts, roles, reporting lines, and access permissions.
                        </p>
                    </div>
                </div>

                <div class="p-3.5 bg-gray-50/70 rounded-xl border border-gray-100 mb-4 flex items-center justify-between">
                    <span class="text-xs font-semibold text-gray-700">Total System Accounts</span>
                    <span class="badge b-navy font-bold text-xs">{{ $users->count() }} accounts</span>
                </div>

                @if(!empty($pendingResetRequests) && $pendingResetRequests->isNotEmpty())
                    <div class="mb-4 p-4 rounded-xl bg-amber-50/70 border border-amber-200">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                                <span class="text-xs font-bold text-amber-900 uppercase tracking-wide">Password Reset Requests</span>
                            </div>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold bg-amber-200 text-amber-800">
                                {{ $pendingResetRequests->count() }} pending
                            </span>
                        </div>
                        <div class="space-y-2 mt-2">
                            @foreach($pendingResetRequests as $resetReq)
                                <div class="p-2.5 bg-white rounded-lg border border-amber-200/80 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <div class="text-xs font-bold text-slate-800 truncate">{{ $resetReq->user?->name ?? 'User' }}</div>
                                        <div class="text-[11px] text-slate-500 flex items-center gap-1 mt-0.5">
                                            <span>{{ $resetReq->user?->role }}</span>
                                            <span>&middot;</span>
                                            <span>{{ $resetReq->created_at?->diffForHumans() }}</span>
                                        </div>
                                    </div>
                                    <button type="button"
                                            @click="$dispatch('confirm-dialog', {
                                                title: 'Generate Temporary Password?',
                                                message: 'Are you sure you want to generate a new temporary password for {{ addslashes($resetReq->user?->name ?? 'this user') }}? Their existing password will be replaced immediately.',
                                                confirmText: 'Generate & Resolve',
                                                cancelText: 'Cancel',
                                                danger: false,
                                                wire: $wire,
                                                action: 'resetUserPassword',
                                                params: [{{ $resetReq->user_id }}],
                                                args: [{{ $resetReq->user_id }}]
                                            })"
                                            class="btn btn-sm btn-primary py-1 px-2.5 text-xs font-semibold shrink-0">
                                        Reset &amp; Issue
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif

                <div class="flex items-center gap-2">
                    <a href="{{ route('users.index') }}" wire:navigate class="btn btn-primary btn-sm inline-flex items-center gap-1.5">
                        <span>Open Users Directory</span>
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>
                        </svg>
                    </a>
                    <button type="button"
                            @click="Livewire.dispatch('open-user-modal', { id: null })"
                            class="btn btn-outline-primary btn-sm inline-flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        <span>Add User</span>
                    </button>
                </div>
            </div>

            {{-- [Preserved legacy embedded users list - safely commented out]
            @if(auth()->user()->role !== 'Sales')
            <div class="card">
                ...
            </div>
            @endif
            --}}
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
