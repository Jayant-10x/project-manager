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

class SalesPersonController extends Controller {
    public function __construct(protected AuditNotificationService $auditService) {
    }

    public function save(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isCoordinator()) return response()->json(['ok' => false, 'error' => 'You do not have permission to do this.']);

        $input = $request->input('data', []);
        $name = trim((string)($input['name'] ?? ''));
        if (empty($name)) return response()->json(['ok' => false, 'error' => 'Name is required.']);

        $id = (int)($input['id'] ?? 0);
        $payload = [
            'name' => $name,
            'phone' => $input['phone'] ?? null,
            'email' => $input['email'] ?? null,
            'target' => (float)($input['target'] ?? 0.0),
            'status' => in_array($input['status'] ?? '', ['Active', 'Inactive'], true) ? $input['status'] : 'Active',
        ];

        if ($id > 0) {
            $sp = SalesPerson::query()->find($id);
            if (!$sp) return response()->json(['ok' => false, 'error' => 'Salesperson not found.']);

            $exists = SalesPerson::query()->whereRaw('LOWER(name) = ?', [strtolower($name)])->where('id', '<>', $id)->exists();
            if ($exists) return response()->json(['ok' => false, 'error' => 'A salesperson with this name already exists.']);

            if ($sp->name !== $name) {
                Customer::query()->where('onboarded_by', $sp->name)->update(['onboarded_by' => $name]);
            }

            $sp->update($payload);
            return response()->json(['ok' => true, 'id' => $id, 'created' => false]);
        }

        if (SalesPerson::query()->whereRaw('LOWER(name) = ?', [strtolower($name)])->exists()) {
            return response()->json(['ok' => false, 'error' => 'A salesperson with this name already exists.']);
        }

        $sp = SalesPerson::query()->create($payload);
        $this->auditService->log($user->name, 'create', 'sales', $sp->id, '', "Sales person added — {$name}");

        return response()->json(['ok' => true, 'id' => $sp->id, 'created' => true]);
    }

    public function delete(Request $request): JsonResponse {
        $user = $request->user();
        if (!$user->isAdmin()) return response()->json(['ok' => false, 'error' => 'Only an Admin can remove this.']);

        $id = (int)$request->input('id', 0);
        SalesPerson::query()->where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }
}
