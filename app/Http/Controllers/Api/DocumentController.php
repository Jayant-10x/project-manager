<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Project;
use App\Services\AuditNotificationService;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class DocumentController extends Controller {
    public function __construct(
        protected DocumentService          $documentService,
        protected AuditNotificationService $auditService
    ) {
    }

    public function upload(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);
        }

        $rawBase64 = (string)$request->input('data', '');
        $customerId = (int)$request->input('customerId', 0);
        $projectId = (int)$request->input('projectId', 0);

        if (empty($rawBase64)) {
            return response()->json(['ok' => false, 'error' => 'The file is empty.']);
        }
        if ($customerId <= 0 && $projectId <= 0) {
            return response()->json(['ok' => false, 'error' => 'Select a customer or project.']);
        }

        if ($projectId > 0) {
            $project = Project::query()->find($projectId);
            if (!$project) {
                return response()->json(['ok' => false, 'error' => 'Project not found.']);
            }
            if ($customerId <= 0) {
                $customerId = $project->customer_id;
            }
        }

        try {
            $document = $this->documentService->storeBase64Document([
                'data' => $rawBase64,
                'name' => $request->input('name', 'file'),
                'title' => $request->input('title', ''),
                'category' => $request->input('category', 'Other'),
                'customerId' => $customerId,
                'projectId' => $projectId,
                'mime' => $request->input('mime', ''),
            ], $user->name);

            $this->auditService->log($user->name, 'upload', 'document', $document->id, '', "{$document->category} uploaded — {$document->title}");

            return response()->json([
                'ok' => true,
                'id' => $document->id,
                'url' => asset('storage/' . rawurlencode($document->stored_as)),
            ]);
        } catch (Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()]);
        }
    }

    public function delete(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);
        }

        $id = (int)$request->input('id', 0);
        $document = Document::query()->find($id);
        if (!$document) {
            return response()->json(['ok' => false, 'error' => 'File not found.']);
        }

        if (!$user->isAdmin() && $document->uploaded_by !== $user->name) {
            return response()->json(['ok' => false, 'error' => 'You can only remove files you uploaded.']);
        }

        $title = $document->title;
        $this->documentService->deleteDocument($document);
        $this->auditService->log($user->name, 'delete', 'document', $id, '', "Document removed — {$title}");

        return response()->json(['ok' => true]);
    }
}
