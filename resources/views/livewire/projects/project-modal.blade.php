<x-modal name="project-modal"
         :title="$projectId ? ($isRejected ? 'Edit & Resubmit Project' : 'Edit Project') : 'New Project'"
         :sub="$projectId ? ($isRejected ? 'Update details to re-submit this project for review' : 'Capture deliverable, timeline and margin settings') : 'Capture deliverable, timeline and margin settings'"
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
                <select wire:model.blur="customer_id" @disabled($projectId)
                class="i @error('customer_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Customer ***</option>
                    @foreach($customers as $customerOption)
                        <option value="{{ $customerOption->id }}">{{ $customerOption->display_name }}</option>
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
                       type="number" step="0.01" min="0" value=""
                       class="i @error('cost') border-red-500! bg-red-50/20! @enderror">
                @error('cost') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                <div class="hint">For margin tracking — not visible to customer</div>
            </label>

            @if(auth()->user()->role === 'Sales')
                <label class="f">
                    <span>Coordinator</span>
                    <input type="text"
                           class="i bg-gray-50/50 dark:bg-gray-800/50 text-gray-700 dark:text-gray-300 cursor-not-allowed opacity-90"
                           value="{{ $assignedCoordinator?->name ?? 'Reporting Coordinator' }}"
                           readonly disabled>
                    <div class="hint">Auto-assigned to your reporting coordinator</div>
                </label>
            @elseif(auth()->user()->role !== 'Coordinator')
                <label class="f">
                    <span>Coordinator <span class="text-red-500"> *</span></span>
                    <select wire:model.blur="coordinator_id"
                            class="i @error('coordinator_id') border-red-500! bg-red-50/20! @enderror">
                        <option value="">*** Select Project Coordinator ***</option>
                        @foreach($coordinators as $coordinator)
                            <option value="{{ $coordinator->id }}">{{ $coordinator->name }} ({{ $coordinator->designation }})</option>
                        @endforeach
                    </select>
                    @error('coordinator_id') <span
                        class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                </label>
            @endif

            @if(auth()->user()->role !== 'Sales')
                <label class="f">
                    <span>Salesperson <span class="text-red-500"> *</span></span>
                    <select wire:model.blur="sales_person_id"
                            class="i @error('sales_person_id') border-red-500! bg-red-50/20! @enderror">
                        <option value="">*** Select Salesperson ***</option>
                        @foreach($salesUsers as $su)
                            <option value="{{ $su->id }}">{{ $su->name }} ({{ $su->designation }})</option>
                        @endforeach
                    </select>
                    @error('sales_person_id')
                    <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                    @enderror
                </label>
            @endif

            <label class="f">
                <span>Tech Person</span>
                <select wire:model.blur="tech_person_id"
                        class="i @error('tech_person_id') border-red-500! bg-red-50/20! @enderror">
                    <option value="" selected>*** Select Tech Person ***</option>
                    @foreach($techPersons as $tp)
                        <option value="{{ $tp->id }}">{{ $tp->name }} ({{ $tp->designation }})</option>
                    @endforeach
                </select>
                @error('tech_person_id')
                <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span>
                @enderror
            </label>

            <label class="f">
                <span>Status <span class="text-red-500"> *</span></span>
                <select wire:model.live="status"
                        class="i @error('status') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Status ***</option>
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
                <select wire:model.live="health"
                        class="i @error('health') border-red-500! bg-red-50/20! @enderror">
                    <option value="">*** Select Project Health ***</option>
                    <option value="On Track">On Track</option>
                    <option value="At Risk">At Risk</option>
                    <option value="Delayed">Delayed</option>
                    <option value="Completed">Completed</option>
                </select>
                @error('health') <span
                    class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
            </label>

            @if($health === 'Delayed' || $status === 'Delayed')
                <label class="f">
                    <span>Delay reason</span>
                    <input wire:model.blur="delay_reason"
                           class="i @error('delay_reason') border-red-500! bg-red-50/20! @enderror"
                           placeholder="e.g. Customer approval pending">
                    @error('delay_reason') <span
                        class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
                </label>
            @endif

            <label class="f">
                <span>Start date <span class="text-red-500"> *</span></span>
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

        {{-- [Safely commented out legacy AMC & Handover sections to focus on financial milestone delivery]
        <div class="divider"></div>
        <div
            style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">
            AMC / Renewal
        </div>
        <div class="form-grid">
            <label class="f full">
                <div class="checkline flex items-center gap-2">
                    <input type="checkbox" wire:model.live="amc_required" id="mAmcReq">
                    <label for="mAmcReq" class="cursor-pointer font-medium">This project has an AMC / renewal contract</label>
                </div>
            </label>

            @if($amc_required)
                <label class="f">
                    <span>AMC start <span class="text-red-500"> *</span></span>
                    <input wire:model.blur="amc_start" type="date" class="i">
                </label>
                <label class="f">
                    <span>AMC end <span class="text-red-500"> *</span></span>
                    <input wire:model.blur="amc_end" type="date" class="i">
                </label>
                <label class="f">
                    <span>AMC amount (₹) <span class="text-red-500"> *</span></span>
                    <input wire:model.blur="amc_amount" type="number" step="0.01" min="0" class="i">
                </label>
                <label class="f">
                    <span>Alert how many days before</span>
                    <input wire:model.blur="renew_alert_days" type="number" min="1" max="180" class="i">
                </label>
            @endif
        </div>

        <div class="divider"></div>
        <div
            style="font-size:12px;font-weight:700;color:var(--text2);text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px">
            Handover & Notes
        </div>
        <div class="checkline flex items-center gap-2 mb-3">
            <input type="checkbox" wire:model.live="handover_done" id="mHandover">
            <label for="mHandover" class="cursor-pointer font-medium">Handover complete</label>
        </div>
        <label class="f full mb-3">
            <span>Handover notes</span>
            <textarea wire:model.blur="handover_notes" class="i" style="min-height:56px"></textarea>
        </label>
        --}}

        <div class="divider"></div>
        <label class="f full">
            <span>Internal notes</span>
            <textarea wire:model.blur="notes"
                      class="i @error('notes') border-red-500! bg-red-50/20! @enderror"
                      placeholder="Add any internal payment or milestone chasing notes..."
                      style="min-height:56px"></textarea>
            @error('notes') <span class="text-xs text-red-500! mt-1 font-medium block">{{ $message }}</span> @enderror
        </label>

        <div class="mt-6 flex justify-end gap-3">
            <button type="button" @click="confirmClose()" class="btn btn-outline-secondary">Cancel</button>
            <button type="submit" class="btn btn-primary min-w-37.5 justify-center" wire:loading.attr="disabled"
                    wire:target="save">
                <span wire:loading.remove wire:target="save"
                      class="inline-flex items-center justify-center gap-1.5">{{ $projectId ? ($isRejected ? 'Update & Resubmit' : 'Save changes') : 'Create project' }}</span>
                <span wire:loading.inline-flex wire:target="save" class="items-center justify-center gap-1.5">
                    <svg class="animate-spin h-4 w-4 text-current" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor"
                                stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor"
                              d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    <span>Saving...</span>
                </span>
            </button>
        </div>
    </form>
</x-modal>
