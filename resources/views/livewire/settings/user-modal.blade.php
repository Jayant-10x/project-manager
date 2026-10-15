<x-modal name="user-modal"
         :title="$userId ? 'Edit User' : 'Add User'"
         sub="Staff account and role management with credential provisioning">
    <form wire:submit="save" novalidate>
        <div class="form-grid">
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
                <input wire:model.live.blur="email"
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
                    <option value="Admin">Admin</option>
                    <option value="Manager">Manager</option>
                    <option value="Coordinator">Coordinator</option>
                    <option value="Sales">Sales</option>
                </select>
                @error('role')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Reports To <span class="text-red-500">*</span></span>
                <select wire:model.live="reporting_to"
                        class="i @error('reporting_to') border-red-500! bg-red-50/20! @enderror">
                    <option value="">— No Supervisor (Top Level) —</option>
                    @foreach($supervisors as $s)
                        <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->role }})</option>
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
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
                @error('status')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>{{ $userId ? 'New password (optional)' : str('Temporary password'." <span class='text-red-500'>*</span>")->toHtmlString() }}</span>
                <input wire:model.live.blur="password"
                       type="password"
                       class="i @error('password') border-red-500! bg-red-50/20! @enderror"
                       placeholder="{{ $userId ? 'Leave blank to preserve existing password' : 'Min 8 chars, 1 uppercase, 1 lowercase, 1 number' }}">
                @error('password')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>
        </div>

        <div class="mt-6 flex justify-end gap-3 border-t border-[#eef2f9] pt-4">
            <button type="button" @click="confirmClose()" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">{{ $userId ? 'Save changes' : 'Add user' }}</button>
        </div>
    </form>
</x-modal>
