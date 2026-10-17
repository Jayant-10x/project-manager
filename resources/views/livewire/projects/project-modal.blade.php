<x-modal name="project-modal"
         :title="$projectId ? 'Edit Project' : 'New Project'"
         sub="Capture deliverable, timeline and margin settings"
         :wide="true">
    <form wire:submit="save" novalidate>
        <div class="form-grid">
            <label class="f full">
                <span>Project name <span class="text-red-500"> *</span></span>
                <input wire:model.blur="name"
                       class="i @error('name') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. E-commerce Website Redesign">
                @error('name') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Customer <span class="text-red-500"> *</span></span>
                <select wire:model.blur="customer_id"
                        class="i @error('customer_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">— Select Customer —</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}">{{ $c->company }}</option>
                    @endforeach
                </select>
                @error('customer_id') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Type</span>
                <input wire:model.blur="project_type" value=""
                       class="i @error('project_type') border-red-500! bg-red-50/20! @enderror"
                       placeholder="Website / SEO / Ads / App">
                @error('project_type') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Project value (₹) <span class="text-red-500"> *</span></span>
                <input wire:model.blur="value"
                       type="number" step="0.01" min="0"
                       class="i @error('value') border-red-500! bg-red-50/20! @enderror">
                @error('value') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Internal cost (₹)</span>
                <input wire:model.blur="cost"
                       type="number" step="0.01" min="0"
                       class="i @error('cost') border-red-500! bg-red-50/20! @enderror">
                @error('cost') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                <div class="hint">For margin tracking — not visible to client</div>
            </label>

            <label class="f">
                <span>Coordinator</span>
                <select wire:model.blur="coordinator_id"
                        class="i @error('coordinator_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">— Assign Staff Member —</option>
                    @foreach($coordinators as $coord)
                        <option value="{{ $coord->id }}">{{ $coord->name }}</option>
                    @endforeach
                </select>
                @error('coordinator_id') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Salesperson <span class="text-red-500"> *</span></span>
                <select wire:model.blur="sales_person_id"
                        class="i @error('sales_person_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">— Select Salesperson —</option>
                    @foreach($salesUsers as $su)
                        <option value="{{ $su->id }}">{{ $su->name }}</option>
                    @endforeach
                </select>
                @error('sales_person_id')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Status <span class="text-red-500"> *</span></span>
                <select wire:model.blur="status"
                        class="i @error('status') border-red-500! bg-red-50/20! @enderror">
                    <option value="Onboarding">Onboarding</option>
                    <option value="In Progress">In Progress</option>
                    <option value="On Hold">On Hold</option>
                    <option value="Delivered">Delivered</option>
                    <option value="Cancelled">Cancelled</option>
                </select>
                @error('status') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Health <span class="text-red-500"> *</span></span>
                <select wire:model.blur="health"
                        class="i @error('health') border-red-500! bg-red-50/20! @enderror">
                    <option value="On Track">On Track</option>
                    <option value="At Risk">At Risk</option>
                    <option value="Delayed">Delayed</option>
                    <option value="Completed">Completed</option>
                </select>
                @error('health') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Delay reason</span>
                <input wire:model.blur="delay_reason"
                       class="i @error('delay_reason') border-red-500! bg-red-50/20! @enderror"
                       placeholder="e.g. Client approval pending">
                @error('delay_reason') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Start date</span>
                <input wire:model.blur="start_date"
                       type="date"
                       class="i @error('start_date') border-red-500! bg-red-50/20! @enderror">
                @error('start_date') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f">
                <span>Delivery date</span>
                <input wire:model.blur="delivery_date"
                       type="date"
                       class="i @error('delivery_date') border-red-500! bg-red-50/20! @enderror">
                @error('delivery_date') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            <label class="f full">
                <span>Description</span>
                <textarea wire:model.blur="description"
                          class="i @error('description') border-red-500! bg-red-50/20! @enderror"
                          style="min-height:56px"></textarea>
                @error('description') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>
        </div>

        <div class="divider"></div>
        <div
            style="font-size:12.4px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">
            AMC / Renewal
        </div>
        <div class="form-grid">
            <label class="f full">
                <div class="checkline flex items-center gap-2">
                    <input type="checkbox" wire:model.live="amc_required" id="mAmcReq">
                    <label for="mAmcReq" class="cursor-pointer font-medium">This project has an AMC / renewal
                        contract</label>
                </div>
            </label>

            @if($amc_required)
                <label class="f">
                    <span>AMC start <span class="text-red-500"> *</span></span>
                    <input wire:model.blur="amc_start"
                           type="date"
                           class="i @error('amc_start') border-red-500! bg-red-50/20! @enderror">
                    @error('amc_start') <span
                        class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                </label>
                <label class="f">
                    <span>AMC end <span class="text-red-500"> *</span></span>
                    <input wire:model.blur="amc_end"
                           type="date"
                           class="i @error('amc_end') border-red-500! bg-red-50/20! @enderror">
                    @error('amc_end') <span
                        class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                </label>
                <label class="f">
                    <span>AMC amount (₹) <span class="text-red-500"> *</span></span>
                    <input wire:model.blur="amc_amount"
                           type="number" step="0.01" min="0"
                           class="i @error('amc_amount') border-red-500! bg-red-50/20! @enderror">
                    @error('amc_amount') <span
                        class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                </label>
                <label class="f">
                    <span>Alert how many days before</span>
                    <input wire:model.blur="renew_alert_days"
                           type="number" min="1" max="180"
                           class="i @error('renew_alert_days') border-red-500! bg-red-50/20! @enderror">
                    @error('renew_alert_days') <span
                        class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                </label>
            @endif
        </div>

        <div class="divider"></div>
        <div
            style="font-size:12.4px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">
            Handover & Notes
        </div>
        <div class="checkline flex items-center gap-2 mb-3">
            <input type="checkbox" wire:model.live="handover_done" id="mHandover">
            <label for="mHandover" class="cursor-pointer font-medium">Handover complete (documents, final payment,
                sign-off)</label>
        </div>
        <label class="f full mb-3">
            <span>Handover notes</span>
            <textarea wire:model.blur="handover_notes"
                      class="i @error('handover_notes') border-red-500! bg-red-50/20! @enderror"
                      style="min-height:56px"></textarea>
            @error('handover_notes') <span
                class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
        </label>
        <label class="f full">
            <span>Internal notes</span>
            <textarea wire:model.blur="notes"
                      class="i @error('notes') border-red-500! bg-red-50/20! @enderror"
                      style="min-height:56px"></textarea>
            @error('notes') <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
        </label>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="confirmClose()" class="btn btn-ghost">Cancel</button>
            <button type="submit" class="btn btn-primary">{{ $projectId ? 'Save changes' : 'Create project' }}</button>
        </div>
    </form>
</x-modal>
