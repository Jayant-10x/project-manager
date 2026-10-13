<?php

namespace App\Livewire\Payments;

use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Services\CodeGeneratorService;
use Livewire\Attributes\On;
use Livewire\Component;

class PaymentModal extends Component {
    public ?int $project_id = null;
    public ?int $term_id = null;
    public ?float $amount = null;
    public ?float $tds = 0.0;
    public string $paid_on = '';
    public string $mode = 'NEFT';
    public string $reference = '';
    public string $bank = '';
    public string $note = '';

    // Sub-header dynamic information
    public string $projectSub = '';

    public function mount(): void {
        $this->paid_on = now()->toDateString();
    }

    #[On('open-payment-modal')]
    public function openPaymentModal(?int $projectId = null, ?int $termId = null): void {
        $this->resetValidation();
        $this->reset(['project_id', 'term_id', 'amount', 'tds', 'reference', 'bank', 'note']);
        $this->paid_on = now()->toDateString();
        $this->mode = 'NEFT';
        $this->tds = 0.0;

        if ($projectId) {
            $this->project_id = $projectId;
            $this->updateProjectContext();
            if ($termId) {
                $this->term_id = $termId;
                $this->updateTermAmount();
            }
        }

        $this->dispatch('open-modal', 'payment-modal');
    }

    public function updated(string $propertyName): void {
        if ($propertyName === 'project_id') {
            $this->term_id = null;
            $this->updateProjectContext();
        } elseif ($propertyName === 'term_id') {
            $this->updateTermAmount();
        }

        $this->validateOnly($propertyName);
    }

    private function updateProjectContext(): void {
        if ($this->project_id) {
            $project = Project::with('customer')->find($this->project_id);
            if ($project) {
                $dueFormatted = number_format($project->money['due'], 0);
                $this->projectSub = ($project->customer ? $project->customer->company . ' · ' : '') . 'due ₹' . $dueFormatted;
                return;
            }
        }
        $this->projectSub = 'Log verified settlement to ledger';
    }

    private function updateTermAmount(): void {
        if ($this->term_id) {
            $term = PaymentTerm::find($this->term_id);
            if ($term) {
                $bal = max(0, (float)$term->amount - (float)$term->received - (float)$term->tds);
                $this->amount = $bal > 0 ? $bal : null;
            }
        }
    }

    protected function rules(): array {
        return [
            'project_id' => 'required|integer|exists:projects,id',
            'term_id' => 'nullable|integer|exists:payment_terms,id',
            'amount' => 'required|numeric|min:0.01',
            'tds' => 'required|numeric|min:0',
            'paid_on' => 'required|date',
            'mode' => 'required|in:Cash,UPI,NEFT,RTGS,Cheque,Card,Other',
            'reference' => 'nullable|string|max:80',
            'bank' => 'nullable|string|max:80',
            'note' => 'nullable|string|max:300',
        ];
    }

    public function save(): void {
        // Normalize empty inputs and prevent null constraint violation on numeric columns
        $this->project_id = $this->project_id ? (int)$this->project_id : null;
        $this->term_id = $this->term_id ? (int)$this->term_id : null;
        $this->amount = ($this->amount === null || $this->amount === '') ? null : (float)$this->amount;
        $this->tds = ($this->tds === null || $this->tds === '') ? 0.0 : (float)$this->tds;
        $this->reference = trim($this->reference);
        $this->bank = trim($this->bank);
        $this->note = trim($this->note);

        $validated = $this->validate();
        $codeService = app(CodeGeneratorService::class);

        $project = Project::forUser(auth()->user())->findOrFail($validated['project_id']);

        $validated['code'] = $codeService->generate('payment', 'RCP-');
        $validated['customer_id'] = $project->customer_id;
        $validated['entered_by_id'] = auth()->id();

        Payment::create($validated);

        if (!empty($validated['term_id'])) {
            $term = PaymentTerm::find($validated['term_id']);
            if ($term) {
                $term->received += (float)$validated['amount'];
                $term->tds += (float)$validated['tds'];
                $term->status = ($term->received + $term->tds >= $term->amount - 0.01) ? 'Paid' : 'Partial';
                $term->save();
            }
        }

        $this->reset(['project_id', 'term_id', 'amount', 'tds', 'reference', 'bank', 'note']);
        $this->paid_on = now()->toDateString();
        $this->dispatch('payment-saved');
        $this->dispatch('close-modal', 'payment-modal');
        $this->redirect(route('payments.index'), navigate: true);
    }

    public function render() {
        $user = auth()->user();
        $projects = Project::query()->forUser($user)->with(['terms', 'customer'])->orderBy('name')->get();
        $terms = $this->project_id
            ? PaymentTerm::where('project_id', $this->project_id)->where('status', '!=', 'Paid')->orderBy('seq')->get()
            : collect();

        return view('livewire.payments.payment-modal', [
            'projects' => $projects,
            'terms' => $terms,
        ]);
    }
}
