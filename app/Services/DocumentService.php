<?php

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class DocumentService {
    public function storeBase64Document(array $attributes, string $uploadedBy): Document {
        $rawBase64 = (string)($attributes['data'] ?? '');
        $originalFileName = $attributes['name'] ?? 'file';
        $title = $attributes['title'] ?? $originalFileName;
        $category = $attributes['category'] ?? 'Other';
        $customerId = !empty($attributes['customerId']) ? (int)$attributes['customerId'] : null;
        $projectId = !empty($attributes['projectId']) ? (int)$attributes['projectId'] : null;

        if (Str::contains($rawBase64, 'base64,')) {
            $rawBase64 = explode('base64,', $rawBase64)[1];
        }

        $binaryData = base64_decode($rawBase64, true);
        if ($binaryData === false) {
            throw new RuntimeException('The file could not be read.');
        }

        $maxBytes = config('crm.max_upload_kb', 10240) * 1024;
        if (strlen($binaryData) > $maxBytes) {
            throw new RuntimeException('The file is larger than ' . round(config('crm.max_upload_kb') / 1024) . ' MB.');
        }

        $extension = strtolower(pathinfo($originalFileName, PATHINFO_EXTENSION));
        $allowedExtensions = config('crm.allowed_extensions', []);
        if (empty($extension) || !in_array($extension, $allowedExtensions, true)) {
            throw new RuntimeException('This file type is not allowed. Allowed: ' . implode(', ', $allowedExtensions) . '.');
        }

        $storageIdentifier = 'd' . ($projectId ?: $customerId ?: '0') . '_' . bin2hex(random_bytes(8)) . '.' . $extension;
        Storage::disk('public')->put($storageIdentifier, $binaryData);

        $mime = $attributes['mime'] ?? 'application/octet-stream';
        $sizeKb = (int)ceil(strlen($binaryData) / 1024);

        return Document::query()->create([
            'customer_id' => $customerId,
            'project_id' => $projectId,
            'category' => $category,
            'title' => $title,
            'file_name' => $originalFileName,
            'stored_as' => $storageIdentifier,
            'mime' => $mime,
            'size_kb' => $sizeKb,
            'uploaded_by' => $uploadedBy,
        ]);
    }

    public function deleteDocument(Document $document): void {
        Storage::disk('public')->delete($document->stored_as);
        $document->delete();
    }
}
