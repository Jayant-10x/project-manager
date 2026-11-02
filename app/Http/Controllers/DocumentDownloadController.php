<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function download(Document $document): StreamedResponse
    {
        $this->authorizeDocumentAccess($document);

        $user = auth()->user();
        app(AuditNotificationService::class)->log(
            $user->id,
            'Downloaded Document',
            'Document',
            $document->id,
            $document->project?->code ?? ($document->customer?->customer_code ?? ''),
            "User {$user->name} downloaded document '{$document->file_name}'"
        );

        if (Storage::disk('documents')->exists($document->stored_as)) {
            return Storage::disk('documents')->download(
                $document->stored_as,
                $document->file_name
            );
        }

        if (Storage::disk('public')->exists($document->stored_as)) {
            return Storage::disk('public')->download(
                $document->stored_as,
                $document->file_name
            );
        }

        abort(404, 'File not found on storage disk.');
    }

    public function preview(Document $document): Response
    {
        $this->authorizeDocumentAccess($document);

        if (Storage::disk('documents')->exists($document->stored_as)) {
            return Storage::disk('documents')->response(
                $document->stored_as,
                $document->file_name,
                ['Content-Disposition' => 'inline; filename="'.$document->file_name.'"']
            );
        }

        if (Storage::disk('public')->exists($document->stored_as)) {
            return Storage::disk('public')->response(
                $document->stored_as,
                $document->file_name,
                ['Content-Disposition' => 'inline; filename="'.$document->file_name.'"']
            );
        }

        abort(404, 'File not found on storage disk.');
    }

    protected function authorizeDocumentAccess(Document $document): void
    {
        $user = auth()->user();
        if (! $user) {
            abort(401);
        }

        if (! $user->isAdmin()) {
            $hasProjectAccess = $document->project_id && Project::forUser($user)->where('id', $document->project_id)->exists();
            $hasCustomerAccess = $document->customer_id && Customer::forUser($user)->where('id', $document->customer_id)->exists();
            $isUploader = $document->uploaded_by === $user->id;

            if (! $hasProjectAccess && ! $hasCustomerAccess && ! $isUploader) {
                abort(403, 'Unauthorized access to this document.');
            }
        }
    }
}
