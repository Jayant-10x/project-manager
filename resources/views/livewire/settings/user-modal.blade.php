<x-modal name="user-modal" wide="true"
         :title="$userId ? ($role === 'Sales' ? 'Edit Salesperson' : 'Edit User') : ($role === 'Sales' ? 'Add Salesperson' : 'Add User')"
         :sub="$role === 'Sales' ? 'Sales account and target management' : 'User account and access permissions'">
    <form wire:submit="save" novalidate>
        <div class="form-grid">
            <!-- Profile Avatar Upload -->
            <div class="f full flex items-center gap-4 p-3 bg-gray-50/70 rounded-xl border border-gray-100">
                <div class="relative shrink-0">
                    @if ($avatarFile)
                        <img src="{{ $avatarFile->temporaryUrl() }}"
                             class="w-14 h-14 rounded-xl object-cover border border-gray-200 shadow-2xs"
                             alt="userImage">
                    @elseif ($existingAvatarUrl)
                        <img src="{{ $existingAvatarUrl }}"
                             class="w-14 h-14 rounded-xl object-cover border border-gray-200 shadow-2xs"
                             alt="userImage">
                    @else
                        <div
                            class="w-14 h-14 rounded-xl bg-blue-100 text-[#0b3d91] flex items-center justify-center font-bold text-base border border-blue-200 shadow-2xs">
                            {{ $name ? strtoupper(substr($name, 0, 2)) : 'USR' }}
                        </div>
                    @endif
                </div>
                <div class="flex-1 min-w-0">
                    <span class="block text-xs font-semibold text-gray-700 mb-1">Profile Photo (Optional)</span>
                    <input type="file" wire:model="avatarFile" accept="image/png,image/jpeg,image/webp"
                           class="block w-full text-xs text-gray-500 file:mr-2.5 file:py-1 file:px-2.5 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-blue-50 file:text-[#0b3d91] hover:file:bg-blue-100 cursor-pointer">
                    <div wire:loading wire:target="avatarFile" class="text-[11px] text-blue-600 mt-1 font-medium">
                        Uploading image...
                    </div>
                    @error('avatarFile') <span
                        class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                </div>
            </div>

            <label class="f full">
                <span>Full name <span class="text-red-500">*</span></span>
                <input wire:model.live.blur="name"
                       class="i @error('name') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Abdurrahman Ansari"
                       maxlength="160">
                @error('name')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Email (login ID) <span class="text-red-500">*</span></span>
                <input wire:model.live.blur="email" aria-autocomplete="new-email"
                       type="email" autocomplete="new"
                       class="i @error('email') border-red-500! bg-red-50/20! @enderror"
                       placeholder="abdur@the10xagency.in"
                       maxlength="160">
                @error('email')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Phone</span>
                <input wire:model.live.blur="phone"
                       class="i @error('phone') border-red-500! bg-red-50/20! @enderror"
                       placeholder="+91 ..."
                       maxlength="30">
                @error('phone')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Role <span class="text-red-500">*</span></span>
                <select wire:model.live="role"
                        class="i @error('role') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Role ***</option>
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Coordinator">Coordinator</option>
                    <option value="TechPerson">Tech Person</option>
                    <option value="Sales">Sales</option>
                </select>
                @error('role')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>
                    @if($role === 'Manager')
                        Reports To (Admin Supervisor)
                    @elseif($role === 'Coordinator' || $role === 'TechPerson')
                        Reports To (Manager Supervisor)
                    @elseif($role === 'Sales')
                        Reports To (Coordinator Supervisor)
                    @else
                        Reports To (Optional)
                    @endif
                </span>
                <select wire:model.live="reporting_to"
                        class="i @error('reporting_to') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** No Supervisor (Top Level) ***</option>
                    @foreach($supervisors as $supervisorOption)
                        <option value="{{ $supervisorOption->id }}">{{ $supervisorOption->name }} ({{ $supervisorOption->role }})</option>
                    @endforeach
                </select>
                @error('reporting_to')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Designation</span>
                <input wire:model.live.blur="designation"
                       class="i @error('designation') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Project Coordinator"
                       maxlength="80">
                @error('designation')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>City</span>
                <input wire:model.live.blur="city"
                       class="i @error('city') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Mumbai"
                       maxlength="60">
                @error('city')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Status <span class="text-red-500">*</span></span>
                <select wire:model.live="status"
                        class="i @error('status') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Status ***</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
                @error('status')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            @if($role === 'Sales')
                <label class="f">
                    <span>Monthly Target (₹)</span>
                    <input wire:model.live.blur="target"
                           type="number" step="0.01" min="0"
                           class="i @error('target') border-red-500! bg-red-50/20! @enderror"
                           placeholder="e.g. 500000">
                    @error('target')
                    <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                    @enderror

                    @if($currentAchieved !== null)
                        @php
                            $targetVal = (float) ($target ?? 0.0);
                            $pct = $targetVal > 0 ? round(($currentAchieved / $targetVal) * 100, 1) : 0;
                            $clamped = min(100, max(0, $pct));
                        @endphp
                        <div class="mt-2 p-2.5 bg-gray-50 rounded-xl border border-gray-200/80 text-xs">
                            <div class="flex items-center justify-between mb-1">
                                <span class="font-medium text-gray-600">Current Progress:</span>
                                <span
                                    class="font-bold {{ $pct >= 100 ? 'text-emerald-700' : ($pct >= 50 ? 'text-blue-700' : 'text-amber-700') }}">
                                    {{ $pct }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-1.5 overflow-hidden mb-1.5">
                                <div
                                    class="h-full rounded-full transition-all duration-300 {{ $pct >= 100 ? 'bg-emerald-500' : ($pct >= 50 ? 'bg-blue-600' : 'bg-amber-500') }}"
                                    style="width: {{ $clamped }}%"></div>
                            </div>
                            <div class="flex items-center justify-between text-[11px] text-gray-500">
                                <span>Booked: <strong><x-money :value="$currentAchieved"/></strong> ({{ $currentDealsCount }} deals)</span>
                                @if($targetVal > $currentAchieved)
                                    <span class="text-amber-600 font-medium"><x-money
                                            :value="$targetVal - $currentAchieved"/> to go</span>
                                @else
                                    <span class="text-emerald-600 font-semibold">Exceeded!</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </label>
            @endif

            <!-- Password Field with Generate Password Button -->
            <div class="f full">
                <span class="text-xs font-semibold text-gray-700">
                    {{ $userId ? 'Password (Leave blank to keep unchanged)' : 'Password (Leave blank for default: Welcome@10x)' }}
                </span>
                <div class="flex items-center gap-2 mt-1">
                    <div class="relative flex-1 min-w-0">
                        <input wire:model.live="password"
                               type="{{ $showPass ? 'text' : 'password' }}"
                               class="i w-full pr-10 @error('password') border-red-500! bg-red-50/20! @enderror"
                               placeholder="{{ $userId ? '••••••••' : 'Enter or generate a password' }}">
                        <button type="button"
                                wire:click="togglePasswordVisibility"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition cursor-pointer"
                                title="Toggle password visibility">
                            @if(!$showPass)
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2">
                                    <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/>
                                    <circle cx="12" cy="12" r="3"/>
                                </svg>
                            @else
                                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                                     stroke-width="2">
                                    <path
                                        d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/>
                                    <line x1="1" y1="1" x2="23" y2="23"/>
                                </svg>
                            @endif
                        </button>
                    </div>
                    <button type="button"
                            wire:click="generateRandomPassword"
                            class="btn btn-ghost shrink-0 inline-flex items-center gap-1.5 h-9.5 px-3.5 text-xs font-semibold cursor-pointer"
                            title="Generate a cryptographically secure random password">
                        <svg class="w-4 h-4 text-blue-600 shrink-0" viewBox="0 0 24 24" fill="none"
                             stroke="currentColor" stroke-width="2">
                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                            <path d="M7 11V7a5 5 0 0110 0v4"/>
                        </svg>
                        <span>Generate Password</span>
                    </button>
                </div>
                @error('password')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
                @if(!$userId)
                    <p class="text-[11px] text-gray-500 mt-1">If left blank, the temporary password will default to
                        <code class="px-1 py-0.5 bg-gray-100 rounded text-gray-700 font-mono">Welcome@10x</code> (user
                        will be required to change on first login).</p>
                @endif
            </div>

            <!-- Permission Management Matrix -->
            <div class="f full mt-2 pt-4 border-t border-slate-100">
                <div class="flex items-center justify-between flex-wrap gap-2 mb-3">
                    <div class="cursor-pointer select-none flex items-center gap-2"
                         wire:click="togglePermissionsDropdown">
                        <button type="button"
                                class="btn btn-ghost btn-xs w-6 h-6 p-0 flex items-center justify-center">
                            <svg class="w-3.5 h-3.5 transition-transform {{ $openPerms ? 'rotate-90' : '' }}"
                                 viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </button>
                        <div>
                            <span class="text-xs font-bold text-[#0d1b34] uppercase tracking-wide">Permissions &amp; Access Controls</span>
                            <span class="text-[11px] text-[#8894ac] block">Module-level access permissions</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if($customPermissions)
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                Customized
                            </span>
                            <button type="button" wire:click="resetPermissionsToRoleDefaults"
                                    class="text-[11px] font-semibold text-blue-600 hover:underline cursor-pointer">
                                Reset to {{ $role }} Defaults
                            </button>
                        @else
                            <span
                                class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-semibold bg-blue-50 text-[#0b3d91] border border-blue-200">
                                Role Defaults ({{ $role }})
                            </span>
                        @endif
                    </div>
                </div>

                @if($openPerms)
                    <div class="mt-2 bg-slate-50/70 p-4 rounded-xl border border-slate-200/80">
                        <div class="overflow-x-auto">
                            <table class="w-full text-xs text-left">
                                <thead>
                                <tr class="border-b border-slate-200 text-[#0d1b34]">
                                    <th class="py-2 px-3 font-semibold">Module</th>
                                    <th class="py-2 px-3 font-semibold text-center w-20">View</th>
                                    <th class="py-2 px-3 font-semibold text-center w-20">Add</th>
                                    <th class="py-2 px-3 font-semibold text-center w-20">Edit</th>
                                    <th class="py-2 px-3 font-semibold text-center w-20">Delete / Export</th>
                                </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200/60 text-slate-700">
                                @php
                                    $modulesList = [
                                        'customers' => ['label' => 'Customers', 'actions' => ['view', 'add', 'edit', 'delete']],
                                        'projects' => ['label' => 'Projects', 'actions' => ['view', 'add', 'edit', 'delete']],
                                        'schedules' => ['label' => 'Payment Schedules', 'actions' => ['view', 'add', 'edit', 'delete']],
                                        'payments' => ['label' => 'Payments & Receipts', 'actions' => ['view', 'add', 'edit', 'delete']],
                                        'today' => ['label' => "Today's Work", 'actions' => ['view', 'add', 'edit', 'delete']],
                                        'reports' => ['label' => 'Reports & Analytics', 'actions' => ['view', 'add', 'edit', 'export']],
                                        'users' => ['label' => 'User Management', 'actions' => ['view', 'add', 'edit', 'delete']],
                                        'team' => ['label' => 'Team Overview', 'actions' => ['view', 'add', 'edit', 'delete']],
                                        'settings' => ['label' => 'System Settings', 'actions' => ['view', 'add', 'edit', 'delete']],
                                    ];
                                @endphp
                                @foreach($modulesList as $modKey => $modCfg)
                                    <tr class="hover:bg-white/60 transition">
                                        <td class="py-2 px-3 font-medium text-[#0d1b34]">{{ $modCfg['label'] }}</td>
                                        @foreach(['view', 'add', 'edit', 'delete'] as $act)
                                            <td class="py-2 px-3 text-center">
                                                @php
                                                    $actionKey = ($modKey === 'reports' && $act === 'delete') ? 'export' : $act;
                                                @endphp
                                                <label class="inline-flex items-center justify-center cursor-pointer"
                                                       title="{{ ucfirst($actionKey) }} permission for {{ $modCfg['label'] }}">
                                                    <input type="checkbox"
                                                           wire:key="perm-{{ $permKey }}-{{ $modKey }}-{{ $actionKey }}"
                                                           wire:model.live="userPermissions.{{ $modKey }}.{{ $actionKey }}"
                                                           class="rounded border-slate-300 text-[#0b3d91] focus:ring-blue-500 w-4 h-4 cursor-pointer">
                                                </label>
                                            </td>
                                        @endforeach
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>
            </div>

            <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
                <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
                <button type="submit"
                        class="btn btn-primary min-w-40 justify-center"
                        wire:loading.attr="disabled"
                        wire:target="save">
                    <span wire:loading.remove wire:target="save" class="inline-flex items-center justify-center gap-1.5">{{ $userId ? 'Save changes' : ($role === 'Sales' ? 'Add salesperson' : 'Add user') }}</span>
                    <span wire:loading.inline-flex wire:target="save" class="items-center justify-center gap-1.5">
                        <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Saving...</span>
                    </span>
                </button>
            </div>
        </div>
    </form>
</x-modal>
