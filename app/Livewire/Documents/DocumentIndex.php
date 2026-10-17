<?php

namespace App\Livewire\Documents;

use App\Models\Document;
use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class DocumentIndex extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategory(): void
    {
        $this->resetPage();
    }

    public function clearFilters(): void
    {
        $this->reset(['search', 'category']);
        $this->resetPage();
    }

    #[On('document-saved')]
    public function refreshDocuments(): void
    {
        $this->resetPage();
    }

    public function deleteDocument(int $id): void
    {
        $user = auth()->user();
        $doc = Document::findOrFail($id);

        if ($user->role !== 'Admin' && $doc->uploaded_by !== $user->id) {
            $this->dispatch('toast', ['msg' => 'You can only remove files you uploaded.', 'type' => 'err']);

            return;
        }

        if (Storage::disk('uploads')->exists($doc->stored_as)) {
            Storage::disk('uploads')->delete($doc->stored_as);
        }
        Storage::disk('public')->delete($doc->stored_as);
        $doc->delete();

        $this->dispatch('toast', ['msg' => 'Document removed', 'type' => 'ok']);
    }

    public function render()
    {
        $user = auth()->user();
        $projectIds = Project::forUser($user)->pluck('id');

        $query = Document::query()
            ->where(function ($q) use ($projectIds) {
                $q->whereIn('project_id', $projectIds)
                    ->orWhereNull('project_id');
            })
            ->with(['customer', 'project', 'uploadedBy'])
            ->when($this->category, fn ($q) => $q->where('category', $this->category))
            ->when($this->search, function ($q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function ($sub) use ($term) {
                    $sub->where('title', 'like', $term)
                        ->orWhere('file_name', 'like', $term)
                        ->orWhere('category', 'like', $term)
                        ->orWhereHas('customer', fn ($c) => $c->where('company', 'like', $term))
                        ->orWhereHas('project', fn ($p) => $p->where('name', 'like', $term)->orWhere('code', 'like', $term));
                });
            })
            ->orderByDesc('id');

        return view('livewire.documents.document-index', [
            'documents' => $query->paginate(20),
            'categories' => ['Contract', 'KYC/GST', 'Invoice', 'Proposal', 'Quotation', 'Delivery Proof', 'AMC', 'Other'],
        ])->layoutData([
            'header' => 'Documents',
            'subtitle' => 'Contracts, KYC, invoices, proposals and delivery sign-offs',
        ]);
    }
}
