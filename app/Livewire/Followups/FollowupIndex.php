<?php

namespace App\Livewire\Followups;

use App\Models\Customer;
use App\Models\Followup;
use App\Models\Project;
use Carbon\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class FollowupIndex extends Component {
    use WithPagination;

    public ?int $customer_id = null;
    public ?int $project_id = null;
    public string $kind = 'Call';
    public string $summary = '';
    public string $detail = '';
    public ?string $next_date = null;
    public string $next_note = '';

    protected function rules(): array {
        return [
            'customer_id' => 'required|integer|exists:customers,id',
            'project_id' => 'nullable|integer|exists:projects,id',
            'kind' => 'required|in:Call,Email,Meeting,WhatsApp,Note',
            'summary' => 'required|string|max:300',
            'detail' => 'nullable|string|max:4000',
            'next_date' => 'nullable|date',
            'next_note' => 'nullable|string|max:200',
        ];
    }

    public function updated(string $propertyName): void {
        if ($propertyName === 'customer_id') {
            $this->project_id = null;
            if (empty($this->customer_id)) {
                $this->customer_id = null;
            }
        }

        if ($propertyName === 'project_id' && empty($this->project_id)) {
            $this->project_id = null;
        }

        if ($propertyName === 'next_date' && empty($this->next_date)) {
            $this->next_date = null;
        }

        $this->validateOnly($propertyName);
    }

    public function save(): void {
        // Sanitize nullable selects, text, and dates
        $this->customer_id = !empty($this->customer_id) ? (int)$this->customer_id : null;
        $this->project_id = !empty($this->project_id) ? (int)$this->project_id : null;
        $this->summary = trim($this->summary);
        $this->detail = trim($this->detail);
        $this->next_note = trim($this->next_note);
        $this->next_date = !empty($this->next_date) ? $this->next_date : null;

        $validated = $this->validate();

        Followup::create([
            'customer_id' => $validated['customer_id'],
            'project_id' => $validated['project_id'],
            'kind' => $validated['kind'],
            'summary' => $validated['summary'],
            'detail' => !empty($validated['detail']) ? $validated['detail'] : null,
            'happened_on' => now(),
            'next_date' => $validated['next_date'],
            'next_note' => !empty($validated['next_note']) ? $validated['next_note'] : null,
            'created_by_id' => auth()->id(),
        ]);

        $this->reset(['customer_id', 'project_id', 'summary', 'detail', 'next_date', 'next_note']);
        $this->kind = 'Call';
        $this->resetValidation();

        $this->dispatch('close-modal', 'followup-modal');
        $this->dispatch('toast', ['msg' => 'Follow-up logged successfully', 'kind' => 'ok']);
    }

    public function deleteFollowup(int $id): void {
        $user = auth()->user();
        $fu = Followup::findOrFail($id);

        if ($user->role !== 'Admin' && $fu->created_by_id !== $user->id) {
            $this->dispatch('toast', ['msg' => 'You can only remove follow-ups you logged.', 'kind' => 'err']);
            return;
        }

        $fu->delete();
        $this->dispatch('toast', ['msg' => 'Follow-up removed', 'kind' => 'ok']);
    }

    public function render() {
        $user = auth()->user();
        $today = Carbon::today()->toDateString();

        $followupsQuery = Followup::with(['customer', 'project', 'createdBy'])
            ->orderByDesc('happened_on');

        $totalCount = (clone $followupsQuery)->count();

        // Pending/Overdue touchpoints due on or before today
        $dueList = Followup::with('customer')
            ->whereNotNull('next_date')
            ->where('next_date', '<=', $today)
            ->orderBy('next_date')
            ->get();

        $availableProjects = $this->customer_id
            ? Project::forUser($user)->where('customer_id', $this->customer_id)->orderBy('name')->get()
            : collect();

        return view('livewire.followups.followup-index', [
            'followups' => $followupsQuery->paginate(20),
            'totalCount' => $totalCount,
            'dueList' => $dueList,
            'customers' => Customer::orderBy('company')->get(),
            'availableProjects' => $availableProjects,
            'today' => $today,
        ])->layoutData([
            'header' => 'Follow-ups',
            'subtitle' => 'Conversation history and what to do next',
        ]);
    }
}
