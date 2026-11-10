<div class="login-wrap flex items-center justify-center min-h-screen p-6"
     style="background: linear-gradient(150deg, #06204d, #0b3d91 55%, #5aa236 165%);">

    <div class="login-card bg-white rounded-2xl p-8 w-full max-w-md shadow-2xl">
        <div
            class="login-logo-plate bg-white rounded-xl flex items-center justify-center p-3 max-w-55 mx-auto mb-5 shadow-sm">
            <img class="h-10 w-auto" src="{{ asset('assets/logo.png') }}" alt="10X Agency">
        </div>

        <div class="login-title text-center text-lg font-bold text-[#0d1b34]">Set your own password</div>
        <div class="login-sub text-center text-xs text-[#8894ac] uppercase tracking-wider mt-1">One required step</div>

        <p class="text-xs text-[#4a5875] text-center mt-3 leading-relaxed">
            Your admin assigned you a temporary password. Please set your permanent password to continue.
        </p>

        <form wire:submit="updatePassword" class="space-y-4 mt-6">
            <div>
                <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Current Password</label>
                <input wire:model="current_password"
                       type="password"
                       class="i text-sm @error('current_password') border-red-500! bg-red-50/20! @enderror"
                       placeholder="Temporary password from admin"
                       autocomplete="current-password">
                @error('current_password')
                <span class="text-xs text-red-500! block mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="text-xs font-bold text-gray-500 uppercase block mb-1">New Password</label>
                <input wire:model="new_password"
                       type="password"
                       class="i text-sm @error('new_password') border-red-500! bg-red-50/20! @enderror"
                       placeholder="Min 8 chars, 1 capital, 1 number"
                       autocomplete="new-password">
                @error('new_password')
                <span class="text-xs text-red-500! block mt-1">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="text-xs font-bold text-gray-500 uppercase block mb-1">Confirm New Password</label>
                <input wire:model="new_password_confirmation"
                       type="password"
                       class="i text-sm @error('new_password_confirmation') border-red-500! bg-red-50/20! @enderror"
                       placeholder="Repeat new password"
                       autocomplete="new-password">
                @error('new_password_confirmation')
                <span class="text-xs text-red-500! block mt-1">{{ $message }}</span>
                @enderror
            </div>

            <button type="submit" class="btn btn-primary w-full justify-center py-2.5 mt-2" wire:loading.attr="disabled" wire:target="updatePassword">
                <span wire:loading.remove wire:target="updatePassword" class="inline-flex items-center justify-center gap-1.5">
                    <svg viewBox="0 0 24 24" class="w-4 h-4">
                        <circle cx="8" cy="15" r="4"/>
                        <path d="M10.8 12.2L20 3M17 6l3 3M14 9l2 2"/>
                    </svg>
                    <span>Save and continue</span>
                </span>
                <span wire:loading.inline-flex wire:target="updatePassword" class="items-center justify-center gap-1.5">
                    <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Saving...</span>
                </span>
            </button>
        </form>

        <div class="text-center mt-5">
            <button type="button"
                    wire:click="logout"
                    class="text-xs font-semibold text-[#8894ac] hover:text-[#0b3d91] transition">
                Sign out
            </button>
        </div>
    </div>
</div>
