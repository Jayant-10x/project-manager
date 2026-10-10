<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Project;
use App\Services\AuditNotificationService;
use App\Services\CodeGeneratorService;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller {
    public function __construct(
        protected CodeGeneratorService     $codeService,
        protected AuditNotificationService $auditService,
        protected DocumentService          $documentService
    ) {
    }

    public function save(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'Only a Coordinator can add or edit projects.']);
        }

        $input = $request->input('data', []);
        $name = trim((string)($input['name'] ?? ''));
        $cid = (int)($input['customerId'] ?? 0);

        if (empty($name)) {
            return response()->json(['ok' => false, 'error' => 'Project name is required.']);
        }
        if ($cid <= 0 || !Customer::query()->where('id', $cid)->exists()) {
            return response()->json(['ok' => false, 'error' => 'Customer not found.']);
        }

        $payload = [
            'customer_id' => $cid,
            'name' => $name,
            'description' => $input['description'] ?? null,
            'project_type' => $input['type'] ?? null,
            'coordinator' => $input['coordinator'] ?? $user->name,
            'value' => (float)($input['value'] ?? 0.0),
            'cost' => (float)($input['cost'] ?? 0.0),
            'start_date' => !empty($input['startDate']) ? $input['startDate'] : null,
            'delivery_date' => !empty($input['deliveryDate']) ? $input['deliveryDate'] : null,
            'actual_end' => !empty($input['actualEnd']) ? $input['actualEnd'] : null,
            'status' => in_array($input['status'] ?? '', ['Onboarding', 'In Progress', 'On Hold', 'Delivered', 'Cancelled'], true) ? $input['status'] : 'Onboarding',
            'health' => in_array($input['health'] ?? '', ['On Track', 'At Risk', 'Delayed', 'Completed'], true) ? $input['health'] : 'On Track',
            'delay_reason' => $input['delayReason'] ?? null,
            'amc_required' => !empty($input['amcRequired']),
            'amc_start' => !empty($input['amcStart']) ? $input['amcStart'] : null,
            'amc_end' => !empty($input['amcEnd']) ? $input['amcEnd'] : null,
            'amc_amount' => (float)($input['amcAmount'] ?? 0.0),
            'renew_alert_days' => max(1, min(180, (int)($input['renewAlertDays'] ?? 7))),
            'handover_done' => !empty($input['handoverDone']),
            'handover_notes' => $input['handoverNotes'] ?? null,
            'notes' => $input['notes'] ?? null,
        ];

        $id = (int)($input['id'] ?? 0);
        if ($id > 0) {
            $project = Project::query()->find($id);
            if (!$project) {
                return response()->json(['ok' => false, 'error' => 'Project not found.']);
            }

            $oldHealth = $project->health;
            $project->update($payload);

            if ($oldHealth !== $payload['health']) {
                $detail = "Health: {$oldHealth} to {$payload['health']}" . ($payload['delay_reason'] ? " — {$payload['delay_reason']}" : '');
                $this->auditService->log($user->name, 'update', 'project', $id, $project->code, $detail);
            } else {
                $this->auditService->log($user->name, 'update', 'project', $id, $project->code, 'Project updated — ' . $name);
            }

            return response()->json(['ok' => true, 'id' => $id, 'code' => $project->code, 'created' => false]);
        }

        $code = $this->codeService->generate('project', 'PJT-');
        $payload['code'] = $code;
        $payload['created_by'] = $user->name;

        $project = Project::query()->create($payload);

        $this->auditService->log($user->name, 'create', 'project', $project->id, $code, "Project created — {$name} (value {$payload['value']})");
        $this->auditService->notifyCoordinators('project', 'New project created', "{$name} ({$code})", 'project', $project->id);

        return response()->json(['ok' => true, 'id' => $project->id, 'code' => $code, 'created' => true]);
    }

    public function delete(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isAdmin()) {
            return response()->json(['ok' => false, 'error' => 'Only an Admin can delete projects.']);
        }

        $id = (int)$request->input('id', 0);
        $project = Project::query()->with('documents')->find($id);
        if (!$project) {
            return response()->json(['ok' => false, 'error' => 'Project not found.']);
        }

        foreach ($project->documents as $doc) {
            $this->documentService->deleteDocument($doc);
        }

        $project->delete();
        $this->auditService->log($user->name, 'delete', 'project', $id, $project->code, 'Project deleted — ' . $project->name);

        return response()->json(['ok' => true]);
    }
}
