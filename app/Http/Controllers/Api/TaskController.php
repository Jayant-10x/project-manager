<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\Project;
use App\Models\SalesPerson;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class TaskController extends Controller {
    public function __construct(protected AuditNotificationService $auditService) {
    }

    public function save(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);

        $input = $request->input('data', []);
        $title = trim((string)($input['title'] ?? ''));
        if (empty($title)) return response()->json(['ok' => false, 'error' => 'Task title is required.']);

        $id = (int)($input['id'] ?? 0);
        $payload = [
            'title' => $title,
            'due_date' => !empty($input['dueDate']) ? $input['dueDate'] : null,
            'done' => !empty($input['done']),
            'priority' => in_array($input['priority'] ?? '', ['Low', 'Medium', 'High', 'Urgent'], true) ? $input['priority'] : 'Medium',
            'assigned_to' => $input['assignedTo'] ?? $user->name,
        ];

        if ($id > 0) {
            Task::query()->where('id', $id)->update($payload);
            $this->auditService->log($user->name, 'update', 'task', $id, '', "Task updated — {$title}");
            return response()->json(['ok' => true, 'id' => $id, 'created' => false]);
        }

        $payload['project_id'] = !empty($input['projectId']) ? (int)$input['projectId'] : null;
        $payload['customer_id'] = !empty($input['customerId']) ? (int)$input['customerId'] : null;
        $payload['created_by'] = $user->name;

        $task = Task::query()->create($payload);
        $this->auditService->log($user->name, 'create', 'task', $task->id, '', "Task created — {$title}");

        return response()->json(['ok' => true, 'id' => $task->id, 'created' => true]);
    }

    public function toggle(Request $request): JsonResponse {
        $id = (int)$request->input('id', 0);
        $task = Task::query()->find($id);
        if ($task) {
            $task->update(['done' => !$task->done]);
        }
        return response()->json(['ok' => true]);
    }

    public function delete(Request $request): JsonResponse {
        $id = (int)$request->input('id', 0);
        Task::query()->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
