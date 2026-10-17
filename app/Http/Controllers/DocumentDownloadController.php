<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentDownloadController extends Controller
{
    public function download(Document $document): StreamedResponse
    {
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
