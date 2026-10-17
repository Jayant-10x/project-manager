<?php

namespace App\Livewire\Documents;

use Livewire\Attributes\On;
use Livewire\Component;

class DocumentPreviewModal extends Component
{
    public string $title = '';

    public string $name = '';

    public string $sizeKb = '';

    public string $category = '';

    public string $url = '';

    public string $mime = '';

    #[On('open-document-preview')]
    public function preview(array $data): void
    {
        $this->title = $data['title'] ?? 'Document Preview';
        $this->name = $data['name'] ?? '';
        $this->sizeKb = (string) ($data['sizeKb'] ?? '');
        $this->category = $data['category'] ?? '';
        $this->url = $data['url'] ?? '';
        $this->mime = $data['mime'] ?? '';

        $this->dispatch('open-modal', 'document-preview-modal');
    }

    public function render()
    {
        $isImage = str_starts_with($this->mime, 'image/') || (bool) preg_match('/\.(png|jpg|jpeg|gif|webp)$/i', $this->name);
        $isPdf = str_contains($this->mime, 'pdf') || (bool) preg_match('/\.pdf$/i', $this->name);

        return view('livewire.documents.document-preview-modal', [
            'isImage' => $isImage,
            'isPdf' => $isPdf,
        ]);
    }
}
