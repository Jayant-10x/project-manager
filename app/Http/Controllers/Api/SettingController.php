<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Setting;
use App\Services\AuditNotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SettingController extends Controller {
    public function __construct(protected AuditNotificationService $auditService) {
    }

    public function save(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isAdmin()) return response()->json(['ok' => false, 'error' => 'Only an Admin can change settings.']);

        $map = [
            'payment_alert_days' => 'paymentAlertDays',
            'amc_alert_days' => 'amcAlertDays',
            'followup_alert_days' => 'followupAlertDays',
            'company_name' => 'companyName',
        ];

        foreach ($map as $dbKey => $payloadKey) {
            $val = $request->input($payloadKey);
            if ($val === null) continue;

            $sanitized = ($dbKey === 'company_name') ? substr(trim($val), 0, 120) : (string)max(1, min(180, (int)$val));
            Setting::setValue($dbKey, $sanitized);
        }

        $this->auditService->log($user->name, 'update', 'settings', null, '', 'Settings updated');

        return response()->json(['ok' => true]);
    }

    public function auditFeed(): JsonResponse {
        $rows = ActivityLog::query()->orderByDesc('id')->limit(300)->get()->map(fn($a) => [
            'who' => $a->who,
            'action' => $a->action,
            'entity' => $a->entity,
            'entity_code' => $a->entity_code,
            'detail' => $a->detail,
            'at' => $a->at_time ? $a->at_time->format('Y-m-d H:i') : '',
        ]);

        return response()->json(['ok' => true, 'data' => $rows]);
    }
}
