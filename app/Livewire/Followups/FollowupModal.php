<?php

namespace App\Livewire\Followups;

use App\Models\Customer;
use App\Models\Followup;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Livewire\Attributes\On;
use Livewire\Component;

class FollowupModal extends Component
{
    public ?int $customer_id = null;

    public ?int $project_id = null;

    public string $type = '';

    public ?string $happened_on = null;

    public string $summary = '';

    public string $detail = '';

    public ?string $next_date = null;

    public string $next_note = '';

    protected function rules(): array
    {
        return [
            'customer_id' => 'required|integer|exists:customers,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'type' => 'required|in:Call,Email,Meeting,WhatsApp,Note',
            'happened_on' => 'nullable|date',
            'summary' => 'required|string|max:300',
            'detail' => 'nullable|string|max:4000',
            'next_date' => 'nullable|date',
            'next_note' => 'nullable|string|max:200',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'customer_id' => 'customer',
            'project_id' => 'project',
            'type' => 'interaction type',
            'happened_on' => 'interaction date',
            'summary' => 'summary',
            'detail' => 'discussion details',
            'next_date' => 'next follow-up date',
            'next_note' => 'next action note',
        ];
    }

    #[On('open-followup-modal')]
    public function openModal(?int $customerId = null, ?int $projectId = null): void
    {
        $this->resetValidation();
        $this->reset(['summary', 'detail', 'next_date', 'next_note', 'happened_on']);
        $this->type = '';
        $this->happened_on = now()->toDateString();
        $this->customer_id = $customerId;
        $this->project_id = $projectId;

        $this->dispatch('open-modal', 'followup-modal');
    }

    public function updatedCustomerId(): void
    {
        $this->project_id = null;
    }

    public function save(): void
    {
        $this->customer_id = ! empty($this->customer_id) ? (int) $this->customer_id : null;
        $this->project_id = ! empty($this->project_id) ? (int) $this->project_id : null;
        $this->summary = trim($this->summary);
        $this->detail = trim($this->detail);
        $this->next_note = trim($this->next_note);
        $this->next_date = ! empty($this->next_date) ? $this->next_date : null;
        $this->happened_on = ! empty($this->happened_on) ? $this->happened_on : null;

        $validated = $this->validate();

        $happenedOn = ! empty($validated['happened_on']) ? Carbon::parse($validated['happened_on'])->startOfDay() : now();

        $fu = Followup::create([
            'customer_id' => $validated['customer_id'],
            'project_id' => $validated['project_id'],
            'type' => $validated['type'],
            'summary' => $validated['summary'],
            'detail' => ! empty($validated['detail']) ? $validated['detail'] : null,
            'happened_on' => $happenedOn,
            'next_date' => $validated['next_date'],
            'next_note' => ! empty($validated['next_note']) ? $validated['next_note'] : null,
            'created_by' => auth()->id(),
        ]);

        $cust = Customer::find($validated['customer_id']);
        app(AuditNotificationService::class)->log(
            auth()->id(),
            'Logged Follow-up',
            'Followup',
            $fu->id,
            $cust?->customer_code ?? '',
            "Logged {$validated['type']} for {$cust?->company}: {$validated['summary']}"
        );

        $this->reset(['customer_id', 'project_id', 'summary', 'detail', 'next_date', 'next_note', 'happened_on']);
        $this->type = 'Call';
        $this->resetValidation();

        $this->dispatch('close-modal', 'followup-modal');
        $this->dispatch('toast', ['msg' => 'Follow-up logged successfully', 'type' => 'ok']);
        $this->dispatch('followup-saved');
    }

    public function render()
    {
        $user = auth()->user();
        $availableProjects = $this->customer_id
            ? Project::forUser($user)->where('customer_id', $this->customer_id)->orderBy('name')->get()
            : collect();

        return view('livewire.followups.followup-modal', [
            'customers' => Customer::orderByRaw('COALESCE(NULLIF(company, ""), contact_name) ASC')->get(),
            'availableProjects' => $availableProjects,
        ]);
    }
}
