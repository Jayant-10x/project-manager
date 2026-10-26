<?php

namespace App\Livewire\Payments;

use App\Models\Document;
use App\Models\Payment;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Models\User;
use App\Services\AuditNotificationService;
use App\Services\CodeGeneratorService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class PaymentModal extends Component
{
    use WithFileUploads;

    public ?int $project_id = null;

    public ?int $customer_id = null;

    public ?int $term_id = null;

    public ?float $amount = null;

    public ?float $tds = 0.0;

    public string $paid_on = '';

    public string $mode = 'NEFT';

    public string $reference = '';

    public string $bank = '';

    public string $note = '';

    public $receipt_file = null;

    // Sub-header dynamic information
    public string $projectSub = '';

    public function mount(): void
    {
        $this->paid_on = now()->toDateString();
    }

    public function openModal(int|array|null $projectId = null, ?int $termId = null, ?int $customerId = null): void
    {
        if (is_array($projectId)) {
            $termId = $projectId['termId'] ?? ($projectId['term_id'] ?? $termId);
            $customerId = $projectId['customerId'] ?? ($projectId['customer_id'] ?? $customerId);
            $projectId = isset($projectId['projectId']) ? (int) $projectId['projectId'] : (isset($projectId['project_id']) ? (int) $projectId['project_id'] : null);
        }

        $this->openPaymentModal($projectId, $termId, $customerId);
    }

    #[On('open-payment-modal')]
    public function openPaymentModal(?int $projectId = null, ?int $termId = null, ?int $customerId = null): void
    {
        $this->resetValidation();
        $this->reset(['project_id', 'term_id', 'amount', 'tds', 'reference', 'bank', 'note', 'customer_id', 'receipt_file']);
        $this->paid_on = now()->toDateString();
        $this->mode = 'NEFT';
        $this->tds = 0.0;

        if ($termId) {
            $term = PaymentTerm::find($termId);
            if ($term) {
                $this->term_id = $termId;
                $this->project_id = $term->project_id;
                $this->updateProjectContext();
                $this->updateTermAmount();
            }
        } elseif ($projectId) {
            $this->project_id = $projectId;
            $this->updateProjectContext();
        } elseif ($customerId) {
            $this->customer_id = $customerId;
            $user = auth()->user();
            $customerProjects = Project::query()->forUser($user)->where('customer_id', $customerId)->get();
            if ($customerProjects->count() === 1) {
                $this->project_id = $customerProjects->first()->id;
                $this->updateProjectContext();
            }
        }

        $this->dispatch('open-modal', 'payment-modal');
    }

    public function updated(string $propertyName): void
    {
        if ($propertyName === 'project_id') {
            $this->term_id = null;
            $this->updateProjectContext();
        } elseif ($propertyName === 'term_id') {
            $this->updateTermAmount();
        }

        $this->validateOnly($propertyName);
    }

    private function updateProjectContext(): void
    {
        if ($this->project_id) {
            $project = Project::with('customer')->find($this->project_id);
            if ($project) {
                $dueFormatted = number_format($project->money['due'], 0);
                $this->projectSub = ($project->customer ? $project->customer->display_name.' · ' : '').'due ₹'.$dueFormatted;

                return;
            }
        }
        $this->projectSub = 'Log verified settlement to ledger';
    }

    private function updateTermAmount(): void
    {
        if ($this->term_id) {
            $term = PaymentTerm::find($this->term_id);
            if ($term) {
                $bal = max(0, (float) $term->amount - (float) $term->received - (float) $term->tds);
                $this->amount = $bal > 0 ? $bal : null;
            }
        }
    }

    protected function rules(): array
    {
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
            'receipt_file' => 'nullable|file|max:10240|mimes:pdf,jpg,jpeg,png,webp,doc,docx',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'project_id' => 'project',
            'term_id' => 'payment installment',
            'amount' => 'amount',
            'tds' => 'TDS',
            'paid_on' => 'payment date',
            'mode' => 'payment mode',
            'reference' => 'reference / UTR',
            'bank' => 'bank name',
            'note' => 'notes',
            'receipt_file' => 'receipt attachment',
        ];
    }

    public function save(): void
    {
        if (auth()->user()?->role === 'Sales') {
            abort(403, 'Sales representatives are not authorized to record payments.');
        }

        // Normalize empty inputs and prevent null constraint violation on numeric columns
        $this->project_id = $this->project_id ? (int) $this->project_id : null;
        $this->term_id = $this->term_id ? (int) $this->term_id : null;
        $this->amount = ($this->amount === null || $this->amount === '') ? null : (float) $this->amount;
        $this->tds = ($this->tds === null || $this->tds === '') ? 0.0 : (float) $this->tds;
        $this->reference = trim($this->reference);
        $this->bank = trim($this->bank);
        $this->note = trim($this->note);

        $validated = $this->validate();
        $codeService = app(CodeGeneratorService::class);

        $project = Project::forUser(auth()->user())->findOrFail($validated['project_id']);

        $validated['code'] = $codeService->generate('payment', 'RCP-');
        $validated['customer_id'] = $project->customer_id;
        $validated['created_by'] = auth()->id();

        // Smart Document: upload receipt/advice if provided
        if ($this->receipt_file) {
            $originalName = pathinfo($this->receipt_file->getClientOriginalName(), PATHINFO_FILENAME);
            $slug = Str::slug($originalName) ?: 'receipt';
            $timestamp = now()->format('Ymd_His');
            $ext = strtolower($this->receipt_file->getClientOriginalExtension());
            $storedName = "{$slug}_{$timestamp}.{$ext}";

            if (Storage::disk('uploads')->exists($storedName) || Storage::disk('public')->exists($storedName)) {
                $storedName = "{$slug}_{$timestamp}_".bin2hex(random_bytes(3)).".{$ext}";
            }

            $this->receipt_file->storeAs('', $storedName, 'uploads');
            $this->receipt_file->storeAs('', $storedName, 'public');

            $doc = Document::create([
                'customer_id' => $project->customer_id,
                'project_id' => $project->id,
                'category' => 'Invoice',
                'title' => "Payment Receipt — {$validated['code']}",
                'file_name' => $this->receipt_file->getClientOriginalName(),
                'stored_as' => $storedName,
                'mime' => $this->receipt_file->getMimeType(),
                'size_kb' => round($this->receipt_file->getSize() / 1024),
                'uploaded_by' => auth()->id(),
            ]);

            $validated['attachment'] = $storedName;
            $validated['document_id'] = $doc->id;
        }

        unset($validated['receipt_file']);

        $payment = Payment::create($validated);

        if (! empty($validated['term_id'])) {
            $term = PaymentTerm::find($validated['term_id']);
            if ($term) {
                $term->received += (float) $validated['amount'];
                $term->tds += (float) $validated['tds'];
                $term->status = ($term->received + $term->tds >= $term->amount - 0.01) ? 'Paid' : 'Partial';
                $term->save();
            }
        }

        $auditService = app(AuditNotificationService::class);
        $auditService->log(
            auth()->id(),
            'Created Payment',
            'Payment',
            $payment->id,
            $validated['code'],
            "Received ₹{$validated['amount']} for {$project->name}"
        );

        if ($project->coordinator_id && $project->coordinator_id !== auth()->id()) {
            $auditService->notify(
                $project->coordinator_id,
                'Payment Received',
                "Payment {$validated['code']} recorded",
                '₹'.number_format($validated['amount'], 2)." received for {$project->name}",
                'project',
                $project->id
            );
        }

        if ($project->sales_person_id && $project->sales_person_id !== auth()->id()) {
            $auditService->notify(
                $project->sales_person_id,
                'Payment Received',
                "Payment {$validated['code']} received",
                '₹'.number_format($validated['amount'], 2)." collected from your client for {$project->name}",
                'project',
                $project->id
            );
        }

        $adminIds = User::where('role', 'Admin')->where('status', 'Active')->where('id', '!=', auth()->id())->pluck('id');
        foreach ($adminIds as $adminId) {
            $auditService->notify(
                $adminId,
                'Payment Received',
                "Payment {$validated['code']} recorded",
                '₹'.number_format($validated['amount'], 2)." received for {$project->name}",
                'project',
                $project->id
            );
        }

        $this->reset(['project_id', 'term_id', 'amount', 'tds', 'reference', 'bank', 'note', 'receipt_file']);
        $this->paid_on = now()->toDateString();
        $this->dispatch('payment-saved');
        $this->dispatch('close-modal', 'payment-modal');
        $this->dispatch('toast', ['msg' => 'Payment receipt recorded successfully', 'type' => 'ok']);
    }

    public function render()
    {
        $user = auth()->user();
        $query = Project::query()->forUser($user)->with(['terms', 'customer'])->orderBy('name');
        if ($this->customer_id) {
            $query->where('customer_id', $this->customer_id);
        }
        $projects = $query->get();
        $terms = $this->project_id
            ? PaymentTerm::where('project_id', $this->project_id)->where('status', '!=', 'Paid')->orderBy('seq')->get()
            : collect();

        return view('livewire.payments.payment-modal', [
            'projects' => $projects,
            'terms' => $terms,
        ]);
    }
}
