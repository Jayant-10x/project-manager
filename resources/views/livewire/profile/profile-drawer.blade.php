<div x-data="{
        showCurPass: false,
        showNewPass: false,
        showConfPass: false
     }"
     x-cloak
     x-show="$wire.isOpen"
     x-on:keydown.escape.window="$wire.closeDrawer()"
     class="relative z-[130]">

    <!-- Backdrop Overlay -->
    <div x-show="$wire.isOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         wire:click="closeDrawer"
         class="fixed inset-0 bg-[#08142a]/60 backdrop-blur-xs cursor-pointer">
    </div>

    <!-- Right Slide-Over Panel -->
    <div x-show="$wire.isOpen"
         x-transition:enter="transition ease-out duration-250 transform"
         x-transition:enter-start="translate-x-full"
         x-transition:enter-end="translate-x-0"
         x-transition:leave="transition ease-in duration-200 transform"
         x-transition:leave-start="translate-x-0"
         x-transition:leave-end="translate-x-full"
         class="fixed top-0 right-0 bottom-0 w-full sm:w-[480px] bg-white shadow-2xl flex flex-col z-[131] border-l border-[#e2e8f3] overflow-hidden">

        <!-- Drawer Header -->
        <div class="px-6 py-4.5 bg-gradient-to-r from-[#0b3d91] to-[#12325f] text-white flex items-center justify-between shrink-0 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-white/10 flex items-center justify-center border border-white/15">
                    <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-base font-bold text-white tracking-tight leading-tight">My Profile</h3>
                    <p class="text-xs text-blue-200/90 font-medium">Manage your personal information & security</p>
                </div>
            </div>

            <button type="button"
                    wire:click="closeDrawer"
                    class="w-8 h-8 rounded-lg bg-white/10 hover:bg-white/20 text-white flex items-center justify-center transition cursor-pointer"
                    title="Close Drawer">
                <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
                </svg>
            </button>
        </div>

        <!-- Drawer Content Stream -->
        <div class="flex-1 overflow-y-auto px-6 py-5 space-y-6">

            <!-- Avatar & Profile Header Card -->
            <div class="p-4.5 bg-[#f8fafc] rounded-2xl border border-[#e2e8f0] flex flex-col items-center text-center relative group">
                <div class="relative mb-3">
                    @if ($avatarFile)
                        <img src="{{ $avatarFile->temporaryUrl() }}"
                             class="w-22 h-22 rounded-2xl object-cover border-2 border-white shadow-md ring-2 ring-blue-500">
                    @elseif ($existingAvatarUrl)
                        <img src="{{ $existingAvatarUrl }}"
                             class="w-22 h-22 rounded-2xl object-cover border-2 border-white shadow-md ring-2 ring-[#0b3d91]/20">
                    @else
                        <div class="w-22 h-22 rounded-2xl bg-gradient-to-br from-[#0b3d91] to-[#1a4ea8] text-white flex items-center justify-center font-bold text-2xl border-2 border-white shadow-md ring-2 ring-[#0b3d91]/20">
                            {{ $name ? strtoupper(substr($name, 0, 2)) : 'ME' }}
                        </div>
                    @endif

                    <!-- Upload Trigger Badge -->
                    <label for="profile-avatar-input"
                           class="absolute -bottom-1 -right-1 w-7 h-7 rounded-lg bg-[#0b3d91] hover:bg-blue-800 text-white flex items-center justify-center cursor-pointer shadow-md transition ring-2 ring-white"
                           title="Upload new photo">
                        <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/>
                            <circle cx="12" cy="13" r="4"/>
                        </svg>
                    </label>
                    <input id="profile-avatar-input"
                           type="file"
                           wire:model="avatarFile"
                           accept="image/png,image/jpeg,image/webp"
                           class="hidden">
                </div>

                <div class="text-sm font-bold text-gray-900">{{ $name ?: 'Your Name' }}</div>
                <div class="text-xs text-gray-500 mt-0.5">{{ $email }}</div>

                <div wire:loading wire:target="avatarFile" class="text-xs text-blue-600 mt-2 font-medium flex items-center gap-1.5">
                    <svg class="animate-spin w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10"/>
                    </svg>
                    <span>Uploading new avatar...</span>
                </div>

                <!-- Avatar Action Buttons -->
                <div class="mt-3 flex items-center justify-center gap-2">
                    <label for="profile-avatar-input" class="btn btn-secondary !py-1 !px-2.5 !text-xs font-semibold cursor-pointer">
                        Change Photo
                    </label>
                    @if ($avatarFile || $existingAvatarUrl)
                        <button type="button"
                                wire:click="markRemoveAvatar"
                                class="btn btn-ghost !py-1 !px-2.5 !text-xs text-red-600 hover:text-red-700 hover:bg-red-50">
                            Remove
                        </button>
                    @endif
                </div>

                @error('avatarFile')
                <span class="text-xs text-red-500 mt-2 font-medium block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Role & Organization Hierarchy Info (Read-Only) -->
            <div class="p-3.5 bg-blue-50/60 rounded-xl border border-blue-100 flex items-center justify-between text-xs">
                <div class="space-y-0.5">
                    <div class="text-[11px] font-bold uppercase tracking-wider text-blue-800/80">Assigned Role</div>
                    <div class="inline-flex items-center gap-1 font-semibold text-[#0b3d91]">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-600"></span>
                        <span>{{ $role }}</span>
                        @if($designation)
                            <span class="text-gray-400 font-normal">({{ $designation }})</span>
                        @endif
                    </div>
                </div>

                @if($supervisorName)
                    <div class="text-right space-y-0.5">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-blue-800/80">Reporting To</div>
                        <div class="font-medium text-gray-700">
                            <span class="font-semibold text-gray-900">{{ $supervisorName }}</span>
                            <span class="text-[11px] text-gray-500 block">({{ $supervisorRole }})</span>
                        </div>
                    </div>
                @else
                    <div class="text-right space-y-0.5">
                        <div class="text-[11px] font-bold uppercase tracking-wider text-blue-800/80">Hierarchy Level</div>
                        <div class="font-medium text-gray-600">Top-Level Authority</div>
                    </div>
                @endif
            </div>

            <!-- Profile Details Form -->
            <form wire:submit="save" id="profile-drawer-form" novalidate class="space-y-4">
                <label class="f full">
                    <span>Full Name <span class="text-red-500">*</span></span>
                    <input wire:model.live.blur="name"
                           type="text"
                           class="i @error('name') border-red-500! bg-red-50/20! @enderror"
                           placeholder="Your full name"
                           maxlength="160">
                    @error('name')
                    <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                    @enderror
                </label>

                <label class="f full">
                    <span class="flex items-center justify-between">
                        <span>Email Address</span>
                        <span class="text-[11px] text-gray-400 font-normal flex items-center gap-1">
                            <svg class="w-3 h-3 text-gray-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                            </svg>
                            System Login ID (Read-only)
                        </span>
                    </span>
                    <input value="{{ $email }}"
                           type="email"
                           disabled
                           class="i bg-gray-100/80 text-gray-500 cursor-not-allowed">
                </label>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <label class="f">
                        <span>Phone</span>
                        <input wire:model.live.blur="phone"
                               type="text"
                               class="i @error('phone') border-red-500! bg-red-50/20! @enderror"
                               placeholder="+91 ..."
                               maxlength="30">
                        @error('phone')
                        <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                        @enderror
                    </label>

                    <label class="f">
                        <span>City / Location</span>
                        <input wire:model.live.blur="city"
                               type="text"
                               class="i @error('city') border-red-500! bg-red-50/20! @enderror"
                               placeholder="e.g. Mumbai"
                               maxlength="60">
                        @error('city')
                        <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                        @enderror
                    </label>
                </div>

                <!-- Password Change Collapsible Section -->
                <div class="mt-4 pt-4 border-t border-[#eef2f9]">
                    <div class="flex items-center justify-between mb-3">
                        <div>
                            <span class="text-xs font-bold text-gray-800 block">Security & Password</span>
                            <span class="text-[11px] text-gray-500">Update your account login password</span>
                        </div>
                        <button type="button"
                                wire:click="togglePasswordSection"
                                class="btn btn-secondary !py-1 !px-2.5 !text-xs font-semibold flex items-center gap-1">
                            <span>{{ $showPasswordSection ? 'Hide' : 'Change Password' }}</span>
                            <svg class="w-3.5 h-3.5 transition-transform {{ $showPasswordSection ? 'rotate-180' : '' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M6 9l6 6 6-6"/>
                            </svg>
                        </button>
                    </div>

                    @if($showPasswordSection)
                        <div class="p-4 bg-gray-50/80 rounded-xl border border-gray-200/80 space-y-3.5">
                            <!-- Current Password -->
                            <div class="f full">
                                <span class="text-xs font-semibold text-gray-700">Current Password <span class="text-red-500">*</span></span>
                                <div class="relative flex items-center mt-1">
                                    <input wire:model="current_password"
                                           :type="showCurPass ? 'text' : 'password'"
                                           class="i w-full pr-10 @error('current_password') border-red-500! bg-red-50/20! @enderror"
                                           placeholder="Enter your current password">
                                    <button type="button"
                                            @click="showCurPass = !showCurPass"
                                            class="absolute right-3 text-gray-400 hover:text-gray-600 transition"
                                            title="Toggle visibility">
                                        <svg x-show="!showCurPass" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <svg x-show="showCurPass" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                            <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                                        </svg>
                                    </button>
                                </div>
                                @error('current_password')
                                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- New Password with Generate Button -->
                            <div class="f full">
                                <span class="text-xs font-semibold text-gray-700">New Password <span class="text-red-500">*</span></span>
                                <div class="flex items-center gap-2 mt-1">
                                    <div class="relative flex-1 min-w-0">
                                        <input wire:model="new_password"
                                               :type="showNewPass ? 'text' : 'password'"
                                               class="i w-full pr-10 @error('new_password') border-red-500! bg-red-50/20! @enderror"
                                               placeholder="Min 8 characters">
                                        <button type="button"
                                                @click="showNewPass = !showNewPass"
                                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 transition"
                                                title="Toggle visibility">
                                            <svg x-show="!showNewPass" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                            </svg>
                                            <svg x-show="showNewPass" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                                <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <button type="button"
                                            wire:click="generateRandomPassword"
                                            class="btn btn-secondary shrink-0 inline-flex items-center gap-1.5 h-[38px] px-3 text-xs font-semibold cursor-pointer"
                                            title="Generate secure password">
                                        <svg class="w-3.5 h-3.5 text-blue-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0110 0v4"/>
                                        </svg>
                                        <span>Generate</span>
                                    </button>
                                </div>
                                @error('new_password')
                                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Confirm New Password -->
                            <div class="f full">
                                <span class="text-xs font-semibold text-gray-700">Confirm New Password <span class="text-red-500">*</span></span>
                                <div class="relative flex items-center mt-1">
                                    <input wire:model="new_password_confirmation"
                                           :type="showConfPass ? 'text' : 'password'"
                                           class="i w-full pr-10"
                                           placeholder="Re-enter new password">
                                    <button type="button"
                                            @click="showConfPass = !showConfPass"
                                            class="absolute right-3 text-gray-400 hover:text-gray-600 transition"
                                            title="Toggle visibility">
                                        <svg x-show="!showConfPass" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8z"/><circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <svg x-show="showConfPass" class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none;">
                                            <path d="M17.94 17.94A10.07 10.07 0 0112 20c-7 0-11-8-11-8a18.45 18.45 0 015.06-5.94M9.9 4.24A9.12 9.12 0 0112 4c7 0 11 8 11 8a18.5 18.5 0 01-2.16 3.19m-6.72-1.07a3 3 0 11-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </form>
        </div>

        <!-- Sticky Drawer Action Footer -->
        <div class="px-6 py-4 border-t border-[#eef2f9] bg-white flex items-center justify-end gap-3 shrink-0 shadow-xs">
            <button type="button"
                    wire:click="closeDrawer"
                    class="btn btn-outline-secondary">
                Cancel
            </button>
            <button type="button"
                    wire:click="save"
                    wire:loading.attr="disabled"
                    class="btn btn-primary inline-flex items-center gap-2">
                <span wire:loading.remove wire:target="save">Save Profile</span>
                <span wire:loading wire:target="save" class="inline-flex items-center gap-1.5">
                    <svg class="animate-spin w-4 h-4 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <circle cx="12" cy="12" r="10" stroke-opacity="0.25"/><path d="M12 2a10 10 0 0 1 10 10"/>
                    </svg>
                    <span>Saving...</span>
                </span>
            </button>
        </div>
    </div>
</div>
