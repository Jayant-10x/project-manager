<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Followup;
use App\Models\Project;
use App\Models\Setting;
use App\Services\AuditNotificationService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FollowupController extends Controller {
    public function __construct(protected AuditNotificationService $auditService) {
    }

    public function save(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);

        $input = $request->input('data', []);
        $cid = (int)($input['customerId'] ?? 0);
        $sum = trim((string)($input['summary'] ?? ''));

        if ($cid <= 0 || !Customer::query()->where('id', $cid)->exists()) return response()->json(['ok' => false, 'error' => 'Select a valid customer.']);
        if (empty($sum)) return response()->json(['ok' => false, 'error' => 'Enter a summary.']);

        $pid = (int)($input['projectId'] ?? 0);
        if ($pid > 0 && !Project::query()->where('id', $pid)->where('customer_id', $cid)->exists()) $pid = null;

        $nextDate = !empty($input['nextDate']) ? $input['nextDate'] : null;

        $followup = Followup::query()->create([
            'customer_id' => $cid,
            'project_id' => $pid ?: null,
            'type' => in_array($input['type'] ?? '', ['Call', 'Email', 'Meeting', 'WhatsApp', 'Note'], true) ? $input['type'] : 'Call',
            'summary' => $sum,
            'detail' => $input['detail'] ?? null,
            'happened_on' => Carbon::now(),
            'next_date' => $nextDate,
            'next_note' => $input['nextNote'] ?? null,
            'created_by' => $user->name,
        ]);

        if ($nextDate) {
            $alertDays = (int)Setting::getValue('followup_alert_days', config('crm.followup_alert_days'));
            if ($nextDate <= Carbon::today()->addDays($alertDays)->toDateString()) {
                $this->auditService->notifyCoordinators('followup', 'Follow-up scheduled', "{$sum} — {$nextDate}", 'customer', $cid);
            }
        }

        $this->auditService->log($user->name, 'create', 'followup', $followup->id, '', "Follow-up logged — {$sum}");

        return response()->json(['ok' => true, 'id' => $followup->id]);
    }

    public function delete(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);

        $id = (int)$request->input('id', 0);
        $followup = Followup::query()->find($id);
        if (!$followup) return response()->json(['ok' => false, 'error' => 'Follow-up not found.']);

        $followup->delete();
        $this->auditService->log($user->name, 'delete', 'followup', $id, '', 'Follow-up removed');

        return response()->json(['ok' => true]);
    }
}
