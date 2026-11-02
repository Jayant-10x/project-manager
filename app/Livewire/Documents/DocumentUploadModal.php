<?php

namespace App\Livewire\Documents;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentUploadModal extends Component
{
    use WithFileUploads;

    public $file;

    public ?int $customer_id = null;

    public ?int $project_id = null;

    public string $category = '';

    public string $title = '';

    #[On('open-document-upload-modal')]
    public function openModal(?int $customerId = null, ?int $projectId = null): void
    {
        $this->resetValidation();
        $this->reset(['file', 'title']);
        $this->category = '';
        $this->customer_id = $customerId;
        $this->project_id = $projectId;

        if ($projectId && ! $customerId) {
            $this->customer_id = Project::find($projectId)?->customer_id;
        }

        $this->dispatch('open-modal', 'document-upload-modal');
    }

    public function updated(string $propertyName): void
    {
        if ($propertyName === 'file' && $this->file && empty($this->title)) {
            $this->title = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME);
        }

        if ($propertyName === 'customer_id' && empty($this->customer_id)) {
            $this->customer_id = null;
        }

        if ($propertyName === 'project_id' && empty($this->project_id)) {
            $this->project_id = null;
        }

        $this->validateOnly($propertyName);
    }

    protected function rules(): array
    {
        return [
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,csv,txt,ppt,pptx,zip',
            'title' => 'required|string|max:200',
            'category' => 'required|in:Contract,KYC/GST,Invoice,Proposal,Quotation,Delivery Proof,AMC,Other',
            'customer_id' => 'required|integer|exists:customers,id',
            'project_id' => 'nullable|integer|exists:projects,id',
        ];
    }

    protected function messages(): array
    {
        return [
            'file.required' => 'Please select or drop a file to upload.',
            'file.max' => 'The file size must not exceed 10 MB.',
            'file.mimes' => 'The selected file format is not supported. Allowed formats: PDF, DOC, DOCX, XLS, XLSX, CSV, TXT, PPT, Images, ZIP.',
            'customer_id.required' => 'Please select a customer for this document.',
            'title.required' => 'Please provide a title for this document.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'file' => 'document file',
            'customer_id' => 'customer',
            'project_id' => 'project',
            'category' => 'category',
            'title' => 'document title',
        ];
    }

    public function save(): void
    {
        $this->customer_id = ! empty($this->customer_id) ? (int) $this->customer_id : null;
        $this->project_id = ! empty($this->project_id) ? (int) $this->project_id : null;
        $this->title = trim($this->title);

        $this->validate();

        $originalName = pathinfo($this->file->getClientOriginalName(), PATHINFO_FILENAME);
        $slug = Str::slug($originalName) ?: 'document';
        $timestamp = now()->format('Ymd_His');
        $ext = strtolower($this->file->getClientOriginalExtension());
        $storedName = "{$slug}_{$timestamp}.{$ext}";

        if (Storage::disk('documents')->exists($storedName) || Storage::disk('public')->exists($storedName)) {
            $storedName = "{$slug}_{$timestamp}_".bin2hex(random_bytes(3)).".{$ext}";
        }

        // Store directly to private documents storage disk
        $this->file->storeAs('', $storedName, 'documents');

        $doc = Document::create([
            'customer_id' => $this->customer_id,
            'project_id' => $this->project_id,
            'category' => $this->category,
            'title' => $this->title,
            'file_name' => $this->file->getClientOriginalName(),
            'stored_as' => $storedName,
            'mime' => $this->file->getMimeType(),
            'size_kb' => round($this->file->getSize() / 1024),
            'uploaded_by' => auth()->id(),
        ]);

        $auditService = app(AuditNotificationService::class);
        $auditService->log(
            auth()->id(),
            'Uploaded Document',
            'Document',
            $doc->id,
            '',
            "Uploaded {$doc->title} ({$doc->file_name})"
        );

        if ($this->project_id) {
            $proj = Project::find($this->project_id);
            if ($proj) {
                $auditService->notifyProjectStakeholders(
                    $proj,
                    'Document Uploaded',
                    "New Document: {$doc->title}",
                    auth()->user()->name." uploaded document '{$doc->title}' ({$doc->category}) for project {$proj->name}.",
                    'project',
                    $proj->id
                );
            }
        } elseif ($this->customer_id) {
            $cust = Customer::find($this->customer_id);
            if ($cust) {
                $auditService->notifyCustomerStakeholders(
                    $cust,
                    'Document Uploaded',
                    "New Document: {$doc->title}",
                    auth()->user()->name." uploaded document '{$doc->title}' ({$doc->category}) for customer {$cust->display_name}.",
                    'customer',
                    $cust->id
                );
            }
        }

        $this->reset(['file', 'title', 'category', 'customer_id', 'project_id']);
        $this->dispatch('document-saved');
        $this->dispatch('close-modal', 'document-upload-modal');
        $this->dispatch('toast', ['msg' => 'Document uploaded successfully', 'type' => 'ok']);
    }

    /**
     * Alias for save to maintain backward compatibility.
     */
    public function upload(): void
    {
        $this->save();
    }

    public function render()
    {
        $user = auth()->user();

        return view('livewire.documents.document-upload-modal', [
            'customers' => Customer::where('status', 'Active')->orderByRaw('COALESCE(NULLIF(company, ""), contact_name) ASC')->get(),
            'projects' => $this->customer_id
                ? Project::forUser($user)->where('customer_id', $this->customer_id)->orderBy('name')->get()
                : Project::forUser($user)->orderBy('name')->get(),
            'categories' => ['Contract', 'KYC/GST', 'Invoice', 'Proposal', 'Quotation', 'Delivery Proof', 'Other'],
        ]);
    }
}
