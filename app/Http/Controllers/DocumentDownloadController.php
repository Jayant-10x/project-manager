<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Document;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function download(Document $document): StreamedResponse
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

        app(AuditNotificationService::class)->log(
            $user->id,
            'Downloaded Document',
            'Document',
            $document->id,
            $document->project?->code ?? ($document->customer?->customer_code ?? ''),
            "User {$user->name} downloaded document '{$document->file_name}'"
        );

        if (Storage::disk('uploads')->exists($document->stored_as)) {
            return Storage::disk('uploads')->download(
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
}
