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

class NotificationController extends Controller {
    public function markAsRead(Request $request): JsonResponse {
        $user = $request->user();
        $id = (int)$request->input('id', 0);

        if ($id > 0) {
            Notification::query()->where('id', $id)->where('user_email', $user->email)->update(['is_read' => true]);
        } else {
            Notification::query()->where('user_email', $user->email)->update(['is_read' => true]);
        }

        return response()->json(['ok' => true]);
    }
}
