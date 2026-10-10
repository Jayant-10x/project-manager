<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Milestone;
use App\Models\PaymentTerm;
use App\Models\Project;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MilestoneController extends Controller {
    public function __construct(
        protected AuditNotificationService $auditService
    ) {
    }

    public function saveMilestones(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) {
            return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);
        }

        $projectId = (int)$request->input('projectId', 0);
        $list = $request->input('milestones', []);
        $project = Project::query()->find($projectId);

        if (!$project || !is_array($list)) {
            return response()->json(['ok' => false, 'error' => 'Project and milestones are required.']);
        }

        $linkedTermsCount = PaymentTerm::query()
            ->where('project_id', $projectId)
            ->whereNotNull('milestone_id')
            ->count();

        $existingMilestones = Milestone::query()->where('project_id', $projectId)->pluck('id', 'id')->all();

        DB::transaction(function () use ($projectId, $list, $existingMilestones, $project, $user, &$count) {
            $seenIds = [];
            $order = 0;

            foreach ($list as $item) {
                if (!is_array($item)) continue;
                $name = trim((string)($item['name'] ?? ''));
                if (empty($name)) continue;

                $order++;
                $done = !empty($item['done']);
                $status = in_array($item['status'] ?? '', ['Pending', 'In Progress', 'Done', 'Blocked'], true) ? $item['status'] : 'Pending';
                if ($done) $status = 'Done';

                $mid = (int)($item['id'] ?? 0);
                $dueDate = !empty($item['dueDate']) ? $item['dueDate'] : null;
                $doneOn = $done ? (!empty($item['doneOn']) ? $item['doneOn'] : Carbon::today()->toDateString()) : null;

                $data = [
                    'name' => $name,
                    'due_date' => $dueDate,
                    'done' => $done,
                    'done_on' => $doneOn,
                    'status' => $status,
                    'sort_order' => $order,
                    'notes' => $item['notes'] ?? null,
                ];

                if ($mid > 0 && isset($existingMilestones[$mid])) {
                    Milestone::query()->where('id', $mid)->where('project_id', $projectId)->update($data);
                    $seenIds[$mid] = true;
                } else {
                    $newMilestone = Milestone::query()->create(array_merge($data, ['project_id' => $projectId]));
                    $seenIds[$newMilestone->id] = true;
                }
            }

            $gone = array_diff(array_keys($existingMilestones), array_keys($seenIds));
            if (!empty($gone)) {
                Milestone::query()->where('project_id', $projectId)->whereIn('id', $gone)->delete();
            }

            $count = $order;
            $this->auditService->log($user->name, 'update', 'milestones', $projectId, $project->code, "{$count} milestones saved");
        });

        return response()->json([
            'ok' => true,
            'count' => $count,
            'linkedWarn' => $linkedTermsCount > 0
        ]);
    }
}
