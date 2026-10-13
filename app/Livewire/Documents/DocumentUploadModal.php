<?php

namespace App\Livewire\Documents;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class DocumentUploadModal extends Component {
    use WithFileUploads;

    public $file;
    public ?int $customer_id = null;
    public ?int $project_id = null;
    public string $category = 'Contract';
    public string $title = '';

    #[On('open-document-upload-modal')]
    public function openModal(?int $customerId = null, ?int $projectId = null): void {
        $this->resetValidation();
        $this->reset(['file', 'title']);
        $this->category = 'Contract';
        $this->customer_id = $customerId;
        $this->project_id = $projectId;

        if ($projectId && !$customerId) {
            $this->customer_id = Project::find($projectId)?->customer_id;
        }

        $this->dispatch('open-modal', 'document-upload-modal');
    }

    public function updated(string $propertyName): void {
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

    protected function rules(): array {
        return [
            'file' => 'required|file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,csv,txt,ppt,pptx,zip',
            'title' => 'required|string|max:200',
            'category' => 'required|in:Contract,KYC/GST,Invoice,Proposal,Quotation,Delivery Proof,AMC,Other',
            'customer_id' => 'required|integer|exists:customers,id',
            'project_id' => 'nullable|integer|exists:projects,id',
        ];
    }

    public function upload(): void {
        $this->customer_id = !empty($this->customer_id) ? (int)$this->customer_id : null;
        $this->project_id = !empty($this->project_id) ? (int)$this->project_id : null;
        $this->title = trim($this->title);

        $this->validate();

        $ext = $this->file->getClientOriginalExtension();
        $storedName = 'd' . ($this->project_id ?: $this->customer_id) . '_' . bin2hex(random_bytes(8)) . '.' . $ext;

        // Store explicitly to the public disk root (storage/app/public)
        $this->file->storeAs('', $storedName, 'public');

        Document::create([
            'customer_id' => $this->customer_id,
            'project_id' => $this->project_id,
            'category' => $this->category,
            'title' => $this->title,
            'file_name' => $this->file->getClientOriginalName(),
            'stored_as' => $storedName,
            'mime' => $this->file->getMimeType(),
            'size_kb' => round($this->file->getSize() / 1024),
            'uploaded_by_id' => auth()->id(),
        ]);

        $this->reset(['file', 'title', 'category', 'customer_id', 'project_id']);
        $this->dispatch('document-saved');
        $this->dispatch('close-modal', 'document-upload-modal');
        $this->dispatch('toast', ['msg' => 'Document uploaded successfully', 'kind' => 'ok']);
        $this->redirect(request()->header('Referer') ?: route('documents.index'), navigate: true);
    }

    public function render() {
        $user = auth()->user();
        return view('livewire.documents.document-upload-modal', [
            'customers' => Customer::orderBy('company')->get(),
            'projects' => $this->customer_id
                ? Project::forUser($user)->where('customer_id', $this->customer_id)->orderBy('name')->get()
                : Project::forUser($user)->orderBy('name')->get(),
            'categories' => ['Contract', 'KYC/GST', 'Invoice', 'Proposal', 'Quotation', 'Delivery Proof', 'AMC', 'Other'],
        ]);
    }
}
